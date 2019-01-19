<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    PeriodInterface,
    Period\Earth\Second,
};

function bootstrap(OperatingSystem $os, Halt $halt, PeriodInterface $period = null): Watch
{
    $processes = $os->control()->processes();

    return new Watch\Fallback(
        new Watch\Tailf($processes),
        new Watch\Stat(
            $processes,
            $halt,
            $os->clock(),
            $period ?? new Second(1)
        )
    );
}
