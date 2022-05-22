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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\NCFRango;
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
    
    public function saveInsert()
    {
        return function () {
            $ncfrango = new NCFRango();
            $cliente = new Cliente();
            $appSettins = new AppSettings;
            $actualCliente = $cliente->get($this->codcliente);
            $actualCliente->idempresa = $appSettins::get('default', 'idempresa');
            $tipocomprobante = "02";
            if ($actualCliente->tipocomprobante !== $this->tipocomprobante && $this->tipocomprobante !== null) {
                $tipocomprobante = $this->tipocomprobante;
            } elseif ($actualCliente->tipocomprobante !== $this->tipocomprobante) {
                $tipocomprobante = $actualCliente->tipocomprobante;
            }

            $ncfRangoToUse = $ncfrango->getByTipoComprobante($actualCliente->idempresa, $tipocomprobante);
            $ncf = $ncfRangoToUse->generateNCF();
            $this->numero2 = $ncf;
            $this->ncffechavencimiento = $ncfRangoToUse->fechavencimiento;
            $this->tipocomprobante = $ncfRangoToUse->tipocomprobante;
            $ncfRangoToUse->correlativo++;
            $ncfRangoToUse->save();
        };
    }

    public function all()
    {
        //parent::all();
        return function () {
            $this->facturarectnumero2 = '';
            if ($this->idfacturarect !== '') {
                $facturaRectificativa = $this->get($this->idfacturarect);
                $this->loadFromData(['facturarectnumero2' => 'SI' ]);
                $this->facturarectnumero2 = 'SI';
            } else {
                $this->loadFromData(['facturarectnumero2' => 'NO HAY']);
                $this->facturarectnumero2 = 'NO';
            }
            return $this;
        };
    }
}
