<?php
/*
 * Copyright (C) 2025 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Test\Plugins;

use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class ClienteTest extends TestCase
{
    use LogErrorsTrait;
    public static function setUpBeforeClass(): void
    {
        $listaCliente = new Cliente();
    }

    public function testCreate()
    {
        $consumidorFinal = new Cliente();
        $consumidorFinal->idcliente = 1;
        $consumidorFinal->nombre = 'Razon Social';
        $consumidorFinal->direccion = 'Direccion';
        $consumidorFinal->telefono = 'Telefono';
        $consumidorFinal->email = 'Email';
        $consumidorFinal->cuit = 'CI';
        $consumidorFinal->personafisica = true;
        $consumidorFinal->cifnif = '1234567890';
        $consumidorFinal->codpago = 'CONT';
        $consumidorFinal->tipoidfiscal = 'CI';
        $consumidorFinal->tipocomprobante = 'CI';
        $consumidorFinal->ncftipopago = 'CI';
        $consumidorFinal->regimeniva = 'General';
        $this->assertTrue($consumidorFinal->save(), 'cliente-can-save');
        $this->assertTrue($consumidorFinal->save(), 'cliente-cant-save');
        $this->assertNotNull($consumidorFinal->primaryColumnValue(), 'cliente-not-stored');
        $this->assertTrue($consumidorFinal->exists(), 'cliente-cant-persist');

        // razón social es igual a nombre
        $this->assertEquals($consumidorFinal->nombre, $consumidorFinal->razonsocial);

        // comprobamos que se ha creado una dirección por defecto
        $addresses = $consumidorFinal->getAddresses();
        $this->assertCount(1, $addresses, 'cliente-default-address-not-created');
        foreach ($addresses as $address) {
            $this->assertEquals($address->cifnif, $consumidorFinal->cifnif);
            $this->assertEquals($address->codagente, $consumidorFinal->codagente);
            $this->assertEquals($address->codcliente, $consumidorFinal->codcliente);
            $this->assertEquals($address->idcontacto, $consumidorFinal->idcontactofact);
        }

        // eliminamos
        $this->assertTrue($consumidorFinal->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($consumidorFinal->delete(), 'cliente-cant-delete');

    }
}
