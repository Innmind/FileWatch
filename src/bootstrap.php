<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Server\Control\Server\Processes;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PeriodInterface,
    Period\Earth\Second,
};

function bootstrap(Processes $processes, Halt $halt, TimeContinuumInterface $clock, PeriodInterface $period = null): Watch
{
    return new Watch\Fallback(
        new Watch\Tailf($processes),
        new Watch\Stat(
            $processes,
            $halt,
            $clock,
            $period ?? new Second(1)
        )
    );
}
