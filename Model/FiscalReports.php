<?php
/**
 * Copyright (C) 2019-2022 Joe Zegarra.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Base\JoinModel;

class FiscalReports extends JoinModel
{
    public const MAIN_TABLE = 'facturascli';
    public const CLIENTES_TABLE = 'clientes';
    public const NCFTIPO_TABLE = 'rd_ncftipo';
    public const NCFTIPOMOV_TABLE = 'rd_ncftipomovimiento';
    public const NCFTIPOANUL_TABLE = 'rd_ncftipoanulacion';
    public const NCFTIPOPAGO_TABLE = 'rd_ncftipopagos';

    protected function getTables(): array
    {
        return [
            static::MAIN_TABLE,
            static::NCFTIPO_TABLE,
            static::NCFTIPOANUL_TABLE,
            static::NCFTIPOMOV_TABLE,
            static::NCFTIPOPAGO_TABLE,
            static::CLIENTES_TABLE
        ];
    }

    protected function getFields(): array
    {
        return [
            'fecha' => static::MAIN_TABLE.'.fecha',
            'codalmacen' => static::MAIN_TABLE.'.codalmacen',
            'cliente' => static::CLIENTES_TABLE.'.nombre',
            'cifnif' => static::CLIENTES_TABLE.'.cifnif',
            'ncf' => static::MAIN_TABLE.'.numero2',
            'ncfmodifica' => static::MAIN_TABLE.'.codigorect',
            'tipocomprobante' => static::MAIN_TABLE.'.tipocomprobante',
            'base_imponible' => static::MAIN_TABLE.'.total',
            'base_exenta' => static::MAIN_TABLE.'.total',
            'itbis' => static::MAIN_TABLE.'.totaliva',
            'estado' => static::MAIN_TABLE.'.idestado'
        ];
    }

    protected function getSQLFrom(): string
    {
        return static::MAIN_TABLE . ''
            . ' LEFT JOIN '. static::CLIENTES_TABLE . ' ON '
            . static::MAIN_TABLE . '.codcliente = ' . static::CLIENTES_TABLE . '.codcliente'
            . ' LEFT JOIN '. static::NCFTIPO_TABLE . ' ON '
            . static::MAIN_TABLE . '.tipocomprobante = ' . static::NCFTIPO_TABLE . '.tipocomprobante'
            . ' LEFT JOIN ' . static::NCFTIPOPAGO_TABLE . ' ON '
            . static::MAIN_TABLE . '.ncftipopago = ' . static::NCFTIPOPAGO_TABLE . '.tipopago';
    }
}