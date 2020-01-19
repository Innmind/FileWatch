<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch;

use function Innmind\FileWatch\bootstrap;
use Innmind\FileWatch\Watch;
use Innmind\Server\Control\Server\Processes;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\TimeContinuumInterface;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testInvokation()
    {
        $watch = bootstrap(
            $this->createMock(Processes::class),
            $this->createMock(Halt::class),
            $this->createMock(TimeContinuumInterface::class)
        );

        $this->assertInstanceOf(Watch::class, $watch);
    }
}
