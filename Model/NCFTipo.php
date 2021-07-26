<?php
/**
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

/**
 * Description of NCFTipo
 *
 * @author "Joe Zegarra <joenilson at gmail dot com>"
 */
class NCFTipo extends Base\ModelClass
{
    use Base\ModelTrait;
    
    /**
     * Movement Class can be sum or rest suma|resta
     * @var string
     */
    public $clasemovimiento;
    /**
     * If the NCF Type is about purchase movement then must to have an X as value
     * @var string
     */
    public $compras;
    /**
     * If the NCF Type is about regular sales or purchase movements then must to have an X as value
     * @var string
     */
    public $contribuyente;
    /**
     * The description of the NCF Type
     * @var string
     */
    public $descripcion;
    /**
     * The status of the record
     * @var bool 
     */
    public $estado;
    /**
     * If the NCF Type is about sales movement then must to have an X as value
     * @var string
     */
    public $ventas;
    /**
     * This is the key value that contains the two code type of document
     * @var string 
     */
    public $tipocomprobante;
    
    /**
     * List of NCF types
     * @var array
     */
    public $arrayComprobantes = array(
        array ('tipocomprobante' => '01', 'descripcion' => 'FACTURA DE CREDITO FISCAL', 'clasemovimiento'=>'suma', 'ventas'=>'Y', 'compras'=>'Y', 'contribuyente'=>'Y', 'estado' => TRUE),
        array ('tipocomprobante' => '02', 'descripcion' => 'FACTURA DE CONSUMO', 'clasemovimiento'=>'suma', 'ventas'=>'Y', 'compras'=>'Y', 'contribuyente'=>'Y', 'estado' => TRUE),
        array ('tipocomprobante' => '03', 'descripcion' => 'NOTA DE DEBITO', 'clasemovimiento'=>'suma', 'ventas'=>'Y', 'compras'=>'Y', 'contribuyente'=>'N', 'estado' => TRUE),
        array ('tipocomprobante' => '04', 'descripcion' => 'NOTA DE CREDITO', 'clasemovimiento'=>'resta', 'ventas'=>'Y', 'compras'=>'Y', 'contribuyente'=>'N', 'estado' => TRUE),
        array ('tipocomprobante' => '11', 'descripcion' => 'COMPROBANTE DE COMPRAS', 'clasemovimiento'=>'suma', 'ventas'=>'N', 'compras'=>'Y', 'contribuyente'=>'Y', 'estado' => TRUE),
        array ('tipocomprobante' => '12', 'descripcion' => 'REGISTRO UNICO DE INGRESOS', 'clasemovimiento'=>'suma', 'ventas'=>'Y', 'compras'=>'N', 'contribuyente'=>'N', 'estado' => TRUE),
        array ('tipocomprobante' => '13', 'descripcion' => 'COMPROBANTE PARA GASTOS MENORES', 'clasemovimiento'=>'suma', 'ventas'=>'N', 'compras'=>'Y', 'contribuyente'=>'N', 'estado' => TRUE),
        array ('tipocomprobante' => '14', 'descripcion' => 'COMPROBANTE DE REGIMENES ESPECIALES', 'clasemovimiento'=>'suma', 'ventas'=>'Y', 'compras'=>'Y', 'contribuyente'=>'Y', 'estado' => TRUE),
        array ('tipocomprobante' => '15', 'descripcion' => 'COMPROBANTE GUBERNAMENTAL', 'clasemovimiento'=>'suma', 'ventas'=>'Y', 'compras'=>'Y', 'contribuyente'=>'Y', 'estado' => TRUE),
        array ('tipocomprobante' => '16', 'descripcion' => 'COMPROBANTE PARA EXPORTACIONES', 'clasemovimiento'=>'suma', 'ventas'=>'Y', 'compras'=>'N', 'contribuyente'=>'Y', 'estado' => TRUE),
        array ('tipocomprobante' => '17', 'descripcion' => 'COMPROBANTE PARA PAGOS AL EXTERIOR', 'clasemovimiento'=>'suma', 'ventas'=>'N', 'compras'=>'Y', 'contribuyente'=>'Y', 'estado' => TRUE),
    );
    
    /**
     * 
     * @return string
     */
    public static function primaryColumn()
    {
        return 'tipocomprobante';
    }
    
    /**
     * 
     * @return string
     */
    public static function tableName()
    {
        return 'rd_ncftipo';
    }
    
    /**
     * 
     * @return string
     */
    public function install() 
    {
        parent::install();
        $sql = "INSERT INTO rd_ncftipo (tipocomprobante, descripcion, estado, clasemovimiento, ventas, compras, contribuyente ) VALUES " .
            "('01','FACTURA DE CREDITO FISCAL',TRUE, 'suma','Y','Y','Y')," .
            "('02','FACTURA DE CONSUMO',TRUE, 'suma','Y','Y','Y')," .
            "('03','NOTA DE DEBITO',TRUE, 'suma','Y','Y','N')," . 
            "('04','NOTA DE CREDITO',TRUE, 'resta','Y','Y','N')," .
            "('11','COMPROBANTE DE COMPRAS',TRUE, 'suma','N','Y','Y')," .
            "('12','REGISTRO UNICO DE INGRESOS',TRUE, 'suma','Y','N','N')," .
            "('13','COMPROBANTE PARA GASTOS MENORES',TRUE, 'suma','N','Y','N')," .
            "('14','COMPROBANTE DE REGIMENES ESPECIALES',TRUE, 'suma','Y','Y','Y')," .
            "('15','COMPROBANTE GUBERNAMENTAL',TRUE, 'suma','Y','Y','Y')," .
            "('16','COMPROBANTE PARA EXPORTACIONES',TRUE, 'suma','Y','N','Y')," .
            "('17','COMPROBANTE PARA PAGOS AL EXTERIOR',TRUE, 'suma','N', 'Y','Y');";
        return($sql);
    }
    
    public function restoreData()
    {
        $dataBase = new DataBase();
        $sqlClean = "DELETE FROM " . $this->tableName() . ";";
        $dataBase->exec($sqlClean);
        foreach ($this->arrayComprobantes as $arrayItem) {
            $initialData = new NCFTipo($arrayItem);
            $initialData->save();
        }
        $this->clear();
    }

    public function allFor($type = "ventas", $movimiento = "suma")
    {
        $where = [new DataBaseWhere($type, 'Y'),new DataBaseWhere('clasemovimiento', $movimiento)];
        return $this->all($where, ['tipocomprobante' => 'ASC'], 0, 50);
    }
}
