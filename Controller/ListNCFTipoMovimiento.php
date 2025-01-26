<?php

/**
 * Copyright (C) 2019 Joe Zegarra.
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;

/**
 * Description of ListNCFTipoMovimiento
 *
 * @author Joe Zegarra
 */
class ListNCFTipoMovimiento extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'accounting';
        $pageData['submenu'] = 'Republica Dominicana';
        $pageData['title'] = 'ncf-movement-types';
        $pageData['icon'] = 'fas fa-list';
        
        return $pageData;
    }
    
    public function addRestoreButton($viewName)
    {
        $restoreButton = [
            'color' => 'danger',
            'icon' => 'fas fa-undo',
            'label' => 'restore-original-data',
            'title' => 'restore-original-data',
            'type' => 'action',
            'action' => 'restore-data',
            'hint' => 'restore-original-data',
            'confirm' => true
        ];
        $this->addButton($viewName, $restoreButton);
    }
    
    protected function createViews()
    {
        $this->addView(
            'ListNCFTipoMovimiento-1',
            'NCFTipoMovimiento',
            'sales',
            'fas fa-store'
        );
        $this->addSearchFields('ListNCFTipoMovimiento-1', ['tipomovimiento','codigo','descripcion']);
        $this->addOrderBy('ListNCFTipoMovimiento-1', ['id'], 'code');
        $this->addOrderBy('ListNCFTipoMovimiento-1', ['descripcion'], 'description');
        $this->addRestoreButton('ListNCFTipoMovimiento-1');
        
        $this->addView('ListNCFTipoMovimiento-2', 'NCFTipoMovimiento', 'purchases', 'fas fa-credit-card');
        $this->addSearchFields('ListNCFTipoMovimiento-2', ['tipomovimiento','codigo','descripcion']);
        $this->addOrderBy('ListNCFTipoMovimiento-2', ['id'], 'code');
        $this->addOrderBy('ListNCFTipoMovimiento-2', ['descripcion'], 'description');
        $this->addRestoreButton('ListNCFTipoMovimiento-2');
    }
    
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'restore-data':
                $this->views['ListNCFTipoMovimiento-1']->model->restoreData();
                Tools::log()->notice('restored-original-data');
                break;
            case 'busca_movimiento':
                $this->setTemplate(false);
                $tipomovimiento = new NCFTipoMovimiento();
                $where = [new DatabaseWhere('tipomovimiento', $_REQUEST['tipomovimiento'])];
                $movimientos = $tipomovimiento->all($where);
                if ($movimientos) {
                    //header('Content-Type: application/json');
                    echo json_encode(['movimientos' => $movimientos]);
                } else {
                    echo '';
                }
                break;
            default:
                parent::execPreviousAction($action);
        }
    }
    
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListNCFTipoMovimiento-1':
                $where = [new DataBaseWhere('tipomovimiento', 'VEN')];
                $view->loadData('', $where);
                break;

            case 'ListNCFTipoMovimiento-2':
                $where = [new DataBaseWhere('tipomovimiento', 'COM')];
                $view->loadData('', $where);
                break;
        }
    }
}
