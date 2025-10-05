<?php
/*
 * Copyright (C) 2022 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib\PDF;

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Tools;

use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;
use FacturaScripts\Core\Lib\PDF\PDFDocument as ParentClass;

abstract class PDFDocument extends ParentClass
{
    protected function insertBusinessDocHeader($model): void
    {
        $headerData = [
            'title' => $this->i18n->trans($model->modelClassName() . '-min'),
            'subject' => $this->i18n->trans('customer'),
            'fieldName' => 'nombrecliente'
        ];

        if (isset($model->codproveedor)) {
            $headerData['subject'] = $this->i18n->trans('supplier');
            $headerData['fieldName'] = 'nombre';
        }

        if (!empty($this->format->titulo)) {
            $headerData['title'] = Utils::fixHtml($this->format->titulo);
        }

        $this->pdf->ezText("\n" . $headerData['title'] . ': ' . $model->codigo . "\n", self::FONT_SIZE + 6);
        $this->newLine();

        $subject = $model->getSubject();
        $tipoidfiscal = empty($subject->tipoidfiscal) ? $this->i18n->trans('cifnif') : $subject->tipoidfiscal;
        $serie = $model->getSerie();

        $tableData = [
            ['key' => $headerData['subject'], 'value' => Utils::fixHtml($model->{$headerData['fieldName']})],
            ['key' => $this->i18n->trans('date'), 'value' => $model->fecha],
            ['key' => $this->i18n->trans('address'), 'value' => $this->getDocAddress($subject, $model)],
            ['key' => $this->i18n->trans('code'), 'value' => $model->codigo],
            ['key' => $tipoidfiscal, 'value' => $model->cifnif],
            ['key' => $this->i18n->trans('tipocomprobante'), 'value' => $model->tipocomprobante],
            ['key' => $this->i18n->trans('number'), 'value' => $model->numero],
            ['key' => $this->i18n->trans('due-date'), 'value' => $model->ncffechavencimiento],
            ['key' => $this->i18n->trans('serie'), 'value' => $serie->descripcion],
        ];

        // rectified invoice?
        if (!empty($model->codigorect) && isset($model->codigorect)) {
            $facturaOrigen = $model->parentDocuments();
            $tableData[9] = ['key' => $this->i18n->trans('ncf-modifies'), 'value' => $facturaOrigen[0]->numeroncf];
        }

        if (property_exists($model, 'numproveedor') && $model->numeroncf) {
            $tableData[3] = ['key' => $this->i18n->trans('ncf-number'), 'value' => $model->numeroncf];
        } elseif (property_exists($model, 'numeroncf') && $model->numeroncf) {
            $tipoComprobante = new NCFTipo();
            $tableData[3] = ['key' => $this->i18n->trans('ncf-number'), 'value' => $model->numeroncf];
        } else {
            $tableData[3] = ['key' => $this->i18n->trans('serie'), 'value' => $serie->descripcion];
            unset($tableData[6]);
        }

        if (property_exists($model, 'tipocomprobante') && $model->tipocomprobante) {
            $tipoComprobante = new NCFTipo();
            $dataTC = $tipoComprobante->get($model->tipocomprobante);
            $tableData[5] = ['key' => $this->i18n->trans('tipocomprobante'), 'value' => $dataTC->descripcion];
        }

        $tableOptions = [
            'width' => $this->tableWidth,
            'showHeadings' => 0,
            'shaded' => 0,
            'lineCol' => [1, 1, 1],
            'cols' => []
        ];
        $this->insertParallelTable($tableData, '', $tableOptions);
        $this->pdf->ezText('');

        if (!empty($model->idcontactoenv)
            && ($model->idcontactoenv !== $model->idcontactofact || !empty($model->codtrans))) {
            $this->insertBusinessDocShipping($model);
        }
    }
}