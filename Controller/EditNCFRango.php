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

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditNCFRango
 *
 * @author "Joe Nilson <joenilson at gmail dot com>"
 */
class EditNCFRango extends EditController
{
    public function getModelClassName()
    {
        return 'NCFRango';
    }
    
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['menu'] = 'RepublicaDominicana';
        $pagedata['title'] = 'edit-ncf-master';
        $pagedata['icon'] = 'fas fa-tasks';

        return $pagedata;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setCustomWidgetValues('EditNCFRango');
        $this->views['EditNCFRango']->disableColumn('usuariocreacion', false, 'true');
        $this->views['EditNCFRango']->disableColumn('fechacreacion', false, 'true');
        $this->views['EditNCFRango']->disableColumn('usuariomodificacion', false, 'true');
        $this->views['EditNCFRango']->disableColumn('fechamodificacion', false, 'true');
    }

    public function setCustomWidgetValues($viewName)
    {
        $customValues = [];
        $customValues[] = ['value'=>'', 'title'=>'-----------'];
        foreach(\range('A', 'Z') as $i){
            $customValues[] = ['value'=>$i, 'title'=>$i];
        }
        $columnToModify = $this->views[$viewName]->columnForName('serie');
        if($columnToModify) {
            $columnToModify->widget->setValuesFromArray($customValues);
        }
    }

    public function execPreviousAction($action)
    {
        switch ($action) {
            default:
                $this->views['EditNCFRango']->model->usuariomodificacion_view = $this->user->nick;
                break;
        }
        parent::execPreviousAction($action);
    }

    public function execAfterAction($action)
    {
        switch ($action) {
            default:
                break;
        }
        parent::execPreviousAction($action);
    }
}
