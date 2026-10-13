<?php

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\ImpuestoAdicionalCalculator as Calc;
use PHPUnit\Framework\TestCase;

final class ImpuestoAdicionalCalculatorTest extends TestCase
{
    public function testPorcentaje(): void
    {
        $r = Calc::calculate('porcentaje', 'unidad', 10, 0, false, 2500, 10);
        $this->assertEquals(0.0, $r['especifico']);
        $this->assertEquals(250.0, $r['advalorem']);
    }

    public function testMontoPorUnidadMasAdvalorem(): void
    {
        // 10 cajetillas a RD$53.51 + 20% sobre RD$1000
        $r = Calc::calculate('monto', 'unidad', 53.51, 20, false, 1000, 10);
        $this->assertEqualsWithDelta(535.1, $r['especifico'], 0.0001);
        $this->assertEqualsWithDelta(200.0, $r['advalorem'], 0.0001);
    }

    public function testMontoLitrosAlcoholAbsoluto(): void
    {
        // 12 botellas de 0.75 L al 40% = 3.6 L de alcohol absoluto
        $r = Calc::calculate('monto', 'litros_alcohol_absoluto', 632.58, 10, false, 6000, 12, 40, 0.75);
        $this->assertEqualsWithDelta(3.6 * 632.58, $r['especifico'], 0.0001);
        $this->assertEqualsWithDelta(600.0, $r['advalorem'], 0.0001);
    }

    public function testAdvaloremSobreEspecifico(): void
    {
        $r = Calc::calculate('monto', 'unidad', 100, 10, true, 1000, 2);
        $this->assertEquals(200.0, $r['especifico']);
        $this->assertEqualsWithDelta(120.0, $r['advalorem'], 0.0001);
    }

    public function testLineAmountsLeeLosMontosGuardados(): void
    {
        $linea = (object)['rdtaxiscespecifico' => 5, 'rdtaxiscadvalorem' => 3, 'rdtaxcdtmonto' => 2];
        $this->assertEquals(10.0, Calc::lineAmounts($linea)['total']);
    }

    public function testMontoCeroConTasaEspecificaNoSeRecalculaComoPorcentaje(): void
    {
        // producto sin grados de alcohol: el monto calculado es 0 aunque la tasa sea 632.58
        $linea = (object)['pvpsindto' => 1000, 'rdtaxisc' => 632.58, 'rdtaxiscespecifico' => 0, 'rdtaxiscadvalorem' => 0];
        $this->assertEquals(0.0, Calc::lineAmounts($linea)['total']);
    }
}
