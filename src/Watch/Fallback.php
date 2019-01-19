<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch,
    Ping,
};
use Innmind\Url\PathInterface;

final class Fallback implements Watch
{
    private $attempt;
    private $fallback;

    public function __construct(Watch $attempt, Watch $fallback)
    {
        $this->attempt = $attempt;
        $this->fallback = $fallback;
    }

    public function __invoke(PathInterface $file): Ping
    {
        return new Ping\Fallback(
            ($this->attempt)($file),
            ($this->fallback)($file)
        );
    }
}
