<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Immutable\Attempt;

final class Ping
{
    private function __construct(
        private Ping\Implementation $implementation,
    ) {
    }

    /**
     * @template C
     * @template R
     *
     * @param C $carry
     * @param callable(R|C, Continuation<R|C>): Continuation<R> $ping
     *
     * @return Attempt<R|C>
     */
    #[\NoDiscard]
    public function __invoke(mixed $carry, callable $ping): Attempt
    {
        return ($this->implementation)($carry, $ping);
    }

    /**
     * @internal
     */
    #[\NoDiscard]
    public static function of(Ping\Implementation $implementation): self
    {
        return new self($implementation);
    }
}
