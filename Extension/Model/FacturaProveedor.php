<?php
/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
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

use FacturaScripts\Dinamic\Model\NCFRango;
use FacturaScripts\Dinamic\Model\NCFTipoMovimiento;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Core\App\AppSettings;

class FacturaProveedor
{
    /**
     * @var date
     */
    public $ncffechavencimiento;
    /**
     * @var string
     */
    public $tipocomprobante;
    /**
     * @var string
     */
    public $ncftipopago;
    /**
     * @var string
     */
    public $ncftipomovimiento;
    /**
     * @var string
     */
    public $ncftipoanulacion;

    public function saveInsert()
    {
        return function () {
            $ArrayTipoNCFCompras = ['11','12','16','17'];
            $ncfrango = new NCFRango();
            $cliente = new Proveedor();
            $appSettings = new AppSettings;
            $actualProveedor = $cliente->get($this->codproveedor);
            $actualProveedor->idempresa = $appSettings::get('default', 'idempresa');
            $tipocomprobante = $this->tipocomprobante;

            if (in_array($tipocomprobante, $ArrayTipoNCFCompras, true)) {
                $ncfRangoToUse = $ncfrango->getByTipoComprobante($actualProveedor->idempresa, $tipocomprobante);
                $ncf = $ncfRangoToUse->generateNCF();
                $this->numproveedor = $ncf;
                $this->ncffechavencimiento = $ncfRangoToUse->fechavencimiento;
                $this->tipocomprobante = $ncfRangoToUse->tipocomprobante;
                $ncfRangoToUse->correlativo++;
                $ncfRangoToUse->save();
            }
        };
    }
}