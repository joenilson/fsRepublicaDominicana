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

use FacturaScripts\Dinamic\Model\NCFTipo;

class EditCliente
{

    public function createViews()
    {
        return function () {
            $ncfTipo = new NCFTipo();
            $tipoComprobantes = $ncfTipo->allFor('ventas', 'suma');
            $customValues = [];
            $customValues[] = ['value'=>'', 'title'=>'-----------'];
            foreach($tipoComprobantes as $tipo) {
                $customValues[] = ['value'=>$tipo->tipocomprobante, 'title'=>$tipo->descripcion];
            }
            $columnToModify = $this->views['EditCliente']->columnForName('tipo_comprobante');
            if($columnToModify) {
                $columnToModify->widget->setValuesFromArray($customValues);
            }
        };
    }
}
