<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch;

use Innmind\FileWatch\{
    Factory,
    Watch\Logger,
    Failed,
    Stop,
};
use Innmind\Server\Control\Server\{
    Processes\Unix,
    Command,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Watch\Select;
use Innmind\Url\Path;
use Innmind\Immutable\Either;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    public function setUp(): void
    {
        @\unlink('/tmp/inmmind/watch-file');
        @\mkdir('/tmp/innmind');
    }

    public function tearDown(): void
    {
        @\unlink('/tmp/inmmind/watch-file');
    }

    public function testWatchFile()
    {
        \touch('/tmp/innmind/watch-file');
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/innmind/watch-file'))(0, static function($count) {
            ++$count;

            if ($count === 2) {
                return Either::left(Stop::of($count));
            }

            return Either::right($count);
        });

        $this->assertSame(
            2,
            $either->match(
                static fn($count) => $count,
                static fn() => null,
            ),
        );
    }

    public function testWatchFileReturnError()
    {
        \touch('/tmp/innmind/watch-file');
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/innmind/watch-file'))(0, static function($count) {
            ++$count;

            if ($count === 2) {
                // because it's not an instance of Stop then it is considered
                // as a general purpose error
                return Either::left($count);
            }

            return Either::right($count);
        });

        $this->assertSame(
            2,
            $either->match(
                static fn() => null,
                static fn($count) => $count,
            ),
        );
    }

    public function testWatchDirectory()
    {
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && touch /tmp/innmind/watch-file && sleep 1 && rm /tmp/innmind/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/innmind/'))(0, static function($count) {
            ++$count;

            if ($count === 2) {
                return Either::left(Stop::of($count));
            }

            return Either::right($count);
        });

        $this->assertSame(
            2,
            $either->match(
                static fn($count) => $count,
                static fn() => null,
            ),
        );
    }

    public function testWatchDirectoryReturnError()
    {
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && touch /tmp/innmind/watch-file && sleep 1 && rm /tmp/innmind/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/innmind/'))(0, static function($count) {
            ++$count;

            if ($count === 2) {
                // because it's not an instance of Stop then it is considered
                // as a general purpose error
                return Either::left($count);
            }

            return Either::right($count);
        });

        $this->assertSame(
            2,
            $either->match(
                static fn() => null,
                static fn($count) => $count,
            ),
        );
    }

    public function testReturnErrorWhenWatchingUnknownFile()
    {
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/unknown/'))(null, static fn() => null);

        $this->assertInstanceOf(
            Failed::class,
            $either->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }
    public function testLog()
    {
        \touch('/tmp/innmind/watch-file');
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file',
        ));

        $inner = Factory::build($processes, new Usleep);
        $watch = Logger::psr($inner, $logger = $this->createMock(LoggerInterface::class));
        $logger
            ->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                [
                    'Starting to watch {path}',
                    ['path' => '/tmp/innmind/watch-file'],
                ],
                [
                    'Content at {path} changed',
                    ['path' => '/tmp/innmind/watch-file'],
                ],
                [
                    'Content at {path} changed',
                    ['path' => '/tmp/innmind/watch-file'],
                ],
            );

        $either = $watch(Path::of('/tmp/innmind/watch-file'))(0, static function($count) {
            ++$count;

            if ($count === 2) {
                return Either::left(Stop::of($count));
            }

            return Either::right($count);
        });

        $this->assertSame(
            2,
            $either->match(
                static fn($count) => $count,
                static fn() => null,
            ),
        );
    }
}
