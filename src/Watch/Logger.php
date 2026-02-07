<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Logger implements Implementation
{
    private function __construct(
        private Implementation $watch,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function __invoke(Path $path): Ping\Implementation
    {
        return Ping\Logger::psr(
            ($this->watch)($path),
            $path,
            $this->logger,
        );
    }

    public static function psr(Implementation $watch, LoggerInterface $logger): self
    {
        return new self($watch, $logger);
    }
}
