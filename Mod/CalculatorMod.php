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
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Core\Base\Contract\CalculatorModInterface;

class CalculatorMod implements CalculatorModInterface
{

    public function apply(BusinessDocument &$doc, array &$lines): bool
    {
        //TODO: Implement apply() method.
        return true;
    }

    public function calculate(BusinessDocument &$doc, array &$lines): bool
    {
        // calculamos el total de impuestos
//        $totalTaxes = 0.0;
//        foreach ($lines as $line) {
//            $totalTaxes += (($line->iva + $line->rdtaxisc + $line->rdtaxcdt) * $line->pvptotal) / 100.0;
//        }
//        $doc->totaliva = round($totalTaxes, FS_NF0);
//        $doc->total = $doc->neto + $doc->totaliva;
        return true;
    }

    public function calculateLine(BusinessDocument $doc, BusinessDocumentLine &$line): bool
    {
        // TODO: Implement calculateLine() method.
        return true;
    }

    public function clear(BusinessDocument &$doc, array &$lines): bool
    {
        // TODO: Implement clear() method.
        return true;
    }

    public function getSubtotals(array &$subtotals, BusinessDocument $doc, array $lines): bool
    {
        // TODO: Implement getSubtotals() method.
        return true;
    }
}