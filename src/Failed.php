<?php
declare(strict_types = 1);

namespace Innmind\FileWatch;

/**
 * It means that no strategy is able to watch the specified path or that at some
 * point the path is no longer watchable (ie: the path has been deleted)
 */
final class Failed
{
}
