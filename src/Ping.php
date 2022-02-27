<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Immutable\Either;

interface Ping
{
    /**
     * @template C
     * @template L
     *
     * @param C $carry
     * @param callable(C): Either<L|Stop<C>, C> $ping
     *
     * @return Either<Failed|L, C>
     */
    public function __invoke(mixed $carry, callable $ping): Either;
}
