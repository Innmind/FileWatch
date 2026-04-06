<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\FileWatch\Watch\{
    Implementation,
    Kind,
    Logger,
    Fallback,
    Tailf,
    Stat,
};
use Innmind\Server\Control\Server\Processes;
use Innmind\Time\{
    Halt,
    Period,
};
use Innmind\Url\Path;
use Psr\Log\LoggerInterface;

final class Watch
{
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    #[\NoDiscard]
    public function __invoke(Path $file): Ping
    {
        return Ping::of(($this->implementation)($file));
    }

    #[\NoDiscard]
    public static function of(
        Processes $processes,
        Halt $halt,
        ?Period $interval = null,
    ): self {
        return match (\PHP_OS) {
            'Linux' => self::linux($processes, $halt, $interval),
            'Darwin' => self::osx($processes, $halt, $interval),
        };
    }

    #[\NoDiscard]
    public static function logger(self $watch, LoggerInterface $logger): self
    {
        return new self(Logger::psr(
            $watch->implementation,
            $logger,
        ));
    }

    /**
     * @internal
     */
    public static function linux(
        Processes $processes,
        Halt $halt,
        ?Period $interval = null,
    ): self {
        return self::new(
            $processes,
            $halt,
            Stat::linux(
                $processes,
                $halt,
                $interval ?? Period::second(1),
            ),
        );
    }

    /**
     * @internal
     */
    public static function osx(
        Processes $processes,
        Halt $halt,
        ?Period $interval = null,
    ): self {
        return self::new(
            $processes,
            $halt,
            Stat::osx(
                $processes,
                $halt,
                $interval ?? Period::second(1),
            ),
        );
    }

    private static function new(
        Processes $processes,
        Halt $halt,
        Stat $directories,
    ): self {
        $files = new Tailf($processes);

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
}
