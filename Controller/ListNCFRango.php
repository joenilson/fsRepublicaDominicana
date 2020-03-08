<?php

/**
 * Copyright (C) 2019 joenilson.
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFRango;

/**
 * Description of ListNCFRango
 *
 * @author joenilson
 */
class ListNCFRango extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'accounting';
        $pageData['submenu'] = 'Republica Dominicana';
        $pageData['title'] = 'ncf-master';
        $pageData['icon'] = 'fas fa-list';
        
        return $pageData;
    }
    
    protected function createViews()
    {
        $this->addView('ListNCFRango', 'NCFRango');
        $this->addSearchFields('ListNCFRango', ['tipocomprobante']);
        $this->addOrderBy('ListNCFRango', ['tipocomprobante','correlativo'], 'tipocomprobante');
        
        $this->setSettings('ListNCFRango', 'modalInsert', 'ncf-rango-insert');
        $this->setCustomWidgetValues('ListNCFRango');
    }

    public function setCustomWidgetValues($viewName)
    {
        $customValues = [];
        $customValues[] = ['value'=>'', 'title'=>'-----------'];
        foreach(\range('A', 'Z') as $i){
            $customValues[] = ['value'=>$i, 'title'=>$i];
        }
        $columnToModify = $this->views[$viewName]->columnModalForName('serie_nueva');
        if($columnToModify) {
            $columnToModify->widget->setValuesFromArray($customValues);
        }
    }

    public function execPreviousAction($action)
    {
        switch ($action) {
            case 'ncf-rango-insert':
                $valueIdEmpresa = $this->request->request->get('idempresa');
                $valueSerie = $this->request->request->get('serie_nueva');
                $valueUsuarioCreacion = $this->request->request->get('usuariocreacion');
                $valueFechaCreacion = $this->request->request->get('fechacreacion');
                $data = $this->request->request->all();
                $data['serie']=(isset($valueSerie) and $valueSerie !=='')?$valueSerie:$data['serie'];
                $data['idempresa']=(isset($valueIdEmpresa) and $valueIdEmpresa !=='')?$valueIdEmpresa:$this->empresa->idempresa;
                $data['usuariomodificacion']=(isset($valueFechaCreacion) and $valueFechaCreacion !=='')?$this->user->nick:null;
                $data['usuariocreacion']=(isset($valueUsuarioCreacion) and $valueUsuarioCreacion !=='')?$valueUsuarioCreacion:$this->user->nick;
                $data['fechacreacion']=(isset($valueFechaCreacion) and $valueFechaCreacion !=='')?$valueFechaCreacion:\date('Y-m-d');
                $rangoNuevo = new NCFRango();
                $rangoNuevo->loadFromData($data);
                $rangoNuevo->save();
                $this->toolBox()->log()->notice('Rango nuevo guardado exitosamente');
                break;
        }
        return parent::execPreviousAction($action);
    }
}
