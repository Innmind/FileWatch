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
        $files = new Watch\Tailf($processes);
        $directories = new Watch\Stat(
            $processes,
            $halt,
            $interval ?? new Second(1),
        );

        return new Watch\Kind(
            new Watch\Fallback(
                $files,
                $directories,
            ),
            $directories,
        );
    }
}
