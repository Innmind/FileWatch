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

$count = $watch(Path::of('/to/some/file/or/folder'))(0, function(int $count): Either {
    // this function is called every time the file is modified
    ++$count;

    if ($count === 42) {
        // by returning a Stop instance on the left side it will instruct to
        // stop watching for changes and the value in the Stop will be moved on
        // the right side to the caller
        return Either::left(Stop::of($count));
    }

    if ($forSomeReason) {
        // by returning a left value it will stop watching for changes and the
        // Either will be returned as is to the caller
        return Either::left($someReason);
    }

    // by returning a right side it will instruct to continue watching for changes
    // and the value will be passed to this callable the next time it's called
    return Either::right($count);
});
```

**Note**: The function may be called multiple times for an single change due to the way `tail` and `stat` works.
