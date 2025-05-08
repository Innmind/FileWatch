<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Immutable\Attempt;

interface Ping
{
    /**
     * @template C
     * @template R
     *
     * @param C $carry
     * @param callable(R|C, Continuation<R|C>): Continuation<R> $ping
     *
     * @return Attempt<R|C>
     */
    public function __invoke(mixed $carry, callable $ping): Attempt;
}
