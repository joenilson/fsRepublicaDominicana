<?php
/*
 * Copyright (C) 2023 Joe Nilson <joenilson@gmail.com>
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

use FacturaScripts\Core\Contract\PurchasesLineModInterface;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Tools;

/**
 * Impuestos adicionales en las líneas de los documentos de compra. Además del cálculo
 * automático, el monto del ISC puede digitarse a mano porque manda lo que facturó el proveedor.
 */
class PurchasesLineHTMLMod implements PurchasesLineModInterface
{
    use AddedTaxesLineTrait;

    private const MANUAL_AMOUNTS = [
        'rdtaxiscespecifico' => 'isc-specific-amount',
        'rdtaxiscadvalorem' => 'isc-advalorem-amount',
    ];

    public function apply(PurchaseDocument &$model, array &$lines, array $formData): void
    {
    }

    public function applyToLine(array $formData, BusinessDocumentLine &$line, string $id): void
    {
        $this->applyAddedTaxCodes($formData, $line, $id);

        // la casilla solo viaja marcada: se comprueba la presencia de los montos de la misma línea
        $hasAmounts = isset($formData['rdtaxiscespecifico_' . $id]) || isset($formData['rdtaxiscadvalorem_' . $id]);
        if (!$hasAmounts) {
            return;
        }

        $line->rdtaxmanual = isset($formData['rdtaxmanual_' . $id]);
        if ($line->rdtaxmanual) {
            foreach (array_keys(self::MANUAL_AMOUNTS) as $field) {
                $line->{$field} = (float)str_replace(',', '.', (string)($formData[$field . '_' . $id] ?? 0));
            }
        }
    }

    public function assets(): void
    {
    }

    public function getFastLine(PurchaseDocument $model, array $formData): ?BusinessDocumentLine
    {
        return null;
    }

    public function map(array $lines, PurchaseDocument $model): array
    {
        $map = $this->mapAddedTaxes($lines);
        $num = 0;
        foreach ($lines as $line) {
            $num++;
            $idlinea = $line->idlinea ?? 'n' . $num;
            foreach (array_keys(self::MANUAL_AMOUNTS) as $field) {
                $map[$field . '_' . $idlinea] = $line->{$field};
            }
        }
        return $map;
    }

    public function newModalFields(): array
    {
        return array_merge(
            ['rdtaxcodisc', 'rdtaxmanual'],
            array_keys(self::MANUAL_AMOUNTS),
            ['rdtaxcodcdt', 'rdtaxcodlegaltip', 'rdtaxcodfirstplate']
        );
    }

    public function newFields(): array
    {
        return ['totalplustaxes'];
    }

    public function newTitles(): array
    {
        return ['totalplustaxes'];
    }

    public function renderField(string $idlinea, BusinessDocumentLine $line, PurchaseDocument $model, string $field): ?string
    {
        if ($field === 'totalplustaxes') {
            return $this->renderTotalPlusTaxes($idlinea, $line);
        }
        if ($field === 'rdtaxmanual') {
            return $this->renderManualToggle($idlinea, $line, $model->editable);
        }
        if (isset(self::MANUAL_AMOUNTS[$field])) {
            return $this->renderManualAmount($idlinea, $line, $model->editable, $field);
        }

        return $this->renderAddedTaxSelect($field, $idlinea, $line, $model->editable);
    }

    public function renderTitle(PurchaseDocument $model, string $field): ?string
    {
        return $field === 'totalplustaxes' ? $this->renderTotalPlusTaxesTitle() : null;
    }

    protected function recalculateAction(): string
    {
        return 'purchasesFormAction';
    }

    private function renderManualToggle(string $idlinea, BusinessDocumentLine $line, bool $editable): string
    {
        $checked = !empty($line->rdtaxmanual) ? ' checked=""' : '';
        $disabled = $editable ? '' : ' disabled=""';
        $id = Tools::noHtml($idlinea);
        return '<div class="col-6">'
            . '<div class="form-check mt-4">'
            . '<input type="checkbox" class="form-check-input isc-manual-toggle" id="rdtaxmanual_' . $id . '"'
            . ' name="rdtaxmanual_' . $id . '" value="1" data-idlinea="' . $id . '"' . $checked . $disabled
            . ' onchange="' . $this->recalculateJs($idlinea) . '"/>'
            . '<label class="form-check-label" for="rdtaxmanual_' . $id . '">'
            . Tools::trans('isc-manual-amount') . '</label>'
            . '</div>'
            . '</div>';
    }

    private function renderManualAmount(string $idlinea, BusinessDocumentLine $line, bool $editable, string $field): string
    {
        $decimals = Tools::settings('default', 'decimals', 2);
        $readonly = ($editable && !empty($line->rdtaxmanual)) ? '' : ' readonly=""';
        $id = Tools::noHtml($idlinea);
        return '<div class="col-6">'
            . '<div class="mb-2">'
            . Tools::trans(self::MANUAL_AMOUNTS[$field])
            . '<input type="number" step="any" class="form-control isc-manual-amount" name="' . $field . '_' . $id . '"'
            . ' data-idlinea="' . $id . '" value="' . number_format((float)$line->{$field}, $decimals, '.', '') . '"'
            . $readonly . ' onchange="' . $this->recalculateJs($idlinea) . '"/>'
            . '</div>'
            . '</div>';
    }
}
