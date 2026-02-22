# Changelog

## [Unreleased]

### Fixed

- When the process to check for a directory change fails it raised a PHP error

## 6.0.0 - 2026-02-07

### Changed

- Requires PHP `8.4`
- Requires `innmind/server-control:~7.0`
- Requires `innmind/time:~1.0`

## 5.0.0 - 2025-05-08

### Changed

- `Innmind\FileWatch\Ping::__invoke()` now returns an `Innmind\Immutable\Attempt`
- `Innmind\FileWatch\Watch` is now a final class
- `Innmind\FileWatch\Ping` is now a final class
- All classes in `Innmind\FileWatch\Watch\*` and `Innmind\FileWatch\Ping\*` are now flagged as internal

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
