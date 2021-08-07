<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Server\Control\Server\Processes;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    Period,
    Earth\Period\Second,
};

function bootstrap(Processes $processes, Halt $halt, Period $period = null): Watch
{
    return new Watch\Fallback(
        new Watch\Tailf($processes),
        new Watch\Stat(
            $processes,
            $halt,
            $period ?? new Second(1),
        ),
    );
}
