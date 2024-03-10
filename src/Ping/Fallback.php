<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\Ping;
use Innmind\Immutable\Maybe;

final class Fallback implements Ping
{
    private Ping $attempt;
    private Ping $fallback;

    public function __construct(Ping $attempt, Ping $fallback)
    {
        $this->attempt = $attempt;
        $this->fallback = $fallback;
    }

    public function __invoke(mixed $carry, callable $ping): Maybe
    {
        return ($this->attempt)($carry, $ping)->otherwise(
            fn() => ($this->fallback)($carry, $ping),
        );
    }
}
