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
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\PeriodInterface;

final class OutputDiff implements Ping
{
    private $processes;
    private $command;
    private $process;
    private $period;

    public function __construct(
        Processes $processes,
        Command $command,
        CurrentProcess $process,
        PeriodInterface $period
    ) {
        $this->processes = $processes;
        $this->command = $command;
        $this->process = $process;
        $this->period = $period;
    }

    public function __invoke(callable $ping): void
    {
        $previous = $this->output();

        do {
            $this->process->halt($this->period);
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
