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
    Clock,
    Period,
};

final class OutputDiff implements Ping
{
    private Processes $processes;
    private Command $command;
    private Halt $halt;
    private Clock $clock;
    private Period $period;

    public function __construct(
        Processes $processes,
        Command $command,
        Halt $halt,
        Clock $clock,
        Period $period
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
        $process = $this->processes->execute($this->command);
        $process->wait();

        if (!$process->exitCode()->successful()) {
            throw new WatchFailed($this->command->toString());
        }

        return $process->output();
    }

    private function diff(Output $previous, Output $now): bool
    {
        return $previous->toString() !== $now->toString();
    }
}
