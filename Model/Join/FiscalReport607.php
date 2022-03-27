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

class FiscalReport607 extends JoinModel
{
    const MAIN_TABLE = 'facturascli';
    const SECONDARY_TABLE = 'facturascli AS f2';
    const SECONDARY_TABLE_ALIAS = 'f2';
    const ESTADOSDOC_TABLE = 'estados_documentos';

    /**
     *
     * @return array
     */
    protected function getFields(): array
    {
        $data = [
            'itemrow' => static::MAIN_TABLE.'.idfactura',
            'idempresa' => static::MAIN_TABLE.'.idempresa',
            'codalmacen' => static::MAIN_TABLE.'.codalmacen',
            'cifnif' => 'CASE WHEN length('.static::MAIN_TABLE.'.cifnif)=9 THEN '.static::MAIN_TABLE.'.cifnif WHEN length('.static::MAIN_TABLE.'.cifnif)=11 THEN '.static::MAIN_TABLE.'.cifnif ELSE NULL END',
            'tipoid' => 'CASE WHEN length('.static::MAIN_TABLE.'.cifnif)=9 THEN 1 WHEN length('.static::MAIN_TABLE.'.cifnif)=11 THEN 2 ELSE 3 END',
            'ncf' => static::MAIN_TABLE.'.numero2',
            'ncfmodifica' => static::SECONDARY_TABLE_ALIAS.'.numero2',
            'tipoingreso' => 'CASE WHEN '.static::MAIN_TABLE.'.ncftipomovimiento is null THEN \'1\' ELSE '.static::MAIN_TABLE.'.ncftipomovimiento END',
            'fecha' => 'to_char('.static::MAIN_TABLE.'.fecha,\'YYYYMMDD\')',
            'fecharetencion' => '\'\'',
            'base' => 'CASE WHEN '.static::ESTADOSDOC_TABLE.'.nombre = \'Anulada\' THEN 0 WHEN '.static::ESTADOSDOC_TABLE.'.nombre = \'Emitida\' AND '.static::MAIN_TABLE.'.neto < 0 THEN '.static::MAIN_TABLE.'.neto*-1 ELSE '.static::MAIN_TABLE.'.neto END',
            'itbis' => 'CASE WHEN '.static::ESTADOSDOC_TABLE.'.nombre = \'Anulada\' THEN 0 WHEN '.static::ESTADOSDOC_TABLE.'.nombre = \'Emitida\' AND '.static::MAIN_TABLE.'.totaliva < 0 THEN '.static::MAIN_TABLE.'.totaliva*-1 ELSE '.static::MAIN_TABLE.'.totaliva END',
            'itbisretenido' => '0',
            'itbispercibido' => '0',
            'rentaretenido' => '0',
            'rentapercibido' => '0',
            'isc' => '0',
            'otrosimpuestos' => '0',
            'propinalegal' => '0',
            'totalefectivo' => 'CASE WHEN '.static::MAIN_TABLE.'.ncftipopago IS NULL OR '.static::MAIN_TABLE.'.ncftipopago = \'\' OR '.static::MAIN_TABLE.'.ncftipopago = \'17\' THEN '.static::MAIN_TABLE.'.neto else 0 END',
            'totalcheque' => 'CASE WHEN '.static::MAIN_TABLE.'.ncftipopago = \'18\' THEN '.static::MAIN_TABLE.'.neto else 0 END',
            'totaltarjeta' => 'CASE WHEN '.static::MAIN_TABLE.'.ncftipopago = \'19\' THEN '.static::MAIN_TABLE.'.neto else 0 END',
            'totalcredito' => 'CASE WHEN '.static::MAIN_TABLE.'.ncftipopago = \'20\' THEN '.static::MAIN_TABLE.'.neto else 0 END',
            'totalbonos' => 'CASE WHEN '.static::MAIN_TABLE.'.ncftipopago = \'21\' THEN '.static::MAIN_TABLE.'.neto else 0 END',
            'totalpermuta' => 'CASE WHEN '.static::MAIN_TABLE.'.ncftipopago = \'22\' THEN '.static::MAIN_TABLE.'.neto else 0 END',
            'totalotrasformas' => 'CASE WHEN '.static::MAIN_TABLE.'.ncftipopago = \'23\' THEN '.static::MAIN_TABLE.'.neto else 0 END',
            'estado' => 'CASE WHEN '.static::ESTADOSDOC_TABLE.'.nombre = \'Emitida\' THEN \'Activo\' ELSE \'Anulado\' END',
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
            . ' LEFT JOIN '. static::SECONDARY_TABLE . ' ON ('
            . static::MAIN_TABLE . '.idfacturarect = ' . static::SECONDARY_TABLE_ALIAS . '.idfactura)'
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
            static::ESTADOSDOC_TABLE
        ];
    }
}
