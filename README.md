# FileWatch

| `develop` |
|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/FileWatch/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/FileWatch/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/FileWatch/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/FileWatch/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/FileWatch/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/FileWatch/build-status/develop) |

Small tool to execute code every time a file (or folder) is modified.

## Installation

```sh
composer require innmind/file-watch
```

## Usage

```php
use function Innmind\FileWatch\bootstrap;
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Path;

$watch = bootstrap(Factory::build());

$watch(new Path('/to/some/file/or/folder'))(function(): void {
    // this function is called every time the file is modified
});
```

**Note**: The function may be called multiple times for an single change due to the way `tail` and `stat` works.
