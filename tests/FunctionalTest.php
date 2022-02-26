<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch;

use function Innmind\FileWatch\bootstrap;
use Innmind\FileWatch\Exception\WatchFailed;
use Innmind\Server\Control\Server\{
    Processes\Unix,
    Command,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Watch\Select;
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    public function testWatchFile()
    {
        @\unlink('/tmp/watch-file');
        \touch('/tmp/watch-file');
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(Command::background(
            'sleep 1 && echo foo >> /tmp/watch-file && sleep 1 && echo foo >> /tmp/watch-file',
        ));

        $watch = bootstrap($processes, new Usleep);
        $stop = new \Exception;
        $count = 0;

        try {
            $watch(Path::of('/tmp/watch-file'))(static function() use (&$count, $stop) {
                ++$count;

                if ($count === 2) {
                    throw $stop;
                }
            });
        } catch (\Throwable $e) {
            $this->assertSame($stop, $e);
            $this->assertSame(2, $count);
        }

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

        $watch = bootstrap($processes, new Usleep);
        $stop = new \Exception;
        $count = 0;

        try {
            $either = $watch(Path::of('/tmp/'))(static function() use (&$count, $stop) {
                ++$count;

                if ($count === 2) {
                    throw $stop;
                }
            });
        } catch (\Throwable $e) {
            $this->assertSame($stop, $e, $e->getMessage());
            $this->assertSame(2, $count);
        }
    }

    public function testReturnErrorWhenWatchingUnknownFile()
    {
        $processes = Unix::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );

        $watch = bootstrap($processes, new Usleep);

        $either = $watch(Path::of('/unknown/'))(static fn() => null);

        $this->assertInstanceOf(
            WatchFailed::class,
            $either->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }
}
