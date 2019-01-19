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
    TimeContinuumInterface,
    PeriodInterface,
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
                $this->createMock(TimeContinuumInterface::class),
                $this->createMock(PeriodInterface::class)
            )
        );
    }

    public function testInvokation()
    {
        $ping = new OutputDiff(
            $processes = $this->createMock(Processes::class),
            $command = Command::foreground('watev'),
            $halt = $this->createMock(Halt::class),
            $clock = $this->createMock(TimeContinuumInterface::class),
            $period = $this->createMock(PeriodInterface::class)
        );
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->with($command)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->exactly(2))
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->exactly(2))
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->exactly(2))
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->at(0))
            ->method('__toString')
            ->willReturn('foo');
        $output
            ->expects($this->at(1))
            ->method('__toString')
            ->willReturn('bar');
        $halt
            ->expects($this->once())
            ->method('__invoke')
            ->with($clock, $period);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('to end test');

        $ping(static function() {
            throw new \Exception('to end test');
        });
    }

    public function testThrowWhenTheProcessFails()
    {
        $ping = new OutputDiff(
            $processes = $this->createMock(Processes::class),
            $command = Command::foreground('watev'),
            $halt = $this->createMock(Halt::class),
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(PeriodInterface::class)
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

        $ping(static function(){});
    }
}
