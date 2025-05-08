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
    Either,
    Maybe,
    Sequence,
    Monoid\Concat,
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
        $previous = $this->output($carry);

        do {
            ($this->halt)($this->period);
            $previous = $previous->flatMap(function($state) use ($ping) {
                [$previous, $carry] = $state;

                return $this
                    ->output($carry)
                    ->flatMap(function($state) use ($previous, $ping) {
                        [$output, $carry] = $state;

                        return $this->maybePing($previous, $output, $ping, $carry);
                    });
            });

            $continue = $previous->match(
                static fn() => true,
                static fn() => false,
            );
        } while ($continue);

        /** @var Maybe<R|C> */
        return $previous
            ->map(static fn($state) => $state[1])
            ->otherwise($this->switchStopValue(...))
            ->maybe();
    }

    /**
     * @template C
     * @template R
     *
     * @param Sequence<Output\Chunk> $previous
     * @param Sequence<Output\Chunk> $output
     * @param callable(R|C, Continuation<R|C>): Continuation<R> $ping
     * @param C $carry
     *
     * @return Either<Stop<R|C>, array{0: Sequence<Output\Chunk>, 1: R|C}>
     */
    private function maybePing(
        Sequence $previous,
        Sequence $output,
        callable $ping,
        mixed $carry,
    ): Either {
        if ($this->diff($previous, $output)) {
            return $ping($carry, Continuation::of($carry))->match(
                static fn($carry) => Either::right([$output, $carry]),
                static fn($carry) => Either::left(Stop::of($carry)),
            );
        }

        return Either::right([$output, $carry]);
    }

    /**
     * @template C
     *
     * @param C $carry
     *
     * @return Either<Failed, array{0: Sequence<Output\Chunk>, 1: C}>
     */
    private function output(mixed $carry): Either
    {
        return $this
            ->processes
            ->execute($this->command)
            ->unwrap()
            ->wait()
            ->leftMap(static fn() => new Failed)
            ->map(static fn($success) => $success->output())
            ->filter(
                static fn($output) => !$output->any(
                    static fn($chunk) => $chunk->type() === Type::error,
                ),
                static fn() => new Failed,
            )
            ->map(static fn($output) => [$output, $carry]);
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

    /**
     * @template C
     * @template R
     *
     * @param R|Stop<C>|Failed $value
     *
     * @return Either<Failed, R|C>
     */
    private function switchStopValue(mixed $value): Either
    {
        return match (true) {
            $value instanceof Stop => Either::right($value->value()),
            $value instanceof Failed => Either::left($value),
            default => Either::right($value),
        };
    }
}
