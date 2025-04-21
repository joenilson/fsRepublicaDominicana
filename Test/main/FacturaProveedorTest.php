<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class FacturaProveedorTest extends TestCase
{
    use LogErrorsTrait;

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
