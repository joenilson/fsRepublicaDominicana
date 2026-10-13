<?php
/*
 * Copyright (C) 2022 Joe Nilson <joenilson at gmail dot com>
 * 
 * fsRepublicaDominicana is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * fsRepublicaDominicana is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with fsRepublicaDominicana. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Extension\Model;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Tools;


class Producto
{
    /**
     * This field is to determinate if the product is a service or a good
     * @var boolean
     */
    public $esservicio;

    /**
     * Grados de alcohol del producto (%), usado en el ISC específico de bebidas alcohólicas.
     * @var float
     */
    public $grados_alcohol = 0.0;

    /**
     * Contenido en litros de cada unidad vendida, usado en el ISC específico de bebidas alcohólicas.
     * @var float
     */
    public $contenido_litros = 0.0;
}
