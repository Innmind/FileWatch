<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping,
    Exception\WatchFailed,
    Stop,
};
use Innmind\Immutable\Either;

final class Fallback implements Ping
{
    private Ping $attempt;
    private Ping $fallback;

    public function __construct(Ping $attempt, Ping $fallback)
    {
        $this->attempt = $attempt;
        $this->fallback = $fallback;
    }

    /**
     * @template C
     * @template L
     *
     * @param C $carry
     * @param callable(C): Either<L|Stop<C>, C> $ping
     *
     * @return Either<WatchFailed|L, C>
     */
    public function __invoke(mixed $carry, callable $ping): Either
    {
        /**
         * @psalm-suppress InvalidArgument
         * @var Either<WatchFailed|L, C>
         */
        return ($this->attempt)($carry, $ping)->otherwise(
            fn($left) => match ($left instanceof WatchFailed) {
                true => ($this->fallback)($carry, $ping),
                false => Either::left($left),
            },
        );
    }
}
