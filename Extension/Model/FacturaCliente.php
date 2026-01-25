<?php
/**
 * Copyright (C) 2020 joenilson.
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Extension\Model;

use Cassandra\Date;
use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\NCFRango;
use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Dinamic\Model\NCFTipoMovimiento;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\CommonModelFunctions;

/**
 * Description of FacturaCliente
 *
 * @author joenilson
 */
class FacturaCliente
{
    /**
     * @var Date
     */
    public $ncffechavencimiento;
    /**
     * @var string
     */
    public $tipocomprobante;
    /**
     * @var string
     */
    public $numeroncf;
    /**
     *
     * @var string
     */
    public $ncftipopago;
    /**
     *
     * @var string
     */
    public $ncftipomovimiento;
    /**
     *
     * @var string
     */
    public $ncftipoanulacion;

    /**
     * @var string
     */
    public $facturarectnumero2;

    public $ecf_trackid = null;

    public $ecf_estado_dgii = null;

    public $ecf_codigo_seguridad = null;

    /**
     * @var \DateTime
     */
    public $ecf_fecha_firma = null;

    public $ecf_pdf_firmado = null;
    public $ecf_xml_firmado = null;

    public $totalexento;

    public $totaladdedtaxes;

    public $totalplustaxes;

    public function saveBefore(): Closure
    {
        return function () {
            if (null !== $this->codigorect && $this->idfactura === null) {
                $this->cleanRefundData();
            }
            $cliente = new Cliente();
            $actualCliente = $cliente::find($this->codcliente);

            if (null === $actualCliente) {
                Tools::log()->error("no-customer-found");
                return false;
            }

            $actualCliente->idempresa = Tools::settings('default', 'idempresa');
            $this->tipocomprobante = $this->tipocomprobante ?? $actualCliente->tipocomprobante;
            $this->tipocomprobante = $_REQUEST['tipocomprobanter'] ?? $this->tipocomprobante;
            $this->ecf_fecha_firma = $_REQUEST['ecf_fecha_firmar'] ?? $this->ecf_fecha_firma;
            $this->ecf_fecha_firma = (empty($this->ecf_fecha_firma) ? null : $this->ecf_fecha_firma);
            if ($this->tipocomprobante !== '' && \in_array($this->numeroncf, ['', null], true)) {
                $tipocomprobante = "02";
                if (($this->tipocomprobante !== null) === true) {
                    $tipocomprobante = $this->tipocomprobante;
                } elseif (($this->tipocomprobante === null) === true) {
                    $tipocomprobante = $actualCliente->tipocomprobante;
                }

                $ncfrango = new NCFRango();
                if (!CommonModelFunctions::setCFRango($actualCliente, $ncfrango, $tipocomprobante, $this)) return false;

                $arrayNCFTypes = ['03','04'];
                if (in_array($this->tipocomprobante, $arrayNCFTypes) === true) {
                    $this->ncftipoanulacion = $_REQUEST['ncftipoanulacionr'] ?? $this->ncftipoanulacion;
                    $this->ncffechavencimiento = $_REQUEST['ncffechavencimientor'] ?? $this->ncffechavencimiento;
                }
            }
            $this->ncffechavencimiento = ($this->ncffechavencimiento === '') ? null : $this->ncffechavencimiento;
            return $this;
        };
    }

    public function all(): Closure
    {
        return function () {
            $this->facturarectnumero2 = '';
            /** @var \FacturaScripts\Dinamic\Model\FacturaCliente $this */
            if ($this->idfacturarect !== '' || $this->idfacturarect !== null) {
                $this->loadFromData(['facturarectnumero2' => 'SI' ]);
                $this->facturarectnumero2 = $this::findWhere(['idfacturarect', $this->idfacturarect])->numero2;
            } else {
                $this->loadFromData(['facturarectnumero2' => 'NO HAY']);
            }
            return $this;
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
