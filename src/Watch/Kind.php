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
    private Watch $files;
    private Watch $directories;

    public function __construct(Watch $files, Watch $directories)
    {
        $this->files = $files;
        $this->directories = $directories;
    }

    public function __invoke(Path $file): Ping
    {
        return match ($file->directory()) {
            true => ($this->directories)($file),
            false => ($this->files)($file),
        };
    }
}
