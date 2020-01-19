<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Url\Path;

interface Watch
{
    public function __invoke(Path $file): Ping;
}
