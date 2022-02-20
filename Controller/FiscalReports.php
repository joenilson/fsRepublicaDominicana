<?php
/*
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente;

/**
 * Description of FiscalReports
 *
 * @author joenilson
 */
class FiscalReports extends ListController
{
    /**
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['submenu'] = 'dominican-republic';
        $data['icon'] = 'fas fa-hand-holding-usd';
        $data['title'] = 'rd-fiscal-reports';
        return $data;
    }

    protected function createViews(): void
    {
        // needed dependencies
        new LineaFacturaCliente();

        $this->createViewsFiscalReportsConsolidated();
        $this->createViewsFiscalReports606();
        $this->createViewsFiscalReports607();
        $this->createViewsFiscalReports608();
    }

    /**
     * @param string $viewName
     */
    protected function createViewsFiscalReportsConsolidated(string $viewName = 'FiscalReports-consolidated')
    {
        $this->addView(
            $viewName,
            'Join\FiscalReports',
            'rd-fiscal-reports-consolidated',
            'fas fa-shipping-fast'
        );
        $this->addOrderBy($viewName, ['ncf'], 'ncf');
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'facturascli.fecha');
        $this->addCommonFilters($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * @param string $viewName
     */
    protected function createViewsFiscalReports608(string $viewName = 'FiscalReport608')
    {
        $this->addView($viewName,
            'Join\FiscalReport608',
            'rd-fiscal-reports-608',
            'fas fa-shopping-cart');
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'facturascli.fecha');
        $this->addCommonFilters($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * @param string $viewName
     */
    protected function createViewsFiscalReports607(string $viewName = 'FiscalReport607')
    {
        $this->addView($viewName, 'Join\FiscalReport607', 'rd-fiscal-reports-607', 'fas fa-copy');
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'facturascli.fecha');
        $this->addCommonFilters($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * @param string $viewName
     */
    protected function createViewsFiscalReports606(string $viewName = 'FiscalReport606')
    {
        $this->addView($viewName, 'Join\FiscalReport606', 'rd-fiscal-reports-606', 'fas fa-copy');
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'facturascli.fecha');
        $this->addCommonFilters($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * @param string $viewName
     */
    private function addCommonFilters(string $viewName)
    {
        $warehouses = Almacenes::codeModel();
        if (count($warehouses) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouses);
        } else {
            $this->views[$viewName]->disableColumn('warehouse');
        }
    }

    /**
     * @param string $viewName
     */
    private function disableButtons(string $viewName)
    {
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
        $this->setSettings($viewName, 'clickable', false);
    }
}
