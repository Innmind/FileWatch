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
    Process,
    Signal,
};
use Innmind\Immutable\Either;

final class ProcessOutput implements Ping
{
    private Processes $processes;
    private Command $command;

    public function __construct(Processes $processes, Command $command)
    {
        $this->processes = $processes;
        $this->command = $command;
    }

    public function __invoke(callable $ping): Either
    {
        $process = $this->processes->execute($this->command);

        try {
            $_ = $process->output()->foreach($ping);

            return $process
                ->wait()
                ->leftMap(fn() => new WatchFailed($this->command->toString()));
        } catch (\Throwable $e) {
            $this->kill($process);

            throw $e;
        }
    }

    private function kill(Process $process): void
    {
        $_ = $process->pid()->match(
            fn($pid) => $this->processes->kill($pid, Signal::terminate),
            static fn() => null,
        );
    }
}
