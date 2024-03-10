<?php
declare(strict_types = 1);

namespace Innmind\FileWatch\Continuation;

/**
 * @psalm-immutable
 */
enum State
{
    case continue;
    case stop;
}
