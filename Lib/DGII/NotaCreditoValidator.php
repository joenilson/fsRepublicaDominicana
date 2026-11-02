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
 * Reglas de las notas de crédito (04 y 34) y de débito (03 y 33):
 * - la de crédito resta (importes negativos) y la de débito suma (importes positivos);
 * - la nota modifica una factura vigente del mismo cliente o proveedor, empresa y divisa;
 * - una nota de crédito, junto con las demás notas vigentes de esa factura, no puede superar su valor;
 * - un documento que rectifica una factura con importe negativo debe ser una nota de crédito.
 *
 * Lógica pura, sin acceso a datos; CommonModelFunctions::validateNote() la alimenta con el documento.
 */
class NotaCreditoValidator
{
    public const TIPOS = ['04', '34'];
    public const TIPOS_DEBITO = ['03', '33'];

    public const POSITIVE_AMOUNT = 'credit-note-positive-amount';
    public const EXCEEDS_INVOICE = 'credit-note-exceeds-invoice';
    public const NEGATIVE_DEBIT = 'debit-note-negative-amount';
    public const NEEDS_CREDIT_NOTE = 'rectifying-negative-needs-credit-note';
    public const ORIGINAL_INVALID = 'note-original-invalid';

    /**
     * @param string|null $tipocomprobante tipo del documento
     * @param float $neto neto del documento
     * @param float $total total del documento
     * @param array|null $original factura que modifica, si se conoce:
     *        ['neto' => float, 'total' => float, 'party' => string, 'company' => int, 'currency' => string, 'cancelled' => bool]
     * @param array $note datos del documento para compararlos con la original:
     *        ['party' => string, 'company' => int, 'currency' => string]
     * @param array $credited lo ya acreditado por las demás notas vigentes de esa factura, en positivo:
     *        ['neto' => float, 'total' => float]
     * @return string|null clave de traducción del error, o null si el documento es válido
     */
    public static function validate(
        ?string $tipocomprobante,
        float $neto,
        float $total,
        ?array $original = null,
        array $note = [],
        array $credited = []
    ): ?string {
        $isCredit = in_array($tipocomprobante, self::TIPOS, true);
        $isDebit = in_array($tipocomprobante, self::TIPOS_DEBITO, true);
        $negative = $neto < -0.004 || $total < -0.004;
        $positive = $neto > 0.004 || $total > 0.004;

        if ($original !== null && !$isCredit && !$isDebit && $negative) {
            return self::NEEDS_CREDIT_NOTE;
        }

        if (!$isCredit && !$isDebit) {
            return null;
        }

        if ($isCredit && $positive) {
            return self::POSITIVE_AMOUNT;
        }

        if ($isDebit && $negative) {
            return self::NEGATIVE_DEBIT;
        }

        if ($original === null) {
            return null;
        }

        if ($original['cancelled']
            || (isset($note['party']) && (string)$note['party'] !== (string)$original['party'])
            || (isset($note['company']) && (int)$note['company'] !== (int)$original['company'])
            || (isset($note['currency']) && (string)$note['currency'] !== (string)$original['currency'])) {
            return self::ORIGINAL_INVALID;
        }

        if ($isCredit
            && (abs($total) + (float)($credited['total'] ?? 0) > $original['total'] + 0.004
                || abs($neto) + (float)($credited['neto'] ?? 0) > $original['neto'] + 0.004)) {
            return self::EXCEEDS_INVOICE;
        }

        return null;
    }
}
