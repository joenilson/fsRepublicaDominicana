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
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoAdicional;

class PurchasesLineMod implements PurchasesLineModInterface
{

    public function apply(PurchaseDocument &$model, array &$lines, array $formData): void
    {
        // TODO: Implement apply() method.
    }

    public function applyToLine(array $formData, BusinessDocumentLine &$line, string $id): void
    {
        // TODO: Implement applyToLine() method.
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
        // TODO: Implement getFastLine() method.
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
        }
        return $map;
    }

    public function newFields(): array
    {
        return [];
    }

    public function newModalFields(): array
    {
        return ['rdtaxcodisc','rdtaxcodcdt','rdtaxcodlegaltip','rdtaxcodfirstplate'];
    }

    public function newTitles(): array
    {
        return [];
    }

    public function renderField(string $idlinea, BusinessDocumentLine $line, PurchaseDocument $model, string $field): ?string
    {
        $i18n = new Translator();
        if ($model->modelClassName() === 'FacturaProveedor') {
            switch ($field) {
                case "rdtaxcodisc":
                    return $this->loadCodeISC($i18n, $idlinea, $line);
                case "rdtaxcodcdt":
                    return self::loadCodeCDT($i18n, $idlinea, $line);
                case "rdtaxcodlegaltip":
                    return self::loadCodeLegalTip($i18n, $idlinea, $line);
                case "rdtaxcodfirstplate":
                    return self::loadCodeFirstPlate($i18n, $idlinea, $line);
                default:
                    return null;
            }
        }
        return null;
    }

    public function loadCodeISC(Translator $i18n, string $idlinea, BusinessDocumentLine $line): string
    {
        $impuestosAdicionales = new ImpuestoAdicional();
        $where = [new DataBaseWhere('tipo_impuesto_short', 'ISC')];
        $iscList = $impuestosAdicionales::all($where, ['codigo'=>'DESC']);
        if (!$iscList) {
            return '';
        }

        $invoiceLineISC = ($line->rdtaxcodisc) ? $line->rdtaxcodisc : "yXy";
        $options = ['<option value="">----------'.$invoiceLineISC.'</option>'];
        foreach ($iscList as $row) {
            $options[] = ($row->codigo === $invoiceLineISC) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->tipo_impuesto_short . ' - ' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->tipo_impuesto_short . ' - ' . $row->descripcion . '</option>';
        }

        //$attributes = ($line->editable) ? 'name="rdtaxcodisc_' . $idlinea . '"' : 'disabled=""';
        $attributes = 'name="rdtaxcodisc_' . $idlinea . '"';
        //$attributes .= (!$line->editable) ? ' disabled=""' : '';
        return $this->getStr($i18n, $attributes, "label-desc-rdtaxcodisc", $idlinea, $options);
    }
    public function loadCodeCDT(Translator $i18n, string $idlinea, BusinessDocumentLine $line): string
    {
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

        //$attributes = ($line->editable) ? 'name="rdtaxcodcdt_' . $idlinea . '"' : 'disabled=""';
        $attributes = 'name="rdtaxcodcdt_' . $idlinea . '"';
        //$attributes .= (!$line->editable) ? ' disabled=""' : '';
        return $this->getStr($i18n, $attributes, "label-desc-rdtaxcodcdt", $idlinea, $options);
    }
    public function loadCodeLegalTip(Translator $i18n, string $idlinea, BusinessDocumentLine $line): string
    {
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

//        $attributes = ($line->editable) ? 'name="rdtaxcodlegaltip_' . $idlinea . '"' : 'disabled=""';
        $attributes = 'name="rdtaxcodlegaltip_' . $idlinea . '"';
        //$attributes .= (!$line->editable) ? ' disabled=""' : '';
        return $this->getStr($i18n, $attributes, "label-desc-rdtaxcodlegaltip", $idlinea, $options);
    }
    public function loadCodeFirstPlate(Translator $i18n, string $idlinea, BusinessDocumentLine $line): string
    {
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

//        $attributes = ($line->editable) ? 'name="rdtaxcodfirstplate_' . $idlinea . '"' : 'disabled=""';
        $attributes = 'name="rdtaxcodfirstplate_' . $idlinea . '"';
        //$attributes .= (!$line->editable) ? ' disabled=""' : '';
        return $this->getStr($i18n, $attributes, "label-desc-rdtaxcodfirstplate", $idlinea, $options);
    }

    public function renderTitle(PurchaseDocument $model, string $field): ?string
    {
        // TODO: Implement renderTitle() method.
        return '';
    }
}