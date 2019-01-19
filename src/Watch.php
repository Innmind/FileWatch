<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

use Innmind\Url\PathInterface;

interface Watch
{
    public function __invoke(PathInterface $file): Ping;
}
