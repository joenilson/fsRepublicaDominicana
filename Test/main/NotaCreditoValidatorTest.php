<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\NotaCreditoValidator as V;
use PHPUnit\Framework\TestCase;

final class NotaCreditoValidatorTest extends TestCase
{
    private const NOTE = ['party' => 'C1', 'company' => 1, 'currency' => 'DOP'];

    private static function original(array $override = []): array
    {
        return array_merge(
            ['neto' => 2000.0, 'total' => 2360.0, 'party' => 'C1', 'company' => 1, 'currency' => 'DOP', 'cancelled' => false],
            $override
        );
    }

    public function testNotaDeCreditoNegativaEsValida(): void
    {
        $this->assertNull(V::validate('04', -500, -590, self::original(), self::NOTE));
        $this->assertNull(V::validate('34', -500, -590, self::original(), self::NOTE));
    }

    public function testNotaDeCreditoPositivaSeRechaza(): void
    {
        $this->assertSame(V::POSITIVE_AMOUNT, V::validate('04', 500, 590, self::original(), self::NOTE));
        $this->assertSame(V::POSITIVE_AMOUNT, V::validate('34', 500, 590));
    }

    public function testNotaSinImporteTodaviaEsValida(): void
    {
        // El encabezado se guarda antes de agregar las líneas
        $this->assertNull(V::validate('04', 0, 0, self::original(), self::NOTE));
        $this->assertNull(V::validate('03', 0, 0, self::original(), self::NOTE));
    }

    public function testNotaMayorQueLaFacturaSeRechaza(): void
    {
        $this->assertSame(V::EXCEEDS_INVOICE, V::validate('04', -50000, -59000, self::original(), self::NOTE));
    }

    public function testNotaPorElTotalExactoEsValida(): void
    {
        $this->assertNull(V::validate('04', -2000, -2360, self::original(), self::NOTE));
    }

    public function testSeSumanLasDemasNotasDeLaMismaFactura(): void
    {
        $credited = ['neto' => 1500.0, 'total' => 1770.0];
        $this->assertNull(V::validate('04', -500, -590, self::original(), self::NOTE, $credited));
        $this->assertSame(V::EXCEEDS_INVOICE, V::validate('04', -508.47, -600, self::original(), self::NOTE, $credited));
    }

    public function testSinFacturaOriginalNoSeComparaElTotal(): void
    {
        $this->assertNull(V::validate('04', -50000, -59000, null));
    }

    public function testNotaDeDebitoDebeSumar(): void
    {
        $this->assertNull(V::validate('03', 300, 354, self::original(), self::NOTE));
        $this->assertNull(V::validate('33', 300, 354, self::original(), self::NOTE));
        $this->assertSame(V::NEGATIVE_DEBIT, V::validate('03', -300, -354, self::original(), self::NOTE));
        $this->assertSame(V::NEGATIVE_DEBIT, V::validate('33', -300, -354));
    }

    public function testLaNotaDeDebitoNoTieneTopeContraLaFactura(): void
    {
        $this->assertNull(V::validate('03', 50000, 59000, self::original(), self::NOTE));
    }

    public function testLaFacturaOriginalDebeSerVigenteYDelMismoTercero(): void
    {
        $this->assertSame(V::ORIGINAL_INVALID, V::validate('04', -500, -590, self::original(['cancelled' => true]), self::NOTE));
        $this->assertSame(V::ORIGINAL_INVALID, V::validate('04', -500, -590, self::original(['party' => 'C2']), self::NOTE));
        $this->assertSame(V::ORIGINAL_INVALID, V::validate('04', -500, -590, self::original(['company' => 2]), self::NOTE));
        $this->assertSame(V::ORIGINAL_INVALID, V::validate('04', -500, -590, self::original(['currency' => 'USD']), self::NOTE));
        $this->assertSame(V::ORIGINAL_INVALID, V::validate('03', 300, 354, self::original(['currency' => 'USD']), self::NOTE));
    }

    public function testUnDocumentoNegativoQueRectificaDebeSerNotaDeCredito(): void
    {
        $this->assertSame(V::NEEDS_CREDIT_NOTE, V::validate('01', -500, -590, self::original(), self::NOTE));
        $this->assertSame(V::NEEDS_CREDIT_NOTE, V::validate(null, -500, -590, self::original(), self::NOTE));
        // sin factura original una factura normal no se valida
        $this->assertNull(V::validate('01', 500, 590));
    }
}
