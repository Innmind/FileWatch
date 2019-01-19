<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\Ping;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};

final class ProcessOutput implements Ping
{
    private $processes;
    private $command;

    public function __construct(Processes $processes, Command $command)
    {
        $this->processes = $processes;
        $this->command = $command;
    }

    public function __invoke(callable $ping): void
    {
        $this
            ->processes
            ->execute($this->command)
            ->output()
            ->foreach(static function() use ($ping): void {
                $ping();
            });
    }
}
