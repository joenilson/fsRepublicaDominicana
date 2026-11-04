<?php
/*
 * Copyright (C) 2026 Joe Nilson <joenilson@gmail.com>
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

use FacturaScripts\Core\Contract\SalesModInterface;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Translator;
use FacturaScripts\Plugins\fsRepublicaDominicana\Lib\DocumentAssets;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;

/**
 * Encabezado de los documentos de venta.
 *
 * Entrega las opciones del selector «Tipo de comprobante» del modal de nuevo cliente. El núcleo no ofrece un
 * punto de extensión para ese formulario, y el pie no sirve porque no se pinta hasta que hay cliente: el
 * encabezado sí está con el documento vacío. El selector lo arma fsrd-customer.js y el tipo elegido lo
 * guarda SalesFooterHTMLMod::applyBefore() en el cliente recién creado.
 */
class SalesHeaderHTMLMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData): void
    {
    }

    public function applyBefore(SalesDocument &$model, array $formData): void
    {
    }

    public function assets(): void
    {
        DocumentAssets::load();
    }

    public function newBtnFields(): array
    {
        return [];
    }

    public function newFields(): array
    {
        return [];
    }

    public function newModalFields(): array
    {
        return ['newcustomer_tipos'];
    }

    public function renderField(SalesDocument $model, string $field): ?string
    {
        if ($field !== 'newcustomer_tipos') {
            return null;
        }

        if (false === $model->editable) {
            return '';
        }

        // <template> es inerte: sus opciones no se envían con el formulario hasta que el JS arma el selector
        $options = '';
        foreach ((new NCFTipo())->allFor('ventas', 'suma') as $row) {
            $selected = $row->tipocomprobante === NCFTipo::TIPO_VENTA_PREDETERMINADO ? ' selected' : '';
            $options .= '<option value="' . htmlspecialchars((string)$row->tipocomprobante) . '"' . $selected . '>'
                . htmlspecialchars((string)$row->descripcion) . '</option>';
        }

        return '<template id="fsrdNewCustomerTipos" data-label="'
            . htmlspecialchars((new Translator())->trans('tipocomprobante')) . '">' . $options . '</template>';
    }
}
