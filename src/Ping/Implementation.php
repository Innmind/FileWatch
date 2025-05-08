<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\Continuation;
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
interface Implementation
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
