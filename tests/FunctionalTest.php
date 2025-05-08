<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch;

use Innmind\FileWatch\{
    Factory,
    Watch\Logger,
};
use Innmind\Server\Control\Server\{
    Processes\Unix,
    Command,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\Url\Path;
use Psr\Log\NullLogger;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

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
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/innmind/watch-file'))(0, static function($count, $continuation) {
            ++$count;

            if ($count === 2) {
                return $continuation->stop($count);
            }

            return $continuation->continue($count);
        });

        $this->assertSame(
            2,
            $either->match(
                static fn($count) => $count,
                static fn() => null,
            ),
        );
    }

    public function testWatchDirectory()
    {
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && touch /tmp/innmind/watch-file && sleep 1 && rm /tmp/innmind/watch-file',
        ));

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/tmp/innmind/'))(0, static function($count, $continuation) {
            ++$count;

            if ($count === 2) {
                return $continuation->stop($count);
            }

            return $continuation->continue($count);
        });

        $this->assertSame(
            2,
            $either->match(
                static fn($count) => $count,
                static fn() => null,
            ),
        );
    }

    public function testReturnErrorWhenWatchingUnknownFile()
    {
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );

        $watch = Factory::build($processes, new Usleep);

        $either = $watch(Path::of('/unknown/'))(null, static fn($_, $continuation) => $continuation);

        $this->assertFalse(
            $either->match(
                static fn() => true,
                static fn() => false,
            ),
        );
    }
    public function testLog()
    {
        \touch('/tmp/innmind/watch-file');
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file && sleep 1 && echo foo >> /tmp/innmind/watch-file',
        ));

        $inner = Factory::build($processes, new Usleep);
        $watch = Logger::psr($inner, new NullLogger);

        $either = $watch(Path::of('/tmp/innmind/watch-file'))(0, static function($count, $continuation) {
            ++$count;

            if ($count === 2) {
                return $continuation->stop($count);
            }

            return $continuation->continue($count);
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
