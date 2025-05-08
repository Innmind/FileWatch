<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch,
    Ping,
};
use Innmind\Url\Path;
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

    #[\Override]
    public function __invoke(Path $file): Ping
    {
        return new Ping\ProcessOutput(
            $this->processes,
            Command::foreground('tail')
                ->withShortOption('f')
                ->withArgument($file->toString())
                ->streamOutput(),
        );
    }
}
