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
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PeriodInterface,
};

final class OutputDiff implements Ping
{
    private Processes $processes;
    private Command $command;
    private Halt $halt;
    private TimeContinuumInterface $clock;
    private PeriodInterface $period;

    public function __construct(
        Processes $processes,
        Command $command,
        Halt $halt,
        TimeContinuumInterface $clock,
        PeriodInterface $period
    ) {
        $this->processes = $processes;
        $this->command = $command;
        $this->halt = $halt;
        $this->clock = $clock;
        $this->period = $period;
    }

    public function __invoke(callable $ping): void
    {
        $previous = $this->output();

        do {
            ($this->halt)($this->clock, $this->period);
            $output = $this->output();

            if ($this->diff($previous, $output)) {
                $ping();
            }

            $previous = $output;
        } while (true);
    }

    private function output(): Output
    {
        $process = $this
            ->processes
            ->execute($this->command)
            ->wait();

        if (!$process->exitCode()->isSuccessful()) {
            throw new WatchFailed((string) $this->command);
        }

        return $process->output();
    }

    private function diff(Output $previous, Output $now): bool
    {
        return (string) $previous !== (string) $now;
    }
}
