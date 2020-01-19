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
use Innmind\TimeContinuum\{
    TimeContinuumInterface,
    PeriodInterface,
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
                $this->createMock(TimeContinuumInterface::class),
                $this->createMock(PeriodInterface::class)
            )
        );
    }

    public function testInvokation()
    {
        $watch = new Stat(
            $processes = $this->createMock(Processes::class),
            $this->createMock(Halt::class),
            $this->createMock(TimeContinuumInterface::class),
            $this->createMock(PeriodInterface::class)
        );

        $ping = $watch(new Path('/path/to/some/file'));

        $this->assertInstanceOf(OutputDiff::class, $ping);
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "find '/path/to/some/file' '-type' 'f' | 'xargs' 'stat' '-f' '%Sm %N' '-t' '%Y-%m-%dT%H-%M-%S'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->exactly(2))
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $process
            ->expects($this->exactly(2))
            ->method('wait')
            ->will($this->returnSelf());
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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('to end test');

        $ping(function(){
            throw new \Exception('to end test');
        });
    }
}
