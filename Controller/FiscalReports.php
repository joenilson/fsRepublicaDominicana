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
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Lib\ListFilter\PeriodTools;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\CommonFunctionsDominicanRepublic;

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
        $this->exportManager->addOption('dgii', 'txt-export', 'fas fa-file-alt');
    }

    public function execAfterAction($action)
    {
        parent::execAfterAction($action);
        $periodStartDate = \date('01-m-Y');
        $periodEndDate = \date('d-m-Y');
        if ($this->request->request->get('filterfecha') !== null) {
            PeriodTools::applyPeriod($this->request->request->get('filterfecha'), $periodStartDate, $periodEndDate);
        }
        $startDate = $this->request->request->get('filterstartfecha') ?? $periodStartDate;
        $endDate = $this->request->request->get('filterendfecha') ?? $periodEndDate;
        $year = substr($startDate, 6, 4);
        $month = substr($startDate, 3, 2);
        $commonFunctions = new CommonFunctionsDominicanRepublic();
        $option = $this->request->get('option');
        $actualTab = $this->request->get('activetab');
        switch ($actualTab) {
            case "FiscalReport606":
                $whereReport = [
                    new DataBaseWhere('facturasprov.fecha', $startDate, '>='),
                    new DataBaseWhere('facturasprov.fecha', $endDate, '<='),
                ];
                $reportCode = 606;
                break;
            case "FiscalReport607":
                $whereReport = [
                    new DataBaseWhere('facturascli.fecha', $startDate, '>='),
                    new DataBaseWhere('facturascli.fecha', $endDate, '<='),
                ];
                $reportCode = 607;
                break;
            case "FiscalReport608":
                $whereReport = [
                    new DataBaseWhere('facturascli.fecha', $startDate, '>='),
                    new DataBaseWhere('facturascli.fecha', $endDate, '<='),
                ];
                $reportCode = 608;
                break;
            default:
                $whereReport = [
                    new DataBaseWhere('facturascli.fecha', $startDate, '>='),
                    new DataBaseWhere('facturascli.fecha', $endDate, '<='),
                ];
                $reportCode = '';
                break;
        }
        if ($action === 'export' && $option === 'dgii' && $reportCode !== '') {
            $this->setTemplate(false);
            $fileName = 'DGII_'.$reportCode.'_'.$this->empresa->cifnif.'_'.$year.'_'.$month.'.txt';
            $commonFunctions->exportTXT($reportCode, $fileName, $this->empresa->cifnif, $year, $month, $whereReport);
            $this->response->headers->set('Content-type', 'text/plain');
            $this->response->headers->set('Content-Disposition', 'attachment;filename=' . $fileName);
            $this->response->setContent(file_get_contents(\FS_FOLDER . DIRECTORY_SEPARATOR . $fileName));
        }
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
        $this->addCommonSearchFields($viewName);
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
        $this->addCommonSearchFields($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * @param string $viewName
     */
    protected function createViewsFiscalReports607(string $viewName = 'FiscalReport607')
    {
        $this->addView($viewName, 'Join\FiscalReport607', 'rd-fiscal-reports-607', 'fas fa-copy');
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'facturascli.fecha');
        $this->addCommonSearchFields($viewName);
        $this->disableButtons($viewName);
    }

    /**
     * @param string $viewName
     */
    protected function createViewsFiscalReports606(string $viewName = 'FiscalReport606')
    {
        $this->addView($viewName, 'Join\FiscalReport606', 'rd-fiscal-reports-606', 'fas fa-copy');
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'facturasprov.fecha');
        $this->addSearchFields($viewName, ['numero2', 'cifnif', 'fecha', 'estado'], 'fecha');
        $this->addOrderBy($viewName, ['facturasprov.fecha'], 'fecha');
        $this->addOrderBy($viewName, ['facturasprov.numproveedor'], 'ncf');
        $this->addOrderBy($viewName, ['cifnif'], 'cifnif');
        $this->disableButtons($viewName);
    }

    /**
     * @param string $viewName
     */
    private function addCommonSearchFields(string $viewName)
    {
        $this->addSearchFields($viewName, ['numero2', 'cifnif', 'fecha', 'estado'], 'fecha');
        $this->addOrderBy($viewName, ['facturascli.fecha'], 'fecha');
        $this->addOrderBy($viewName, ['facturascli.numero2'], 'ncf');
        $this->addOrderBy($viewName, ['cifnif'], 'cifnif');
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

    /**
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $periodStartDate = \date('Y-m-01');
        $periodEndDate = \date('Y-m-d');
        PeriodTools::applyPeriod('last-month', $periodStartDate, $periodEndDate);
        if ($this->request->request->get('filterfecha') !== null) {
            PeriodTools::applyPeriod($this->request->request->get('filterfecha'), $periodStartDate, $periodEndDate);
        }
        switch ($viewName) {
            case 'FiscalReport606':
                $where = [
                    new DataBaseWhere('facturasprov.fecha', $periodStartDate, '>='),
                    new DataBaseWhere('facturasprov.fecha', $periodEndDate, '<='),
                ];
                $view->loadData('', $where);
                break;
            case 'FiscalReport607':
            case 'FiscalReport608':
            case 'FiscalReports-consolidated':
                $where = [
                    new DataBaseWhere('facturascli.fecha', $periodStartDate, '>='),
                    new DataBaseWhere('facturascli.fecha', $periodEndDate, '<='),
                ];
                $view->loadData('', $where);
                break;
            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
