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
     * @deprecated since 1.37, sin uso desde que la consulta de RNC usa getExternalAPI(). Se eliminará en 1.38.
     */
    public $wsdlDGII = 'https://www.dgii.gov.do/wsMovilDGII/WSMovilDGII.asmx?WSDL';

    /**
     * @var string
     * @deprecated since 1.37, solo lo usaba el scraping de la DGII. Se eliminará en 1.38.
     */
    public $searchInitiator = 'https://dgii.gov.do/herramientas/consultas/Paginas/RNC.aspx';

    /**
     * @var string
     * @deprecated since 1.37, solo lo usaba el scraping de la DGII. Se eliminará en 1.38.
     */
    public $searchProcessor = 'https://dgii.gov.do/app/WebApps/ConsultasWeb2/ConsultasWeb/consultas/rnc.aspx';

    private $externalAPI = 'https://apiv1.artesanik.com/contribuyente';

    public function getExternalAPI($queryParam)
    {
        $ch = curl_init();
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',

        );
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl/FacturaScripts/2022.4');
        curl_setopt($ch, CURLOPT_URL, $this->externalAPI."?query=$queryParam");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $result = \json_decode($response, false, 512, JSON_THROW_ON_ERROR);
        curl_close($ch);
        return $result;
    }

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
    ): string {
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
            new DataBaseWhere('cifnif', $item->RGE_RUC)
        ];
        $cli->all($where);
        if ($cli[0] !== null) {
            $cliente = $cli[0];
            $item->existe = true;
            $item->codcliente = $cliente->codcliente;
        }
    }

    /**
     * @deprecated since 1.37, no tiene llamadas: el scraping de la DGII se reemplazó por getExternalAPI().
     *             Se eliminará en 1.38 junto con $wsdlDGII, $searchInitiator y $searchProcessor.
     */
    private function curlSearch($page, $postData = '')
    {
        $result = "";
        $h = curl_init();
        curl_setopt($h, CURLOPT_URL, $page);
        curl_setopt(
            $h,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0'
        );
        curl_setopt($h, CURLOPT_REFERER, $this->searchInitiator);
        if ($postData !== '') {
            curl_setopt($h, CURLOPT_POST, true);
            curl_setopt($h, CURLOPT_POSTFIELDS, $postData);
        }

        curl_setopt($h, CURLOPT_HEADER, false);
        curl_setopt($h, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($h);
        curl_close($h);
        return $result;
    }
}