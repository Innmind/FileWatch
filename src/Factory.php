<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Server\Control\Server\Processes;
use Innmind\Time\{
    Halt,
    Period,
};

final class Factory
{
    #[\NoDiscard]
    public static function build(
        Processes $processes,
        Halt $halt,
        ?Period $interval = null,
    ): Watch {
        return Watch::of($processes, $halt, $interval);
    }
}
