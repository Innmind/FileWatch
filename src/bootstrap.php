<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Server\Control\Server\Processes;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    Clock,
    Period,
    Earth\Period\Second,
};

function bootstrap(Processes $processes, Halt $halt, Clock $clock, Period $period = null): Watch
{
    return new Watch\Fallback(
        new Watch\Tailf($processes),
        new Watch\Stat(
            $processes,
            $halt,
            $clock,
            $period ?? new Second(1),
        ),
    );
}
