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

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\WebserviceDgii;

class ConsultaDGII extends ListController
{
    protected function createViews()
    {
        // TODO: Implement createViews() method.
    }

    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'busca_rnc':
                $this->setTemplate(false);
                $rncSearch = new WebserviceDgii();
                $resultado = $rncSearch->wdslSearch(0, $_REQUEST['cifnif']);
                if ($resultado) {
                    echo $resultado;
                } else {
                    echo '{"RGE_ERROR": "true"}';
                }
                break;
            default:
                break;
        }
        parent::execPreviousAction($action);
    }
}