<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping\ProcessOutput,
    Ping,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process,
    Process\Output,
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

        $called = false;
        $this->assertNull($ping(static function() use (&$called): void {
            $called = true;
        }));
        $this->assertTrue($called);
    }
}
