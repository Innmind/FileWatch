<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch\Logger,
    Watch,
    Ping,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Fixtures\Innmind\Url\Path;

class LoggerTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Watch::class,
            new Logger(
                $this->createMock(Watch::class),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testWatch()
    {
        $this
            ->forAll(Path::any())
            ->then(function($path) {
                $inner = $this->createMock(Watch::class);
                $inner
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($path);
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects($this->never())
                    ->method('info');
                $watch = new Logger($inner, $logger);

                $this->assertInstanceOf(Ping\Logger::class, $watch($path));
            });
    }
}
