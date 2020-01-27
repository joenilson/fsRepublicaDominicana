<?php

/*
 * Copyright (C) 2019 Joe Zegarra.
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

namespace Facturascripts\Plugins\fsRepublicaDominicana\Model;

use FacturaScripts\Core\Model\Base;
/**
 * Description of NCFTipoPago
 *
 * @author Joe Zegarra
 */
class NCFTipoPago extends Base\ModelClass
{
    use Base\ModelTrait;
    
    /**
     * two digit string to identify the Payment Type
     * @var string
     */
    public $codigo;
    /**
     * The description of the Payment Type
     * @var string
     */
    public $descripcion;
    /**
     * The status of the record
     * @var bool 
     */
    public $estado;

    
    /**
     * List of Payment types
     * @var array
     */
    public $array_tipos = array(
        array ('codigo' => '17', 'descripcion' => 'Efectivo'),
        array ('codigo' => '18', 'descripcion' => 'Cheque/Transferencia/Depósito'),
        array ('codigo' => '19', 'descripcion' => 'Tarjeta Débito/Crédito'),
        array ('codigo' => '20', 'descripcion' => 'Venta a Crédito'),
        array ('codigo' => '21', 'descripcion' => 'Bonos o Certificados de Regalo'),
        array ('codigo' => '22', 'descripcion' => 'Permuta'),
        array ('codigo' => '23', 'descripcion' => 'Otras Formas de Ventas')
    );
    
    /**
     * 
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codigo';
    }
    
    /**
     * 
     * @return string
     */
    public static function tableName()
    {
        return 'rd_ncftipopago';
    }
    
    /**
     * 
     * @return string
     */
    public function install() 
    {
        parent::install();
        return "INSERT INTO rd_ncftipopago (codigo, descripcion, estado) VALUES ".
            "('17','Efectivo',true),
            ('18','Cheque/Transferencia/Depósito',true),
            ('19','Tarjeta Débito/Crédito',true),
            ('20','Venta a Crédito',true),
            ('21','Bonos o Certificados de Regalo',true),
            ('22','Permuta',true),
            ('23','Otras Formas de Ventas',true);";
    }
    
}
