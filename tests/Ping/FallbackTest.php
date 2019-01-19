<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Ping;

use Innmind\FileWatch\{
    Ping\Fallback,
    Ping,
    Exception\WatchFailed,
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
            ->method('__invoke');
        $fallback
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($ping(function(){}));
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
            ->will($this->throwException(new WatchFailed));
        $fallback
            ->expects($this->once())
            ->method('__invoke');

        $this->assertNull($ping(function(){}));
    }

    public function testDoesntCatchFallbackException()
    {
        $ping = new Fallback(
            $attempt = $this->createMock(Ping::class),
            $fallback = $this->createMock(Ping::class)
        );
        $attempt
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new WatchFailed));
        $fallback
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new WatchFailed));

        $this->expectException(WatchFailed::class);

        $ping(function(){});
    }
}
