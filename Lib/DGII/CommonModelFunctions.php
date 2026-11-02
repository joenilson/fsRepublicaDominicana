<?php
/*
 * Copyright (C) 2025 Joe Nilson <joenilson at gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

class CommonModelFunctions
{
    public static function setCFRango($actualBusinessContact, $ncfrango, $tipocomprobante, &$document): bool
    {
        $ncfRangoToUse = $ncfrango->getByTipoComprobante($actualBusinessContact->idempresa, $tipocomprobante);
        if (!$ncfRangoToUse) {
            Tools::log()->error("no-ncf-range-for-$tipocomprobante");
            return false;
        }
        $ncf = $ncfRangoToUse->generateNCF();
        $document->numeroncf = $ncf;
        $document->ncffechavencimiento = $ncfRangoToUse->fechavencimiento;
        $document->tipocomprobante = $ncfRangoToUse->tipocomprobante;
        $ncfRangoToUse->correlativo++;
        return $ncfRangoToUse->save();
    }

    /**
     * Valida una nota de crédito (04 o 34) antes de guardarla: debe restar y, junto con las demás notas vigentes
     * de la misma factura, no puede superar el total de la factura que modifica.
     *
     * @param \FacturaScripts\Core\Model\Base\SalesDocument|\FacturaScripts\Core\Model\Base\PurchaseDocument $document
     */
    public static function validateCreditNote($document): bool
    {
        if (!in_array($document->tipocomprobante, NotaCreditoValidator::TIPOS, true)) {
            return true;
        }

        $originalTotal = null;
        $otherCredits = 0.0;
        if (!empty($document->idfacturarect)) {
            $class = get_class($document);
            $original = new $class();
            if ($original->load($document->idfacturarect)) {
                $originalTotal = (float)$original->total;
                $where = [Where::eq('idfacturarect', $document->idfacturarect)];
                if (!empty($document->idfactura)) {
                    $where[] = Where::notEq('idfactura', $document->idfactura);
                }
                foreach ($original->all($where, [], 0, 0) as $other) {
                    $state = new EstadoDocumento();
                    if ($state->load($other->idestado) && $state->nombre === 'Anulada') {
                        continue;
                    }
                    if (in_array($other->tipocomprobante, NotaCreditoValidator::TIPOS, true)) {
                        $otherCredits += abs((float)$other->total);
                    }
                }
            }
        }

        $error = NotaCreditoValidator::validate(
            $document->tipocomprobante,
            (float)$document->neto,
            (float)$document->total,
            $originalTotal,
            $otherCredits
        );
        if ($error === null) {
            return true;
        }

        Tools::log()->error($error, [
            '%total%' => Tools::number(abs((float)$document->total)),
            '%original%' => Tools::number((float)$originalTotal),
            '%credited%' => Tools::number($otherCredits),
        ]);
        return false;
    }
}
