<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\FileWatch\Exception\WatchFailed;
use Innmind\Immutable\{
    Either,
    SideEffect,
};

interface Ping
{
    /**
     * @param callable(): void $ping
     *
     * @return Either<WatchFailed, SideEffect>
     */
    public function __invoke(callable $ping): Either;
}
