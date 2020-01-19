<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping\ProcessOutput,
    Ping,
    Exception\WatchFailed,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Signal,
    Process,
    Process\Output,
    Process\ExitCode,
    Process\Pid,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};
use PHPUnit\Framework\TestCase;

class ProcessOutputTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Ping::class,
            new ProcessOutput(
                $this->createMock(Processes::class),
                Command::foreground('watev')
            )
        );
    }

    public function testInvokation()
    {
        $ping = new ProcessOutput(
            $processes = $this->createMock(Processes::class),
            $command = Command::foreground('watch')
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('foreach')
            ->with($this->callback(static function($callable): bool {
                $callable(); //simulate one output

                return true;
            }));
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $called = false;
        $this->assertNull($ping(static function() use (&$called): void {
            $called = true;
        }));
        $this->assertTrue($called);
    }

    public function testThrowWhenProcessFails()
    {
        $ping = new ProcessOutput(
            $processes = $this->createMock(Processes::class),
            $command = Command::foreground('watch')
        );
        $processes
            ->expects($this->never())
            ->method('kill');
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('foreach')
            ->with($this->callback(static function($callable): bool {
                $callable(); //simulate one output

                return true;
            }));
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(1));

        $this->expectException(WatchFailed::class);
        $this->expectExceptionMessage($command->toString());

        $ping(static function(){});
    }

    public function testKillProcessWhenPingThrowsAnException()
    {
        $ping = new ProcessOutput(
            $processes = $this->createMock(Processes::class),
            $command = Command::foreground('watch')
        );
        $process = new class implements Process {
            public function pid(): Pid
            {
                return new Pid(42);
            }

            public function output(): Output
            {
                return new Output\Output(
                    Sequence::of(
                        'array',
                        [Str::of(''), Output\Type::output()], // simulate one output
                    ),
                );
            }

            public function exitCode(): ExitCode
            {}
            public function wait(): void {}
            public function isRunning(): bool
            {
                return true;
            }
        };

        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($process);
        $processes
            ->expects($this->once())
            ->method('kill')
            ->with($process->pid(), Signal::terminate());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('watev');

        $ping(static function() {
            throw new \Exception('watev');
        });
    }

    public function testDoesntTryToKillProcessWhenPingThrowsAnExceptionButProcessAlreadyFinished()
    {
        $ping = new ProcessOutput(
            $processes = $this->createMock(Processes::class),
            $command = Command::foreground('watch')
        );
        $process = new class implements Process {
            public function pid(): Pid
            {
                return new Pid(42);
            }

            public function output(): Output
            {
                return new Output\Output(
                    Sequence::of(
                        'array',
                        [Str::of(''), Output\Type::output()], // simulate one output
                    ),
                );
            }

            public function exitCode(): ExitCode
            {}
            public function wait(): void {}
            public function isRunning(): bool
            {
                return false;
            }
        };

        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($process);
        $processes
            ->expects($this->never())
            ->method('kill');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('watev');

        $ping(static function() {
            throw new \Exception('watev');
        });
    }
}
