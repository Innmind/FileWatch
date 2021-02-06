<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch\Watch;

use Innmind\FileWatch\{
    Watch\Fallback,
    Watch,
    Ping,
    Exception\WatchFailed,
};
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class FallbackTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Watch::class,
            new Fallback(
                $this->createMock(Watch::class),
                $this->createMock(Watch::class)
            )
        );
    }

    public function testInvokation()
    {
        $watch = new Fallback(
            $attempt = $this->createMock(Watch::class),
            $fallback = $this->createMock(Watch::class)
        );
        $file = Path::none();
        $attempt
            ->expects($this->once())
            ->method('__invoke')
            ->with($file)
            ->willReturn($attemptPing = $this->createMock(Ping::class));
        $attemptPing
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->throwException(new WatchFailed));
        $fallback
            ->expects($this->once())
            ->method('__invoke')
            ->with($file)
            ->willReturn($fallbackPing = $this->createMock(Ping::class));
        $fallbackPing
            ->expects($this->once())
            ->method('__invoke');

        $ping = $watch($file);

        $this->assertInstanceOf(Ping\Fallback::class, $ping);
        $this->assertNull($ping(static function() {}));
    }
}
