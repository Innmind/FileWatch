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
    public function testWatchFile()
    {
        if (\getenv('CI') && \PHP_OS === 'Linux') {
            // skip this test on linux as for some reason the kill command doesn't
            // work on linux in the CI
            $this->markTestSkipped();
        }

        @\unlink('/tmp/watch-file');
        \touch('/tmp/watch-file');
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/watch-file && sleep 1 && echo foo >> /tmp/watch-file && sleep 1 && echo foo >> /tmp/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/watch-file'))(0, static function($count) {
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

        \unlink('/tmp/watch-file');
    }

    public function testWatchFileReturnError()
    {
        if (\getenv('CI') && \PHP_OS === 'Linux') {
            // skip this test on linux as for some reason the kill command doesn't
            // work on linux in the CI
            $this->markTestSkipped();
        }

        @\unlink('/tmp/watch-file');
        \touch('/tmp/watch-file');
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/watch-file && sleep 1 && echo foo >> /tmp/watch-file && sleep 1 && echo foo >> /tmp/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/watch-file'))(0, static function($count) {
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

        \unlink('/tmp/watch-file');
    }

    public function testWatchDirectory()
    {
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && touch /tmp/watch-file && sleep 1 && rm /tmp/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/'))(0, static function($count) {
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
            'sleep 1 && touch /tmp/watch-file && sleep 1 && rm /tmp/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/'))(0, static function($count) {
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
        if (\getenv('CI') && \PHP_OS === 'Linux') {
            // skip this test on linux as for some reason the kill command doesn't
            // work on linux in the CI
            $this->markTestSkipped();
        }

        @\unlink('/tmp/watch-file');
        \touch('/tmp/watch-file');
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/watch-file && sleep 1 && echo foo >> /tmp/watch-file && sleep 1 && echo foo >> /tmp/watch-file',
        ));

        $inner = Factory::build($processes, new Usleep);
        $watch = Logger::psr($inner, $logger = $this->createMock(LoggerInterface::class));
        $logger
            ->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                [
                    'Starting to watch {path}',
                    ['path' => '/tmp/watch-file'],
                ],
                [
                    'Content at {path} changed',
                    ['path' => '/tmp/watch-file'],
                ],
                [
                    'Content at {path} changed',
                    ['path' => '/tmp/watch-file'],
                ],
            );

        $either = $watch(Path::of('/tmp/watch-file'))(0, static function($count) {
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

        \unlink('/tmp/watch-file');
    }
}
