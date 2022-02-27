<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Server\Control\Server\Processes;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    Period,
    Earth\Period\Second,
};

final class Factory
{
    public static function build(
        Processes $processes,
        Halt $halt,
        Period $interval = null,
    ): Watch {
        return new Watch\Fallback(
            new Watch\Tailf($processes),
            new Watch\Stat(
                $processes,
                $halt,
                $interval ?? new Second(1),
            ),
        );
    }
}
