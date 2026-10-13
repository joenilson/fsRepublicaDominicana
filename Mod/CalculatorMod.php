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
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Tools;

use FacturaScripts\Core\Contract\CalculatorModInterface;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\ImpuestoAdicionalCalculator;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoAdicional;

class CalculatorMod implements CalculatorModInterface
{

    public function apply(BusinessDocument &$doc, array &$lines): bool
    {
        //TODO: Implement apply() method.
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

        // totaladdedtaxes y totalplustaxes se mantienen aparte del total del núcleo (neto + IVA).
        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
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

        $taxInfo = ImpuestoAdicional::findWhereEq('codigo', $taxCode);
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
        $product = new Producto();
        return $product->load($line->idproducto) ? $product : null;
    }

    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        // TODO: Implement getSubtotals() method.
        return true;
    }
}