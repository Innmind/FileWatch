<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Ping;

use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Fallback implements Implementation
{
    public function __construct(
        private ProcessOutput $attempt,
        private OutputDiff $fallback,
    ) {
    }

    #[\Override]
    public function __invoke(mixed $carry, callable $ping): Attempt
    {
        return ($this->attempt)($carry, $ping)->recover(
            fn() => ($this->fallback)($carry, $ping),
        );
    }
}
