<?php
/*
 * Copyright (C) 2020 Joe Zegarra.
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
use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFRango;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoPago;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\RNCDGIIDB;

use FacturaScripts\Core\Base\AjaxForms\SalesFooterHTML;
use FacturaScripts\Core\Base\AjaxForms\PurchasesFooterHTML;

/**
 * Description of Init
 *
 * @author Joe Zegarra
 */
class Init extends InitClass
{
    public function init()
    {
        $this->loadExtension(new Extension\Model\Cliente());
        $this->loadExtension(new Extension\Model\FacturaCliente());
        $this->loadExtension(new Extension\Model\FacturaProveedor());
        $this->loadExtension(new Extension\Model\Producto());
        $this->loadExtension(new Extension\Controller\EditCliente());
        $this->loadExtension(new Extension\Controller\EditProveedor());
        $this->loadExtension(new Extension\Controller\EditFacturaCliente());
        $this->loadExtension(new Extension\Controller\EditFacturaProveedor());
        AssetManager::add('js', \FS_ROUTE . '/Plugins/fsRepublicaDominicana/Assets/JS/CommonDomFunctions.js');
        SalesFooterHTML::addMod(new Mod\SalesFooterMod());
        PurchasesFooterHTML::addMod(new Mod\PurchasesFooterMod());
    }

    private function ActualizarEstados()
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
            $lista = $estados->all(
                [
                    new DataBaseWhere('nombre', 'Anulada'),
                    new DataBaseWhere('tipodoc', $documento)
                ]
            );

            if (count($lista) === 0) {
                $nuevoDocumento = new EstadoDocumento();
                $nuevoDocumento->nombre = 'Anulada';
                $nuevoDocumento->tipodoc = $documento;
                $nuevoDocumento->icon = 'fas fa-handshake-slash';
                $nuevoDocumento->editable = false;
                $nuevoDocumento->bloquear = true;
                $nuevoDocumento->actualizastock = 0;
                $nuevoDocumento->predeterminado = false;
                $nuevoDocumento->save();
            }
        }
    }

    private function ActualizarNumeroNCF()
    {
        $dataBase = new DataBase();
        $dataBase->exec("UPDATE facturascli SET numeroncf = numero2 WHERE numero2 != '' and tipocomprobante != '' AND numeroncf = '';");
        $dataBase->exec("UPDATE facturasprov SET numeroncf = numproveedor WHERE numproveedor != '' and tipocomprobante != '' AND numeroncf = '';");
    }
    
    public function update()
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
        new RNCDGIIDB();
        $this->ActualizarEstados();
        $this->ActualizarNumeroNCF();
    }
}
