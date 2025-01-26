<?php
/*
 * Copyright (C) 2023 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Extension\Controller;

use Closure;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;

class EditSettings
{
    public function execAfterAction(): Closure
    {
         return function($action) {
            //return $action;
            if ($action === 'fixfacturasprov') {
                $dataBase = new DataBase();
                $sqlType = " CONSTRAINT ";
                if (strtoupper(FS_DB_TYPE) === 'MYSQL') {
                    $sqlType = " INDEX ";
                    $dataBase->exec("set FOREIGN_KEY_CHECKS=0;");
                }
                $result = $dataBase->exec("ALTER TABLE facturasprov DROP " . $sqlType .
                                                " IF EXISTS uniq_empresancf_facturasprov;");
                if (strtoupper(FS_DB_TYPE) === 'MYSQL') {
                    $dataBase->exec("set FOREIGN_KEY_CHECKS=1;");
                }
                if ($result) {
                    Tools::log()->notice('success-drop-index-uniq_empresancf_facturasprov');
                } else {
                    Tools::log()->warning('error-drop-index-uniq_empresancf_facturasprov');
                }


            } elseif ($action === 'fixfacturascli') {
                Tools::log()->notice('fixfacturascli');
            }
         };
    }

}