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
use Innmind\OperatingSystem\CurrentProcess;
use Innmind\TimeContinuum\PeriodInterface;

final class Stat implements Watch
{
    private $processes;
    private $process;
    private $period;

    public function __construct(
        Processes $processes,
        CurrentProcess $process,
        PeriodInterface $period
    ) {
        $this->processes = $processes;
        $this->process = $process;
        $this->period = $period;
    }

    public function __invoke(PathInterface $file): Ping
    {
        return new Ping\OutputDiff(
            $this->processes,
            Command::foreground('find')
                ->withArgument((string) $file)
                ->withShortOption('type')
                ->withArgument('f')
                ->pipe(
                    Command::foreground('xargs')
                        ->withArgument('stat')
                        ->withShortOption('f')
                        ->withArgument('%Sm %N')
                        ->withShortOption('t')
                        ->withArgument('%Y-%m-%dT%H-%M-%S')
                ),
            $this->process,
            $this->period
        );
    }
}
