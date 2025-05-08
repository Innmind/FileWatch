<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\FileWatch\Ping;
use Innmind\Immutable\Attempt;

final class Fallback implements Ping
{
    private Ping $attempt;
    private Ping $fallback;

    public function __construct(Ping $attempt, Ping $fallback)
    {
        $this->attempt = $attempt;
        $this->fallback = $fallback;
    }

    #[\Override]
    public function __invoke(mixed $carry, callable $ping): Attempt
    {
        return ($this->attempt)($carry, $ping)->recover(
            fn() => ($this->fallback)($carry, $ping),
        );
    }
}
