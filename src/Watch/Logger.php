<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Logger
{
    private function __construct(
        private Kind $watch,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Path $path): Ping\Implementation
    {
        return Ping\Logger::psr(
            ($this->watch)($path),
            $path,
            $this->logger,
        );
    }

    public static function psr(Kind|self $watch, LoggerInterface $logger): self
    {
        return new self(self::extract($watch), $logger);
    }

    private static function extract(Kind|self $watch): Kind
    {
        if ($watch instanceof Kind) {
            return $watch;
        }

        return self::extract($watch->watch);
    }
}
