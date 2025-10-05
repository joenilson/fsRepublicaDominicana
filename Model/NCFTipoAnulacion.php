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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;


/**
 * Description of NCFTipoAnulacion
 *
 * @author Joe Zegarra
 */
class NCFTipoAnulacion extends ModelClass
{
    use ModelTrait;
    
    /**
     * The key for the record
     * @var string
     */
    public $codigo;
    
    /**
     * The description of the record
     * @var string
     */
    public $descripcion;
    
    /**
     * The status of the record, true if is active
     * @var bool
     */
    public $estado;
    
    public $arrayTipoAnulacion = array(
        ['codigo' => '01', 'descripcion' => 'Deterioro de Factura Pre-Imprensa', 'estado' => true],
        ['codigo' => '02', 'descripcion' => 'Errores de Impresión (Factura Pre-Impresa)', 'estado' => true],
        ['codigo' => '03', 'descripcion' => 'Impresión defectuosa', 'estado' => true],
        ['codigo' => '04', 'descripcion' => 'Duplicidad de Factura', 'estado' => true],
        ['codigo' => '05', 'descripcion' => 'Corrección de la Información', 'estado' => true],
        ['codigo' => '06', 'descripcion' => 'Cambio de Productos', 'estado' => true],
        ['codigo' => '07', 'descripcion' => 'Devolución de Productos', 'estado' => true],
        ['codigo' => '08', 'descripcion' => 'Omisión de Productos', 'estado' => true]
    );
    
    public static function primaryColumn(): string
    {
        return 'codigo';
    }

    public static function tableName(): string
    {
        return 'rd_ncftipoanulacion';
    }
    
    public function install(): string
    {
        parent::install();
        return "INSERT INTO rd_ncftipoanulacion (codigo, descripcion, estado) VALUES " .
            "('01','Deterioro de Factura Pre-Imprensa',true), " .
             "('02','Errores de Impresión (Factura Pre-Impresa)',true), " .
             "('03','Impresión defectuosa',true), " .
             "('04','Duplicidad de Factura',true), " .
             "('05','Corrección de la Información',true), " .
             "('06','Cambio de Productos',true), " .
             "('07','Devolución de Productos',true), " .
             "('08','Omisión de Productos',true);";
    }
    
    public function restoreData()
    {
        $dataBase = new DataBase();
        $sqlClean = "DELETE FROM " . $this->tableName() . ";";
        $dataBase->exec($sqlClean);
        foreach ($this->arrayTipoAnulacion as $arrayItem) {
            $initialData = new NCFTipoAnulacion($arrayItem);
            $initialData->save();
        }
        $this->clear();
    }
}
