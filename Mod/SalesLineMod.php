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

use FacturaScripts\Core\Base\Contract\SalesLineModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\ImpuestoProducto;

class SalesLineMod implements SalesLineModInterface
{

    public function apply(SalesDocument &$model, array &$lines, array $formData)
    {
        // TODO: Implement apply() method.
    }

    /**
     * @param array $formData
     * @param SalesDocumentLine $line
     * @param string $id
     * @return void
     */
    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id)
    {
        $line->rdtaxisc = $formData['rdtaxisc_' . $id] ?? null;
        $line->rdtaxcdt = $formData['rdtaxcdt_' . $id] ?? null;
    }

    public function assets(): void
    {
    }

    public function map(array $lines, SalesDocument $model): array
    {
        return [];
    }

    public function newModalFields(): array
    {
        return ['rdtaxisc', 'rdtaxcdt'];
    }

    public function newFields(): array
    {
        return [];
    }

    public function newTitles(): array
    {
        return [];
    }

    public function renderField(Translator $i18n, string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string
    {
        if ($field === 'rdtaxisc') {
            return $this->rdTax($i18n, $idlinea, $line, $model, $field);
        }
        if ($field === 'rdtaxcdt') {
            return $this->rdTax($i18n, $idlinea, $line, $model, $field);
        }
        return null;
    }

    public function renderTitle(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        return null;
    }

    protected function rdTax($i18n, $idlinea, $line, $model, $field): string
    {
        $attributes = $model->editable ?
            'name="'. $field .'_' . $idlinea . '"' :
            'disabled=""';

        $title = $this->rdTaxTitle($i18n, $field);
        $lineTax = $this->productoTaxValue($line->idproducto, $field);
        //$line->$field = $lineTax->porcentaje;
        $fieldValue = is_null($lineTax) ? 0 : $lineTax->porcentaje;

        return '<div class="col-6">'
            . $title
            . '<input type="number" ' . $attributes . ' value="' . $fieldValue. '" class="form-control" readonly=""/>'
            . '</div>'
            . '</div>';
    }

    protected function rdTaxTitle($i18n, $field): string
    {
        return '<div class="mb-2">' . $i18n->trans($field);
    }

    protected function productoTaxValue($idproducto, $rdtaxid): ?ImpuestoProducto
    {
        if (null !== $idproducto) {
            $taxProducts = new ImpuestoProducto();
            $taxType = ($rdtaxid === 'rdtaxisc') ? "ISC" : "CDT";
            return $taxProducts->getTaxByProduct($idproducto, $taxType, 'venta');
        }
        return null;
    }

    public function getFastLine(SalesDocument $model, array $formData): ?SalesDocumentLine
    {
        // TODO: Implement getFastLine() method.
    }
}