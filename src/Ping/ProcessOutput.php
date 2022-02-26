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
    Process\Output\Type,
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
            $_ = $process->output()->foreach(static function($_, $type) use ($ping) {
                if ($type === Type::error) {
                    throw new WatchFailed;
                }

                $ping();
            });

            return Either::right($_);
        } catch (WatchFailed $e) {
            $this->kill($process);

            return Either::left($e);
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
