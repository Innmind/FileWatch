<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\Continuation;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process\Output,
    Process\Output\Type,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Attempt,
    Sequence,
    Monoid\Concat,
    Predicate\Instance,
};

/**
 * @internal
 */
final class OutputDiff implements Implementation
{
    public function __construct(
        private Processes $processes,
        private Command $command,
        private Halt $halt,
        private Period $period,
    ) {
    }

    /**
     * @template C
     * @template R
     *
     * @param C $carry
     * @param callable(R|C, Continuation<R|C>): Continuation<R> $ping
     *
     * @return Attempt<R|C>
     */
    #[\Override]
    public function __invoke(mixed $carry, callable $ping): Attempt
    {
        $stop = new \stdClass;

        $result = Sequence::lazy(function() {
            while (true) {
                yield $this->output();
                ($this->halt)($this->period);
            }
        })
            ->flatMap(static fn($maybe) => $maybe->match(
                Sequence::of(...),
                static fn($e) => Sequence::of($e, $stop),
            ))
            ->takeWhile(static fn($value) => $value !== $stop)
            ->keep(
                Instance::of(Sequence::class)
                    ->or(Instance::of(\Throwable::class)),
            )
            ->aggregate(function($previous, $now) {
                if ($previous instanceof \Throwable) {
                    return Sequence::of($previous);
                }

                if ($now instanceof \Throwable) {
                    return Sequence::of($now);
                }

                /**
                 * @psalm-suppress MixedArgument
                 * @psalm-suppress MixedArgumentTypeCoercion
                 */
                return match ($this->diff($previous, $now)) {
                    true => Sequence::of($previous, $now),
                    false => Sequence::of($now),
                };
            })
            ->sink($carry)
            ->until(static function($carry, $output, $continuation) use ($ping) {
                if ($output instanceof \Throwable) {
                    /** @psalm-suppress InvalidArgument */
                    return $continuation->stop($output);
                }

                /** @psalm-suppress MixedArgument */
                return $ping($carry, Continuation::of($carry))->match(
                    $continuation->continue(...),
                    $continuation->stop(...),
                );
            });

        if ($result instanceof \Throwable) {
            return Attempt::error($result);
        }

        return Attempt::result($result);
    }

    /**
     * @return Attempt<Sequence<Output\Chunk>>
     */
    private function output(): Attempt
    {
        return $this
            ->processes
            ->execute($this->command)
            ->flatMap(static fn($process) => $process->wait()->match(
                Attempt::result(...),
                fn() => Attempt::error(new \RuntimeException(\sprintf(
                    'Failed to run command "%s"',
                    $this->command->toString(),
                ))),
            ))
            ->map(static fn($success) => $success->output())
            ->flatMap(
                static fn($output) => $output
                    ->find(static fn($chunk) => $chunk->type() === Type::error)
                    ->match(
                        static fn($chunk) => Attempt::error(new \RuntimeException(
                            $chunk->data()->toString(),
                        )),
                        static fn() => Attempt::result($output),
                    ),
            );
    }

    /**
     * @param Sequence<Output\Chunk> $previous
     * @param Sequence<Output\Chunk> $now
     */
    private function diff(Sequence $previous, Sequence $now): bool
    {
        $previous = $previous
            ->map(static fn($chunk) => $chunk->data())
            ->fold(new Concat)
            ->toString();
        $now = $now
            ->map(static fn($chunk) => $chunk->data())
            ->fold(new Concat)
            ->toString();

        return $previous !== $now;
    }
}
