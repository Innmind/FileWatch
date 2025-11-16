<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\Ping;
use Innmind\Url\Path;

/**
 * @internal
 */
interface Implementation
{
    public function __invoke(Path $file): Ping\Implementation;
}
