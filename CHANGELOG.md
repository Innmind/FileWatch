# Changelog

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
