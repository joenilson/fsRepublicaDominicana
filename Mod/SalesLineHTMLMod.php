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

use FacturaScripts\Core\Contract\SalesLineModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Base\SalesDocumentLine;

/**
 * Impuestos adicionales en las líneas de los documentos de venta.
 */
class SalesLineHTMLMod implements SalesLineModInterface
{
    use AddedTaxesLineTrait;

    public function apply(SalesDocument &$model, array &$lines, array $formData): void
    {
    }

    public function applyToLine(array $formData, SalesDocumentLine &$line, string $id): void
    {
        $this->applyAddedTaxCodes($formData, $line, $id);
    }

    public function assets(): void
    {
    }

    public function getFastLine(SalesDocument $model, array $formData): ?SalesDocumentLine
    {
        return null;
    }

    public function map(array $lines, SalesDocument $model): array
    {
        return $this->mapAddedTaxes($lines);
    }

    public function newModalFields(): array
    {
        return array_keys(self::addedTaxFields());
    }

    public function newFields(): array
    {
        return ['totalplustaxes'];
    }

    public function newTitles(): array
    {
        return ['totalplustaxes'];
    }

    public function renderField(string $idlinea, SalesDocumentLine $line, SalesDocument $model, string $field): ?string
    {
        if ($field === 'totalplustaxes') {
            return $this->renderTotalPlusTaxes($idlinea, $line);
        }

        return $this->renderAddedTaxSelect($field, $idlinea, $line, $model->editable);
    }

    public function renderTitle(SalesDocument $model, string $field): ?string
    {
        return $field === 'totalplustaxes' ? $this->renderTotalPlusTaxesTitle() : null;
    }

    protected function recalculateAction(): string
    {
        return 'salesFormAction';
    }
}
