<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\NotaCreditoValidator as V;
use PHPUnit\Framework\TestCase;

final class NotaCreditoValidatorTest extends TestCase
{
    public function testNotaDeCreditoNegativaEsValida(): void
    {
        $this->assertNull(V::validate('04', -500, -590, 2360));
        $this->assertNull(V::validate('34', -500, -590, 2360));
    }

    public function testNotaDeCreditoPositivaSeRechaza(): void
    {
        $this->assertSame(V::POSITIVE_AMOUNT, V::validate('04', 500, 590, 2360));
        $this->assertSame(V::POSITIVE_AMOUNT, V::validate('34', 500, 590));
    }

    public function testNotaSinImporteTodaviaEsValida(): void
    {
        // El encabezado se guarda antes de agregar las líneas
        $this->assertNull(V::validate('04', 0, 0, 2360));
    }

    public function testNotaMayorQueLaFacturaSeRechaza(): void
    {
        $this->assertSame(V::EXCEEDS_INVOICE, V::validate('04', -50000, -59000, 2360));
    }

    public function testNotaPorElTotalExactoEsValida(): void
    {
        $this->assertNull(V::validate('04', -2000, -2360, 2360));
    }

    public function testSeSumanLasDemasNotasDeLaMismaFactura(): void
    {
        // 2360 de factura, 1770 ya acreditados: 590 cabe y 600 no
        $this->assertNull(V::validate('04', -500, -590, 2360, 1770));
        $this->assertSame(V::EXCEEDS_INVOICE, V::validate('04', -508.47, -600, 2360, 1770));
    }

    public function testSinFacturaOriginalNoSeComparaElTotal(): void
    {
        $this->assertNull(V::validate('04', -50000, -59000, null));
    }

    public function testOtrosTiposNoSeValidan(): void
    {
        $this->assertNull(V::validate('01', 500, 590));
        $this->assertNull(V::validate('03', 500, 590, 100));
        $this->assertNull(V::validate(null, 500, 590));
    }
}
