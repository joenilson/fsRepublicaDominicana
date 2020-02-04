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

use FacturaScripts\Core\Base\DataBase;
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
     * sales|purchase 01|02 options
     * @var string
     */
    public $tipopago;    
    /**
     * two digit string to identify the Payment Code
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
    public $arrayTipos = array(
        array ('tipopago'=>'01','codigo' => '17', 'descripcion' => 'EFECTIVO','estado'=>true),
        array ('tipopago'=>'01','codigo' => '18', 'descripcion' => 'CHEQUES/TRANSFERENCIAS/DEPOSITO','estado'=>true),
        array ('tipopago'=>'01','codigo' => '19', 'descripcion' => 'TARJETA CRÉDITO/DÉBITO','estado'=>true),
        array ('tipopago'=>'01','codigo' => '20', 'descripcion' => 'VENTA A CREDITO','estado'=>true),
        array ('tipopago'=>'01','codigo' => '21', 'descripcion' => 'BONOS O CERTIFICADOS DE REGALO','estado'=>true),
        array ('tipopago'=>'01','codigo' => '22', 'descripcion' => 'PERMUTA','estado'=>true),
        array ('tipopago'=>'01','codigo' => '23', 'descripcion' => 'OTRAS FORMAS DE VENTAS','estado'=>true),
        array ('tipopago'=>'02','codigo' => '01', 'descripcion' => 'EFECTIVO','estado'=>true),
        array ('tipopago'=>'02','codigo' => '02', 'descripcion' => 'CHEQUES/TRANSFERENCIAS/DEPOSITO','estado'=>true),
        array ('tipopago'=>'02','codigo' => '03', 'descripcion' => 'TARJETA CRÉDITO/DÉBITO','estado'=>true),
        array ('tipopago'=>'02','codigo' => '04', 'descripcion' => 'COMPRA A CREDITO','estado'=>true),
        array ('tipopago'=>'02','codigo' => '05', 'descripcion' => 'PERMUTA','estado'=>true),
        array ('tipopago'=>'02','codigo' => '06', 'descripcion' => 'NOTA DE CREDITO','estado'=>true),
        array ('tipopago'=>'02','codigo' => '07', 'descripcion' => 'MIXTO','estado'=>true)
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
        return 'rd_ncftipopagos';
    }
    
    /**
     * 
     * @return string
     */
    public function install() 
    {
        parent::install();
        return "INSERT INTO rd_ncftipopagos (tipopago, codigo, descripcion, estado) VALUES ".
            "('01','17','EFECTIVO',true),
            ('01','18','CHEQUES/TRANSFERENCIAS/DEPOSITO',true),
            ('01','19','TARJETA CRÉDITO/DÉBITO',true),
            ('01','20','VENTA A CRÉDITO',true),
            ('01','21','BONOS O CERTIFICADOS DE REGALO',true),
            ('01','22','PERMUTA',true),
            ('01','23','OTRAS FORMAS DE VENTAS',true),
            ('02','01','EFECTIVO',true),
            ('02','02','CHEQUES/TRANSFERENCIAS/DEPOSITO',true),
            ('02','03','TARJETA CRÉDITO/DÉBITO',true),
            ('02','04','COMPRA A CREDITO',true),
            ('02','05','PERMUTA',true),
            ('02','06','NOTA DE CREDITO',true),
            ('02','07','MIXTO',true);";
    }
    
    public function restoreData()
    {
        $dataBase = new DataBase();
        $sqlClean = "DELETE FROM ".$this->tableName().";";
        $dataBase->exec($sqlClean);
        foreach ($this->arrayTipos as $arrayItem) {
            $initialData = new NCFTipoPago($arrayItem);
            $initialData->save();
        }
        $this->clear();
    }
    
}
