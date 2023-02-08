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
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;

/**
 * Description of EditNCFTipoMovimiento
 *
 * @author joenilson
 */
class EditNCFTipoMovimiento extends EditController
{
    public function getModelClassName(): string
    {
        return 'NCFTipoMovimiento';
    }

    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['menu'] = 'RepublicaDominicana';
        $pagedata['title'] = 'edit-ncf-movement-type';
        $pagedata['icon'] = 'fas fa-tasks';

        return $pagedata;
    }

    public function execAfterAction($action)
    {
        switch ($action) {
            case 'busca_movimiento':
                $this->setTemplate(false);
                $tipomovimiento = new NCFTipoMovimiento();
                $where = [new DatabaseWhere('tipomovimiento', $_REQUEST['tipomovimiento'])];
                $movimientos = $tipomovimiento->all($where);
                if ($movimientos) {
                    //header('Content-Type: application/json');
                    echo json_encode(['movimientos' => $movimientos], JSON_THROW_ON_ERROR);
                } else {
                    echo '';
                }
                break;
            default:
                break;
        }
    }

}
