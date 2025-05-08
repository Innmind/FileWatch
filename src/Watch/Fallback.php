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
    private Tailf $attempt;
    private Stat $fallback;

    public function __construct(Tailf $attempt, Stat $fallback)
    {
        $this->attempt = $attempt;
        $this->fallback = $fallback;
    }

    #[\Override]
    public function __invoke(Path $file): Ping
    {
        return new Ping\Fallback(
            ($this->attempt)($file),
            ($this->fallback)($file),
        );
    }
}
