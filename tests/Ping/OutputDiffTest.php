<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping\OutputDiff,
    Ping,
    Exception\WatchFailed,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process,
    Process\Output,
    Process\ExitCode,
};
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use PHPUnit\Framework\TestCase;

class OutputDiffTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Ping::class,
            new OutputDiff(
                $this->createMock(Processes::class),
                Command::foreground('watev'),
                $this->createMock(Halt::class),
                $this->createMock(Clock::class),
                $this->createMock(Period::class)
            )
        );
    }

    public function testInvokation()
    {
        $ping = new OutputDiff(
            $processes = $this->createMock(Processes::class),
            $command = Command::foreground('watev'),
            $halt = $this->createMock(Halt::class),
            $clock = $this->createMock(Clock::class),
            $period = $this->createMock(Period::class)
        );
        $process1 = $this->createMock(Process::class);
        $process1
            ->expects($this->once())
            ->method('wait');
        $process1
            ->expects($this->any())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process1
            ->expects($this->once())
            ->method('output')
            ->willReturn($output1 = $this->createMock(Output::class));
        $output1
            ->expects($this->any())
            ->method('toString')
            ->willReturn('foo');
        $process2 = $this->createMock(Process::class);
        $process2
            ->expects($this->once())
            ->method('wait');
        $process2
            ->expects($this->any())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process2
            ->expects($this->once())
            ->method('output')
            ->willReturn($output2 = $this->createMock(Output::class));
        $output2
            ->expects($this->any())
            ->method('toString')
            ->willReturn('bar');
        $process3 = $this->createMock(Process::class);
        $process3
            ->expects($this->once())
            ->method('wait');
        $process3
            ->expects($this->any())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process3
            ->expects($this->once())
            ->method('output')
            ->willReturn($output3 = $this->createMock(Output::class));
        $output3
            ->expects($this->any())
            ->method('toString')
            ->willReturn('foo');
        $processes
            ->expects($this->exactly(3))
            ->method('execute')
            ->with($command)
            ->will($this->onConsecutiveCalls($process1, $process2, $process3));
        $halt
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->with($clock, $period);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('to end test');

        $count = 0;
        $ping(static function() use (&$count) {
            ++$count;

            if ($count >= 2) {
                throw new \Exception('to end test');
            }
        });
    }

    public function testThrowWhenTheProcessFails()
    {
        $ping = new OutputDiff(
            $processes = $this->createMock(Processes::class),
            $command = Command::foreground('watev'),
            $halt = $this->createMock(Halt::class),
            $this->createMock(Clock::class),
            $this->createMock(Period::class),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(1));
        $process
            ->expects($this->never())
            ->method('output');
        $halt
            ->expects($this->never())
            ->method('__invoke');

        $this->expectException(WatchFailed::class);
        $this->expectExceptionMessage('watev');

        $ping(static function() {});
    }
}
