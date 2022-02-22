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
    Process\Output\Output,
};
use Innmind\Immutable\{
    Either,
    SideEffect,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class TailfTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Watch::class,
            new Tailf($this->createMock(Processes::class)),
        );
    }

    public function testInvokation()
    {
        $watch = new Tailf(
            $processes = $this->createMock(Processes::class),
        );

        $ping = $watch(Path::of('/path/to/some/file'));

        $this->assertInstanceOf(ProcessOutput::class, $ping);
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "[ -f /path/to/some/file ] && tail '-f' '/path/to/some/file'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process
            ->method('output')
            ->willReturn(new Output(Sequence::of()));

        $ping(static function() {});
    }
}
