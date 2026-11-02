<?php
/*
 * Copyright (C) 2026 Joe Nilson <joenilson at gmail.com>
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

/**
 * Reglas de las notas de crédito (tipos 04 y 34): deben restar y no pueden superar la factura que modifican.
 * Lógica pura, sin acceso a datos; CommonModelFunctions::validateCreditNote() la alimenta con el documento.
 */
class NotaCreditoValidator
{
    public const TIPOS = ['04', '34'];
    public const POSITIVE_AMOUNT = 'credit-note-positive-amount';
    public const EXCEEDS_INVOICE = 'credit-note-exceeds-invoice';

    /**
     * @param string|null $tipocomprobante tipo del documento
     * @param float $neto neto del documento (una nota de crédito lo tiene negativo)
     * @param float $total total del documento
     * @param float|null $originalTotal total de la factura que modifica, si se conoce
     * @param float $otherCredits suma (en positivo) de las demás notas de crédito vigentes de esa factura
     * @return string|null clave de traducción del error, o null si el documento es válido
     */
    public static function validate(
        ?string $tipocomprobante,
        float $neto,
        float $total,
        ?float $originalTotal = null,
        float $otherCredits = 0.0
    ): ?string {
        if (!in_array($tipocomprobante, self::TIPOS, true)) {
            return null;
        }

        if ($neto > 0.004) {
            return self::POSITIVE_AMOUNT;
        }

        if ($originalTotal !== null && abs($total) + $otherCredits > $originalTotal + 0.004) {
            return self::EXCEEDS_INVOICE;
        }

        return null;
    }
}
