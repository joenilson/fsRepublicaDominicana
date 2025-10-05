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

/**
 * Description of ListNCFTipoPago
 *
 * @author Joe Zegarra
 */
class ListNCFTipoPago extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'accounting';
        $pageData['submenu'] = 'Republica Dominicana';
        $pageData['title'] = 'ncf-payment-types';
        $pageData['icon'] = 'fa-solid fa-list';
        
        return $pageData;
    }
    
    public function addRestoreButton($viewName)
    {
        $restoreButton = [
            'color' => 'danger',
            'icon' => 'fa-solid fa-undo',
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
        $this->addView('ListNCFTipoPago-1', 'NCFTipoPago', 'sales', 'fa-solid fa-store');
        $this->addSearchFields('ListNCFTipoPago-1', ['tipopago','codigo','descripcion']);
        $this->addOrderBy('ListNCFTipoPago-1', ['codigo'], 'code');
        $this->addOrderBy('ListNCFTipoPago-1', ['descripcion'], 'description');
        $this->addRestoreButton('ListNCFTipoPago-1');
        
        $this->addView('ListNCFTipoPago-2', 'NCFTipoPago', 'purchases', 'fa-solid fa-credit-card');
        $this->addSearchFields('ListNCFTipoPago-2', ['tipopago','codigo','descripcion']);
        $this->addOrderBy('ListNCFTipoPago-2', ['codigo'], 'code');
        $this->addOrderBy('ListNCFTipoPago-2', ['descripcion'], 'description');
        $this->addRestoreButton('ListNCFTipoPago-2');
    }
    
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'restore-data':
                $this->views['ListNCFTipoPago-1']->model->restoreData();
                Tools::log()->notice('restored-original-data');
                break;
            case 'busca_pago':
                $this->setTemplate(false);
                $where = [new DatabaseWhere('tipopago', $_REQUEST['tipopago'])];
                $pagos = $this->views['ListNCFTipoPago-1']->model->all($where);
                if ($pagos) {
                    echo json_encode(['pagos' => $pagos], JSON_THROW_ON_ERROR);
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
            case 'ListNCFTipoPago-1':
                $where = [new DataBaseWhere('tipopago', '01')];
                $view->loadData('', $where);
                break;

            case 'ListNCFTipoPago-2':
                $where = [new DataBaseWhere('tipopago', '02')];
                $view->loadData('', $where);
                break;
        }
    }
}
