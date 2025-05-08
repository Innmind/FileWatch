<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\Immutable\Attempt;

final class Fallback implements Implementation
{
    private ProcessOutput $attempt;
    private OutputDiff $fallback;

    public function __construct(ProcessOutput $attempt, OutputDiff $fallback)
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
