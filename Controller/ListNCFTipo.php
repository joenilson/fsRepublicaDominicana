<?php

/*
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of ListNCFTipo
 *
 * @author Joe Zegarra
 */
class ListNCFTipo extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'accounting';
        $pageData['submenu'] = 'Republica Dominicana';
        $pageData['title'] = 'ncf-types';
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
        $this->addView('ListNCFTipo', 'NCFTipo');
        $this->addSearchFields('ListNCFTipo', ['tipocomprobante', 'descripcion'], 'descripcion');
        $this->addOrderBy('ListNCFTipo', ['tipocomprobante', 'descripcion'], 'tipocomprobante');
        $this->addRestoreButton('ListNCFTipo');
    }
    
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'restore-data':
                $this->views['ListNCFTipo']->model->restoreData();
                $this->toolBox()->i18nLog()->notice('restored-original-data');
                break;
            case 'busca_tipo':
                $this->setTemplate(false);
                $where = [new DatabaseWhere($_REQUEST['tipodocumento'], 'Y')];
                $tipocomprobantes = $this->views['ListNCFTipo']->model->all($where);
                if ($tipocomprobantes) {
                    echo json_encode(['tipocomprobantes' => $tipocomprobantes], JSON_THROW_ON_ERROR);
                } else {
                    echo '';
                }
                break;
            case 'busca_infocliente':
                $this->setTemplate(false);
                $tipocliente = $this->views['ListNCFTipo']->model->tipoCliente($_REQUEST['codcliente']);
                if ($tipocliente) {
                    echo json_encode(['infocliente' => $tipocliente], JSON_THROW_ON_ERROR);
                } else {
                    echo '';
                }
                break;
            case 'busca_infoproveedor':
                $this->setTemplate(false);
                $tipoproveedor = $this->views['ListNCFTipo']->model->tipoProveedor($_REQUEST['codproveedor']);
                if ($tipoproveedor) {
                    echo json_encode(['infoproveedor' => $tipoproveedor], JSON_THROW_ON_ERROR);
                } else {
                    echo '';
                }
                break;
            default:
                break;
        }
        return parent::execPreviousAction($action);
    }
}
