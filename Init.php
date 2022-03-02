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

use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FactuaScripts\Dinamic\Model\Cliente;
use FactuaScripts\Dinamic\Model\FacturaCliente;
use FactuaScripts\Dinamic\Model\Proveedor;
use FactuaScripts\Dinamic\Model\FacturaProveedor;
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
    }
}
