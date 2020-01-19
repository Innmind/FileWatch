# FileWatch

| `develop` |
|-----------|
| [![codecov](https://codecov.io/gh/Innmind/FileWatch/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/FileWatch) |
| [![Build Status](https://github.com/Innmind/FileWatch/workflows/CI/badge.svg)](https://github.com/Innmind/FileWatch/actions?query=workflow%3ACI) |

Small tool to execute code every time a file (or folder) is modified.

## Installation

```sh
composer require innmind/file-watch
```

## Usage

```php
use function Innmind\FileWatch\bootstrap;
use Innmind\Server\Control\ServerFactory;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\RimeContinuum\Earth\Clock;
use Innmind\Url\Path;

$watch = bootstrap(
    ServerFactory::build()->processes(),
    new Usleep,
    new Clock,
);

$watch(new Path('/to/some/file/or/folder'))(function(): void {
    // this function is called every time the file is modified
});
```

**Note**: The function may be called multiple times for an single change due to the way `tail` and `stat` works.
