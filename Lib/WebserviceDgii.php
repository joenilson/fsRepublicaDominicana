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
use simplehtmldom\HtmlDocument;

class WebserviceDgii
{
    /**
     * @var string
     */
    public $wsdlDGII = 'https://www.dgii.gov.do/wsMovilDGII/WSMovilDGII.asmx?WSDL';

    public $searchInitiator = 'https://dgii.gov.do/herramientas/consultas/Paginas/RNC.aspx';
    public $searchProcessor = 'https://dgii.gov.do/app/WebApps/ConsultasWeb2/ConsultasWeb/consultas/rnc.aspx';

    private $viewState;
    private $viewStateGenerator;
    private $eventValidation;

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
            new DataBaseWhere('cifnif', $item->RGE_RUC)
        ];
        $cli->all($where);
        if ($cli[0] !== null) {
            $cliente = $cli[0];
            $item->existe = true;
            $item->codcliente = $cliente->codcliente;
        }
    }

    private function curlSearch($page, $postData = '')
    {
        $result = "";
        $h = curl_init();
        curl_setopt($h, CURLOPT_URL, $page);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0');
        curl_setopt($curl, CURLOPT_REFERER, $this->searchInitiator);
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

    //Pedimos que nos den el VIEWSTATE y el EVENTVALIDATION a la página de busqueda
    public function autorizacionWeb(): void
    {
        $result = $this->curlSearch($this->searchInitiator);
        $html = new HtmlDocument();
        $html->load($result);

        $this->viewState = $html->getElementById('#__VIEWSTATE', 0)->value;
//        var_dump($html->getElementById('#__VIEWSTATE', 0)->value);

        $this->viewStateGenerator = $html->getElementById('#__VIEWSTATEGENERATOR', 0)->value;

        $this->eventValidation = $html->getElementById('#__EVENTVALIDATION', 0)->value;
//        var_dump($html->getElementById('#__EVENTVALIDATION', 0)->value);
    }

    //Si la busqueda no es por RNC y en su lugar es por nombre actualizamos viewstate y eventvalidation
    public function actualizarAutorizacion($tipoBusqueda)
    {
        $post = array(
            '__EVENTTARGET' => 'rbtnlTipoBusqueda$1',
            '__EVENTARGUMENT' => "",
            '__LASTFOCUS' => "",
            '__VIEWSTATE' => $this->viewState,
            '__VIEWSTATEGENERATOR' => $this->viewStateGenerator,
            '__EVENTVALIDATION' => $this->eventValidation,
            'rbtnlTipoBusqueda' => $tipoBusqueda,
            'txtRncCed' => ''
        );

        $query = http_build_query($post);
        $result = $this->curlSearch($this->searchProcessor, $query);

        $doc = new HtmlDocument();
        $html = $doc->load($result);
        $this->viewState = $html->getElementById('#__VIEWSTATE', 0)->value;
        $this->eventValidation = $html->getElementById('#__EVENTVALIDATION', 0)->value;
    }

    public function buscar($rnc = '', $nombre = '')
    {
        $resultados = '';
        $this->autorizacionWeb();
        $tipoBusqueda = (!empty($rnc)) ? 0 : 1;
        $valorBuscar = (!empty($rnc)) ? $rnc : strtoupper(trim($nombre));
        $this->rnc = $rnc;
        $this->nombre = $nombre;
        $campo = (!empty($rnc)) ? 'ctl00$cphMain$txtRNCCedula' : 'ctl00$cphMain$txtRazonSocial';
        $smMain = (!empty($rnc)) ? 'ctl00$cphMain$upBusqueda|ctl00$cphMain$btnBuscarPorRNC': '';
        $boton = (!empty($rnc)) ? 'ctl00$cphMain$btnBuscarPorRNC' : 'ctl00$cphMain$btnBuscarPorRazonSocial';

        if ($tipoBusqueda === 1) {
            $this->actualizarAutorizacion($tipoBusqueda);
        }

        $post = array(
            '__EVENTTARGET' => "",
            '__EVENTARGUMENT' => "",
            '__LASTFOCUS' => "",
            '__VIEWSTATE' => $this->viewState,
            '__VIEWSTATEGENERATOR' => $this->viewStateGenerator,
            '__EVENTVALIDATION' => $this->eventValidation,
            'rbtnlTipoBusqueda' => $tipoBusqueda,
            'ctl00$smMain' => $smMain,
            '__ASYNCPOST' => 'true',
            $campo => $valorBuscar,
            $boton => 'BUSCAR'
        );

        $query = http_build_query($post);
        $result = $this->curlSearch($this->searchProcessor, $query);

        $doc = new HtmlDocument();
        $html = $doc->load($result);

        $vacio = trim($html->getElementById('#cphMain_lblInformacion', 0)->value);

        if ($vacio !== '') {
            $resultados = $html->getElementById('#cphMain_lblInformacion', 0)->value;
            return $resultados;
        } else {
            $cabeceras = array();
            $detalles = array();
            $table = $html->getElementById('#cphMain_dvDatosContribuyentes');
            $tbody = $table->find('tbody');
            foreach ($html->getElementById('#cphMain_dvDatosContribuyentes') as $lista) {
                print_r($lista);
                //$cabeceras = $this->loopLista($lista);
            }
            $this->cabecera = $cabeceras;
            $lista_interna = 0;
            foreach ($html->find('.GridItemStyle') as $lista) {
                $detalles[$lista_interna] = $this->loopLista($lista);
                $lista_interna++;
            }
            foreach ($html->find('.bg_celdas_alt') as $lista) {
                $detalles[$lista_interna] = $this->loopLista($lista);
                $lista_interna++;
            }
            $this->detalle = $detalles;
//            $this->total_cabecera = count($cabeceras);
//            $this->total_resultados = count($this->detalle);
            return $cabeceras;
        }
    }

    private function loopLista($lista)
    {
        $array = array();
        foreach ($lista->find('td') as $item) {
            $array[] = $item->plaintext;
        }
        return $array;
    }
}