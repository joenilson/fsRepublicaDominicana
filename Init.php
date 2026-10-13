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
use FacturaScripts\Dinamic\Model\LineaAlbaranCliente;
use FacturaScripts\Dinamic\Model\LineaAlbaranProveedor;
use FacturaScripts\Dinamic\Model\LineaFacturaCliente;
use FacturaScripts\Dinamic\Model\LineaFacturaProveedor;
use FacturaScripts\Dinamic\Model\LineaPedidoCliente;
use FacturaScripts\Dinamic\Model\LineaPedidoProveedor;
use FacturaScripts\Dinamic\Model\LineaPresupuestoCliente;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoAdicional;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoProducto;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFModificacion;
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
        $this->loadExtension(new Extension\Model\PresupuestoCliente());
        $this->loadExtension(new Extension\Model\PedidoCliente());
        $this->loadExtension(new Extension\Model\AlbaranCliente());
        $this->loadExtension(new Extension\Model\FacturaCliente());
        $this->loadExtension(new Extension\Model\LineaPresupuestoCliente());
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
        // selectores con búsqueda (select2 del núcleo) para los impuestos adicionales de las líneas
        AssetManager::addCss($route . '/node_modules/select2/dist/css/select2.min.css?v=5');
        AssetManager::addCss($route . '/node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css?v=5');
        AssetManager::addJs($route . '/node_modules/select2/dist/js/select2.min.js?v=5', 2);
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
        if ($isc !== null) {
            $isc->delete();
        }

        $cdt = $impuesto::find('CDT');
        if ($cdt !== null) {
            $cdt->delete();
        }
    }

    /**
     * Las líneas guardadas antes de existir los montos solo tienen la tasa. Se calcula su monto
     * una vez por porcentaje; solo se tocan líneas sin monto cuyo código sea de tipo porcentaje,
     * por eso es seguro ejecutarlo en cada actualización.
     */
    private function actualizarMontosImpuestosAdicionales(): void
    {
        // al instanciar los modelos de línea se actualizan las columnas de monto
        new LineaFacturaCliente();
        new LineaAlbaranCliente();
        new LineaPedidoCliente();
        new LineaPresupuestoCliente();
        new LineaFacturaProveedor();
        new LineaAlbaranProveedor();
        new LineaPedidoProveedor();

        $porcentaje = "(SELECT codigo FROM rd_impuestosadicionales WHERE tipo_tasa = 'porcentaje')";
        $tablas = [
            'lineasfacturascli' => '', 'lineasalbaranescli' => '', 'lineaspedidoscli' => '',
            'lineaspresupuestoscli' => '',
            'lineasfacturasprov' => ' AND rdtaxmanual = false', 'lineasalbaranesprov' => ' AND rdtaxmanual = false',
            'lineaspedidosprov' => ' AND rdtaxmanual = false',
        ];
        $columnas = [
            // columna de monto => [columna de tasa, columna de código, otras condiciones]
            'rdtaxiscadvalorem' => ['rdtaxisc', 'rdtaxcodisc', ' AND rdtaxiscespecifico = 0'],
            'rdtaxcdtmonto' => ['rdtaxcdt', 'rdtaxcodcdt', ''],
            'rdtaxlegaltipmonto' => ['rdtaxlegaltip', 'rdtaxcodlegaltip', ''],
            'rdtaxfirstplatemonto' => ['rdtaxfirstplate', 'rdtaxcodfirstplate', ''],
        ];

        $dataBase = new DataBase();
        foreach ($tablas as $tabla => $condicionTabla) {
            if (!$dataBase->tableExists($tabla)) {
                continue;
            }
            foreach ($columnas as $monto => [$tasa, $codigo, $extra]) {
                $dataBase->exec('UPDATE ' . $tabla . ' SET ' . $monto . ' = pvpsindto * ' . $tasa . ' / 100.0'
                    . ' WHERE ' . $monto . ' = 0 AND ' . $tasa . ' > 0 AND ' . $codigo . ' IN ' . $porcentaje
                    . $extra . $condicionTabla . ';');
            }
        }
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

    private function migrateFromNCFRangoOld()
    {
        $NCFRango = new NCFRango();
        if ($NCFRango::count() === 0) {
            $dataBase = new DataBase();
            $oldTableExists = $dataBase->tableExists('ncf_rango');
            if($oldTableExists) {
                $dataBase->exec("INSERT INTO `rd_ncfrango` (`autorizacion`, `areaimpresion`, `codalmacen`, `contado`, `correlativo`, `division`, `estado`, `fechacreacion`, `fechamodificacion`, `fechavencimiento`, `id`, `idempresa`, `puntoemision`, `secuenciafin`, `secuenciainicio`, `serie`, `solicitud`, `tipocomprobante`, `usuariocreacion`, `usuariomodificacion`) SELECT `autorizacion`, `area_impresion`, `codalmacen`, `contado`, `correlativo`, `division`, `estado`, `fecha_creacion`, `fecha_modificacion`, `fecha_vencimiento`, `id`, `idempresa`, `punto_emision`, `secuencia_fin`, `secuencia_inicio`, `serie`, `solicitud`, `tipo_comprobante`, `usuario_creacion`, `usuario_modificacion` FROM `ncf_rango`;");
            }
        }
    }

    public function update(): void
    {
        new NCFTipoPago();
        new NCFTipoAnulacion();
        new NCFModificacion();
        new NCFTipoMovimiento();
        new NCFTipo();
        new NCFRango();
        new Cliente();
        new FacturaCliente();
        new Proveedor();
        new FacturaProveedor();
        new ImpuestoProducto();
        new ImpuestoAdicional();
        ImpuestoAdicional::installEspecificos();
        $this->actualizarMontosImpuestosAdicionales();
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
