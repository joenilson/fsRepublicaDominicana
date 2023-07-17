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

class EditSettings
{
    public function execAfterAction(): Closure
    {
         return function($action) {
            //return $action;
            if ($action === 'fixfacturasprov') {
                $dataBase = new DataBase();
                $sqlType = " CONSTRAINT ";
                if (FS_DB_TYPE === 'MYSQL') {
                    $sqlType = " INDEX ";
                }
                $result = $dataBase->exec("ALTER TABLE FACTURASPROV DROP " . $sqlType .
                                                " IF EXISTS uniq_empresancf_facturasprov;");
                //Exec drop if exists index uniq_empresancf_facturasprov
                if ($result) {
                    self::toolBox()->i18nLog()->notice('success-drop-index-uniq_empresancf_facturasprov');
                } else {
                    self::toolBox()->i18nLog()->warning('error-drop-index-uniq_empresancf_facturasprov');
                }


            } elseif ($action === 'fixfacturascli') {
                self::toolBox()->i18nLog()->notice('fixfacturascli');
            }
         };
    }

}