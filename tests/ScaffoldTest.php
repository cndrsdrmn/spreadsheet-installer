<?php

declare(strict_types=1);

namespace Tests;

use Cndrsdrmn\PhpScaffold\Scaffold;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ScaffoldTest extends TestCase
{
    #[Test]
    public function it_should_run(): void
    {
        $this->assertSame(0, (new Scaffold)->run());
    }
}
