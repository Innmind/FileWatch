<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\{
    PeriodInterface,
    Period\Earth\Second,
};

function bootstrap(OperatingSystem $os, PeriodInterface $period = null): Watch
{
    $processes = $os->control()->processes();

    return new Watch\Fallback(
        new Watch\Tailf($processes),
        new Watch\Stat(
            $processes,
            $os->process(),
            $period ?? new Second(1)
        )
    );
}
