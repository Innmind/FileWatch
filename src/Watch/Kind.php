<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch,
    Ping,
};
use Innmind\Url\Path;

final class Kind implements Watch
{
    private Fallback $files;
    private Stat $directories;

    public function __construct(Fallback $files, Stat $directories)
    {
        $this->files = $files;
        $this->directories = $directories;
    }

    #[\Override]
    public function __invoke(Path $file): Ping
    {
        return match ($file->directory()) {
            true => ($this->directories)($file),
            false => ($this->files)($file),
        };
    }
}
