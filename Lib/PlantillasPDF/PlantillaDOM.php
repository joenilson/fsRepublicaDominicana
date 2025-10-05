<?php
/*
 * Copyright (C) 2025 Joe Nilson <joenilson@gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Lib\PlantillasPDF;

use DeepCopy\DeepCopy;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Dinamic\Lib\PlantillasPDF\Helper\PaymentMethodBankDataHelper;
use FacturaScripts\Dinamic\Lib\PlantillasPDF\Helper\ReceiptBankDataHelper;
use FacturaScripts\Dinamic\Model\AgenciaTransporte;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Plugins\PlantillasPDF\Lib\PlantillasPDF\BaseTemplate;
use FacturaScripts\Core\Tools;

class PlantillaDOM extends BaseTemplate
{

    public function addInvoiceFooter($model)
    {
        $i18n = Tools::lang();

    }

    public function addInvoiceHeader($model)
    {
        // TODO: Implement addInvoiceHeader() method.
        $html = $this->getInvoiceHeaderBilling($model)
            . $this->getInvoiceHeaderShipping($model);
        $this->writeHTML('<table class="table-big table-border"><tr>' . $html . '</tr></table><br/>');
    }

    public function addInvoiceLines($model)
    {
        $lines = $model->getLines();
        $this->autoHideLineColumns($lines);

        $tHead = '<thead><tr>';
        foreach ($this->getInvoiceLineFields() as $field) {
            $tHead .= '<th class="' . $field['css'] . '" align="' . $field['align'] . '">' . $field['title'] . '</th>';
        }
        $tHead .= '</tr></thead>';

        $tBody = '';
        $numLinea = 1;
        $tLines = [];
        foreach ($lines as $line) {
            $tLines[] = $line;
            $line->numlinea = $numLinea;
            $tBody .= '<tr>';
            foreach ($this->getInvoiceLineFields() as $field) {
                $tBody .= '<td class="' . $field['css'] . '" align="' . $field['align'] . '" valign="top">' . $this->getInvoiceLineValue($model, $line, $field) . '</td>';
            }
            $tBody .= '</tr>';
            $numLinea++;

            if (property_exists($line, 'salto_pagina') && $line->salto_pagina) {
                $this->writeHTML('<div class="table-lines"><table class="table-big table-list">' . $tHead . $tBody . '</table></div>');
                $this->writeHTML($this->getInvoiceTotalsPartial($model, $tLines, 'mt-20'));
                $this->mpdf->AddPage();
                $tBody = '';
            }
        }

        $this->writeHTML('<div class="table-lines"><table class="table-big table-list">' . $tHead . $tBody . '</table></div>');

        // clonamos el documento y añadimos los totales para ver si salta de página
        $copier = new DeepCopy();
        $clonedPdf = $copier->copy($this->mpdf);
        $clonedPdf->writeHTML($this->getInvoiceTotalsFinal($model, 'mt-20'));

        // comprobamos si clonedPdf tiene más páginas que el original
        if (count($clonedPdf->pages) > count($this->mpdf->pages)) {
            $this->mpdf->AddPage();
        }

        // si tiene las mismas páginas, añadimos los totales
        $this->writeHTML($this->getInvoiceTotalsFinal($model, 'mt-20'));
    }

    protected function css(): string
    {
        return parent::css()
            . '.title {border-bottom: 2px solid ' . $this->get('color1') . ';}'
            . '.table-border {border-top: 1px solid ' . $this->get('color1') . '; border-bottom: 1px solid ' . $this->get('color1') . ';}'
            . '.table-dual {border-top: 1px solid ' . $this->get('color1') . '; border-bottom: 1px solid ' . $this->get('color1') . ';}'
            . '.table-list {border-spacing: 0px; border-top: 1px solid ' . $this->get('color1') . '; border-bottom: 1px solid ' . $this->get('color1') . ';}'
            . '.table-list tr:nth-child(even) {background-color: ' . $this->get('color3') . ';}'
            . '.table-list th {background-color: ' . $this->get('color1') . '; color: ' . $this->get('color2') . '; padding: 5px; text-transform: uppercase;}'
            . '.table-list td {padding: 5px;}'
            . '.thanks-title {'
            . 'font-size: ' . $this->get('titlefontsize') . 'px; font-weight: bold; color: ' . $this->get('color1') . '; '
            . 'text-align: right; width: 50%; padding: 15px; border-end: 1px solid ' . $this->get('color1') . ';'
            . '}'
            . '.color-navy {color: navy;}'
            . '.color-blue {color: blue;}'
            . '.color-template {color: ' . $this->get('color1') .';}'
            . '.thanks-text {padding: 15px;}'
            . '.imagetext {margin-top: 15px; text-align: ' . $this->get('endalign') . ';}'
            . '.imagefooter {text-align: ' . $this->get('footeralign') . ';}';
    }

    protected function getSubjectIdFiscalStr(BusinessDocument $model): string
    {
        return empty($model->cifnif) ? '' : '<b>' . $model->getSubject()->tipoidfiscal . '</b>: ' . $model->cifnif;
    }

    protected function getInvoiceHeaderBilling($model): string
    {
        if ($this->format->hidebillingaddress) {
            return '';
        }

        $subject = $model->getSubject();
        $address = isset($model->codproveedor) && !isset($model->direccion) ? $subject->getDefaultAddress() : $model;
        $customerCode = $this->get('showcustomercode') ? $model->subjectColumnValue() : '';
        $customerEmail = $this->get('showcustomeremail') && !empty($subject->email) ? '<br>' . Tools::lang()->trans('email') . ': ' . $subject->email : '';
        $break = empty($model->cifnif) ? '' : '<br/>';
        return '<td align="left" valign="top">'
            . '<br/><b> ' . $this->getSubjectTitle($model) . ':</b> ' . $customerCode
            . $this->getSubjectName($model) . $break . $this->getSubjectIdFiscalStr($model)
            . '<br/><b>' . Tools::lang()->trans('address'). ':</b> ' .$this->combineAddress($address) . $this->getInvoiceHeaderBillingPhones($subject)
            . '<br/><b>' . Tools::lang()->trans('email'). ':</b> ' .$customerEmail
            . '<br/>'
            . '</td>';
    }

    protected function getInvoiceHeaderShipping($model): string
    {
        if ($this->format->hideshippingaddress) {
            return '';
        }

        $contacto = new Contacto();
        if ($this->get('hideshipping') ||
            !isset($model->idcontactoenv) ||
            empty($model->idcontactoenv) ||
            $model->idcontactoenv == $model->idcontactofact ||
            false === $contacto->load($model->idcontactoenv)) {
            return '';
        }

        return '<td><b>' . Tools::lang()->trans('shipping-address') . '</b>'
            . '<br/>' . $this->combineAddress($contacto, true) . '</td>';
    }

    protected function getInvoiceHeaderBillingPhones($subject): string
    {
        if (true !== $this->get('showcustomerphones')) {
            return '';
        }

        $strPhones = $this->getPhones($subject->telefono1, $subject->telefono2);
        if (empty($strPhones)) {
            return '';
        }

        return '<br/>' . $strPhones;
    }

    protected function headerLeft(): string
    {
        $i18n = Tools::lang();
        $contactData = [];
        foreach (['telefono1', 'telefono2', 'email', 'web'] as $field) {
            if ($this->empresa->{$field}) {
                $contactData[] = $this->empresa->{$field};
            }
        }

        $title = $this->showHeaderTitle ? '<h1 class="title">' . $this->get('headertitle') . '</h1>' . $this->spacer() : '';
        if ($this->isSketchInvoice()) {
            $title .= '<div class="color-red font-big font-bold">' . Tools::lang()->trans('invoice-is-sketch') . '</div>';
        }

        $descTC = ($this->headerModel->tipocomprobante < 30) ? "<b>NCF:</b> " : "<b>e-NCF:</b> ";
        $fechaVencimiento = ($this->headerModel->ncffechavencimiento !== null) ? '<b>Fecha Vencimiento:</b> ' . $this->headerModel->ncffechavencimiento : '';
        $fechaEmision = '<b>Fecha Emisi&oacute;n:</b> ' . $this->headerModel->fecha;

        return '<table class="table-big">'
            . '<tr>'
                . '<td valign="top"><img src="' . $this->logoPath . '" height="' . $this->get('logosize') . '"/>' . '</td>'
                . '<td align="right" valign="top">' . $title . '</td>'
            . '</tr>'
            . '<tr>'
                . '<td align="left" valign="top">'
                . '<p><b>' . $this->empresa->nombre . '</b>'
                . '<br/>' . $this->empresa->tipoidfiscal . ': ' . $this->empresa->cifnif
                . '<br/>' . Tools::lang()->trans('address') . ': ' . $this->combineAddress($this->empresa) . '</p>'
                . '<p>' . implode(' · ', $contactData) . '</p>'
                . $fechaEmision
                . '</td>'
                . '<td align="right" valign="top">'
                .  '<div class="color-template font-big font-bold">' . $this->headerModel->descripcionTipoComprobante() . '</div>'
                .  $descTC
                .  $this->headerModel->numeroncf . '<br/>'
                .  $fechaVencimiento . '<br/>'
                .  '<b>' . $i18n->trans('payment-method') . ':</b> '
                .  PaymentMethodBankDataHelper::get($this->headerModel)
                . '</td>'
            . '</tr>'
            . '</table>';
    }
    protected function headerRight(): string
    {
        $contactData = [];
        foreach (['telefono1', 'telefono2', 'email', 'web'] as $field) {
            if ($this->empresa->{$field}) {
                $contactData[] = $this->empresa->{$field};
            }
        }

        $title = $this->showHeaderTitle ? '<h1 class="title">' . $this->get('headertitle') . '</h1>' . $this->spacer() : '';
        if ($this->isSketchInvoice()) {
            $title .= '<div class="color-red font-big font-bold">' . Tools::lang()->trans('invoice-is-sketch') . '</div>';
        }

        return '<table class="table-big">'
            . '<tr>'
            . '<td> Titulo ' . $title
            . '<p><b>' . $this->empresa->nombre . '</b>'
            . '<br/>' . $this->empresa->tipoidfiscal . ': ' . $this->empresa->cifnif
            . '<br/>' . $this->combineAddress($this->empresa) . '</p>' . $this->spacer()
            . '<p>' . implode(' · ', $contactData) . '</p>'
            . '</td>'
            . '<td align="right"><img src="' . $this->logoPath . '" height="' . $this->get('logosize') . '"/></td>'
            . '</tr>'
            . '</table>';
    }

    protected function getInvoiceTotalsFinal($model, $css = ''): string
    {
        $observations = '';
        if (!empty($this->getObservations($model))) {
            $observations .= '<p><b>' . Tools::lang()->trans('observations') . '</b><br/>'
                . $this->getObservations($model) . '</p>';
        }

        return $this->format->hidetotals ?
            $observations :
            $this->getInvoiceTotalsPartial($model, [], $css) . $observations;
    }

    protected function getInvoiceTotalsPartial($model, array $lines = [], string $css = ''): string
    {
        if ($this->format->hidetotals) {
            return '';
        }

        $i18n = Tools::lang();
        $trs = '';
        $fields = [
            'netosindto' => $i18n->trans('subtotal'),
            'dtopor1' => $i18n->trans('global-dto'),
            'dtopor2' => $i18n->trans('global-dto-2'),
            'neto' => $i18n->trans('net'),
            'totaliva' => $i18n->trans('taxes'),
            'totalrecargo' => $i18n->trans('re'),
            'totalirpf' => $i18n->trans('retention'),
            'totalsuplidos' => $i18n->trans('supplied-amount'),
            'total' => $i18n->trans('total'),
        ];

        $lines = empty($lines) ? $model->getLines() : $lines;
        $this->getTotalsModel($model, $lines);
        $taxes = $this->getTaxesRows($model, $lines);
        $irpfs = $this->format->hide_breakdowns ? [] : $this->getIrpfs($model, $lines);

        // pintamos los irpfs
        foreach ($irpfs as $irpf) {
            $trs .= '<tr>'
                . '<td align="right"><b>' . $irpf['name'] . '</b>:</td>'
                . '<td class="nowrap" align="right">' . Tools::money($irpf['total'], $model->coddivisa) . '</td>'
                . '</tr>';
        }

        // ocultamos el neto si no hay impuestos o si hay un impuesto y el neto es igual al neto sin dto
        if (empty($taxes['iva']) || (count($taxes['iva']) == 1 && $model->neto == $model->netosindto)) {
            unset($fields['neto']);
            unset($fields['totaliva']);
        }

        // si tenemos marcada la opción de ocultar desgloses, eliminamos todos los campos excepto el total
        if ($this->format->hide_breakdowns) {
            $fields = ['total' => $i18n->trans('total')];
        }

        foreach ($fields as $key => $title) {
            if (empty($model->{$key}) || $key === 'totalirpf') {
                continue;
            }

            switch ($key) {
                case 'dtopor1':
                case 'dtopor2':
                    $trs .= '<tr>'
                        . '<td align="right"><b>' . $title . '</b>:</td>'
                        . '<td class="nowrap" align="right">' . Tools::number($model->{$key}) . '%</td>'
                        . '</tr>';
                    break;

                case 'total':
                    $trs .= '<tr>'
                        . '<td class="text-end"><b>' . $title . '</b>:</td>'
                        . '<td class="text-end nowrap">' . Tools::money($model->{$key}, $model->coddivisa) . '</td>'
                        . '</tr>';
                    break;

                case 'netosindto':
                    if ($model->netosindto == $model->neto) {
                        break;
                    }
                // no break
                default:
                    $trs .= '<tr>'
                        . '<td align="right"><b>' . $title . '</b>:</td>'
                        . '<td class="nowrap" align="right">' . Tools::money($model->{$key}, $model->coddivisa) . '</td>'
                        . '</tr>';
                    break;
            }
        }

        return '<table class="table-big table-border ' . $css . '">'
            . '<tr>'
            . '<td> ' . $this->getInvoiceTaxes($model, $lines) . '</td>'
            . '<td align="right" valign="top"><table>' . $trs . '</table></td>'
            . '</tr>'
            . '<tr>'
            . '<td>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '&nbsp;<b>Son:</b> ' .  $this->convert_to_words($model->total) . ' pesos dominicanos'
            . '<br/>';
    }

    protected function getInvoiceTaxes(BusinessDocument $model, array $lines, string $class = 'table-big'): string
    {
        if ($this->format->hide_vat_breakdown) {
            return '';
        }

        $taxes = $this->getTaxesRows($model, $lines);
        if (empty($taxes['iva']) && empty($model->totalirpf)) {
            return '';
        }

        $i18n = Tools::lang();

        $trs = '';
        foreach ($taxes['iva'] as $row) {
            $trs .= '<tr>'
                . '<td class="nowrap" align="left">' . Impuestos::get($row['codimpuesto'])->descripcion . '</td>'
                . '<td class="nowrap" align="center">' . Tools::money($row['neto'], $model->coddivisa) . '</td>'
                . '<td class="nowrap" align="center">' . Tools::number($row['iva']) . '%</td>'
                . '<td class="nowrap" align="center">' . Tools::money($row['totaliva'], $model->coddivisa) . '</td>';

            if (empty($model->totalrecargo)) {
                $trs .= '</tr>';
                continue;
            }

            $trs .= '<td class="nowrap" align="center">' . (empty($row['recargo']) ? '-' : Tools::number($row['recargo']) . '%') . '</td>'
                . '<td class="nowrap" align="right">' . (empty($row['totalrecargo']) ? '-' : Tools::money($row['totalrecargo'])) . '</td>'
                . '</tr>';
        }

        if (empty($model->totalrecargo)) {
            return '<table class="' . $class . '">'
                . '<thead>'
                . '<tr>'
                . '<th align="left">' . $i18n->trans('tax') . '</th>'
                . '<th align="center">' . $i18n->trans('tax-base') . '</th>'
                . '<th align="center">' . $i18n->trans('percentage') . '</th>'
                . '<th align="center">' . $i18n->trans('amount') . '</th>'
                . '</tr>'
                . '</thead>'
                . $trs
                . '</table>';
        }

        return '<table class="' . $class . '">'
            . '<tr>'
            . '<th align="left">' . $i18n->trans('tax') . '</th>'
            . '<th align="center">' . $i18n->trans('tax-base') . '</th>'
            . '<th align="center">' . $i18n->trans('tax') . '</th>'
            . '<th align="center">' . $i18n->trans('amount') . '</th>'
            . '<th align="center">' . $i18n->trans('re') . '</th>'
            . '<th align="right">' . $i18n->trans('amount') . '</th>'
            . '</tr>'
            . $trs
            . '</table>';
    }

    private function convert_to_words($number): ?string
    {
        $words = array(
            0 => 'cero',
            1 => 'un',
            2 => 'dos',
            3 => 'tres',
            4 => 'cuatro',
            5 => 'cinco',
            6 => 'seis',
            7 => 'siete',
            8 => 'ocho',
            9 => 'nueve',
            10 => 'diez',
            11 => 'once',
            12 => 'doce',
            13 => 'trece',
            14 => 'catorce',
            15 => 'quince',
            16 => 'dieciséis',
            17 => 'diecisiete',
            18 => 'dieciocho',
            19 => 'diecinueve',
            20 => 'veinte',
            30 => 'treinta',
            40 => 'cuarenta',
            50 => 'cincuenta',
            60 => 'sesenta',
            70 => 'setenta',
            80 => 'ochenta',
            90 => 'noventa',
            100 => 'cien',
            200 => 'doscientos',
            300 => 'trescientos',
            400 => 'cuatrocientos',
            500 => 'quinientos',
            600 => 'seiscientos',
            700 => 'setecientos',
            800 => 'ochocientos',
            900 => 'novecientos'
        );

        if ($number == 0) {
            return ucfirst($words[0]);
        }

        if ($number == 1) {
            return ucfirst("uno");
        }

        if ($number <= 20) {
            return ucfirst($words[$number]);
        }

        if ($number < 100) {
            return ucfirst($words[10 * floor($number / 10)]
                . ($number % 10 > 0 ? ' y ' . $words[$number % 10] : ''));
        }

        if ($number < 1000) {
            $hundreds = floor($number / 100) * 100;
            return ucfirst($words[$hundreds]
                . ($number % 100 > 0 ? ($hundreds == 100 ? 'to ' : ' ')
                    . $this->convert_to_words($number % 100) : ''));
        }

        if ($number < 1000000) {
            $thousands = floor($number / 1000);
            return ucfirst(($thousands > 1 ? $this->convert_to_words($thousands) . ' ' : '')
                . 'mil'
                . ($number % 1000 > 0 ? ' ' . $this->convert_to_words($number % 1000) : ''));
        }

        if ($number < 1000000000) {
            $millions = floor($number / 1000000);
            return ucfirst(($millions > 1 ? $this->convert_to_words($millions) . ' ' : 'un ')
                . 'millón'
                . ($millions > 1 ? 'es' : '')
                . ($number % 1000000 > 0 ? ' ' . $this->convert_to_words($number % 1000000) : ''));
        }

        $billions = floor($number / 1000000000);
        return ucfirst(($billions > 1 ? $this->convert_to_words($billions) . ' ' : 'un ')
            . 'billón'
            . ($billions > 1 ? 'es' : '')
            . ($number % 1000000000 > 0 ? ' ' . $this->convert_to_words($number % 1000000000) : ''));
    }
}