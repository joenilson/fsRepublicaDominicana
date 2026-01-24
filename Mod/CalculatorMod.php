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
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Tools;

use FacturaScripts\Core\Contract\CalculatorModInterface;
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
            // Calculamos el importe de los impuestos adicionales basándonos en pvpsindto
            $lineAddedTaxes = (float)($line->pvpsindto ?? 0) * ((float)($line->rdtaxisc ?? 0) + (float)($line->rdtaxcdt ?? 0) + (float)($line->rdtaxlegaltip ?? 0) + (float)($line->rdtaxfirstplate ?? 0)) / 100.0;
            $totalAddedTaxes += $lineAddedTaxes;

            // El total exento suele ser el neto de las líneas con IVA 0.
            // Como pvptotal ya incluye los impuestos adicionales, el totalExento también.
            $totalExento += ($line->codimpuesto === 'ITBIS0') ? (float)($line->pvptotal ?? 0) : 0;
        }

        $doc->totalexento = Tools::round($totalExento);
        $doc->totaladdedtaxes = Tools::round($totalAddedTaxes);

        $totalPlusTaxes = 0.0;
        foreach ($lines as $line) {
            $totalPlusTaxes += (float)($line->totalplustaxes ?? 0);
        }
        $doc->totalplustaxes = Tools::round($totalPlusTaxes);

        // Ya no sumamos totaladdedtaxes al total aquí, porque al haberse sumado al
        // pvptotal de cada línea en calculateLine(), el núcleo ya lo ha incluido
        // en el neto y en el total del documento.
        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
        // Cargamos las tasas de los impuestos adicionales
        $taxes = new ImpuestoAdicional();
        $line->rdtaxisc = $this->calculateTaxes($line->rdtaxcodisc, $taxes::findWhereEq('codigo', $line->rdtaxcodisc), $line);
        $line->rdtaxcdt = $this->calculateTaxes($line->rdtaxcodcdt, $taxes::findWhereEq('codigo', $line->rdtaxcodcdt), $line);
        $line->rdtaxlegaltip = $this->calculateTaxes($line->rdtaxcodlegaltip, $taxes::findWhereEq('codigo', $line->rdtaxcodlegaltip), $line);
        $line->rdtaxfirstplate = $this->calculateTaxes($line->rdtaxcodfirstplate, $taxes::findWhereEq('codigo', $line->rdtaxcodfirstplate), $line);

        // Sumamos estos impuestos al pvptotal de la línea para que el total del documento
        // cuadre con la suma de las líneas y no genere error de validación.
        $lineAddedTaxes = (float)($line->pvpsindto ?? 0) * ((float)($line->rdtaxisc ?? 0) + (float)($line->rdtaxcdt ?? 0) + (float)($line->rdtaxlegaltip ?? 0) + (float)($line->rdtaxfirstplate ?? 0)) / 100.0;

        // Calculamos el total de la línea incluyendo el IVA real (sobre pvpsindto)
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
            $line->totaladdedtaxes = 0.0;
        }
        return true;
    }

    public function calculateTaxes($taxCode, ImpuestoAdicional|null $taxInfo, &$line): float
    {
        return (null !== $taxInfo) ? $taxInfo->tasa : 0;
    }

    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        // TODO: Implement getSubtotals() method.
        return true;
    }
}