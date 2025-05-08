<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping,
    Continuation,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process\Output,
    Process\Output\Type,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    Monoid\Concat,
    Predicate\Instance,
};

final class OutputDiff implements Ping
{
    private Processes $processes;
    private Command $command;
    private Halt $halt;
    private Period $period;

    public function __construct(
        Processes $processes,
        Command $command,
        Halt $halt,
        Period $period,
    ) {
        $this->processes = $processes;
        $this->command = $command;
        $this->halt = $halt;
        $this->period = $period;
    }

    /**
     * @template C
     * @template R
     *
     * @param C $carry
     * @param callable(R|C, Continuation<R|C>): Continuation<R> $ping
     *
     * @return Maybe<R|C>
     */
    #[\Override]
    public function __invoke(mixed $carry, callable $ping): Maybe
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
                static fn() => Sequence::of(new Failed, $stop),
            ))
            ->takeWhile(static fn($value) => $value !== $stop)
            ->keep(
                Instance::of(Sequence::class)
                    ->or(Instance::of(Failed::class)),
            )
            ->aggregate(function($previous, $now) {
                if ($previous instanceof Failed) {
                    return Sequence::of($previous);
                }

                if ($now instanceof Failed) {
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
                if ($output instanceof Failed) {
                    /** @psalm-suppress InvalidArgument */
                    return $continuation->stop($output);
                }

                /** @psalm-suppress MixedArgument */
                return $ping($carry, Continuation::of($carry))->match(
                    $continuation->continue(...),
                    $continuation->stop(...),
                );
            });

        if ($result instanceof Failed) {
            return Maybe::nothing();
        }

        return Maybe::just($result);
    }

    /**
     * @return Maybe<Sequence<Output\Chunk>>
     */
    private function output(): Maybe
    {
        return $this
            ->processes
            ->execute($this->command)
            ->maybe()
            ->flatMap(static fn($process) => $process->wait()->maybe())
            ->map(static fn($success) => $success->output())
            ->filter(static fn($output) => !$output->any(
                static fn($chunk) => $chunk->type() === Type::error,
            ));
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
