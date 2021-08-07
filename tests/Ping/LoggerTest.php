<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping\Logger,
    Ping,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Fixtures\Innmind\Url\Path as FPath;

class LoggerTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Ping::class,
            new Logger(
                $this->createMock(Ping::class),
                Path::none(),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testPing()
    {
        $this
            ->forAll(FPath::any())
            ->then(function($path) {
                $inner = $this->createMock(Ping::class);
                $inner
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($this->callback(static function($ping) {
                        $ping();
                        $ping();

                        return true;
                    }))
                    ->willReturn($expected = Either::right(new SideEffect));
                $logger = $this->createMock(LoggerInterface::class);
                $logger
                    ->expects($this->exactly(3))
                    ->method('info')
                    ->withConsecutive(
                        [
                            'Starting to watch {path}',
                            ['path' => $path->toString()],
                        ],
                        [
                            'Content at {path} changed',
                            ['path' => $path->toString()],
                        ],
                        [
                            'Content at {path} changed',
                            ['path' => $path->toString()],
                        ],
                    );
                $ping = new Logger($inner, $path, $logger);
                $count = 0;

                $this->assertSame($expected, $ping(static function() use (&$count) {
                    $count++;
                }));
                $this->assertSame(2, $count);
            });
    }
}
