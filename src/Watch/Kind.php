<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;

/**
 * @internal
 */
final class Kind
{
    public function __construct(
        private Fallback $files,
        private Stat $directories,
    ) {
    }

    public function __invoke(Path $file): Ping\Implementation
    {
        return match ($file->directory()) {
            true => ($this->directories)($file),
            false => ($this->files)($file),
        };
    }
}
