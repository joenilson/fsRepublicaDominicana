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
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\ReportBalance;

/**
 * Description of FiscalReports
 *
 * @author joenilson
 */
class FiscalReports extends ListController
{
    /**
     *
     * @var array
     */
    private $companyList;

    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'fiscal-reports';
        $pageData['menu'] = 'reports';
        $pageData['submenu'] = 'dominican-republic';
        $pageData['icon'] = 'fas fa-hand-holding-usd';
        return $pageData;
    }

    /**
     * Add to indicated view a filter select with company list
     *
     * @param string $viewName
     */
    private function addCommonFilter(string $viewName)
    {
        if (empty($this->companyList)) {
            $this->companyList = $this->codeModel->all('empresas', 'idempresa', 'nombrecorto');
        }
        $this->addFilterSelect($viewName, 'idcompany', 'company', 'idcompany', $this->companyList);
        $this->addFilterNumber($viewName, 'channel', 'channel', 'channel', '=');
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');
    }

    /**
     *
     * @param string $viewName
     */
    protected function addGenerateButton(string $viewName)
    {
//        $this->addButton($viewName, [
//            'action' => 'generate-balances',
//            'color' => 'warning',
//            'confirm' => true,
//            'icon' => 'fas fa-magic',
//            'label' => 'generate'
//        ]);
    }

    /**
     * Inserts the views or tabs to display.
     */
    protected function createViews()
    {
//        $this->createViewsLedger();
        $this->createViewsAmount();
//        $this->createViewsBalance();
//        $this->createViewsPreferences();
    }

    /**
     * Inserts the view for amount balances.
     *
     * @param string $viewName
     */
    protected function createViewsAmount(string $viewName = 'FiscalReports')
    {
        $this->addView($viewName, 'FiscalReports', 'fiscal-reports', 'fas fa-hand-holding-usd');
        $this->addOrderBy($viewName, ['fecha'], 'fecha');
        $this->addOrderBy($viewName, ['idcompany', 'fecha'], 'company');
        $this->addSearchFields($viewName, ['ncf']);
        $this->addCommonFilter($viewName);
        $this->addGenerateButton($viewName);
    }

    /**
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'generate-balances':
                return $this->generateBalancesAction();
            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     *
     * @return bool
     */
    protected function generateBalancesAction(): bool
    {
        $total = 0;
        $ejercicioModel = new Ejercicio();
        foreach ($ejercicioModel->all() as $eje) {
            $this->generateBalances($total, $eje);
        }

        $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $total]);
        return true;
    }

    /**
     * Load values into special widget columns
     *
     * @param string $viewName
     */
    protected function loadWidgetValues($viewName)
    {
        $typeColumn = $this->views[$viewName]->columnForField('type');
        if ($typeColumn) {
            $typeColumn->widget->setValuesFromArray(ReportBalance::typeList());
        }

        $formatColumn = $this->views[$viewName]->columnForField('subtype');
        if ($formatColumn) {
            $formatColumn->widget->setValuesFromArray(ReportBalance::subtypeList());
        }
    }
}
