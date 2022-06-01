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
use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Dinamic\Model\NCFTipoPago;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;

class EditCliente
{
    public function createViews(): Closure
    {
        return function () {
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonModals.js');
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonDomFunctions.js');
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/BusquedaRNCDGII.js');
            $ncfTipo = new NCFTipo();
            $ncfTipos = $ncfTipo->allFor('ventas', 'suma');
            $customValues = [];
            $customValues[] = ['value'=>'', 'title'=>'-----------'];
            foreach ($ncfTipos as $tipo) {
                $customValues[] = ['value'=>$tipo->tipocomprobante, 'title'=>$tipo->descripcion];
            }
            $columnToModify = $this->views['EditCliente']->columnForName('tipocomprobante');
            if ($columnToModify) {
                $columnToModify->widget->setValuesFromArray($customValues);
            }

            $ncfTipoPago = new NCFTipoPago();
            $ncfTiposPago = $ncfTipoPago->findAllByTipopago('01');
            $customValuesNTP = [];
            $customValuesNTP[] = ['value'=>'', 'title'=>'-----------'];
            foreach ($ncfTiposPago as $tipopago) {
                $customValuesNTP[] = ['value'=>$tipopago->codigo, 'title'=>$tipopago->descripcion];
            }
            $columnToModifyNTP = $this->views['EditCliente']->columnForName('ncf-payment-types');
            if ($columnToModifyNTP) {
                $columnToModifyNTP->widget->setValuesFromArray($customValuesNTP);
            }
        };
    }
}
