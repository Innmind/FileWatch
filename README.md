# FileWatch

[![Build Status](https://github.com/innmind/filewatch/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/filewatch/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/filewatch/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/filewatch)
[![Type Coverage](https://shepherd.dev/github/innmind/filewatch/coverage.svg)](https://shepherd.dev/github/innmind/filewatch)

Small tool to execute code every time a file (or folder) is modified.

## Installation

```sh
composer require innmind/file-watch
```

## Usage

```php
use Innmind\FileWatch\{
    Factory,
    Stop,
};
use Innmind\Server\Control\ServerFactory;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Stream\Streams;
use Innmind\Url\Path;
use Innmind\Immutable\Either;

$watch = Factory::build(
    ServerFactory::build(
        new Clock,
        Streams::fromAmbientAuthority(),
        new Usleep,
    )->processes(),
    new Usleep,
);

$count = $watch(Path::of('/to/some/file/or/folder'))(0, function(int $count, Continuation $continuation): Continuation {
    // this function is called every time the file is modified
    ++$count;

    if ($count === 42) {
        // This will stop watching the folder for changes and return the count
        return $continuation->stop($count);
    }

    // This will instruct to continue watching for changes and the value will be
    // passed to this callable the next time it's called
    return $continuation->continue($count);
})->match(
    static fn(int $count) => $count, // always 42 as it's the stopping value
    static fn() => throw new \RuntimeException('Failed to watch for changes'),
);
```

> [!WARNING]
> The function may be called multiple times for an single change due to the way `tail` and `stat` works.
