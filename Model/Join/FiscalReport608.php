<?php
/*
 * Copyright (C) 2022 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model\Join;

use FacturaScripts\Core\Model\Base\JoinModel;

class FiscalReport608 extends JoinModel
{
    const MAIN_TABLE = 'facturascli';
    const NCFCANCELTYPE_TABLE = 'rd_ncftipoanulacion';
    const ESTADOSDOC_TABLE = 'estados_documentos';

    /**
     *
     * @return array
     */
    protected function getFields(): array
    {
        $data = [
            'itemrow' => 'rank() over (order by '.static::MAIN_TABLE.'.numero2)',
            'idempresa' => static::MAIN_TABLE.'.idempresa',
            'codalmacen' => static::MAIN_TABLE.'.codalmacen',
            'ncf' => static::MAIN_TABLE.'.numero2',
            'tipoanulacion' => 'CASE WHEN ' .
                                static::ESTADOSDOC_TABLE . '.nombre = \'Anulada\' AND ' .
                                static::MAIN_TABLE.'.ncftipoanulacion is null ' .
                                'THEN CONCAT(\'05\',\' - \',\'Corrección de la Información\') ELSE ' .
                                'concat(' . static::MAIN_TABLE.'.ncftipoanulacion, \' - \', ' .
                                static::NCFCANCELTYPE_TABLE.'.descripcion) END',
            'fecha' => 'to_char('.static::MAIN_TABLE.'.fecha,\'YYYYMMDD\')',
            'estado' => 'CASE WHEN ' . static::ESTADOSDOC_TABLE .
                        '.nombre = \'Emitida\' THEN \'Activo\' ELSE \'Anulado\' END',
        ];
        return $data;
    }

    /**
     *
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return static::MAIN_TABLE
            . ' JOIN ' . static::ESTADOSDOC_TABLE . ' ON ('
            . static::MAIN_TABLE . '.idestado = ' . static::ESTADOSDOC_TABLE . '.idestado AND '.static::ESTADOSDOC_TABLE.'.nombre = \'Anulada\')'
            . ' LEFT JOIN ' . static::NCFCANCELTYPE_TABLE . ' ON ('
            . static::MAIN_TABLE . '.ncftipoanulacion = ' . static::NCFCANCELTYPE_TABLE . '.codigo)';
    }

    /**
     *
     * @return array
     */
    protected function getTables(): array
    {
        return [
            static::MAIN_TABLE,
            static::NCFCANCELTYPE_TABLE,
            static::ESTADOSDOC_TABLE
        ];
    }
}
