<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Continuation,
    Ping,
};
use Innmind\Url\Path;
use Innmind\Immutable\Maybe;
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
     * @template R
     *
     * @param C $carry
     * @param callable(R|C, Continuation<R|C>): Continuation<R> $ping
     *
     * @return Maybe<R|C>
     */
    public function __invoke(mixed $carry, callable $ping): Maybe
    {
        $this->logger->info( // todo use debug
            'Starting to watch {path}',
            ['path' => $this->path->toString()],
        );

        /**
         * @psalm-suppress InvalidArgument
         * @var Maybe<R|C>
         */
        return ($this->ping)($carry, function(mixed $carry, Continuation $continuation) use ($ping): Continuation {
            /** @var C $carry */
            $this->logger->info(
                'Content at {path} changed',
                ['path' => $this->path->toString()],
            );

            return $ping($carry, $continuation);
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
