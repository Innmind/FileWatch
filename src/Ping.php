<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

interface Ping
{
    public function __invoke(callable $ping): void;
}
