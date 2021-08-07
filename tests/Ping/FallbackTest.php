<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping\Fallback,
    Ping,
    Exception\WatchFailed,
};
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class FallbackTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Ping::class,
            new Fallback(
                $this->createMock(Ping::class),
                $this->createMock(Ping::class)
            )
        );
    }

    public function testOnlyCallFirstStrategyByDefault()
    {
        $ping = new Fallback(
            $attempt = $this->createMock(Ping::class),
            $fallback = $this->createMock(Ping::class)
        );
        $attempt
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn($expected = Either::right(new SideEffect));
        $fallback
            ->expects($this->never())
            ->method('__invoke');

        $this->assertEquals($expected, $ping(static function() {}));
    }

    public function testUseFallbackWhenFirstStrategyFails()
    {
        $ping = new Fallback(
            $attempt = $this->createMock(Ping::class),
            $fallback = $this->createMock(Ping::class)
        );
        $attempt
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Either::left(new WatchFailed));
        $fallback
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn($expected = Either::right(new SideEffect));

        $this->assertSame($expected, $ping(static function() {}));
    }

    public function testReturnFallbackError()
    {
        $ping = new Fallback(
            $attempt = $this->createMock(Ping::class),
            $fallback = $this->createMock(Ping::class)
        );
        $attempt
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Either::left(new WatchFailed));
        $fallback
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn($expected = Either::left(new WatchFailed));

        $this->assertSame($expected, $ping(static function() {}));
    }
}
