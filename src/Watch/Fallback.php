<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;

/**
 * @internal
 */
final class Fallback
{
    public function __construct(
        private Tailf $attempt,
        private Stat $fallback,
    ) {
    }

    public function __invoke(Path $file): Ping\Implementation
    {
        return new Ping\Fallback(
            ($this->attempt)($file),
            ($this->fallback)($file),
        );
    }
}
