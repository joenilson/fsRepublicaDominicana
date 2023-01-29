<?php
/**
 * Copyright (C) 2020 Joe Nilson <joenilson at gmail dot com>
 * 
 * fsRepublicaDominicana is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * fsRepublicaDominicana is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with fsRepublicaDominicana. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Extension\Controller;

use Closure;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\NCFTipoPago;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\WebserviceDgii;

class EditProveedor
{

    public function createViews(): Closure
    {
        return function () {
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonModals.js');
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonDomFunctions.js');
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/BusquedaRNCDGII.js');
            $ncfTipoPago = new NCFTipoPago();
            $ncfTiposPago = $ncfTipoPago->findAllByTipopago('02');
            $customValuesNTP = [];
            $customValuesNTP[] = ['value'=>'', 'title'=>'-----------'];
            foreach ($ncfTiposPago as $tipopago) {
                $customValuesNTP[] = ['value'=>$tipopago->codigo, 'title'=>$tipopago->descripcion];
            }
            $columnToModifyNTP = $this->views['EditProveedor']->columnForName('ncf-payment-types');
            if ($columnToModifyNTP) {
                $columnToModifyNTP->widget->setValuesFromArray($customValuesNTP);
            }
        };
    }

    public function execPreviousAction()
    {
        return function ($action) {
            switch ($action) {
                case 'busca_rnc':
                    $this->setTemplate(false);
                    $consulta = new WebserviceDgii();
                    $rncNotFound = self::toolBox()->i18n()->trans('rnc-not-found');
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
                            echo json_encode($arrayResultado);
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
}
