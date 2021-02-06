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

final class ProcessOutput implements Ping
{
    private Processes $processes;
    private Command $command;

    public function __construct(Processes $processes, Command $command)
    {
        $this->processes = $processes;
        $this->command = $command;
    }

    public function __invoke(callable $ping): void
    {
        $process = $this->processes->execute($this->command);

        try {
            $process
                ->output()
                ->foreach(static function() use ($ping): void {
                    $ping();
                });

            if (!$process->exitCode()->successful()) {
                throw new WatchFailed($this->command->toString());
            }
        } catch (WatchFailed $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->kill($process);

            throw $e;
        }
    }

    private function kill(Process $process): void
    {
        if (!$process->isRunning()) {
            return;
        }

        $this->processes->kill($process->pid(), Signal::terminate());
    }
}
