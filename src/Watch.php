<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\FileWatch\Watch\{
    Kind,
    Logger,
    Fallback,
    Tailf,
    Stat,
};
use Innmind\Server\Control\Server\Processes;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;
use Innmind\Url\Path;
use Psr\Log\LoggerInterface;

final class Watch
{
    private function __construct(
        private Kind|Logger $implementation,
    ) {
    }

    public function __invoke(Path $file): Ping
    {
        return Ping::of(($this->implementation)($file));
    }

    public static function of(
        Processes $processes,
        Halt $halt,
        ?Period $interval = null,
    ): self {
        $files = new Tailf($processes);
        $directories = new Stat(
            $processes,
            $halt,
            $interval ?? Period::second(1),
        );

        return new self(
            new Kind(
                new Fallback(
                    $files,
                    $directories,
                ),
                $directories,
            ),
        );
    }

    public static function logger(self $watch, LoggerInterface $logger): self
    {
        return new self(Logger::psr(
            $watch->implementation,
            $logger,
        ));
    }
}
