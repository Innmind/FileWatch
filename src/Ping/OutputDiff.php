<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping,
    Exception\WatchFailed,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process\Output,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Either,
    SideEffect,
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

    public function __invoke(callable $ping): Either
    {
        $previous = $this->output();

        do {
            ($this->halt)($this->period);
            $previous = $previous->flatMap(
                fn($previous) => $this->output()->map(function($output) use ($previous, $ping) {
                    if ($this->diff($previous, $output)) {
                        $ping();
                    }

                    return $output;
                }),
            );
            $continue = $previous->match(
                static fn() => false,
                static fn() => true,
            );
        } while ($continue);

        return $previous->map(static fn() => new SideEffect);
    }

    /**
     * @return Either<WatchFailed, Output>
     */
    private function output(): Either
    {
        $process = $this->processes->execute($this->command);

        return $process
            ->wait()
            ->leftMap(fn() => new WatchFailed($this->command->toString()))
            ->map(static fn() => $process->output());
    }

    private function diff(Output $previous, Output $now): bool
    {
        return $previous->toString() !== $now->toString();
    }
}
