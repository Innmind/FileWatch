<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};

/**
 * @internal
 */
final class Tailf
{
    public function __construct(
        private Processes $processes,
    ) {
    }

    public function __invoke(Path $file): Ping\ProcessOutput
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
