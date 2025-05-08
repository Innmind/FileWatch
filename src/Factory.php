<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Server\Control\Server\Processes;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;

final class Factory
{
    public static function build(
        Processes $processes,
        Halt $halt,
        ?Period $interval = null,
    ): Watch {
        return Watch::of($processes, $halt, $interval);
    }
}
