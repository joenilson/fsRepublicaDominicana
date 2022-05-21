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

namespace FacturaScripts\Plugins\fsRepublicaDominicana\Mod;

use FacturaScripts\Core\Base\Contract\SalesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;

class SalesHeaderMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData, User $user)
    {
        // TODO: Implement apply() method.
    }

    public function applyBefore(SalesDocument &$model, array $formData, User $user)
    {
        // TODO: Implement applyBefore() method.
    }

    public function assets(): void
    {
        // TODO: Implement assets() method.
    }

    public function newFields(): array
    {
        // TODO: Implement newFields() method.
        return ['numero2'];
    }

    public function renderField(Translator $i18n, SalesDocument $model, string $field): ?string
    {
        // TODO: Implement renderField() method.
        if ($model->modelClassName() === 'FacturaCliente') {
            if ($field === 'numero2') {
                return self::numero2($i18n, $model);
            }
            return null;
        }
        return null;
    }

    private static function numero2(Translator $i18n, SalesDocument $model): string
    {
        $attributes = $model->editable ? 'name="numero2" maxlength="50" readonly=""' : 'disabled=""';
        return empty($model->codcliente) ? '' : '<div class="col-sm">'
            . '<div class="form-group">'
            . $i18n->trans('number2')
            . '<input type="text" ' . $attributes . ' value="' . $model->numero2 . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }
}