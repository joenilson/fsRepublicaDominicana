<?php

/*
 * Copyright (C) 2026 Joe Nilson <joenilson@gmail.com>
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

use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Códigos de modificación de un comprobante fiscal (campo CodigoModificacion del e-CF).
 *
 * 1, 2 y 3 aplican solo a notas de crédito o débito electrónicas (34 y 33),
 * 4 solo a facturas de crédito fiscal (31) y 5 solo a facturas de consumo (32).
 *
 * @author Joe Nilson
 */
class NCFModificacion extends ModelClass
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

    /**
     * @var string
     */
    public $fechacreacion;

    /**
     * @var string
     */
    public $usuariocreacion;

    /**
     * @var string
     */
    public $fechamodificacion;

    /**
     * @var string
     */
    public $usuariomodificacion;

    public static function primaryColumn(): string
    {
        return 'codigo';
    }

    public static function tableName(): string
    {
        return 'rd_ncfmodificacion';
    }

    /**
     * Códigos de modificación permitidos según el tipo de comprobante emitido.
     *
     * @param string|null $tipocomprobante
     * @return string[]
     */
    public static function codigosPorTipoComprobante(?string $tipocomprobante): array
    {
        switch ($tipocomprobante) {
            case '33':
            case '34':
                return ['1', '2', '3'];
            case '31':
                return ['4'];
            case '32':
                return ['5'];
            default:
                return [];
        }
    }

    public function install(): string
    {
        parent::install();
        $fecha = date('Y-m-d');
        return "INSERT INTO " . static::tableName()
            . " (codigo, descripcion, estado, fechacreacion, usuariocreacion) VALUES "
            . "('1','Anula el NCF modificado',true,'" . $fecha . "','system'), "
            . "('2','Corrige Texto del Comprobante Fiscal modificado',true,'" . $fecha . "','system'), "
            . "('3','Corrige montos del NCF modificado',true,'" . $fecha . "','system'), "
            . "('4','Reemplazo NCF emitido en contingencia',true,'" . $fecha . "','system'), "
            . "('5','Referencia Factura Consumo Electrónica',true,'" . $fecha . "','system');";
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);
        $this->fechacreacion = $this->fechacreacion ?? Tools::date();
        $this->usuariocreacion = $this->usuariocreacion ?? Session::user()->nick;

        return parent::test();
    }

    protected function saveUpdate(array $values = []): bool
    {
        $this->fechamodificacion = Tools::date();
        $this->usuariomodificacion = Session::user()->nick;

        return parent::saveUpdate($values);
    }
}
