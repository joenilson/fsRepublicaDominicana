<?php

/*
 * Copyright (C) 2020 joenilson.
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib;

use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Lib\BusinessDocSubType as ParentClass;

/**
 * Description of BusinessDocSubType
 *
 * @author joenilson
 * @deprecated since v0.3
 */
class BusinessDocSubType extends ParentClass
{
    const SUB_TYPE_DOCUMENT_NCF01 = '01';
    const SUB_TYPE_DOCUMENT_NCF02 = '02';
    const SUB_TYPE_DOCUMENT_NCF03 = '03';
    const SUB_TYPE_DOCUMENT_NCF04 = '04';
    /** DON'T EXIST FROM 05 TO 10 **/
    const SUB_TYPE_DOCUMENT_NCF11 = '11';
    const SUB_TYPE_DOCUMENT_NCF12 = '12';
    const SUB_TYPE_DOCUMENT_NCF13 = '13';
    const SUB_TYPE_DOCUMENT_NCF14 = '14';
    const SUB_TYPE_DOCUMENT_NCF15 = '15';
    const SUB_TYPE_DOCUMENT_NCF16 = '16';
    const SUB_TYPE_DOCUMENT_NCF17 = '17';
    
    /**
     *
     * @var Translator
     */
    public static $i18n;
    
    /**
     * Returns all the available options
     *
     * @return array
     */
    public static function all()
    {
        if (!isset(self::$i18n)) {
            self::$i18n = new Translator();
        }

        return [
            self::SUB_TYPE_DOCUMENT_NCF01 => self::$i18n->trans('desc-ncf-type-01'),
            self::SUB_TYPE_DOCUMENT_NCF02 => self::$i18n->trans('desc-ncf-type-02'),
            self::SUB_TYPE_DOCUMENT_NCF03 => self::$i18n->trans('desc-ncf-type-03'),
            self::SUB_TYPE_DOCUMENT_NCF04 => self::$i18n->trans('desc-ncf-type-04'),
            self::SUB_TYPE_DOCUMENT_NCF11 => self::$i18n->trans('desc-ncf-type-11'),
            self::SUB_TYPE_DOCUMENT_NCF12 => self::$i18n->trans('desc-ncf-type-12'),
            self::SUB_TYPE_DOCUMENT_NCF13 => self::$i18n->trans('desc-ncf-type-13'),
            self::SUB_TYPE_DOCUMENT_NCF14 => self::$i18n->trans('desc-ncf-type-14'),
            self::SUB_TYPE_DOCUMENT_NCF15 => self::$i18n->trans('desc-ncf-type-15'),
            self::SUB_TYPE_DOCUMENT_NCF16 => self::$i18n->trans('desc-ncf-type-16'),
            self::SUB_TYPE_DOCUMENT_NCF17 => self::$i18n->trans('desc-ncf-type-17'),
        ];
    }
}
