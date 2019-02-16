<?php
declare(strict_types = 1);

namespace Tests\Innmind\FileWatch;

use function Innmind\FileWatch\bootstrap;
use Innmind\FileWatch\Watch;
use Innmind\OperatingSystem\OperatingSystem;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testInvokation()
    {
        $watch = bootstrap(
            $this->createMock(OperatingSystem::class)
        );

        $this->assertInstanceOf(Watch::class, $watch);
    }
}
