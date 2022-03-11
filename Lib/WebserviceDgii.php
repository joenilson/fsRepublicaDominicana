<?php
/*
 * Copyright (C) 2022 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Cliente;

class WebserviceDgii
{
    /**
     * @var string
     */
    public $wsdlDGII = 'https://www.dgii.gov.do/wsMovilDGII/WSMovilDGII.asmx?WSDL';

    /**
     * @return void
     * @throws \SoapFault
     */
    public function wdslConnection(): object
    {
        return new \SoapClient($this->wsdlDGII, array('encoding' => 'UTF-8'));
    }

    /**
     * @param object $wsdlConn
     * @param integer $patronBusqueda
     * @param string $paramValue
     * @param integer $inicioFilas
     * @param integer $filaFilas
     * @return void
     * @throws \JsonException
     */
    public function wdslSearch(
        int $patronBusqueda = 0,
        string $paramValue = '',
        int $inicioFilas = 1,
        int $filaFilas = 1
    ): string
    {
        $opciones = [
            'patronBusqueda' => $patronBusqueda,
            'value' => $paramValue,
            'inicioFilas' => $inicioFilas,
            'filaFilas' => $filaFilas,
            'IMEI' => 0
        ];

        $wsdlConn = $this->wdslConnection();

        $result = $wsdlConn->__soapCall('GetContribuyentes', ['GetContribuyentes' => $opciones]);
        $list = array();
        $getResult = explode("@@@", $result->GetContribuyentesResult);

        return $getResult[0];
    }

    /**
     * @param object $wsdlConn
     * @param string $paramValue
     * @return object
     */
    public function wdslSearchCount(object $wsdlConn, string $paramValue = ''): object
    {
        $opciones = [
            'value' => $paramValue,
            'IMEI' => 0
        ];

        $result = $wsdlConn->__soapCall('GetContribuyentesCount', ['GetContribuyentesCount' => $opciones]);
        return $result->GetContribuyentesCountResult;
    }

    /**
     * @param object $item
     * @return void
     */
    public function buscarCliente(&$item): void
    {
        $item->existe = false;
        $item->codcliente = '';
        $cli = new Cliente();
        $where = [
            new DataBaseWhere('cifnif' , $item->RGE_RUC)
        ];
        $cli->all($where);
        if ($cli[0] !== null) {
            $cliente = $cli[0];
            $item->existe = true;
            $item->codcliente = $cliente->codcliente;
        }
    }
}