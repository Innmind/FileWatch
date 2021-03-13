<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;
use Psr\Log\LoggerInterface;

final class Logger implements Ping
{
    private Ping $ping;
    private Path $path;
    private LoggerInterface $logger;

    public function __construct(
        Ping $ping,
        Path $path,
        LoggerInterface $logger
    ) {
        $this->ping = $ping;
        $this->path = $path;
        $this->logger = $logger;
    }

    public function __invoke(callable $ping): void
    {
        $this->logger->info(
            'Starting to watch {path}',
            ['path' => $this->path->toString()],
        );

        ($this->ping)(function() use ($ping): void {
            $this->logger->info(
                'Content at {path} changed',
                ['path' => $this->path->toString()],
            );

            $ping();
        });
    }
}
