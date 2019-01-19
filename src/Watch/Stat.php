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
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PeriodInterface,
};

final class Stat implements Watch
{
    private $processes;
    private $halt;
    private $clock;
    private $period;

    public function __construct(
        Processes $processes,
        Halt $halt,
        TimeContinuumInterface $clock,
        PeriodInterface $period
    ) {
        $this->processes = $processes;
        $this->halt = $halt;
        $this->clock = $clock;
        $this->period = $period;
    }

    public function __invoke(PathInterface $file): Ping
    {
        return new Ping\OutputDiff(
            $this->processes,
            Command::foreground('stat')
                ->withArgument((string) $file),
            $this->halt,
            $this->clock,
            $this->period
        );
    }
}
