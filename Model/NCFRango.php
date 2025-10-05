<?php
/**
 * Copyright (C) 2019-2020 Joe Zegarra.
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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

use FacturaScripts\Core\Base\DataBase;

/**
 * Description of NCFRango
 *
 * @author Joe Zegarra
 */
class NCFRango extends ModelClass
{
    use ModelTrait;
    
    /**
     * The key of all the records
     * @var int
     */
    public $id;
    
    /**
     * Authorization number gived by DGII
     * @var int
     */
    public $autorizacion;
    
    /**
     * Old record for printing area
     * @var string
     * @deprecated since version 2017.50 added only for migration purposes
     */
    public $areaimpresion;
    
    /**
     * Old record for warehouse location
     * @var string
     * @deprecated since version 2017.50 added only for migration purposes
     */
    public $codalmacen;
    
    /**
     * Old record for cash or credit payment rule
     * @var bool
     * @deprecated since version 2017.50
     */
    public $contado;
    
    /**
     * The next number to assign for a NCF number
     * @var int
     */
    public $correlativo;
    
    /**
     * Old record for business unit where had been generated the NCF
     * @var string
     * @deprecated since version 2017.50
     */
    public $division;
    
    /**
     * Status of the record true or false, true if active
     * @var bool
     */
    public $estado;
    
    /**
     * Record creation date
     * @var date
     */
    public $fechacreacion;
    
    /**
     * Record modification date
     * @var date
     */
    public $fechamodificacion;
    
    /**
     * NCF Authorization expiration date
     * @var date
     */
    public $fechavencimiento;
    
    /**
     * Compnay id for who the records is created
     * @var int
     */
    public $idempresa;
    
    /**
     * Old record for point of NCF generation
     * @var string
     * @deprecated since version 2017.50
     */
    public $puntoemision;
    
    /**
     * Start number for the DGII NCF sequence
     * @var int
     */
    public $secuenciainicio;
    /**
     * Last number for the DGII NCF sequence
     * @var int
     */
    public $secuenciafin;
    
    /**
     * The letter assigned to the NCF sequence
     * @var string
     */
    public $serie;
    
    /**
     * The request number generated in the DGII Virtual Office for the NCF sequence
     * @var int
     */
    public $solicitud;
    
    /**
     * The NCF type for this sequence
     * @var string
     */
    public $tipocomprobante;
    
    /**
     * The user nickname that created the record
     * @var string
     */
    public $usuariocreacion;
    
    /**
     * The user nickname that modified the record
     * @var string
     */
    public $usuariomodificacion;
    
    public static function primaryColumn(): string
    {
        return 'id';
    }
    
    public static function tableName(): string
    {
        return 'rd_ncfrango';
    }
    
    protected function saveUpdate(array $values = array()): bool
    {
        if ($this->id and isset($this->usuariomodificacion_view)) {
            $this->fechamodificacion = \date('d-m-Y');
            $this->usuariomodificacion = $this->usuariomodificacion_view;
        }
        return parent::saveUpdate($values);
    }
    
    public function getByTipoComprobante($idempresa, $tipocomprobante)
    {
        $dataBase = new DataBase();
        $sql = 'SELECT * FROM '
                . $this->tableName()
                . ' WHERE idempresa = '
                . $idempresa
                . ' AND tipocomprobante = '
                . $dataBase->var2str($tipocomprobante)
                . ' AND estado = ' . $dataBase->var2str(true)
                . ' AND correlativo <= secuenciafin '
                . ';';
        $data = $dataBase->select($sql);
        if (empty($data) === true || in_array($data[0], [null, ''], true)) {
            return false;
        }
        return new NCFRango($data[0]);
    }
    
    public function generateNCF()
    {
        return $this->serie
        . $this->tipocomprobante
        . \str_pad($this->correlativo, 8, '0', STR_PAD_LEFT);
    }
}
