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
            $_ = $process
                ->output()
                ->foreach(static function() use ($ping): void {
                    $ping();
                });
            $throwOnError = $process
                ->wait()
                ->leftMap(fn() => new WatchFailed($this->command->toString()))
                ->match(
                    static fn($e) => static fn() => throw $e,
                    static fn() => static fn() => null,
                );
            $throwOnError();
        } catch (WatchFailed $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->kill($process);

            throw $e;
        }
    }

    private function kill(Process $process): void
    {
        $_ = $process->pid()->match(
            fn($pid) => $this->processes->kill($pid, Signal::terminate()),
            static fn() => null,
        );
    }
}
