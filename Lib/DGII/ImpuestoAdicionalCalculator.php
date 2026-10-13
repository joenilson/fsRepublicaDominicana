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
 * Cálculo de los impuestos adicionales (ISC, CDT, Propina Legal, Primera Placa) de una línea.
 *
 * Es una clase pura (sin base de datos) para que las fórmulas se puedan probar y ajustar
 * en un solo lugar. Las fórmulas parametrizadas por ImpuestoAdicional son:
 *
 *  - tipo_tasa = porcentaje:   monto = pvpsindto * tasa / 100
 *  - tipo_tasa = monto:        específico = factor * tasa, donde factor depende de base_calculo:
 *        unidad                  -> cantidad
 *        litros_alcohol_absoluto -> cantidad * contenido_litros * grados_alcohol / 100
 *    advalorem = base * tasa_advalorem / 100, donde base = pvpsindto (+ específico si
 *    advalorem_sobre_especifico está activo).
 *
 * TODO: validar con la Ley 253-12 y la descripción técnica del e-CF de la DGII.
 */
class ImpuestoAdicionalCalculator
{
    public const TIPO_PORCENTAJE = 'porcentaje';
    public const TIPO_MONTO = 'monto';

    public const BASE_UNIDAD = 'unidad';
    public const BASE_LITROS_ALCOHOL = 'litros_alcohol_absoluto';

    /**
     * Columnas de monto en la línea por cada impuesto adicional.
     */
    public const AMOUNT_FIELDS = [
        'rdtaxiscespecifico',
        'rdtaxiscadvalorem',
        'rdtaxcdtmonto',
        'rdtaxlegaltipmonto',
        'rdtaxfirstplatemonto',
    ];

    /**
     * @return array{especifico: float, advalorem: float}
     */
    public static function calculate(
        string $tipoTasa,
        string $baseCalculo,
        float  $tasa,
        float  $tasaAdvalorem,
        bool   $advaloremSobreEspecifico,
        float  $pvpsindto,
        float  $cantidad,
        float  $gradosAlcohol = 0.0,
        float  $contenidoLitros = 0.0
    ): array {
        if ($tipoTasa !== self::TIPO_MONTO) {
            return ['especifico' => 0.0, 'advalorem' => $pvpsindto * $tasa / 100.0];
        }

        switch ($baseCalculo) {
            case self::BASE_LITROS_ALCOHOL:
                $factor = $cantidad * $contenidoLitros * $gradosAlcohol / 100.0;
                break;
            case self::BASE_UNIDAD:
            default:
                $factor = $cantidad;
                break;
        }

        $especifico = $factor * $tasa;
        $baseAdvalorem = $advaloremSobreEspecifico ? $pvpsindto + $especifico : $pvpsindto;
        return ['especifico' => $especifico, 'advalorem' => $baseAdvalorem * $tasaAdvalorem / 100.0];
    }

    /**
     * Montos de una línea. Usa los montos guardados; si la línea es anterior a la
     * existencia de esas columnas (todos en cero con tasas cargadas) calcula como porcentaje.
     *
     * @param object $line
     * @return array{isc: float, cdt: float, legaltip: float, firstplate: float, total: float}
     */
    public static function lineAmounts($line): array
    {
        $stored = [
            'isc' => (float)($line->rdtaxiscespecifico ?? 0) + (float)($line->rdtaxiscadvalorem ?? 0),
            'cdt' => (float)($line->rdtaxcdtmonto ?? 0),
            'legaltip' => (float)($line->rdtaxlegaltipmonto ?? 0),
            'firstplate' => (float)($line->rdtaxfirstplatemonto ?? 0),
        ];

        if (array_sum($stored) == 0.0) {
            $pvpsindto = (float)($line->pvpsindto ?? 0);
            $stored = [
                'isc' => $pvpsindto * (float)($line->rdtaxisc ?? 0) / 100.0,
                'cdt' => $pvpsindto * (float)($line->rdtaxcdt ?? 0) / 100.0,
                'legaltip' => $pvpsindto * (float)($line->rdtaxlegaltip ?? 0) / 100.0,
                'firstplate' => $pvpsindto * (float)($line->rdtaxfirstplate ?? 0) / 100.0,
            ];
        }

        $stored['total'] = array_sum($stored);
        return $stored;
    }
}
