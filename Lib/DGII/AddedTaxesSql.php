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
 * Expresiones SQL para sumar los impuestos adicionales de las líneas de un documento
 * en los reportes fiscales (606 y 607).
 *
 * Usa los montos guardados en cada línea, igual que ImpuestoAdicionalCalculator::lineAmounts().
 */
class AddedTaxesSql
{
    public const ISC = 'isc';
    public const OTROS = 'otros';
    public const PROPINA = 'propina';

    /**
     * Subconsulta con la suma de un tipo de impuesto adicional de todas las líneas del documento.
     *
     * @param string $linesTable tabla de líneas, por ejemplo lineasfacturasprov
     * @param string $docTable tabla del documento, por ejemplo facturasprov
     * @param string $type ISC, OTROS (CDT y primera placa) o PROPINA
     */
    public static function sum(string $linesTable, string $docTable, string $type): string
    {
        switch ($type) {
            case self::ISC:
                $amount = 'COALESCE(l_added.rdtaxiscespecifico,0) + COALESCE(l_added.rdtaxiscadvalorem,0)';
                break;
            case self::OTROS:
                $amount = 'COALESCE(l_added.rdtaxcdtmonto,0) + COALESCE(l_added.rdtaxfirstplatemonto,0)';
                break;
            case self::PROPINA:
            default:
                $amount = 'COALESCE(l_added.rdtaxlegaltipmonto,0)';
                break;
        }

        return '(SELECT COALESCE(SUM(' . $amount . '), 0) FROM ' . $linesTable . ' l_added'
            . ' WHERE l_added.idfactura = ' . $docTable . '.idfactura)';
    }

    /**
     * Aplica el criterio de los reportes: anuladas en cero y notas con importe negativo en positivo.
     *
     * @param string $sumSql subconsulta de sum()
     * @param string $docTable tabla del documento
     * @param string $statesTable tabla estados_documentos
     */
    public static function reportSign(string $sumSql, string $docTable, string $statesTable): string
    {
        return 'CASE WHEN ' . $statesTable . '.nombre = \'Anulada\' THEN 0'
            . ' WHEN ' . $statesTable . '.nombre = \'Emitida\' AND ' . $docTable . '.neto < 0 THEN ' . $sumSql . '*-1'
            . ' ELSE ' . $sumSql . ' END';
    }
}
