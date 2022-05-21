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

use FacturaScripts\Core\Base\Contract\PurchasesModInterface;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoPago;

class PurchasesFooterMod implements PurchasesModInterface
{
    public function apply(PurchaseDocument &$model, array $formData, User $user)
    {
        // TODO: Implement apply() method.
    }

    public function applyBefore(PurchaseDocument &$model, array $formData, User $user)
    {
        // TODO: Implement applyBefore() method.
        if ($model->modelClassName() === 'FacturaProveedor') {
            $model->tipocomprobante = isset($formData['tipocomprobante']) ? (string)$formData['tipocomprobante'] : $model->tipocomprobante;
            $model->ncffechavencimiento = isset($formData['ncffechavencimiento']) ? (string)$formData['ncffechavencimiento'] : $model->ncffechavencimiento;
            $model->ncftipopago = isset($formData['ncftipopago']) ? (string)$formData['ncftipopago'] : $model->ncftipopago;
            $model->ncftipomovimiento = isset($formData['ncftipomovimiento']) ? (string)$formData['ncftipomovimiento'] : $model->ncftipomovimiento;
            $model->ncftipoanulacion = isset($formData['ncftipoanulacion']) ? (string)$formData['ncftipoanulacion'] : $model->ncftipoanulacion;
        }
    }

    public function assets(): void
    {
        // TODO: Implement assets() method.
    }

    public function newFields(): array
    {
        // TODO: Implement newFields() method.
        return ['tipocomprobante', 'ncffechavencimiento', 'ncftipopago', 'ncftipomovimiento', 'ncftipoanulacion'];
    }

    public function renderField(Translator $i18n, PurchaseDocument $model, string $field): ?string
    {
        // TODO: Implement renderField() method.
        if ($model->modelClassName() === 'FacturaProveedor') {
            switch ($field) {
                case "tipocomprobante":
                    return $this->tipoComprobante($i18n, $model);
                case "ncffechavencimiento":
                    return $this->ncfFechaVencimiento($i18n, $model);
                case "ncftipopago":
                    return $this->ncfTipoPago($i18n, $model);
                case "ncftipomovimiento":
                    return $this->ncfTipoMovimiento($i18n, $model);
                case "ncftipoanulacion":
                    return $this->ncfTipoAnulacion($i18n, $model);
                default:
                    return null;
            }
        }
        return null;
    }

    private static function infoProveedor($codproveedor)
    {
        $proveedor = new Proveedor();
        $actualProveedor = $proveedor->get($codproveedor);
        if ('' !== $actualProveedor) {
            return $actualProveedor;
        }
        return null;
    }

    private static function tipoComprobante(Translator $i18n, PurchaseDocument $model): string
    {
        $tipoComprobante = NCFTipo::allCompras();
        if (count($tipoComprobante) === 0) {
            return '';
        }

        $invoiceTipoComprobante = ($model->tipocomprobante) ? $model->tipocomprobante : "01";

        $options = ['<option value="">------</option>'];
        foreach ($tipoComprobante as $row) {
            $options[] = ($row->tipocomprobante === $invoiceTipoComprobante) ?
                '<option value="' . $row->tipocomprobante . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->tipocomprobante . '">' . $row->descripcion . '</option>';
        }

        $attributes = ($model->editable || $model->numproveedor === '') ? 'name="tipocomprobante" required=""' : 'disabled=""';
        return '<div class="col-sm-3">'
            . '<div class="form-group">'
            .  $i18n->trans('tipocomprobante')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoPago(Translator $i18n, PurchaseDocument $model): string
    {
        $NCFTipoPago = new NCFTipoPago();
        $tipoPago = $NCFTipoPago->findAllByTipopago('02');
        if (count($tipoPago) === 0) {
            return '';
        }

        $proveedor = self::infoProveedor($model->codproveedor);

        if ($model->ncftipopago) {
            $invoiceTipoPago = $model->ncftipopago;
        } else {
            $invoiceTipoPago = ($proveedor->ncftipopago !== '') ? $proveedor->ncftipopago : "01";
        }

        $options = ['<option value="">------</option>'];
        foreach ($tipoPago as $row) {
            $options[] = ($row->codigo === $invoiceTipoPago) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="ncftipopago" required=""' : 'disabled=""';
        return '<div class="col-sm-2">'
            . '<div class="form-group">'
            .  $i18n->trans('ncf-payment-type')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoMovimiento(Translator $i18n, PurchaseDocument $model): string
    {
        $NCFTipoMovimiento = new NCFTipoMovimiento();
        $tipoMovimiento = $NCFTipoMovimiento->findAllByTipomovimiento('COM');
        if (count($tipoMovimiento) === 0) {
            return '';
        }

        $invoiceTipoMovimiento = ($model->ncftipomovimiento) ?: "09";

        $options = ['<option value="">------</option>'];
        foreach ($tipoMovimiento as $row) {
            $options[] = ($row->codigo === $invoiceTipoMovimiento) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="ncftipomovimiento" required=""' : 'disabled=""';
        return '<div class="col-sm-3">'
            . '<div class="form-group">'
            .  $i18n->trans('ncf-movement-type')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoAnulacion(Translator $i18n, PurchaseDocument $model): string
    {
        $NCFTipoAnulacion = new NCFTipoAnulacion();
        $tipoAnulacion = $NCFTipoAnulacion->all();
        if (count($tipoAnulacion) === 0) {
            return '';
        }

        $invoiceTipoAnulacion = ($model->ncftipoanulacion) ?: "";

        $options = ['<option value="">------</option>'];
        foreach ($tipoAnulacion as $row) {
            $options[] = ($row->codigo === $invoiceTipoAnulacion) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="ncftipoanulacion"' : 'disabled=""';
        return '<div class="col-sm-2">'
            . '<div class="form-group">'
            .  $i18n->trans('ncf-cancellation-type')
            . '<select ' . $attributes . ' class="form-control">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfFechaVencimiento(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = ($model->editable && $model->numproveedor === '') ? 'name="ncffechavencimiento"' : 'disabled=""';
        return '<div class="col-sm-2">'
            . '<div class="form-group">' . $i18n->trans('due-date')
            . '<input type="date" ' . $attributes . ' value="' . date('Y-m-d', strtotime($model->ncffechavencimiento)) . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }
}