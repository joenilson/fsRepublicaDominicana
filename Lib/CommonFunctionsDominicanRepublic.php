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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib;

use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Plugins\fsRepublicaDominicana\Interfaces\CommonFunctionsInterface;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\NCFRango;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;

class CommonFunctionsDominicanRepublic implements CommonFunctionsInterface
{
    public static function ncfRango()
    {
        // TODO: Implement ncfRango() method.
    }

    public static function ncfCorrelativo($tipoComprobante, $idempresa)
    {
        $tipocomprobante = new NCFRango();
        $where = [
            //new DatabaseWhere('tipocomprobante', $_REQUEST['tipocomprobante']),
            new DatabaseWhere('tipocomprobante', $tipoComprobante),
            new DatabaseWhere('idempresa', $idempresa),
            new DatabaseWhere('estado', 1)
        ];
        $comprobante = $tipocomprobante->all($where);
        if ($comprobante) {
            echo json_encode(['existe' => $comprobante], JSON_THROW_ON_ERROR);
        } else {
            echo json_encode(['existe' => false], JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @throws \JsonException
     */
    public static function ncfTipoPago(string $tipoPago)
    {
        $where = [new DatabaseWhere('tipopago', $tipoPago)];
        $tipoPagos = new NCFTipoPago();
        $pagos = $tipoPagos->all($where);
        if ($pagos) {
            echo \json_encode(['pagos' => $pagos], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    /**
     * @throws \JsonException
     */
    public static function ncfTipoMovimiento(string $tipoMovimiento)
    {
        $tipomovimiento = new NCFTipoMovimiento();
        $where = [new DatabaseWhere('tipomovimiento', $tipoMovimiento)];
        $movimientos = $tipomovimiento->all($where);
        if ($movimientos) {
            echo json_encode(['movimientos' => $movimientos], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    public static function ncfTipoAnulacion(string $tipoAnulacion)
    {
        $where = [new DatabaseWhere('codigo', $tipoAnulacion)];
        $tipoAnulaciones = new NCFTipoAnulacion();
        $anulaciones = $tipoAnulaciones->all($where);
        if ($anulaciones) {
            echo \json_encode(['anulaciones' => $anulaciones], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    /**
     * @throws \JsonException
     */
    public static function ncfTipoComprobante($tipoComprobante)
    {
        $where = [new DatabaseWhere($tipoComprobante, 'Y')];
        $tipoComprobantes = new NCFTipo();
        $lista = $tipoComprobantes->all($where);
        if ($lista) {
            echo json_encode(['tipocomprobantes' => $lista], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }

    public static function ncfFechaVencimiento()
    {
        // TODO: Implement ncfFechaVencimiento() method.
    }

    /**
     * @throws \JsonException
     */
    public static function ncfTipoCliente(string $cliente)
    {
        $NCFTipo = new NCFTipo();
        $tipoCliente = $NCFTipo->tipoCliente($cliente);
        if ($tipoCliente) {
            echo json_encode(['infocliente' => $tipoCliente], JSON_THROW_ON_ERROR);
        } else {
            echo '';
        }
    }
}