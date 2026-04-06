<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\Time\{
    Halt,
    Period,
};

/**
 * @internal
 */
final class Stat
{
    private function __construct(
        private Processes $processes,
        private Halt $halt,
        private Period $period,
        private Command $stat,
    ) {
    }

    public function __invoke(Path $file): Ping\OutputDiff
    {
        return new Ping\OutputDiff(
            $this->processes,
            Command::foreground('find')
                ->withArgument($file->toString())
                ->withShortOption('type')
                ->withArgument('f')
                ->pipe($this->stat),
            $this->halt,
            $this->period,
        );
    }

    /**
     * @internal
     */
    public static function linux(
        Processes $processes,
        Halt $halt,
        Period $period,
    ): self {
        return new self(
            $processes,
            $halt,
            $period,
            Command::foreground('xargs')
                ->withShortOption('n', '1')
                ->withShortOption('r')
                ->withArgument('stat')
                ->withOption('format', '%y %n'),
        );
    }

    /**
     * @internal
     */
    public static function osx(
        Processes $processes,
        Halt $halt,
        Period $period,
    ): self {
        return new self(
            $processes,
            $halt,
            $period,
            Command::foreground('xargs')
                ->withShortOption('n', '1')
                ->withShortOption('r')
                ->withArgument('stat')
                ->withShortOption('f')
                ->withArgument('%Sm %N')
                ->withShortOption('t')
                ->withArgument('%Y-%m-%dT%H-%M-%S'),
        );
    }
}
