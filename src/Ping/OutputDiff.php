<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping,
    Failed,
    Stop,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process\Output,
    Process\Output\Type,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\Either;

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
     * @template L
     *
     * @param C $carry
     * @param callable(C): Either<L|Stop<C>, C> $ping
     *
     * @return Either<Failed|L, C>
     */
    public function __invoke(mixed $carry, callable $ping): Either
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

        /** @var Either<Failed|L, C> */
        return $previous
            ->map(static fn($state) => $state[1])
            ->otherwise($this->switchStopValue(...));
    }

    /**
     * @template C
     * @template L
     *
     * @param callable(C): Either<L|Stop<C>, C> $ping
     * @param C $carry
     *
     * @return Either<L|Stop<C>, array{0: Output, 1: C}>
     */
    private function maybePing(
        Output $previous,
        Output $output,
        callable $ping,
        mixed $carry,
    ): Either {
        if ($this->diff($previous, $output)) {
            return $ping($carry)->map(static fn($carry) => [$output, $carry]);
        }

        return Either::right([$output, $carry]);
    }

    /**
     * @template C
     *
     * @param C $carry
     *
     * @return Either<Failed, array{0: Output, 1: C}>
     */
    private function output(mixed $carry): Either
    {
        return $this
            ->processes
            ->execute($this->command)
            ->wait()
            ->leftMap(static fn() => new Failed)
            ->map(static fn($success) => $success->output())
            ->filter(
                static fn($output) => $output
                    ->filter(static fn($_, $type) => $type === Type::error)
                    ->chunks()
                    ->empty(),
                static fn() => new Failed,
            )
            ->map(static fn($output) => [$output, $carry]);
    }

    private function diff(Output $previous, Output $now): bool
    {
        return $previous->toString() !== $now->toString();
    }

    /**
     * @template C
     * @template L
     *
     * @param L|Stop<C>|Failed $value
     *
     * @return Either<Failed|L, C>
     */
    private function switchStopValue(mixed $value): Either
    {
        return match (true) {
            $value instanceof Stop => Either::right($value->value()),
            default => Either::left($value),
        };
    }
}
