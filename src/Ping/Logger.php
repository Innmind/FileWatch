<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping,
    Stop,
    Failed,
};
use Innmind\Url\Path;
use Innmind\Immutable\Either;
use Psr\Log\LoggerInterface;

final class Logger implements Ping
{
    private Ping $ping;
    private Path $path;
    private LoggerInterface $logger;

    private function __construct(
        Ping $ping,
        Path $path,
        LoggerInterface $logger,
    ) {
        $this->ping = $ping;
        $this->path = $path;
        $this->logger = $logger;
    }

    /**
     * @template C
     * @template L
     *
     * @param C $carry
     * @param callable(C): Either<L|Stop<C>, C> $ping
     *
     * @return Either<Failed|L, C>
     */
    public function __invoke(mixed $carry, callable $ping): Either
    {
        $this->logger->info( // todo use debug
            'Starting to watch {path}',
            ['path' => $this->path->toString()],
        );

        /** @var Either<Failed|L, C> */
        return ($this->ping)($carry, function(mixed $carry) use ($ping): Either {
            /** @var C $carry */
            $this->logger->info(
                'Content at {path} changed',
                ['path' => $this->path->toString()],
            );

            return $ping($carry);
        });
    }

    public static function psr(
        Ping $ping,
        Path $path,
        LoggerInterface $logger,
    ): self {
        return new self($ping, $path, $logger);
    }
}
