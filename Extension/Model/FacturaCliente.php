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

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\NCFRango;
use FacturaScripts\Dinamic\Model\NCFTipo;
use FacturaScripts\Dinamic\Model\NCFTipoMovimiento;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Core\App\AppSettings;

/**
 * Description of FacturaCliente
 *
 * @author joenilson
 */
class FacturaCliente
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

    public function saveBefore(): Closure
    {
        return function () {
            if (null !== $this->codigorect && $this->idfactura === null) {
                $this->cleanRefundData();
            }

            $cliente = new Cliente();
            $appSettins = new AppSettings;
            $actualCliente = $cliente->get($this->codcliente);
            $actualCliente->idempresa = $appSettins::get('default', 'idempresa');
            $this->tipocomprobante = $this->tipocomprobante ?? $actualCliente->tipocomprobante;
            $this->tipocomprobante = $_REQUEST['tipocomprobanter'] ?? $this->tipocomprobante;
            if ($this->tipocomprobante !== '' && \in_array($this->numeroncf, ['', null], true)) {
                $tipocomprobante = "02";
                if (($this->tipocomprobante !== null) === true) {
                    $tipocomprobante = $this->tipocomprobante;
                } elseif (($this->tipocomprobante === null) === true) {
                    $tipocomprobante = $actualCliente->tipocomprobante;
                }

                if ('' === $this->numeroncf) {
                    $ncfrango = new NCFRango();
                    $ncfRangoToUse = $ncfrango->getByTipoComprobante($actualCliente->idempresa, $tipocomprobante);
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

                //Verificamos si $this->tipoComprobante es una nota de crédito o debito
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
            if ($this->idfacturarect !== '') {
                $facturaRectificativa = $this->get($this->idfacturarect);
                $this->loadFromData(['facturarectnumero2' => 'SI' ]);
                $this->facturarectnumero2 = $facturaRectificativa->numero2;
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
            $ncftipocomprobante->loadFromCode($this->tipocomprobante);
            return $ncftipocomprobante->descripcion;
        };
    }
    protected function cleanRefundData()
    {
        return function () {
            $this->numeroncf = '';
            $this->tipocomprobante = null;
            $this->ncffechavencimiento = null;
            $this->ncftipomovimiento = null;
            $this->ncftipopago = null;
        };
    }
}
