<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Immutable\Maybe;

interface Ping
{
    /**
     * @template C
     * @template R
     *
     * @param C $carry
     * @param callable(R|C, Continuation<R|C>): Continuation<R> $ping
     *
     * @return Maybe<R|C>
     */
    public function __invoke(mixed $carry, callable $ping): Maybe;
}
