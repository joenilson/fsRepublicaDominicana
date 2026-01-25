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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\NCFRango;
use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Dinamic\Model\NCFTipoMovimiento;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\CommonModelFunctions;

/**
 * @property $codproveedor
 */
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

    public $ecf_recibido = null;

    public $ecf_aprobacion_comercial = null;

    public $ecf_codigo_seguridad = null;

    public $ecf_fecha_firma = null;

    public $ecf_pdf_firmado = null;
    public $ecf_xml_firmado = null;

    public $totalexento;

    public $totaladdedtaxes;

    public $totalplustaxes;

    public function saveBefore(): Closure
    {
        return function () {
            //$ArrayTipoNCFCompras = ['','','04','05','11', '12', '16', '17','31','32','34','35','46','47'];
            $ArrayTipoNCFCompras = ['11','13','41','43'];
            $ncfrango = new NCFRango();
            $proveedor = new Proveedor();
            $actualProveedor = $proveedor::find($this->codproveedor);

            if (null === $actualProveedor) {
                Tools::log()->error("no-supplier-found");
                return false;
            }

            $actualProveedor->idempresa = Tools::settings('default', 'idempresa');
            $this->tipocomprobante = $_REQUEST['tipocomprobanter'] ?? $this->tipocomprobante;
            $this->numeroncf = $_REQUEST['numeroncfr'] ?? $this->numeroncf;
            $this->ecf_fecha_firma = $_REQUEST['ecf_fecha_firmar'] ?? $this->ecf_fecha_firma;
            $this->ecf_fecha_firma = (empty($this->ecf_fecha_firma) ? null : $this->ecf_fecha_firma);
            $this->ecf_codigo_seguridad = $_REQUEST['ecf_codigo_seguridadr'] ?? $this->ecf_codigo_seguridad;
            $tipocomprobante = $this->tipocomprobante;
            if ($tipocomprobante && in_array($tipocomprobante, $ArrayTipoNCFCompras, true)) {
                if (!CommonModelFunctions::setCFRango($actualProveedor, $ncfrango, $tipocomprobante, $this)) {
                    return false;
                }
            }
            if (in_array($this->tipocomprobante, ['03', '04', '33', '34'], true)) {
                $this->ncftipoanulacion = $_REQUEST['ncftipoanulacionr'] ?? $this->ncftipoanulacion;
                $this->ncffechavencimiento = $_REQUEST['ncffechavencimientor'] ?? $this->ncffechavencimiento;
            }
            $this->ncffechavencimiento = $this->ncffechavencimiento !== '' ? $this->ncffechavencimiento : null;
        };
    }

    public function descripcionTipoComprobante(): Closure
    {
        return function () {
            $ncftipocomprobante = new NCFTipo();
            $ncftipocomprobante->load($this->tipocomprobante);
            return $ncftipocomprobante->descripcion;
        };
    }

    protected function cleanRefundData(): Closure
    {
        return function () {
            $this->numeroncf = '';
            $this->tipocomprobante = null;
            $this->ncffechavencimiento = null;
            $this->ncftipomovimiento = null;
            $this->ncftipopago = null;
            $this->ecf_fecha_firma = null;
            $this->ecf_codigo_seguridad = null;
        };
    }
}