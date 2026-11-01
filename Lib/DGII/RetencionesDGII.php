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

use FacturaScripts\Core\Base\DataBase;

/**
 * Catálogo de retenciones de la DGII.
 *
 * Las retenciones de ITBIS que trae FacturaScripts para República Dominicana (RETEN30 y RETEN100)
 * se marcan como tipo ITBIS: su porcentaje es sobre el ITBIS facturado. Las de ISR se agregan con
 * los porcentajes de la guía "Retenciones de ISR e ITBIS" de la DGII y el código del campo
 * "Tipo de Retención en ISR" del formato 606.
 */
class RetencionesDGII
{
    /** Retenciones de ITBIS que ya existen en el catálogo de FacturaScripts. */
    public const ITBIS = ['RETEN30', 'RETEN100'];

    /**
     * código, descripción, porcentaje sobre el neto, código DGII del tipo de retención en ISR
     *
     * @return array<int, array{0: string, 1: string, 2: float, 3: string}>
     */
    public static function isr(): array
    {
        return [
            ['RETISR1', 'RETENCION ISR 10% ALQUILERES', 10.0, '1'],
            ['RETISR2', 'RETENCION ISR 10% HONORARIOS POR SERVICIOS', 10.0, '2'],
            ['RETISR3', 'RETENCION ISR 10% OTRAS RENTAS', 10.0, '3'],
            ['RETISR5', 'RETENCION ISR 1% INTERESES A PERSONAS JURIDICAS', 1.0, '5'],
            ['RETISR6', 'RETENCION ISR 1% INTERESES A PERSONAS FISICAS', 1.0, '6'],
            ['RETISR7', 'RETENCION ISR 5% PROVEEDORES DEL ESTADO', 5.0, '7'],
            ['RETISR8', 'RETENCION ISR 5% JUEGOS TELEFONICOS', 5.0, '8'],
            ['RETISR9', 'RETENCION ISR 1% GANADERIA CARNE BOVINA', 1.0, '9'],
        ];
    }

    /**
     * Marca como ITBIS las retenciones RETEN30 y RETEN100 y agrega las de ISR que falten. No modifica
     * las retenciones existentes (salvo el tipo de las de ITBIS), así que es seguro ejecutarlo en cada
     * actualización.
     */
    public static function install(): void
    {
        $db = new DataBase();
        if (!$db->tableExists('retenciones')) {
            return;
        }

        $codes = array_map([$db, 'var2str'], self::ITBIS);
        $db->exec("UPDATE retenciones SET rdtipo = 'ITBIS' WHERE codretencion IN (" . implode(',', $codes) . ');');

        foreach (self::isr() as [$codigo, $descripcion, $porcentaje, $tipoIsr]) {
            $exists = $db->select('SELECT 1 FROM retenciones WHERE codretencion = ' . $db->var2str($codigo));
            if (!empty($exists)) {
                continue;
            }
            $db->exec('INSERT INTO retenciones (codretencion, descripcion, porcentaje, activa, rdtipo, rdtipoisr) VALUES ('
                . $db->var2str($codigo) . ', ' . $db->var2str($descripcion) . ', ' . (float)$porcentaje
                . ", true, 'ISR', " . $db->var2str($tipoIsr) . ');');
        }
    }
}
