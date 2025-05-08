# Changelog

## [Unreleased]

### Fixed

- PHP `8.4` deprecation
- Fix a memory leak when watching a file that has been changed many times

## 4.0.0 - 2024-03-10

### Changed

- When the ping callable is called it must return a `Innmind\FileWatch\Continuation`

### Removed

- `Innmind\FileWatch\Stop`
- `Innmind\FileWatch\Failed`

## 3.2.0 - 2023-11-12

### Added

- `Innmind\FileWatch\Watch\Kind`

### Changed

- Directly use an output diff when the watched path targets a directory

### Removed

- Support for PHP `8.1`

## 3.1.0 - 2023-01-29

### Changed

- Require `innmind/server-control:~5.0`

## 3.0.0 - 2022-02-27

### Added

- `Innmind\FileWatch\Factory::build()`
- `Innmind\FileWatch\Failed`
- `Innmind\FileWatch\Stop`

### Changed

- `Innmind\FileWatch\Ping::__invoke()` now has a value as first parameter that will be passed to the callable at each call, the callable now must return a `Innmind\Immutable\Either<L|Innmind\FileWatch\Stop<C>, C>`
- `Innmind\FileWatch\Ping\Logger` constructor is now private, use `::psr()` named constructor instead
- `Innmind\FileWatch\Watch\Logger` constructor is now private, use `::psr()` named constructor instead

### Removed

- Support for php `7.4` and `8.0`
- `Innmind\FileWatch\bootstrap()`, use `Innmind\FileWatch\Factory::build()` instead
- `Innmind\FileWatch\Exception\Exception`
- `Innmind\FileWatch\Exception\RuntimeException`
- `Innmind\FileWatch\Exception\WatchFailed`

### Fixed

- Support for watching directories on Linux
