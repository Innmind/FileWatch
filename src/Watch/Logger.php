<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch,
    Ping,
};
use Innmind\Url\Path;
use Psr\Log\LoggerInterface;

final class Logger implements Watch
{
    private Watch $watch;
    private LoggerInterface $logger;

    private function __construct(Watch $watch, LoggerInterface $logger)
    {
        $this->watch = $watch;
        $this->logger = $logger;
    }

    public function __invoke(Path $path): Ping
    {
        return Ping\Logger::psr(
            ($this->watch)($path),
            $path,
            $this->logger,
        );
    }

    public static function psr(Watch $watch, LoggerInterface $logger): self
    {
        return new self($watch, $logger);
    }
}
