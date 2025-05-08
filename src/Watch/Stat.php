<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;
use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;

/**
 * @internal
 */
final class Stat
{
    public function __construct(
        private Processes $processes,
        private Halt $halt,
        private Period $period,
    ) {
    }

    public function __invoke(Path $file): Ping\OutputDiff
    {
        if (\PHP_OS === 'Linux') {
            $stat = Command::foreground('xargs')
                ->withShortOption('n', '1')
                ->withShortOption('r')
                ->withArgument('stat')
                ->withOption('format', '%y %n');
        } else {
            $stat = Command::foreground('xargs')
                ->withShortOption('n', '1')
                ->withShortOption('r')
                ->withArgument('stat')
                ->withShortOption('f')
                ->withArgument('%Sm %N')
                ->withShortOption('t')
                ->withArgument('%Y-%m-%dT%H-%M-%S');
        }

        return new Ping\OutputDiff(
            $this->processes,
            Command::foreground('find')
                ->withArgument($file->toString())
                ->withShortOption('type')
                ->withArgument('f')
                ->pipe($stat),
            $this->halt,
            $this->period,
        );
    }
}
