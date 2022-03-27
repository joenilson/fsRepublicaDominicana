<?php
/*
 * Copyright (C) 2022 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\WebserviceDgii;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\RNCDGIIDB;

class ConsultaDGII extends ListController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'accounting';
        $pageData['submenu'] = 'Republica Dominicana';
        $pageData['title'] = 'ncf-dgii-db';
        $pageData['icon'] = 'fas fa-file-archive';

        return $pageData;
    }

    public function addUpdateButton($viewName)
    {
        $updateButton = [
            'color' => 'warning',
            'icon' => 'fas fa-file-archive',
            'label' => 'update-dgiidb-data',
            'title' => 'update-dgiidb-data',
            'type' => 'action',
            'action' => 'update-data',
            'hint' => 'update-dgiidb-data',
            'confirm' => true
        ];
        $this->addButton($viewName, $updateButton);
    }

    protected function createViews()
    {
        $this->addView('ConsultaDGII', 'RNCDGIIDB');
        $this->addSearchFields('ConsultaDGII', ['rnc', 'nombre', 'razonsocial', 'estado'], 'rnc');
        $this->addOrderBy('ConsultaDGII', ['rnc', ], 'rnc');
        $this->addOrderBy('ConsultaDGII', ['nombre'], 'nombre');
        $this->addOrderBy('ConsultaDGII', ['razonsocial'], 'razonsocial');
        $this->addOrderBy('ConsultaDGII', ['estado'], 'estado');
        $this->addUpdateButton('ConsultaDGII');

        $estados = [
            ['code' => 'ACTIVO', 'description' => 'Activo'],
            ['code' => 'SUSPENDIDO', 'description' => 'Suspendido'],
            ['code' => 'DADO DE BAJA', 'description' => 'Dado de Baja'],
            ['code' => 'RECHAZADO', 'description' => 'Rechazado'],
            ['code' => 'ANULADO', 'description' => 'Anulado'],
            ['code' => 'CESE TEMPORAL', 'description' => 'Cese Temporal'],
        ];
        $this->addFilterSelect('ConsultaDGII', 'estado', 'status', 'estado', $estados);
        $this->addFilterPeriod('ConsultaDGII', 'inicioactividad', 'period', 'inicioactividad');

        $this->setSettings('ConsultaDGII', 'clickable', false);
    }

    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'update-data':
                $this->views['ConsultaDGII']->model->updateFile();
                $this->views['ConsultaDGII']->model->clear();
                $this->toolBox()->i18nLog()->notice('updated-rnc-data');

                break;
            case 'busca_rnc':
                $this->setTemplate(false);
                $this->views['ConsultaDGII']->model->clear();
                $registros = $this->views['ConsultaDGII']->model->count();
                if ($registros !== 0) {
                    $resultado = $this->views['ConsultaDGII']->model->get($_REQUEST['cifnif']);
                    if ($resultado) {
                        $arrayResultado = [];
                        $arrayResultado["RGE_RUC"] = $resultado->rnc;
                        $arrayResultado["RGE_NOMBRE"] = $resultado->nombre;
                        $arrayResultado["NOMBRE_COMERCIAL"] = $resultado->razonsocial;
                        $arrayResultado["ESTATUS"] = $resultado->estado;
                        echo json_encode($arrayResultado);
                    } else {
                        echo '{"RGE_ERROR": "true", "message": "RNC No encontrado."}';
                    }
                } else {
                    echo '{"RGE_ERROR": "true", "message": "La tabla de RNC está vacia, por favor Ejecute la actualización de RNC."}';
                }
                break;
            default:
                break;
        }
        parent::execPreviousAction($action);
    }


}