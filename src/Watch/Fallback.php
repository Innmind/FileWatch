<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch,
    Ping,
};
use Innmind\Url\Path;

final class Fallback implements Watch
{
    private Watch $attempt;
    private Watch $fallback;

    public function __construct(Watch $attempt, Watch $fallback)
    {
        $this->attempt = $attempt;
        $this->fallback = $fallback;
    }

    public function __invoke(Path $file): Ping
    {
        return new Ping\Fallback(
            ($this->attempt)($file),
            ($this->fallback)($file),
        );
    }
}
