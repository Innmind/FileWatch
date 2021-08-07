<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\Ping;
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

    public function __invoke(callable $ping): Either
    {
        return ($this->attempt)($ping)->otherwise(
            fn() => ($this->fallback)($ping),
        );
    }
}
