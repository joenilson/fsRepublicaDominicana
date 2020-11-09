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
use FacturaScripts\Core\Lib\BusinessDocTypeOperation as ParentClass;

/**
 * Description of BusinessDocTypeOperation
 *
 * @author joenilson
 */
class BusinessDocTypeOperation extends ParentClass
{
    /**
     * Standard
     *
     * @var string
     */
    const TYPE_OPERATION_DOCUMENT_SALES_IPO = '1';
    const TYPE_OPERATION_DOCUMENT_SALES_IF = '2';
    const TYPE_OPERATION_DOCUMENT_SALES_IE = '3';
    const TYPE_OPERATION_DOCUMENT_SALES_IA = '4';
    const TYPE_OPERATION_DOCUMENT_SALES_IVAD = '5';
    const TYPE_OPERATION_DOCUMENT_SALES_OI = '6';
    
    const TYPE_OPERATION_DOCUMENT_PURCHASES_GP = '01';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_GTSS = '02';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_A = '03';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_GAF = '04';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_GR = '05';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_ODA = '06';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_GF = '07';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_GE = '08';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_CGCV = '09';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_AA = '10';
    const TYPE_OPERATION_DOCUMENT_PURCHASES_GS = '11';
    
    /**
     * Determinates the list to return sales or purchases
     * by default is sales
     * @var string 
     */
    public static $type_operation = 'sales';
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
        
        if(self::$type_operation === 'sales') {
            return [
                self::TYPE_OPERATION_DOCUMENT_SALES_IPO => self::$i18n->trans('ncf-sales-income-type-ipo'),
                self::TYPE_OPERATION_DOCUMENT_SALES_IF => self::$i18n->trans('ncf-sales-income-type-if'),
                self::TYPE_OPERATION_DOCUMENT_SALES_IE => self::$i18n->trans('ncf-sales-income-type-ie'),
                self::TYPE_OPERATION_DOCUMENT_SALES_IA => self::$i18n->trans('ncf-sales-income-type-ia'),
                self::TYPE_OPERATION_DOCUMENT_SALES_IVAD => self::$i18n->trans('ncf-sales-income-type-ivad'),
                self::TYPE_OPERATION_DOCUMENT_SALES_OI => self::$i18n->trans('ncf-sales-income-type-oi'),
            ];
        } else {
            return [
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_GP => self::$i18n->trans('ncf-purchase-outcome-type-gp'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_GTSS => self::$i18n->trans('ncf-purchase-outcome-type-gtss'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_A => self::$i18n->trans('ncf-purchase-outcome-type-a'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_GAF => self::$i18n->trans('ncf-purchase-outcome-type-gaf'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_GR => self::$i18n->trans('ncf-purchase-outcome-type-gr'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_ODA => self::$i18n->trans('ncf-purchase-outcome-type-oda'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_GF => self::$i18n->trans('ncf-purchase-outcome-type-gf'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_GE => self::$i18n->trans('ncf-purchase-outcome-type-ge'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_CGCV => self::$i18n->trans('ncf-purchase-outcome-type-cgcv'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_AA => self::$i18n->trans('ncf-purchase-outcome-type-aa'),
                self::TYPE_OPERATION_DOCUMENT_PURCHASES_GS => self::$i18n->trans('ncf-purchase-outcome-type-gs'),
            ];
        }
    }

    /**
     * Returns the default value
     *
     * @return string
     */
    public static function defaultValue()
    {
        return (self::$type_operation === 'sales')?self::TYPE_OPERATION_DOCUMENT_SALES_IPO:self::TYPE_OPERATION_DOCUMENT_PURCHASES_GP;
    }
}
