<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch\Stat,
    Watch,
    Ping\OutputDiff,
};
use Innmind\Url\Path;
use Innmind\Server\Control\Server\{
    Processes,
    Process,
    Process\ExitCode,
    Process\Output,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class StatTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Watch::class,
            new Stat(
                $this->createMock(Processes::class),
                $this->createMock(Halt::class),
                $this->createMock(Period::class),
            ),
        );
    }

    public function testInvokation()
    {
        $watch = new Stat(
            $processes = $this->createMock(Processes::class),
            $this->createMock(Halt::class),
            $this->createMock(Period::class),
        );

        $ping = $watch(Path::of('/path/to/some/file'));

        $this->assertInstanceOf(OutputDiff::class, $ping);
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "find '/path/to/some/file' '-type' 'f' | 'xargs' 'stat' '-f' '%Sm %N' '-t' '%Y-%m-%dT%H-%M-%S'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->exactly(2))
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process
            ->expects($this->exactly(2))
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->exactly(2))
            ->method('toString')
            ->will($this->onConsecutiveCalls('foo', 'bar'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('to end test');

        $ping(static function() {
            throw new \Exception('to end test');
        });
    }
}
