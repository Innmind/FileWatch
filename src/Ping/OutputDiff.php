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
        Period $period
    ) {
        $this->processes = $processes;
        $this->command = $command;
        $this->halt = $halt;
        $this->period = $period;
    }

    public function __invoke(callable $ping): void
    {
        $previous = $this->output();

        do {
            ($this->halt)($this->period);
            $output = $this->output();

            if ($this->diff($previous, $output)) {
                $ping();
            }

            $previous = $output;
        } while (true);
    }

    private function output(): Output
    {
        $process = $this->processes->execute($this->command);
        $throwOnError = $process
            ->wait()
            ->leftMap(fn() => new WatchFailed($this->command->toString()))
            ->match(
                static fn($e) => static fn() => throw $e,
                static fn() => static fn() => null,
            );
        $throwOnError();

        return $process->output();
    }

    private function diff(Output $previous, Output $now): bool
    {
        return $previous->toString() !== $now->toString();
    }
}
