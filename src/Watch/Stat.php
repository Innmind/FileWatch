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
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    Clock,
    Period,
};

final class Stat implements Watch
{
    private Processes $processes;
    private Halt $halt;
    private Clock $clock;
    private Period $period;

    public function __construct(
        Processes $processes,
        Halt $halt,
        Clock $clock,
        Period $period
    ) {
        $this->processes = $processes;
        $this->halt = $halt;
        $this->clock = $clock;
        $this->period = $period;
    }

    public function __invoke(Path $file): Ping
    {
        return new Ping\OutputDiff(
            $this->processes,
            Command::foreground('find')
                ->withArgument($file->toString())
                ->withShortOption('type')
                ->withArgument('f')
                ->pipe(
                    Command::foreground('xargs')
                        ->withArgument('stat')
                        ->withShortOption('f')
                        ->withArgument('%Sm %N')
                        ->withShortOption('t')
                        ->withArgument('%Y-%m-%dT%H-%M-%S'),
                ),
            $this->halt,
            $this->clock,
            $this->period,
        );
    }
}
