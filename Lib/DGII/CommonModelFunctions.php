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
     * Valida una nota de crédito (04, 34) o de débito (03, 33) antes de guardarla. Ver NotaCreditoValidator.
     *
     * @param \FacturaScripts\Core\Model\Base\SalesDocument|\FacturaScripts\Core\Model\Base\PurchaseDocument $document
     */
    public static function validateNote($document): bool
    {
        $partyField = isset($document->codcliente) ? 'codcliente' : 'codproveedor';
        $original = null;
        $credited = ['neto' => 0.0, 'total' => 0.0];
        if (!empty($document->idfacturarect)) {
            $class = get_class($document);
            $invoice = new $class();
            if ($invoice->load($document->idfacturarect)) {
                $original = [
                    'neto' => (float)$invoice->neto,
                    'total' => (float)$invoice->total,
                    'party' => (string)$invoice->{$partyField},
                    'company' => (int)$invoice->idempresa,
                    'currency' => (string)$invoice->coddivisa,
                    'cancelled' => self::isCancelled($invoice),
                ];
                $where = [Where::eq('idfacturarect', $document->idfacturarect)];
                if (!empty($document->idfactura)) {
                    $where[] = Where::notEq('idfactura', $document->idfactura);
                }
                // Todo documento vigente con importe negativo que rectifica la factura cuenta como acreditado
                foreach ($invoice->all($where, [], 0, 0) as $other) {
                    if ((float)$other->total < 0 && !self::isCancelled($other)) {
                        $credited['total'] += abs((float)$other->total);
                        $credited['neto'] += abs((float)$other->neto);
                    }
                }
            }
        }

        $error = NotaCreditoValidator::validate(
            $document->tipocomprobante,
            (float)$document->neto,
            (float)$document->total,
            $original,
            [
                'party' => (string)$document->{$partyField},
                'company' => (int)$document->idempresa,
                'currency' => (string)$document->coddivisa,
            ],
            $credited
        );
        if ($error === null) {
            return true;
        }

        Tools::log()->error($error, [
            '%total%' => Tools::number(abs((float)$document->total)),
            '%original%' => Tools::number((float)($original['total'] ?? 0)),
            '%credited%' => Tools::number($credited['total']),
        ]);
        return false;
    }

    private static function isCancelled($document): bool
    {
        $state = new EstadoDocumento();
        return $state->load($document->idestado) && $state->nombre === 'Anulada';
    }
}
