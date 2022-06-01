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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

/**
 * Description of NCFTipoMovimiento
 *
 * @author Joe Zegarra
 */
class NCFTipoMovimiento extends Base\ModelClass
{
    use Base\ModelTrait;
    /**
     * two digit string to identify the Payment Type
     * sales|purchase 01|02 options
     * @var string
     */
    public $tipomovimiento;
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
     * List of Movement types
     * @var array
     */
    private $arrayTiposMovimiento = [
        ['tipomovimiento'=>'COM','codigo' => '01', 'descripcion' => 'GASTOS DE PERSONAL', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '02', 'descripcion' => 'GASTOS POR TRABAJOS, SUMINISTROS Y SERVICIOS', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '03', 'descripcion' => 'ARRENDAMIENTOS', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '04', 'descripcion' => 'GASTOS DE ACTIVOS FIJOS', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '05', 'descripcion' => 'GASTOS DE REPRESENTACIÓN', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '06', 'descripcion' => 'OTRAS DEDUCCIONES ADMITIDAS', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '07', 'descripcion' => 'GASTOS FINANCIEROS', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '08', 'descripcion' => 'GASTOS EXTRAORDINARIOS', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '09', 'descripcion' => 'COMPRAS Y GASTOS QUE FORMARÁN PARTE DEL COSTO DE VENTA', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '10', 'descripcion' => 'ADQUISICIONES DE ACTIVOS', 'estado' => true],
        ['tipomovimiento'=>'COM','codigo' => '11', 'descripcion' => 'GASTOS DE SEGUROS', 'estado' => true],
        ['tipomovimiento'=>'VEN','codigo' => '1', 'descripcion' => 'Ingresos por operaciones (No financieros)', 'estado' => true],
        ['tipomovimiento'=>'VEN','codigo' => '2', 'descripcion' => 'Ingresos Financieros', 'estado' => true],
        ['tipomovimiento'=>'VEN','codigo' => '3', 'descripcion' => 'Ingresos Extraordinarios', 'estado' => true],
        ['tipomovimiento'=>'VEN','codigo' => '4', 'descripcion' => 'Ingresos por Arrendamientos', 'estado' => true],
        ['tipomovimiento'=>'VEN','codigo' => '5', 'descripcion' => 'Ingresos por Venta de Activo Depreciable', 'estado' => true],
        ['tipomovimiento'=>'VEN','codigo' => '6', 'descripcion' => 'Otros Ingresos', 'estado' => true]
    ];
    
    /**
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }
    
    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'rd_ncftipomovimiento';
    }
    
    /**
     * @return string
     */
    public function install(): string
    {
        parent::install();
        return "INSERT INTO rd_ncftipomovimiento (tipomovimiento, codigo, descripcion, estado) VALUES " .
            "('COM','01','GASTOS DE PERSONAL',true), ".
            "('COM','02','GASTOS POR TRABAJOS, SUMINISTROS Y SERVICIOS',true), ".
            "('COM','03','ARRENDAMIENTOS',true), ".
            "('COM','04','GASTOS DE ACTIVOS FIJOS',true), ".
            "('COM','05','GASTOS DE REPRESENTACIÓN',true), ".
            "('COM','06','OTRAS DEDUCCIONES ADMITIDAS',true), ".
            "('COM','07','GASTOS FINANCIEROS',true), ".
            "('COM','08','GASTOS EXTRAORDINARIOS',true), ".
            "('COM','09','COMPRAS Y GASTOS QUE FORMARÁN PARTE DEL COSTO DE VENTA',true), ".
            "('COM','10','ADQUISICIONES DE ACTIVOS',true), ".
            "('COM','11','GASTOS DE SEGUROS',true), ".
            "('VEN','1','Ingresos por operaciones (No financieros)',true), ".
            "('VEN','2','Ingresos Financieros',true), ".
            "('VEN','3','Ingresos Extraordinarios',true), ".
            "('VEN','4','Ingresos por Arrendamientos',true), ".
            "('VEN','5','Ingresos por Venta de Activo Depreciable',true), ".
            "('VEN','6','Otros Ingresos',true);";
    }
    
    public function restoreData()
    {
        $dataBase = new DataBase();
        $sqlClean = "DELETE FROM " . $this->tableName() . ";";
        $dataBase->exec($sqlClean);
        foreach ($this->arrayTiposMovimiento as $arrayItem) {
            $initialData = new NCFTipoMovimiento($arrayItem);
            $initialData->save();
        }
        $this->clear();
    }

    public function findAllByTipomovimiento(string $tipomovimiento): array
    {
        $where = [new DataBaseWhere('tipomovimiento', $tipomovimiento)];
        return $this->all($where, ['codigo' => 'ASC'], 0, 50);
    }
}
