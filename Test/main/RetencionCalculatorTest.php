<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\RetencionCalculator as Calc;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\RetencionesDGII;
use PHPUnit\Framework\TestCase;

final class RetencionCalculatorTest extends TestCase
{
    public function testItbis30PorCientoDelItbisEsPorcentajeSobreElNeto(): void
    {
        // 30 % del ITBIS del 18 % = 5.4 % del neto
        $this->assertEqualsWithDelta(5.4, Calc::lineIrpf('ITBIS', 30, 18), 0.000001);
        $this->assertEqualsWithDelta(540.0, Calc::lineAmount('ITBIS', 30, 18, 10000), 0.0001);
    }

    public function testItbis100PorCientoRetieneTodoElItbis(): void
    {
        $this->assertEqualsWithDelta(18.0, Calc::lineIrpf('ITBIS', 100, 18), 0.000001);
        $this->assertEqualsWithDelta(1800.0, Calc::lineAmount('ITBIS', 100, 18, 10000), 0.0001);
    }

    public function testItbisRespetaLaTasaDeItbisDeLaLinea(): void
    {
        $this->assertEqualsWithDelta(4.8, Calc::lineIrpf('ITBIS', 30, 16), 0.000001);
        $this->assertEquals(0.0, Calc::lineIrpf('ITBIS', 30, 0));
    }

    public function testIsrEsPorcentajeSobreElNeto(): void
    {
        $this->assertEquals(10.0, Calc::lineIrpf('ISR', 10, 18));
        $this->assertEqualsWithDelta(1000.0, Calc::lineAmount('ISR', 10, 18, 10000), 0.0001);
    }

    public function testLineaConRetencionCeroSeRespeta(): void
    {
        $this->assertFalse(Calc::shouldRecalculate(0.0));
        $this->assertTrue(Calc::shouldRecalculate(30.0));
    }

    public function testTotalesDelDocumentoSegunElTipo(): void
    {
        $this->assertEquals(['itbis' => 540.0, 'isr' => 0.0, 'tipoisr' => null], Calc::documentTotals(540.0, 'ITBIS', '3'));
        $this->assertEquals(['itbis' => 0.0, 'isr' => 1000.0, 'tipoisr' => '2'], Calc::documentTotals(1000.0, 'ISR', '2'));
        $this->assertEquals(['itbis' => 0.0, 'isr' => 0.0, 'tipoisr' => null], Calc::documentTotals(0.0, 'ISR', '2'));
    }

    public function testTipoDeRetencionEnIsrInvalidoSeIgnora(): void
    {
        $this->assertNull(Calc::documentTotals(100.0, 'ISR', '99')['tipoisr']);
        $this->assertNull(Calc::documentTotals(100.0, 'ISR', null)['tipoisr']);
    }

    public function testCatalogoDeRetencionesIsrUsaCodigosValidosDelFormato606(): void
    {
        foreach (RetencionesDGII::isr() as [$codigo, $descripcion, $porcentaje, $tipoIsr]) {
            $this->assertArrayHasKey($tipoIsr, Calc::TIPOS_ISR, $codigo);
            $this->assertLessThanOrEqual(10, strlen($codigo));
            $this->assertLessThanOrEqual(50, strlen($descripcion));
            $this->assertGreaterThan(0, $porcentaje);
        }
    }
}
