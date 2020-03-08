<?php
/**
 * Copyright (C) 2020 Joe Nilson <joenilson at gmail dot com>
 * 
 * fsRepublicaDominicana is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * fsRepublicaDominicana is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with fsRepublicaDominicana. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FFacturaScripts\Plugins\fsRepublicaDominicana\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Model\Base;

/**
 * Description of NCFVentas
 *
 * @author "Joe Nilson <joenilson at gmail dot com>"
 */
class NCFVentas extends Base\ModelClass
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
     * 
     * @return string
     */
    public static function primaryColumn()
    {
        return 'ncf';
    }
    
    /**
     * 
     * @return string
     */
    public static function tableName()
    {
        return 'rd_ncfventas';
    }
    
    /**
     * 
     * @return string
     */
    public function install() 
    {
        parent::install();
        $sql = "";
        return($sql);
    }
}
