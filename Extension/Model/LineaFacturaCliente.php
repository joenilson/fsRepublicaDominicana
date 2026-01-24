<?php
/*
 * Copyright (C) 2026 Joe Nilson <joenilson at gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Extension\Model;

use Closure;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\CommonModelFunctions;

class LineaFacturaCliente
{
    /**
     * @var float
     */
    public $rdtaxcdt = 0.0;
    /**
     * @var string
     */
    public $rdtaxcodcdt;
    /**
     * @var string
     */
    public $rdtaxcodfirstplate;
    /**
     * @var string
     */
    public $rdtaxcodisc;
    /**
     * @var string
     */
    public $rdtaxcodlegaltip;
    /**
     * @var float
     */
    public $rdtaxfirstplate = 0.0;
    /**
     * @var float
     */
    public $rdtaxisc = 0.0;
    /**
     * @var float
     */
    public $rdtaxlegaltip = 0.0;

    /**
     * @var float
     */
    public $totalplustaxes;

    public function clear(): Closure
    {
        return function () {
            $this->rdtaxisc = 0.0;
            $this->rdtaxcdt = 0.0;
            $this->rdtaxlegaltip = 0.0;
            $this->rdtaxfirstplate = 0.0;
            $this->rdtaxcodisc = null;
            $this->rdtaxcodcdt = null;
            $this->rdtaxcodlegaltip = null;
            $this->rdtaxcodfirstplate = null;
        };
    }
}