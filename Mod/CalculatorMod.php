<?php
/*
 * Copyright (C) 2023 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Mod;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Retencion;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Contract\CalculatorModInterface;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\ImpuestoAdicionalCalculator;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\RetencionCalculator;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoAdicional;

/**
 * Calcula los impuestos adicionales de cada línea y los totales del documento.
 */
class CalculatorMod implements CalculatorModInterface
{
    /** @var array<string, ImpuestoAdicional|null> impuestos ya consultados, por código */
    private static $taxes = [];

    /** @var array<int, Producto|null> productos ya consultados, por id */
    private static $products = [];

    /** @var \WeakMap|null retención del cliente o proveedor de cada documento, para consultarla una sola vez por documento */
    private static $retentions;

    public function apply(BusinessDocument &$doc, array &$lines): bool
    {
        return true;
    }

    public function calculate(BusinessDocument &$doc, array &$lines): bool
    {
        $totalExento = 0.0;
        $totalAddedTaxes = 0.0;
        foreach ($lines as $line) {
            // Los montos de los impuestos adicionales ya fueron calculados y guardados en calculateLine()
            $totalAddedTaxes += ImpuestoAdicionalCalculator::lineAmounts($line)['total'];

            // El total exento suele ser el neto de las líneas con IVA 0.
            $totalExento += ($line->codimpuesto === 'ITBIS0') ? (float)($line->pvptotal ?? 0) : 0;
        }

        $doc->totalexento = Tools::round($totalExento);
        $doc->totaladdedtaxes = Tools::round($totalAddedTaxes);

        $totalPlusTaxes = 0.0;
        foreach ($lines as $line) {
            $totalPlusTaxes += (float)($line->totalplustaxes ?? 0);
        }
        $doc->totalplustaxes = Tools::round($totalPlusTaxes);

        $this->saveWithholdingTotals($doc);

        // totaladdedtaxes y totalplustaxes se mantienen aparte del total del núcleo (neto + IVA).
        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
        $this->applyWithholding($doc, $line);

        $product = $this->loadProduct($line);

        $isc = $this->calculateTax($line->rdtaxcodisc, $line, $product);
        $cdt = $this->calculateTax($line->rdtaxcodcdt, $line, $product);
        $legalTip = $this->calculateTax($line->rdtaxcodlegaltip, $line, $product);
        $firstPlate = $this->calculateTax($line->rdtaxcodfirstplate, $line, $product);

        // La tasa guardada es informativa: porcentaje, o monto por unidad en tasas específicas.
        $line->rdtaxisc = $isc['tasa'];
        $line->rdtaxcdt = $cdt['tasa'];
        $line->rdtaxlegaltip = $legalTip['tasa'];
        $line->rdtaxfirstplate = $firstPlate['tasa'];

        // Montos calculados que quedan guardados en la línea. En compras el monto del ISC
        // puede digitarse a mano (manda lo que facturó el proveedor) y entonces no se recalcula.
        if (empty($line->rdtaxmanual)) {
            $line->rdtaxiscespecifico = Tools::round($isc['especifico']);
            $line->rdtaxiscadvalorem = Tools::round($isc['advalorem']);
        }
        $line->rdtaxcdtmonto = Tools::round($cdt['especifico'] + $cdt['advalorem']);
        $line->rdtaxlegaltipmonto = Tools::round($legalTip['especifico'] + $legalTip['advalorem']);
        $line->rdtaxfirstplatemonto = Tools::round($firstPlate['especifico'] + $firstPlate['advalorem']);

        $lineAddedTaxes = ImpuestoAdicionalCalculator::lineAmounts($line)['total'];

        // IVA real de la línea (sobre pvpsindto)
        $ivaImporte = (float)($line->pvpsindto ?? 0) * (float)($line->iva ?? 0) / 100.0;
        $line->totalplustaxes = $line->pvptotal + $ivaImporte + $lineAddedTaxes;
        return true;
    }

    /**
     * La retención de ITBIS de la DGII (30 % o 100 %) es sobre el ITBIS facturado, pero FacturaScripts aplica
     * el porcentaje de la línea sobre su neto. Si el cliente o proveedor tiene una retención de tipo ITBIS,
     * se convierte su porcentaje a porcentaje sobre el neto con el ITBIS de la línea. Las líneas con
     * retención 0 se respetan.
     */
    private function applyWithholding(BusinessDocument $doc, BusinessDocumentLine &$line): void
    {
        $retention = $this->subjectRetention($doc);
        if ($retention === null || $retention['tipo'] !== RetencionCalculator::TIPO_ITBIS) {
            return;
        }

        if (false === RetencionCalculator::shouldRecalculate((float)($line->irpf ?? 0))) {
            return;
        }

        $line->irpf = RetencionCalculator::lineIrpf(
            RetencionCalculator::TIPO_ITBIS,
            $retention['porcentaje'],
            (float)($line->iva ?? 0)
        );
    }

    /**
     * Guarda en el documento cuánto se retuvo de ITBIS y de ISR (y el tipo de retención en ISR), que es lo
     * que leen los reportes 606 y 607. Solo las facturas tienen esas columnas.
     */
    private function saveWithholdingTotals(BusinessDocument &$doc): void
    {
        if (false === $doc->hasColumn('rdtotalretitbis')) {
            return;
        }

        $retention = $this->subjectRetention($doc);
        $totals = RetencionCalculator::documentTotals(
            (float)($doc->totalirpf ?? 0),
            $retention['tipo'] ?? null,
            $retention['tipoisr'] ?? null
        );

        $doc->rdtotalretitbis = Tools::round($totals['itbis']);
        $doc->rdtotalretisr = Tools::round($totals['isr']);
        $doc->rdtiporetisr = $totals['tipoisr'];
    }

    /**
     * Retención predeterminada del cliente (ventas) o del proveedor (compras) del documento.
     *
     * @return array{tipo: string, porcentaje: float, tipoisr: string|null}|null
     */
    private function subjectRetention(BusinessDocument $doc): ?array
    {
        self::$retentions = self::$retentions ?? new \WeakMap();
        if (isset(self::$retentions[$doc])) {
            return self::$retentions[$doc]['value'];
        }

        $value = null;
        if (!empty($doc->codcliente)) {
            $subject = new Cliente();
            $code = $doc->codcliente;
        } elseif (!empty($doc->codproveedor)) {
            $subject = new Proveedor();
            $code = $doc->codproveedor;
        }

        if (isset($subject) && $subject->load($code) && !empty($subject->codretencion)) {
            $retention = new Retencion();
            if ($retention->load($subject->codretencion)) {
                $value = [
                    'tipo' => (string)($retention->rdtipo ?: RetencionCalculator::TIPO_ISR),
                    'porcentaje' => (float)$retention->porcentaje,
                    'tipoisr' => $retention->rdtipoisr ?: null,
                ];
            }
        }

        self::$retentions[$doc] = ['value' => $value];
        return $value;
    }

    public function clear(BusinessDocument &$doc, array &$lines): bool
    {
        foreach ($lines as $line) {
            $line->rdtaxisc = 0.0;
            $line->rdtaxcdt = 0.0;
            $line->rdtaxlegaltip = 0.0;
            $line->rdtaxfirstplate = 0.0;
            foreach (ImpuestoAdicionalCalculator::AMOUNT_FIELDS as $field) {
                // el monto digitado a mano del ISC se conserva
                if (!empty($line->rdtaxmanual) && in_array($field, ['rdtaxiscespecifico', 'rdtaxiscadvalorem'], true)) {
                    continue;
                }
                $line->{$field} = 0.0;
            }
            $line->totaladdedtaxes = 0.0;
        }
        return true;
    }

    /**
     * @return array{tasa: float, especifico: float, advalorem: float}
     */
    public function calculateTax(?string $taxCode, BusinessDocumentLine $line, ?Producto $product): array
    {
        $empty = ['tasa' => 0.0, 'especifico' => 0.0, 'advalorem' => 0.0];
        if (empty($taxCode)) {
            return $empty;
        }

        if (!array_key_exists($taxCode, self::$taxes)) {
            self::$taxes[$taxCode] = ImpuestoAdicional::findWhereEq('codigo', $taxCode);
        }
        $taxInfo = self::$taxes[$taxCode];
        if (null === $taxInfo) {
            return $empty;
        }

        $amounts = ImpuestoAdicionalCalculator::calculate(
            (string)$taxInfo->tipo_tasa,
            (string)($taxInfo->base_calculo ?? ''),
            (float)$taxInfo->tasa,
            (float)($taxInfo->tasa_advalorem ?? 0),
            (bool)($taxInfo->advalorem_sobre_especifico ?? false),
            (float)($line->pvpsindto ?? 0),
            (float)($line->cantidad ?? 0),
            (float)($product->grados_alcohol ?? 0),
            (float)($product->contenido_litros ?? 0)
        );
        return ['tasa' => (float)$taxInfo->tasa] + $amounts;
    }

    private function loadProduct(BusinessDocumentLine $line): ?Producto
    {
        if (empty($line->idproducto)) {
            return null;
        }
        if (!array_key_exists($line->idproducto, self::$products)) {
            $product = new Producto();
            self::$products[$line->idproducto] = $product->load($line->idproducto) ? $product : null;
        }

        return self::$products[$line->idproducto];
    }

    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        return true;
    }
}