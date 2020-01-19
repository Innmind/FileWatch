<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch,
    Ping,
};
use Innmind\Url\PathInterface;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};

final class Tailf implements Watch
{
    private Processes $processes;

    public function __construct(Processes $processes)
    {
        $this->processes = $processes;
    }

    public function __invoke(PathInterface $file): Ping
    {
        return new Ping\ProcessOutput(
            $this->processes,
            Command::foreground("[ -f $file ] && tail")
                ->withShortOption('f')
                ->withArgument((string) $file),
        );
    }
}
