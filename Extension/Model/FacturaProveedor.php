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

use Closure;
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

    /**
     * @var string
     */
    public $numeroncf;

    public function saveBefore(): Closure
    {
        return function () {
            $ArrayTipoNCFCompras = ['11', '12', '16', '17'];
            $ncfrango = new NCFRango();
            $cliente = new Proveedor();
            $appSettings = new AppSettings;
            $actualProveedor = $cliente->get($this->codproveedor);
            $actualProveedor->idempresa = $appSettings::get('default', 'idempresa');
            $this->tipocomprobante = $_REQUEST['tipocomprobanter'] ?? $this->tipocomprobante;
            $this->numeroncf = $_REQUEST['numeroncfr'] ?? $this->numeroncf;
            $tipocomprobante = $this->tipocomprobante;
            if ($tipocomprobante !== null && in_array($tipocomprobante, $ArrayTipoNCFCompras, true)) {
                $ncfRangoToUse = $ncfrango->getByTipoComprobante($actualProveedor->idempresa, $tipocomprobante);
                if (!$ncfRangoToUse) {
                    $this->toolBox()->i18nLog()->error("no-ncf-range-for-$tipocomprobante");
                    return false;
                }
                $ncf = $ncfRangoToUse->generateNCF();
                $this->numeroncf = $ncf;
                $this->ncffechavencimiento = $ncfRangoToUse->fechavencimiento;
                $this->tipocomprobante = $ncfRangoToUse->tipocomprobante;
                $ncfRangoToUse->correlativo++;
                $ncfRangoToUse->save();
            }
            if (($this->tipocomprobante === '03' || $this->tipocomprobante === '04') === true) {
                $this->ncftipoanulacion = $_REQUEST['ncftipoanulacionr'];
                $this->ncffechavencimiento = $_REQUEST['ncffechavencimientor'];
            }
        };
    }
}