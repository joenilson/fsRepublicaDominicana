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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Dinamic\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\CommonFunctionsDominicanRepublic;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\WebserviceDgii;

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
                    CommonFunctionsDominicanRepublic::verifyDocument($_REQUEST['ncf'],$_REQUEST['proveedor']);
                    break;
                case 'busca_correlativo':
                    $this->setTemplate(false);
                    CommonFunctionsDominicanRepublic::ncfCorrelativo($_REQUEST['tipocomprobante'], $this->empresa->idempresa);
                    break;
                case 'busca_rnc':
                    $this->setTemplate(false);
                    $consulta = new WebserviceDgii();
                    $rncNotFound = Tools::lang()->trans('rnc-not-found');
                    $respuesta = $consulta->getExternalAPI($_REQUEST['cifnif']);
                    $registros = $respuesta->totalResults;
                    if ($registros !== 0) {
                        $resultado = $respuesta->entry[0];
                        if ($resultado) {
                            $arrayResultado = [];
                            $arrayResultado["RGE_RUC"] = $resultado->rnc;
                            $arrayResultado["RGE_NOMBRE"] = $resultado->nombre;
                            $arrayResultado["NOMBRE_COMERCIAL"] = $resultado->razonsocial;
                            $arrayResultado["ESTATUS"] = $resultado->estado;
                            echo json_encode($arrayResultado, JSON_THROW_ON_ERROR);
                        } else {
                            echo '{"RGE_ERROR": "true", "message": "'.$rncNotFound.'"}';
                        }
                    } else {
                        echo '{"RGE_ERROR": "true", "message": "'.$rncNotFound.'"}';
                    }
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