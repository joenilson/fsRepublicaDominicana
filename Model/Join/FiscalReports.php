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
use FacturaScripts\Core\Tools;


class FiscalReports extends JoinModel
{
    const MAIN_TABLE = 'facturascli';
    const SECONDARY_TABLE = 'facturascli AS f2';
    const SECONDARY_TABLE_ALIAS = 'f2';
    const LINES_TABLE = 'lineasfacturascli';
    const ALMACENES_TABLE = 'almacenes';
    const NCFTIPO_TABLE = 'rd_ncftipo';
    const NCFTIPOMOV_TABLE = 'rd_ncftipomovimiento';
    const NCFTIPOANUL_TABLE = 'rd_ncftipoanulacion';
    const NCFTIPOPAGO_TABLE = 'rd_ncftipopagos';
    const ESTADOSDOC_TABLE = 'estados_documentos';

    /**
     *
     * @return array
     */
    protected function getFields(): array
    {
        $dateFormat = (FS_DB_TYPE === 'postgresql') ? "to_char" : "date_format";
        $dateFormatString = (FS_DB_TYPE === 'postgresql') ? "YYYYMMDD" : "%Y%m%d";

        $data = [
            'idfactura' => static::MAIN_TABLE.'.idfactura',
            'idempresa' => static::MAIN_TABLE.'.idempresa',
            'fecha' => static::MAIN_TABLE.'.fecha',
            'codalmacen' => static::MAIN_TABLE.'.codalmacen',
            'almacen' => static::ALMACENES_TABLE.'.nombre',
            'cliente' => static::MAIN_TABLE.'.nombrecliente',
            'cifnif' => static::MAIN_TABLE.'.cifnif',
            'ncf' => static::MAIN_TABLE.'.numeroncf',
            'baseimponible' => 'sum(case when '.static::LINES_TABLE.'.iva != 0 then '.static::LINES_TABLE.'.pvptotal else 0 end)',
            'baseexenta' => 'sum(case when '.static::LINES_TABLE.'.iva = 0 then '.static::LINES_TABLE.'.pvptotal else 0 end)',
            'itbis' => static::MAIN_TABLE.'.totaliva',
            'total' => static::MAIN_TABLE.'.total',
            'pagada' => static::MAIN_TABLE.'.pagada',
            'estado' => static::ESTADOSDOC_TABLE.'.nombre',
            'ncfmodifica' => static::SECONDARY_TABLE_ALIAS.'.numeroncf',
            'tipocomprobante' => static::NCFTIPO_TABLE.'.descripcion',
            'tipopago' => static::NCFTIPOPAGO_TABLE.'.descripcion',
            'tipomovimiento' => static::NCFTIPOMOV_TABLE.'.descripcion',
            'tipoanulacion' => static::NCFTIPOANUL_TABLE.'.descripcion',
        ];
        return $data;
    }

    /**
     *
     * @return string
     */
    protected function getGroupFields(): string
    {
        return static::MAIN_TABLE.'.idfactura, '.
            static::MAIN_TABLE.'.idempresa, '.
            static::MAIN_TABLE.'.fecha, '.
            static::MAIN_TABLE.'.codalmacen, '.
            static::ALMACENES_TABLE.'.nombre, '.
            static::MAIN_TABLE.'.nombrecliente, '.
            static::MAIN_TABLE.'.cifnif, '.
            static::MAIN_TABLE.'.numeroncf, '.
            static::MAIN_TABLE.'.total, '.
            static::MAIN_TABLE.'.totaliva, '.
            static::MAIN_TABLE.'.pagada, '.
            static::ESTADOSDOC_TABLE.'.nombre, '.
            static::SECONDARY_TABLE_ALIAS.'.numeroncf, '.
            static::NCFTIPO_TABLE.'.descripcion, '.
            static::NCFTIPOPAGO_TABLE.'.descripcion, '.
            static::NCFTIPOMOV_TABLE.'.descripcion, '.
            static::NCFTIPOANUL_TABLE.'.descripcion';
    }

    /**
     *
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return static::MAIN_TABLE
            . ' LEFT JOIN ' . static::LINES_TABLE . ' ON ('
            . static::MAIN_TABLE . '.idfactura = ' . static::LINES_TABLE . '.idfactura)'
            . ' LEFT JOIN '. static::SECONDARY_TABLE . ' ON ('
            . static::MAIN_TABLE . '.idfacturarect = ' . static::SECONDARY_TABLE_ALIAS . '.idfactura)'
            . ' LEFT JOIN '. static::ALMACENES_TABLE . ' ON ('
            . static::MAIN_TABLE . '.codalmacen = ' . static::ALMACENES_TABLE . '.codalmacen)'
            . ' LEFT JOIN '. static::NCFTIPO_TABLE . ' ON ('
            . static::MAIN_TABLE . '.tipocomprobante = ' . static::NCFTIPO_TABLE . '.tipocomprobante)'
            . ' LEFT JOIN ' . static::NCFTIPOPAGO_TABLE . ' ON ('
            . static::MAIN_TABLE . '.ncftipopago = ' . static::NCFTIPOPAGO_TABLE . '.codigo)'
            . ' LEFT JOIN ' . static::NCFTIPOMOV_TABLE . ' ON ('
            . static::MAIN_TABLE . '.ncftipomovimiento = ' . static::NCFTIPOMOV_TABLE . '.codigo)'
            . ' LEFT JOIN ' . static::NCFTIPOANUL_TABLE . ' ON ('
            . static::MAIN_TABLE . '.ncftipoanulacion = ' . static::NCFTIPOANUL_TABLE . '.codigo)'
            . ' LEFT JOIN ' . static::ESTADOSDOC_TABLE . ' ON ('
            . static::MAIN_TABLE . '.idestado = ' . static::ESTADOSDOC_TABLE . '.idestado)';
    }

    /**
     *
     * @return array
     */
    protected function getTables(): array
    {
        return [
            static::MAIN_TABLE,
            static::ALMACENES_TABLE,
            static::LINES_TABLE,
            static::NCFTIPO_TABLE,
            static::NCFTIPOANUL_TABLE,
            static::NCFTIPOMOV_TABLE,
            static::NCFTIPOPAGO_TABLE,
            static::ESTADOSDOC_TABLE
        ];
    }
}
