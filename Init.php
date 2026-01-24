<?php
/*
 * Copyright (C) 2020-2024 Joe Zegarra.
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\AjaxForms\PurchasesLineHTML;
use FacturaScripts\Core\Lib\AjaxForms\PurchasesFooterHTML;
use FacturaScripts\Core\Lib\AjaxForms\SalesFooterHTML;
use FacturaScripts\Core\Lib\AjaxForms\SalesLineHTML;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Controller\SendTicket;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\Tickets\RepDominicana;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoAdicional;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoProducto;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFRango;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoPago;

/**
 * Description of Init
 *
 * @author Joe Zegarra
 */
class Init extends InitClass
{
    public function init(): void
    {
        $this->loadExtension(new Extension\Model\Cliente());
        $this->loadExtension(new Extension\Model\PedidoCliente());
        $this->loadExtension(new Extension\Model\AlbaranCliente());
        $this->loadExtension(new Extension\Model\FacturaCliente());
        $this->loadExtension(new Extension\Model\LineaPedidoCliente());
        $this->loadExtension(new Extension\Model\LineaAlbaranCliente());
        $this->loadExtension(new Extension\Model\LineaFacturaCliente());
        $this->loadExtension(new Extension\Model\PedidoProveedor());
        $this->loadExtension(new Extension\Model\AlbaranProveedor());
        $this->loadExtension(new Extension\Model\FacturaProveedor());
        $this->loadExtension(new Extension\Model\LineaPedidoProveedor());
        $this->loadExtension(new Extension\Model\LineaAlbaranProveedor());
        $this->loadExtension(new Extension\Model\LineaFacturaProveedor());
        $this->loadExtension(new Extension\Model\Producto());
        $this->loadExtension(new Extension\Controller\EditCliente());
        $this->loadExtension(new Extension\Controller\EditProveedor());
        $this->loadExtension(new Extension\Controller\EditFacturaCliente());
        $this->loadExtension(new Extension\Controller\EditFacturaProveedor());
        $this->loadExtension(new Extension\Controller\EditProducto());
        $this->loadExtension(new Extension\Controller\EditSettings());

        $route = Tools::config('route');
        AssetManager::addJs($route . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonDomFunctions.js');

        SalesLineHTML::addMod(new Mod\SalesLineHTMLMod());
        SalesFooterHTML::addMod(new Mod\SalesFooterHTMLMod());
        PurchasesLineHTML::addMod(new Mod\PurchasesLineHTMLMod());
        PurchasesFooterHTML::addMod(new Mod\PurchasesFooterHTMLMod());
        Calculator::addMod(new Mod\CalculatorMod());

        if (Plugins::isEnabled('Tickets')) {
            SendTicket::addFormat(RepDominicana::class, 'FacturaCliente', 'ncf-dominicana');
        }
    }

    private function actualizarEstados(): void
    {
        $arrayDocumentos = [
            'FacturaCliente',
            'FacturaProveedor',
            'AlbaranCliente',
            'AlbaranProveedor',
            'PedidoCliente',
            'PedidoProveedor'
        ];
        $estados = new EstadoDocumento();

        foreach ($arrayDocumentos as $documento) {
            $lista = $estados::all(
                [
                    new DataBaseWhere('nombre', 'Anulada'),
                    new DataBaseWhere('tipodoc', $documento)
                ]
            );

            if (count($lista) === 0) {
                $nuevoDocumento = new EstadoDocumento();
                $nuevoDocumento->nombre = 'Anulada';
                $nuevoDocumento->tipodoc = $documento;
                $nuevoDocumento->icon = 'fa-solid fa-handshake-slash';
                $nuevoDocumento->editable = false;
                $nuevoDocumento->bloquear = true;
                $nuevoDocumento->actualizastock = 0;
                $nuevoDocumento->predeterminado = false;
                $nuevoDocumento->save();
            }
        }
    }

    private function actualizarEstadoFacturaElectronica(): void
    {
        $arrayDocumentos = [
            'FacturaCliente',
            'FacturaProveedor'
        ];

        $estados = new EstadoDocumento();

        foreach ($arrayDocumentos as $documento) {
            $listaEspera = $estados::all(
                [
                    new DataBaseWhere('nombre', 'En espera'),
                    new DataBaseWhere('tipodoc', $documento)
                ]
            );

            if (isset($listaEspera)) {
                foreach ($listaEspera as $espera) {
                    $espera->delete();
                }
            }

//            if (count($listaEspera) === 0) {
//                $nuevoDocumento = new EstadoDocumento();
//                $nuevoDocumento->nombre = 'En espera';
//                $nuevoDocumento->tipodoc = $documento;
//                $nuevoDocumento->icon = 'fa-solid fa-hourglass-start';
//                $nuevoDocumento->editable = false;
//                $nuevoDocumento->bloquear = true;
//                $nuevoDocumento->actualizastock = 0;
//                $nuevoDocumento->predeterminado = false;
//                $nuevoDocumento->save();
//            }

            $listaFirma = $estados::all(
                [
                    new DataBaseWhere('nombre', 'Firmada'),
                    new DataBaseWhere('tipodoc', $documento)
                ]
            );


            if (isset($listaFirma)) {
                foreach ($listaFirma as $firma) {
                    $firma->delete();
                }
            }

//            if (count($listaFirma) === 0) {
//                $nuevoDocumento = new EstadoDocumento();
//                $nuevoDocumento->nombre = 'Firmada';
//                $nuevoDocumento->tipodoc = $documento;
//                $nuevoDocumento->icon = 'fa-solid fa-file-circle-check';
//                $nuevoDocumento->editable = false;
//                $nuevoDocumento->bloquear = true;
//                $nuevoDocumento->actualizastock = 1;
//                $nuevoDocumento->predeterminado = false;
//                $nuevoDocumento->save();
//            }
        }
    }

    private function actualizarNumeroNCF(): void
    {
        $dataBase = new DataBase();
        $dataBase->exec("UPDATE facturascli SET numeroncf = numero2 WHERE numero2 != '' and tipocomprobante != '' AND numeroncf is null;");
        $dataBase->exec("UPDATE facturasprov SET numeroncf = numproveedor WHERE numproveedor != '' and tipocomprobante != '' AND numeroncf is null;");
    }

    private function actualizarImpuestos(): void
    {
        $impuesto = new Impuesto();
        $isc = $impuesto::find('ISC');
        if ($isc !== false) {
            $isc->delete();
        }

        $cdt = $impuesto::find('CDT');
        if ($cdt !== false) {
            $cdt->delete();
        }

        $dataBase = new DataBase();
        $dataBase->exec("DELETE FROM rd_impuestosadicionales where codigo IN ('006', '007', '008', '009', '010', '011', '012', '013', '014', '015', '016', '017', '018', '019', '020', '021', '022');");
    }

    private function actualizarECF(): void
    {
        $arrayECF = [
            ['tipocomprobante' => '31', 'descripcion' => 'FACTURA DE CRÉDITO FISCAL ELECTRÓNICA', 'clasemovimiento' => 'suma', 'ventas' => 'Y', 'compras' => 'Y', 'contribuyente' => 'Y', 'estado' => true],
            ['tipocomprobante' => '32', 'descripcion' => 'FACTURA DE CONSUMO ELECTRÓNICA', 'clasemovimiento' => 'suma', 'ventas' => 'Y', 'compras' => 'Y', 'contribuyente' => 'Y', 'estado' => true],
            ['tipocomprobante' => '33', 'descripcion' => 'NOTA DE DÉBITO ELECTRÓNICA', 'clasemovimiento' => 'suma', 'ventas' => 'Y', 'compras' => 'Y', 'contribuyente' => 'N', 'estado' => true],
            ['tipocomprobante' => '34', 'descripcion' => 'NOTA DE CRÉDITO ELECTRÓNICA', 'clasemovimiento' => 'resta', 'ventas' => 'Y', 'compras' => 'Y', 'contribuyente' => 'N', 'estado' => true],
            ['tipocomprobante' => '41', 'descripcion' => 'COMPROBANTE ELECTRÓNICO DE COMPRAS', 'clasemovimiento' => 'suma', 'ventas' => 'N', 'compras' => 'Y', 'contribuyente' => 'Y', 'estado' => true],
            ['tipocomprobante' => '43', 'descripcion' => 'COMPROBANTE ELECTRÓNICO PARA GASTOS MENORES', 'clasemovimiento' => 'suma', 'ventas' => 'N', 'compras' => 'Y', 'contribuyente' => 'N', 'estado' => true],
            ['tipocomprobante' => '44', 'descripcion' => 'COMPROBANTE ELECTRÓNICO PARA REGÍMENES ESPECIALES', 'clasemovimiento' => 'suma', 'ventas' => 'Y', 'compras' => 'Y', 'contribuyente' => 'Y', 'estado' => true],
            ['tipocomprobante' => '45', 'descripcion' => 'COMPROBANTE ELECTRÓNICO GUBERNAMENTAL', 'clasemovimiento' => 'suma', 'ventas' => 'Y', 'compras' => 'Y', 'contribuyente' => 'Y', 'estado' => true],
            ['tipocomprobante' => '46', 'descripcion' => 'COMPROBANTE ELECTRÓNICO PARA EXPORTACIONES', 'clasemovimiento' => 'suma', 'ventas' => 'Y', 'compras' => 'Y', 'contribuyente' => 'Y', 'estado' => true],
            ['tipocomprobante' => '47', 'descripcion' => 'COMPROBANTE ELECTRÓNICO PARA PAGOS AL EXTERIOR', 'clasemovimiento' => 'suma', 'ventas' => 'N', 'compras' => 'Y', 'contribuyente' => 'Y', 'estado' => true],
        ];

        $tipoComprobantes = new NCFTipo();
        foreach ($arrayECF as $ecf) {
            $registro = $tipoComprobantes->get($ecf['tipocomprobante']);
            if ($registro === false) {
                $registro = new NCFTipo();
                $registro->tipocomprobante = $ecf['tipocomprobante'];
                $registro->descripcion = $ecf['descripcion'];
                $registro->clasemovimiento = $ecf['clasemovimiento'];
                $registro->ventas = $ecf['ventas'];
                $registro->compras = $ecf['compras'];
                $registro->contribuyente = $ecf['contribuyente'];
                $registro->estado = $ecf['estado'];
                $registro->save();
            }
        }
    }

    public function update(): void
    {
        new NCFTipoPago();
        new NCFTipoAnulacion();
        new NCFTipoMovimiento();
        new NCFTipo();
        new NCFRango();
        new Cliente();
        new FacturaCliente();
        new Proveedor();
        new FacturaProveedor();
        new ImpuestoProducto();
        new ImpuestoAdicional();
        $this->actualizarEstados();
        $this->actualizarEstadoFacturaElectronica();
        $this->actualizarNumeroNCF();
        $this->actualizarImpuestos();
        $this->actualizarECF();

    }

    public function uninstall(): void
    {
        // código de desinstalación aquí
    }
}
