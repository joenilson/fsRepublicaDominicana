<?php
/*
 * Copyright (C) 2025-2026 Joe Nilson <joenilson at gmail.com>
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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Mod;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Contract\PurchasesLineModInterface;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Translator;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DGII\ImpuestoAdicionalCalculator;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoAdicional;
use FacturaScripts\Core\Tools;

class PurchasesLineHTMLMod implements PurchasesLineModInterface
{

    public function apply(PurchaseDocument &$model, array &$lines, array $formData): void
    {
        // TODO: Implement apply() method.
    }

    public function applyToLine(array $formData, BusinessDocumentLine &$line, string $id): void
    {
        // TODO: Implement applyToLine() method.
        $line->rdtaxcodisc = $formData['rdtaxcodisc_' . $id] ?? $line->rdtaxcodisc;
        $line->rdtaxcodcdt = $formData['rdtaxcodcdt_' . $id] ?? $line->rdtaxcodcdt;
        $line->rdtaxcodlegaltip = $formData['rdtaxcodlegaltip_' . $id] ?? $line->rdtaxcodlegaltip;
        $line->rdtaxcodfirstplate = $formData['rdtaxcodfirstplate_' . $id] ?? $line->rdtaxcodfirstplate;
        $line->totalplustaxes = $formData['totalplustaxes_' . $id] ?? $line->totalplustaxes;

        // monto del ISC digitado a mano: el checkbox solo viaja marcado, por eso se
        // comprueba la presencia de los campos de monto de la misma línea
        if (isset($formData['rdtaxiscespecifico_' . $id]) || isset($formData['rdtaxiscadvalorem_' . $id])) {
            $line->rdtaxmanual = isset($formData['rdtaxmanual_' . $id]);
            if ($line->rdtaxmanual) {
                $line->rdtaxiscespecifico = $this->toFloat($formData['rdtaxiscespecifico_' . $id] ?? 0);
                $line->rdtaxiscadvalorem = $this->toFloat($formData['rdtaxiscadvalorem_' . $id] ?? 0);
            }
        }
    }

    private function toFloat($value): float
    {
        return (float)str_replace(',', '.', (string)$value);
    }

    public function assets(): void
    {
        // TODO: Implement assets() method.
    }

    /**
     * @param Translator $i18n
     * @param string $attributes
     * @param string $label
     * @param int $idlinea
     * @param array $options
     * @return string
     */
    private function getStr(Translator $i18n, string $attributes, string $label, $idlinea, array $options): string
    {
        $idlinea  = (!$idlinea) ? 0 : $idlinea;
        return '<div class="col-6">'
            . '<div class="mb-2">'
            . $i18n->trans($label)
            . '<select onchange="return purchasesFormAction(\'recalculate-line\', \'' . $idlinea . '\');"'
            . $attributes . ' class="form-select" >' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    public function getFastLine(PurchaseDocument $model, array $formData): ?BusinessDocumentLine
    {
        return null;
    }

    private function getLineAddedTaxes(BusinessDocumentLine $line): float
    {
        return ImpuestoAdicionalCalculator::lineAmounts($line)['total'];
    }

    public function map(array $lines, PurchaseDocument $model): array
    {
        $map = [];
        $num = 0;
        foreach ($lines as $line) {
            $num++;
            $idlinea = $line->idlinea ?? 'n' . $num;
            $map['rdtaxcodisc_' . $idlinea] = $line->rdtaxcodisc;
            $map['rdtaxcodcdt_' . $idlinea] = $line->rdtaxcodcdt;
            $map['rdtaxcodlegaltip_' . $idlinea] = $line->rdtaxcodlegaltip;
            $map['rdtaxcodfirstplate_' . $idlinea] = $line->rdtaxcodfirstplate;
            $map['rdtaxiscespecifico_' . $idlinea] = $line->rdtaxiscespecifico;
            $map['rdtaxiscadvalorem_' . $idlinea] = $line->rdtaxiscadvalorem;
            // El total de la línea es el totalplustaxes (pvptotal + IVA)
            $map['totalplustaxes_' . $idlinea] = $line->totalplustaxes;
        }
        return $map;
    }

    public function newFields(): array
    {
        return ['totalplustaxes'];
    }

    public function newModalFields(): array
    {
        return ['rdtaxcodisc', 'rdtaxmanual', 'rdtaxiscespecifico', 'rdtaxiscadvalorem', 'rdtaxcodcdt', 'rdtaxcodlegaltip', 'rdtaxcodfirstplate'];
    }

    public function newTitles(): array
    {
        return ['totalplustaxes'];
    }

    public function renderField(string $idlinea, BusinessDocumentLine $line, PurchaseDocument $model, string $field): ?string
    {
        $i18n = new Translator();
        switch ($field) {
            case "rdtaxcodisc":
                return $this->loadCodeISC($idlinea, $line, $model->editable);
            case "rdtaxmanual":
                return $this->renderManualToggle($idlinea, $line, $model->editable);
            case "rdtaxiscespecifico":
                return $this->renderManualAmount($idlinea, $line, $model->editable, 'rdtaxiscespecifico');
            case "rdtaxiscadvalorem":
                return $this->renderManualAmount($idlinea, $line, $model->editable, 'rdtaxiscadvalorem');
            case "rdtaxcodcdt":
                return $this->loadCodeCDT($idlinea, $line, $model->editable);
            case "rdtaxcodlegaltip":
                return $this->loadCodeLegalTip($idlinea, $line, $model->editable);
            case "rdtaxcodfirstplate":
                return $this->loadCodeFirstPlate($idlinea, $line, $model->editable);
            case "totalplustaxes":
                return $this->renderTotalField($idlinea, $line, $model, $model->editable);
            default:
                return null;
        }
    }

    private function renderManualToggle(string $idlinea, BusinessDocumentLine $line, bool $editable): string
    {
        $checked = !empty($line->rdtaxmanual) ? ' checked=""' : '';
        $disabled = $editable ? '' : ' disabled=""';
        return '<div class="col-6">'
            . '<div class="form-check mt-4">'
            . '<input type="checkbox" class="form-check-input isc-manual-toggle" id="rdtaxmanual_' . $idlinea . '"'
            . ' name="rdtaxmanual_' . $idlinea . '" value="1" data-idlinea="' . $idlinea . '"' . $checked . $disabled
            . ' onchange="return purchasesFormAction(\'recalculate-line\', \'' . $idlinea . '\');"/>'
            . '<label class="form-check-label" for="rdtaxmanual_' . $idlinea . '">'
            . Tools::trans('isc-manual-amount') . '</label>'
            . '</div>'
            . '</div>';
    }

    private function renderManualAmount(string $idlinea, BusinessDocumentLine $line, bool $editable, string $field): string
    {
        $nf0 = Tools::settings('default', 'decimals', 2);
        $readonly = ($editable && !empty($line->rdtaxmanual)) ? '' : ' readonly=""';
        $label = $field === 'rdtaxiscespecifico' ? 'isc-specific-amount' : 'isc-advalorem-amount';
        return '<div class="col-6">'
            . '<div class="mb-2">'
            . Tools::trans($label)
            . '<input type="number" step="any" class="form-control isc-manual-amount" name="' . $field . '_' . $idlinea . '"'
            . ' data-idlinea="' . $idlinea . '" value="' . number_format((float)$line->{$field}, $nf0, '.', '') . '"'
            . $readonly . ' onchange="return purchasesFormAction(\'recalculate-line\', \'' . $idlinea . '\');"/>'
            . '</div>'
            . '</div>';
    }

    private function renderTotalField(string $idlinea, BusinessDocumentLine $line, PurchaseDocument $model): string
    {
        $nf0 = Tools::settings('default', 'decimals', 2);

        return '<div class="col col-lg-1 order-8 columnTotalPlusTaxes" lang="es-DO">'
            . '<div class="d-lg-none mt-2 small">' . Tools::trans('desc-total-plustaxes') . '</div>'
            . '<input type="number" name="totalplustaxes_' . $idlinea . '"  value="' . number_format($line->totalplustaxes, $nf0, '.', '')
            . '" class="form-control form-control-sm text-lg-end border-0" readonly/></div>';
    }

    public function loadCodeISC(string $idlinea, BusinessDocumentLine $line, $editable): string
    {
        $i18n = new Translator();
        $iscList = ImpuestoAdicional::all([new DataBaseWhere('tipo_impuesto_short', 'ISC')], ['codigo' => 'ASC']);
        if (!$iscList) {
            return '';
        }

        $invoiceLineISC = $line->rdtaxcodisc ?: '';
        $options = ['<option value="">------</option>'];
        foreach ($iscList as $row) {
            $selected = ($row->codigo === $invoiceLineISC) ? ' selected=""' : '';
            $options[] = '<option value="' . $row->codigo . '"' . $selected . '>'
                . Tools::noHtml($row->optionLabel()) . '</option>';
        }

        // el selector con búsqueda (select2) se inicia en CommonDomFunctions.js
        $attributes = 'name="rdtaxcodisc_' . $idlinea . '"' . ($editable ? '' : ' disabled=""')
            . ' data-isc-select="1" data-placeholder="------"';
        return $this->getStr($i18n, $attributes, "label-desc-rdtaxcodisc", $idlinea, $options);
    }
    public function loadCodeCDT(string $idlinea, BusinessDocumentLine $line, $editable): string
    {
        $i18n = new Translator();
        $impuestosAdicionales = new ImpuestoAdicional();
        $where = [new DataBaseWhere('tipo_impuesto_short', 'CDT')];
        $cdtList = $impuestosAdicionales::all($where, ['codigo'=>'DESC']);
        if (!$cdtList) {
            return '';
        }

        $invoiceLineCDT = ($line->rdtaxcodcdt) ? $line->rdtaxcodcdt : "";

        $options = ['<option value="">----------</option>'];
        foreach ($cdtList as $row) {
            $options[] = ($row->codigo === $invoiceLineCDT) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->tipo_impuesto_short . ' - ' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->tipo_impuesto_short . ' - ' . $row->descripcion . '</option>';
        }

        $attributes = ($editable) ? 'name="rdtaxcodcdt_' . $idlinea . '"' : 'name="rdtaxcodcdt_' . $idlinea . '" disabled=""';
        return $this->getStr($i18n, $attributes, "label-desc-rdtaxcodcdt", $idlinea, $options);
    }
    public function loadCodeLegalTip(string $idlinea, BusinessDocumentLine $line, $editable): string
    {
        $i18n = new Translator();
        $impuestosAdicionales = new ImpuestoAdicional();
        $where = [new DataBaseWhere('tipo_impuesto_short', 'Propina Legal')];
        $legalTipList = $impuestosAdicionales::all($where, ['codigo'=>'DESC']);
        if (!$legalTipList) {
            return '';
        }

        $invoiceLineLegalTip = ($line->rdtaxcodlegaltip) ? $line->rdtaxcodlegaltip : "";

        $options = ['<option value="">----------</option>'];
        foreach ($legalTipList as $row) {
            $options[] = ($row->codigo === $invoiceLineLegalTip) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->tipo_impuesto_short . ' - ' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->tipo_impuesto_short . ' - ' . $row->descripcion . '</option>';
        }

        $attributes = ($editable) ? 'name="rdtaxcodlegaltip_' . $idlinea . '"' : 'name="rdtaxcodlegaltip_' . $idlinea . '" disabled=""';
        return $this->getStr($i18n, $attributes, "label-desc-rdtaxcodlegaltip", $idlinea, $options);
    }
    public function loadCodeFirstPlate(string $idlinea, BusinessDocumentLine $line, $editable): string
    {
        $i18n = new Translator();
        $impuestosAdicionales = new ImpuestoAdicional();
        $where = [new DataBaseWhere('tipo_impuesto_short', 'Primera Placa')];
        $firstPlateList = $impuestosAdicionales::all($where, ['codigo'=>'DESC']);
        if (!$firstPlateList) {
            return '';
        }

        $invoiceLineFirstPlate = ($line->rdtaxcodfirstplate) ? $line->rdtaxcodfirstplate : "";

        $options = ['<option value="">----------</option>'];
        foreach ($firstPlateList as $row) {
            $options[] = ($row->codigo === $invoiceLineFirstPlate) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->tipo_impuesto_short . ' - ' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->tipo_impuesto_short . ' - ' . $row->descripcion . '</option>';
        }

        $attributes = ($editable) ? 'name="rdtaxcodfirstplate_' . $idlinea . '"' : 'name="rdtaxcodfirstplate_' . $idlinea . '" disabled=""';
        return $this->getStr($i18n, $attributes, "label-desc-rdtaxcodfirstplate", $idlinea, $options);
    }

    public function renderTitle(PurchaseDocument $model, string $field): ?string
    {
        switch ($field) {
            case "totalplustaxes":
                return '<div class="col-lg-1 text-end order-8 columTotalPlusTaxes">' .Tools::trans('desc-total-plustaxes') . '</div>';
            default:
                return null;
        }
    }
}