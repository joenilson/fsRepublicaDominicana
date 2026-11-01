<?php
/**
 * Copyright (C) 2026 Joe Nilson <joenilson@gmail.com>
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
 * Cálculo de las retenciones de la DGII sobre una línea.
 *
 * FacturaScripts guarda en cada línea un único porcentaje (campo irpf) y lo aplica sobre el neto
 * de la línea. Eso sirve para el ISR, pero la retención de ITBIS de la DGII (30 % o 100 % según
 * Normas 02-05, 01-11, 07-09, 05-19 y 07-07) se calcula sobre el ITBIS facturado. Por eso, para
 * una retención de tipo ITBIS el porcentaje sobre el neto es: porcentaje × ITBIS de la línea / 100.
 *
 * Es una clase pura (sin base de datos) para poder probarla en un solo lugar.
 */
class RetencionCalculator
{
    public const TIPO_ISR = 'ISR';
    public const TIPO_ITBIS = 'ITBIS';

    /** Tipos de retención en ISR del formato 606 (campo "Tipo de Retención en ISR"). */
    public const TIPOS_ISR = [
        '1' => 'Alquileres',
        '2' => 'Honorarios por servicios',
        '3' => 'Otras rentas',
        '4' => 'Otras rentas (rentas presuntas)',
        '5' => 'Intereses pagados a personas jurídicas residentes',
        '6' => 'Intereses pagados a personas físicas residentes',
        '7' => 'Retención por proveedores del Estado',
        '8' => 'Juegos telefónicos',
        '9' => 'Retenciones subsector de ganadería de carne bovina',
    ];

    /**
     * Porcentaje que FacturaScripts debe aplicar sobre el neto de la línea.
     *
     * @param string $tipo        TIPO_ISR o TIPO_ITBIS
     * @param float  $porcentaje  porcentaje de la retención (sobre el neto en ISR, sobre el ITBIS en ITBIS)
     * @param float  $ivaLinea    porcentaje de ITBIS de la línea (por ejemplo 18)
     */
    public static function lineIrpf(string $tipo, float $porcentaje, float $ivaLinea): float
    {
        if ($tipo !== self::TIPO_ITBIS) {
            return $porcentaje;
        }

        return round($porcentaje * $ivaLinea / 100.0, 6);
    }

    /**
     * Importe retenido de una línea.
     */
    public static function lineAmount(string $tipo, float $porcentaje, float $ivaLinea, float $pvptotal): float
    {
        return $pvptotal * self::lineIrpf($tipo, $porcentaje, $ivaLinea) / 100.0;
    }

    /**
     * Una línea con retención 0 se respeta (el usuario excluyó esa línea). Con retención mayor a 0
     * se recalcula siempre desde la retención del cliente o proveedor.
     */
    public static function shouldRecalculate(float $currentIrpf): bool
    {
        return $currentIrpf > 0.0;
    }

    /**
     * Reparte el total retenido del documento según el tipo de retención.
     *
     * @return array{itbis: float, isr: float, tipoisr: string|null}
     */
    public static function documentTotals(float $totalRetenido, ?string $tipo, ?string $tipoIsr): array
    {
        if ($totalRetenido == 0.0) {
            return ['itbis' => 0.0, 'isr' => 0.0, 'tipoisr' => null];
        }

        if ($tipo === self::TIPO_ITBIS) {
            return ['itbis' => $totalRetenido, 'isr' => 0.0, 'tipoisr' => null];
        }

        $tipoIsr = (string)$tipoIsr;
        return [
            'itbis' => 0.0,
            'isr' => $totalRetenido,
            'tipoisr' => isset(self::TIPOS_ISR[$tipoIsr]) ? $tipoIsr : null,
        ];
    }
}
