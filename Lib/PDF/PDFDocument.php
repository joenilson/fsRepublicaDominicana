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

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Lib\TwoFactorManager;
use FacturaScripts\Dinamic\Model\Empresa;
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
            $headerData['title'] = Tools::fixHtml($this->format->titulo);
        }

        $tipoComprobante = new NCFTipo();
        $dataTC = $tipoComprobante::findWhereEq('tipocomprobante', $model->tipocomprobante);
        $y = $this->pdf->y;
        $this->pdf->ezText($dataTC->descripcion, self::FONT_SIZE + 2, ['justification' => 'right']);
        $this->pdf->ezSetY($y);
        $this->pdf->ezText($headerData['title'] . ': ' . $model->numeroncf . "\n", self::FONT_SIZE + 2);
        $this->newLine();

        $subject = $model->getSubject();
        $tipoidfiscal = empty($subject->tipoidfiscal) ? $this->i18n->trans('cifnif') : $subject->tipoidfiscal;

        $tableData = [
            ['key' => $headerData['subject'], 'value' => Tools::fixHtml($model->{$headerData['fieldName']})],
            ['key' => $this->i18n->trans('date'), 'value' => $model->fecha],
            ['key' => $this->i18n->trans('address'), 'value' => $this->getDocAddress($subject, $model)],
            ['key' => $tipoidfiscal, 'value' => $model->cifnif],
            ['key' => $this->i18n->trans('tipocomprobante'), 'value' => $dataTC->descripcion],
            ['key' => $this->i18n->trans('number'), 'value' => $model->numeroncf],
            ['key'=>$this->i18n->trans('code'), 'value'=> $model->codigo],
            ['key' => $this->i18n->trans('due-date'), 'value' => $model->ncffechavencimiento],
            ['key' => $this->i18n->trans('currency'), 'value' => $this->getDivisaName($model->coddivisa)],
        ];

        // rectified invoice?
        if (!empty($model->codigorect)) {
            $facturaOrigen = $model->parentDocuments();
            $tableData[9] = ['key' => $this->i18n->trans('ncf-modifies'), 'value' => $facturaOrigen[0]->numeroncf];
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
    protected function getLineHeaders(): array
    {
        return [
            'codarticulo' => ['type' => 'text', 'title' => $this->i18n->trans('code')],
            'referencia' => ['type' => 'text', 'title' => $this->i18n->trans('reference')],
            'cantidad' => ['type' => 'number', 'title' => $this->i18n->trans('quantity')],
            'pvpunitario' => ['type' => 'number', 'title' => $this->i18n->trans('price')],
            'pvptotal' => ['type' => 'number', 'title' => $this->i18n->trans('subtotal')],
            'iva_value' => ['type' => 'number', 'title' => $this->i18n->trans('iva')],
            'rdtaxisc_value' => ['type' => 'number', 'title' => $this->i18n->trans('rdtaxisc')],
            'rdtaxcdt_value' => ['type' => 'number', 'title' => $this->i18n->trans('rdtaxcdt')],
            'rdtaxlegaltip_value' => ['type' => 'number', 'title' => $this->i18n->trans('rdtaxlegaltip')],
            'rdtaxfirstplate_value' => ['type' => 'number', 'title' => $this->i18n->trans('rdtaxfirstplate')],
            'totalplustaxes' => ['type' => 'number', 'title' => $this->i18n->trans('total')]
        ];
    }

    protected function insertBusinessDocBody($model)
    {
        $qrTitle = $this->pipe('qrTitleAfterLines', $model);
        $qrImage = $this->pipe('qrImageAfterLines', $model);
        $qrSubtitle = $this->pipe('qrSubtitleAfterLines', $model);

        $headers = [];
        $tableOptions = [
            'cols' => [],
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'width' => $this->tableWidth
        ];

        // fill headers and options with the line headers information
        $lineHeaders = $this->getLineHeaders();
        foreach ($lineHeaders as $key => $value) {
            $headers[$key] = $value['title'];
            if (in_array($value['type'], ['number', 'percentage'], true)) {
                $tableOptions['cols'][$key] = ['justification' => 'right'];
            }
        }

        $allLines = $model->getlines();
        $fullTableData = [];
        foreach ($allLines as $line) {
            // Calcular valores adicionales en valor monetario
            $line->iva_value = (float)($line->pvptotal ?? 0) * (float)($line->iva ?? 0) / 100.0;
            $line->rdtaxisc_value = (float)($line->pvpsindto ?? 0) * (float)($line->rdtaxisc ?? 0) / 100.0;
            $line->rdtaxcdt_value = (float)($line->pvpsindto ?? 0) * (float)($line->rdtaxcdt ?? 0) / 100.0;
            $line->rdtaxlegaltip_value = (float)($line->pvpsindto ?? 0) * (float)($line->rdtaxlegaltip ?? 0) / 100.0;
            $line->rdtaxfirstplate_value = (float)($line->pvpsindto ?? 0) * (float)($line->rdtaxfirstplate ?? 0) / 100.0;

            $data = [];
            foreach ($lineHeaders as $key => $value) {
                if (property_exists($line, 'mostrar_precio') &&
                    $line->mostrar_precio === false &&
                    in_array($key, ['pvpunitario', 'dtopor', 'dtopor2', 'pvptotal', 'iva', 'recargo', 'irpf', 'iva_value', 'rdtaxisc_value', 'rdtaxcdt_value', 'rdtaxlegaltip_value', 'rdtaxfirstplate_value', 'totalplustaxes'], true)) {
                    continue;
                }

                if ($key === 'referencia') {
                    $data[$key] = empty($line->referencia) ? Tools::fixHtml($line->descripcion) : Tools::fixHtml($line->referencia . " - " . $line->descripcion);
                } elseif ($key === 'cantidad' && property_exists($line, 'mostrar_cantidad')) {
                    $data[$key] = $line->mostrar_cantidad ? $line->{$key} : '';
                } elseif ($value['type'] === 'percentage') {
                    $data[$key] = Tools::number($line->{$key}) . '%';
                } elseif ($value['type'] === 'number') {
                    $data[$key] = Tools::number($line->{$key});
                } else {
                    $data[$key] = $line->{$key};
                }
            }
            $fullTableData[] = $data;
        }

        // Eliminamos las columnas vacías en todo el documento para mantener consistencia
        $this->removeEmptyCols($fullTableData, $headers, Tools::number(0));

        $tableData = [];
        foreach ($fullTableData as $index => $data) {
            $tableData[] = $data;
            $line = $allLines[$index];

            if (property_exists($line, 'salto_pagina') && $line->salto_pagina) {
                $this->pdf->ezTable($tableData, $headers, '', $tableOptions);
                $tableData = [];
                $this->pdf->ezNewPage();
            }
        }

        if (false === empty($tableData)) {
            $this->pdf->ezTable($tableData, $headers, '', $tableOptions);
        }

        // añadir el código QR si existe
        if ($model->modelClassName() === 'FacturaCliente' && !empty($qrImage)) {
            // Añadir margen superior antes del QR
            $this->pdf->y -= 10;

            // Calcular el ancho disponible con margen derecho (usar mismo layout que el header)
            $pageWidth = $this->pdf->ez['pageWidth'] - $this->pdf->ez['leftMargin'] - $this->pdf->ez['rightMargin'];
            $rightBlockWidth = $pageWidth * 0.2; // 20% para el QR (igual que en header)
            $leftBlockWidth = $pageWidth * 0.8;  // 80% espacio libre a la izquierda (igual que en header)

            $this->renderQRimage($qrImage, $qrTitle, $qrSubtitle, $this->pdf->ez['leftMargin'], $this->pdf->y, $leftBlockWidth, $rightBlockWidth);
        }
    }

    protected function insertBusinessDocFooter($model)
    {
        if (!empty($model->observaciones)) {
            $this->newPage();
            $this->pdf->ezText($this->i18n->trans('observations') . "\n", self::FONT_SIZE);
            $this->newLine();
            $this->pdf->ezText(Tools::fixHtml($model->observaciones) . "\n", self::FONT_SIZE);
        }

        $this->newPage();

        // taxes
        $taxHeaders = [
            'tax' => $this->i18n->trans('tax'),
            'taxbase' => $this->i18n->trans('tax-base'),
            'taxp' => $this->i18n->trans('percentage'),
            'taxamount' => $this->i18n->trans('amount'),
            'taxsurchargep' => $this->i18n->trans('re'),
            'taxsurcharge' => $this->i18n->trans('amount')
        ];
        $taxRows = $this->getTaxesRows($model);
        $taxTableOptions = [
            'cols' => [
                'tax' => ['justification' => 'right'],
                'taxbase' => ['justification' => 'right'],
                'taxp' => ['justification' => 'right'],
                'taxamount' => ['justification' => 'right'],
                'taxsurchargep' => ['justification' => 'right'],
                'taxsurcharge' => ['justification' => 'right']
            ],
            'shadeCol' => [0.95, 0.95, 0.95],
            'shadeHeadingCol' => [0.95, 0.95, 0.95],
            'width' => $this->tableWidth
        ];
        if (count($taxRows) > 1) {
            $this->removeEmptyCols($taxRows, $taxHeaders, Tools::number(0));
            $this->pdf->ezTable($taxRows, $taxHeaders, '', $taxTableOptions);
            $this->pdf->ezText("\n");
        } elseif ($this->pdf->ezPageCount < 2 && strlen($this->format->texto ?? '') < 400 && $this->pdf->y > static::INVOICE_TOTALS_Y) {
            $this->pdf->y = static::INVOICE_TOTALS_Y;
        }

        // Tarea 2: Totales específicos
        $headers = [
            'label' => '',
            'value' => ''
        ];
        $rows = [
            ['label' => $this->i18n->trans('total-exempt'), 'value' => Tools::number($model->totalexento)],
            ['label' => $this->i18n->trans('total-taxed'), 'value' => Tools::number($model->neto - $model->totalexento)],
        ];

        // Suma de cada impuesto adicional
        $addedTaxes = [
            'rdtaxisc' => 0,
            'rdtaxcdt' => 0,
            'rdtaxlegaltip' => 0,
            'rdtaxfirstplate' => 0
        ];
        foreach ($model->getLines() as $line) {
            foreach (array_keys($addedTaxes) as $tax) {
                if (!empty($line->{$tax})) {
                    $addedTaxes[$tax] += (float)$line->pvpsindto * (float)$line->{$tax} / 100.0;
                }
            }
        }
        $rows[] = ['label' => $this->i18n->trans('iva') . ' ' . $line->iva . '%', 'value' => Tools::number($model->totaliva)];
        foreach ($addedTaxes as $tax => $value) {
            if ($value > 0) {
                $rows[] = ['label' => $this->i18n->trans($tax) . ' ' . $line->{$tax} . '%', 'value' => Tools::number($value)];
            }
        }

        $rows[] = ['label' => $this->i18n->trans('total-added-taxes') , 'value' => Tools::number($model->totaladdedtaxes)];

        $total_final = ($model->totalplustaxes !== 0.0) ? $model->totalplustaxes : ($model->totaliva + $model->totaladdedtaxes + $model->neto);

        $rows[] = ['label' => $this->i18n->trans('total'), 'value' => Tools::number($total_final)];

        // QR y Datos de Seguridad
        $qrBlockWidth = 0;
        $startY = $this->pdf->y;
        $qrBottomY = $startY;

        if ($model->modelClassName() === 'FacturaCliente') {
            $code = Tools::settings('default', 'idempresa', '');
            $company = new Empresa();
            if ($company->load($code)) {
                $url = "https://fc.dgii.gov.do/ecf/consultatimbrefc?" . http_build_query([
                    'RncEmisor' => $company->cifnif,
                    'ENCF' => $model->numeroncf,
                    'FechaEmision' => $model->fecha,
                    'MontoTotal' => $model->totalplustaxes,
                    'FechaFirma' => $model->ecf_fecha_firma,
                    'CodigoSeguridad' => $model->ecf_codigo_seguridad
                ]);

                $qrImage = TwoFactorManager::getQRCodeImage($url);
                $qrBlockWidth = 130;
                $this->renderQRimage($qrImage, null, null, $this->pdf->ez['leftMargin'], $startY + 15, 0, $qrBlockWidth);

                // Manualmente agregamos los datos de seguridad debajo del QR
                $qrSize = 110;
                $qrX = $this->pdf->ez['leftMargin'] + 10 + ($qrBlockWidth - 10 - $qrSize) / 2;
                $textX = $qrX + 6;
                $textY = $startY - $qrSize;
                $this->pdf->addText($textX, $textY, self::FONT_SIZE - 2, $this->i18n->trans('desc-ecf_codigo_seguridad') . ": " . $model->ecf_codigo_seguridad, 0, 'left');
                $this->pdf->addText($textX, $textY - self::FONT_SIZE, self::FONT_SIZE - 2, $this->i18n->trans('desc-ecf_fecha_firma') . ': ' . $model->ecf_fecha_firma, 0, 'left');
                $qrBottomY = $textY - self::FONT_SIZE - 10;
            }
        }

        // Totales al lado del QR
        $this->pdf->ezSetY($startY);
        $tableOptions = [
            'showHeadings' => 0,
            'shaded' => 0,
            'width' => $this->tableWidth - $qrBlockWidth,
            'cols' => [
                'label' => ['justification' => 'right', 'width' => ($this->tableWidth - $qrBlockWidth) * 0.8],
                'value' => ['justification' => 'right', 'width' => ($this->tableWidth - $qrBlockWidth) * 0.2]
            ]
        ];

        $oldMarginLeft = $this->pdf->ez['leftMargin'];
        $this->pdf->ez['leftMargin'] += $qrBlockWidth;
        $this->pdf->ezTable($rows, $headers, '', $tableOptions);
        $tableBottomY = $this->pdf->y;
        $this->pdf->ez['leftMargin'] = $oldMarginLeft;

        // Aseguramos que los siguientes elementos comiencen después de ambos bloques
        $this->pdf->y = min($qrBottomY, $tableBottomY);

        // receipts
        if ($model->modelClassName() === 'FacturaCliente') {
            $this->insertInvoiceReceipts($model);
        } elseif (isset($model->codcliente)) {
            $this->insertInvoicePayMethod($model);
        }

        if (!empty($this->format->texto)) {
            $this->pdf->ezText("\n" . Tools::fixHtml($this->format->texto), self::FONT_SIZE);
        }
    }
}