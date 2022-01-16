<?php
/*
 * Copyright (C) 2020 Joe Nilson <joenilson@gmail.com>
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

use FacturaScripts\Core\Model\Base\ModelCore;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Dinamic\Model\NCFTipoAnulacion;
use FacturaScripts\Dinamic\Model\NCFTipoPago;
use FacturaScripts\Dinamic\Model\NCFTipoMovimiento;

class EditFacturaCliente
{
    public function createViews(): \Closure
    {
        return function () {
            parent::createViews();
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonModals.js');
            AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonDomFunctions.js');

            $ncfTipo = new NCFTipo();
            $ncfTipos = $ncfTipo->allByType('ventas');
            $customValues = [];
            $customValues[] = ['value'=>'', 'title'=>'-----------'];
            foreach ($ncfTipos as $tipo) {
                $customValues[] = ['value'=>$tipo->tipocomprobante, 'title'=>$tipo->descripcion];
            }
            $columnToModify = $this->views['EditFacturaCliente']->columnForName('tipocomprobante');
            if ($columnToModify) {
                $columnToModify->widget->setValuesFromArray($customValues);
            }

            $ncfTipoPago = new NCFTipoPago();
            $ncfTiposPago = $ncfTipoPago->findAllByTipopago('01');
            $customValuesNTP = [];
            $customValuesNTP[] = ['value' => '', 'title' => '-----------'];
            foreach ($ncfTiposPago as $tipopago) {
                $customValuesNTP[] = ['value' => $tipopago->codigo, 'title' => $tipopago->descripcion];
            }
            $columnToModifyNTP = $this->views['EditFacturaCliente']->columnForName('ncf-payment-type');
            if ($columnToModifyNTP) {
                $columnToModifyNTP->widget->setValuesFromArray($customValuesNTP);
            }

            $ncfTipoAnulacion = new NCFTipoAnulacion();
            $ncfTiposAnulacion = $ncfTipoAnulacion->all();
            $customValuesNTA = [];
            $customValuesNTA[] = ['value' => '', 'title' => '-----------'];
            foreach ($ncfTiposAnulacion as $tipoanulacion) {
                $customValuesNTA[] = ['value' => $tipoanulacion->codigo, 'title' => $tipoanulacion->descripcion];
            }
            $columnToModifyNTA1 = $this->views['EditFacturaCliente']->columnForName('ncf-cancellation-type');
            if ($columnToModifyNTA1) {
                $columnToModifyNTA1->widget->setValuesFromArray($customValuesNTA);
            }

            $ncfTipoMovimiento = new NCFTipoMovimiento();
            $ncfTiposMovimiento = $ncfTipoMovimiento->findAllByTipomovimiento('VEN');
            $customValuesNTM = [];
            $customValuesNTM[] = ['value' => '', 'title' => '-----------'];
            foreach ($ncfTiposMovimiento as $tipomovimiento) {
                $customValuesNTM[] = ['value' => $tipomovimiento->codigo, 'title' => $tipomovimiento->descripcion];
            }
            $columnToModifyNTM = $this->views['EditFacturaCliente']->columnForName('ncf-movement-type');
            if ($columnToModifyNTM) {
                $columnToModifyNTM->widget->setValuesFromArray($customValuesNTM);
            }
        };
    }
}