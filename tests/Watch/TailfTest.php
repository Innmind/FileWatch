<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch\Tailf,
    Watch,
    Ping\ProcessOutput,
};
use Innmind\Url\Path;
use Innmind\Server\Control\Server\{
    Processes,
    Process,
    Process\ExitCode,
};
use PHPUnit\Framework\TestCase;

class TailfTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Watch::class,
            new Tailf($this->createMock(Processes::class))
        );
    }

    public function testInvokation()
    {
        $watch = new Tailf(
            $processes = $this->createMock(Processes::class)
        );

        $ping = $watch(new Path('/path/to/some/file'));

        $this->assertInstanceOf(ProcessOutput::class, $ping);
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "[ -f /path/to/some/file ] && tail '-f' '/path/to/some/file'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $ping(function(){});
    }
}
