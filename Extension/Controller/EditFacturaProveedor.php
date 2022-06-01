<?php
/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Dinamic\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\CommonFunctionsDominicanRepublic;

class EditFacturaProveedor
{
    public function createViews(): Closure
    {
        return function () {
            parent::createViews();
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonModals.js');
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonDomFunctions.js');
        };
    }

    public function execPreviousAction()
    {
        return function ($action) {
            switch ($action) {
                case 'busca_tipo':
                    $this->setTemplate(false);
                    CommonFunctionsDominicanRepublic::ncfTipoComprobante($_REQUEST['tipodocumento']);
                    break;
                case 'busca_movimiento':
                    $this->setTemplate(false);
                    CommonFunctionsDominicanRepublic::ncfTipoMovimiento($_REQUEST['tipomovimiento']);
                    break;
                case 'busca_tipoanulacion':
                    $this->setTemplate(false);
                    CommonFunctionsDominicanRepublic::ncfTipoAnulacion($_REQUEST['tipoanulacion']);
                    break;
                case 'busca_pago':
                    $this->setTemplate(false);
                    CommonFunctionsDominicanRepublic::ncfTipoPago($_REQUEST['tipopago']);
                    break;
                case "verifica_documento":
                    $this->setTemplate(false);
                    $facturasProveedores = new FacturaProveedor();
                    $where = [
                        new DataBaseWhere('numproveedor', $_REQUEST['ncf']),
                        new DataBaseWhere('codproveedor', $_REQUEST['proveedor'])
                    ];
                    $verificacion = $facturasProveedores->all($where);
                    if (!$verificacion) {
                        echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
                    } else {
                        $message = "Factura: " . $verificacion[0]->idfactura . " Fecha: " . $verificacion[0]->fecha;
                        echo json_encode(['error' => true, 'message' => $message], JSON_THROW_ON_ERROR);
                    }
                    break;
                case 'busca_correlativo':
                    $this->setTemplate(false);
                    CommonFunctionsDominicanRepublic::ncfCorrelativo($_REQUEST['tipocomprobante'], $this->empresa->idempresa);
                    break;
                default:
                    break;
            }
        };
    }

    public function ncftipo()
    {
        return function () {
            return NCFTipo::allVentas();
        };
    }

    public function ncftipoanulacion()
    {
        return function () {
            $tiposAnulacion = new NCFTipoAnulacion();
            return $tiposAnulacion->all();
        };
    }
}