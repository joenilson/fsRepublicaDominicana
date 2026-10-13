<?php
/*
 * Copyright (C) 2026 Joe Nilson <joenilson at gmail.com>
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
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoAdicional;

/**
 * Campos de impuestos adicionales (ISC, CDT, propina legal y primera placa) en el modal
 * de las líneas. Lo comparten los mods de ventas y de compras, que solo se diferencian
 * en la función JavaScript que recalcula la línea.
 */
trait AddedTaxesLineTrait
{
    /**
     * Campo de la línea => [tipo de impuesto adicional, título, con búsqueda].
     */
    private static function addedTaxFields(): array
    {
        return [
            'rdtaxcodisc' => ['ISC', 'label-desc-rdtaxcodisc', true],
            'rdtaxcodcdt' => ['CDT', 'label-desc-rdtaxcodcdt', false],
            'rdtaxcodlegaltip' => ['Propina Legal', 'label-desc-rdtaxcodlegaltip', false],
            'rdtaxcodfirstplate' => ['Primera Placa', 'label-desc-rdtaxcodfirstplate', false],
        ];
    }

    /**
     * Nombre de la función JavaScript que recalcula una línea del documento.
     */
    abstract protected function recalculateAction(): string;

    protected function applyAddedTaxCodes(array $formData, $line, string $id): void
    {
        foreach (array_keys(self::addedTaxFields()) as $field) {
            $line->{$field} = $formData[$field . '_' . $id] ?? $line->{$field};
        }
        $line->totalplustaxes = $formData['totalplustaxes_' . $id] ?? $line->totalplustaxes;
    }

    protected function mapAddedTaxes(array $lines): array
    {
        $map = [];
        $num = 0;
        foreach ($lines as $line) {
            $num++;
            $idlinea = $line->idlinea ?? 'n' . $num;
            foreach (array_keys(self::addedTaxFields()) as $field) {
                $map[$field . '_' . $idlinea] = $line->{$field};
            }
            // El total de la línea es el totalplustaxes (pvptotal + IVA + impuestos adicionales)
            $map['totalplustaxes_' . $idlinea] = $line->totalplustaxes;
        }
        return $map;
    }

    protected function renderAddedTaxSelect(string $field, string $idlinea, $line, bool $editable): ?string
    {
        $fields = self::addedTaxFields();
        if (!isset($fields[$field])) {
            return null;
        }

        [$type, $title, $searchable] = $fields[$field];
        $taxes = ImpuestoAdicional::all([new DataBaseWhere('tipo_impuesto_short', $type)], ['codigo' => 'ASC']);
        if (!$taxes) {
            return '';
        }

        $current = $line->{$field} ?: '';
        $options = ['<option value="">------</option>'];
        foreach ($taxes as $tax) {
            $selected = ($tax->codigo === $current) ? ' selected=""' : '';
            $options[] = '<option value="' . Tools::noHtml($tax->codigo) . '"' . $selected . '>'
                . Tools::noHtml($tax->optionLabel()) . '</option>';
        }

        $attributes = 'name="' . $field . '_' . Tools::noHtml($idlinea) . '"' . ($editable ? '' : ' disabled=""');
        if ($searchable) {
            // el selector con búsqueda (select2) se inicia en CommonDomFunctions.js
            $attributes .= ' data-isc-select="1" data-placeholder="------"';
        }

        return '<div class="col-6">'
            . '<div class="mb-2">' . Tools::trans($title)
            . '<select onchange="' . $this->recalculateJs($idlinea) . '" ' . $attributes . ' class="form-select">'
            . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    protected function renderTotalPlusTaxes(string $idlinea, $line): string
    {
        $decimals = Tools::settings('default', 'decimals', 2);

        return '<div class="col col-lg-1 order-8 columnTotalPlusTaxes" lang="es-DO">'
            . '<div class="d-lg-none mt-2 small">' . Tools::trans('desc-total-plustaxes') . '</div>'
            . '<input type="number" name="totalplustaxes_' . Tools::noHtml($idlinea) . '" value="'
            . number_format((float)$line->totalplustaxes, $decimals, '.', '')
            . '" class="form-control form-control-sm text-lg-end border-0" readonly/>'
            . '</div>';
    }

    protected function renderTotalPlusTaxesTitle(): string
    {
        return '<div class="col-lg-1 text-end order-8 columTotalPlusTaxes">'
            . Tools::trans('desc-total-plustaxes') . '</div>';
    }

    protected function recalculateJs(string $idlinea): string
    {
        // el id va dentro de una cadena JavaScript que a su vez está en un atributo HTML
        $idlinea = Tools::noHtml(addslashes($idlinea ?: '0'));
        return 'return ' . $this->recalculateAction() . '(\'recalculate-line\', \'' . $idlinea . '\');';
    }
}
