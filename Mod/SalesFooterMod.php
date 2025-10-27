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

use FacturaScripts\Core\Contract\SalesModInterface;
use FacturaScripts\Core\Translator;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoPago;

class SalesFooterMod implements SalesModInterface
{
    public function apply(SalesDocument &$model, array $formData): void
    {
        
    }

    public function applyBefore(SalesDocument &$model, array $formData): void
    {
        if ($model->modelClassName() === 'FacturaCliente') {
            $model->numeroncf = isset($formData['numeroncf']) ? (string)$formData['numeroncf'] : $model->numeroncf;
            $model->tipocomprobante = isset($formData['tipocomprobante']) ? (string)$formData['tipocomprobante'] : $model->tipocomprobante;
            $model->ncffechavencimiento = isset($formData['ncffechavencimiento']) ? (string)$formData['ncffechavencimiento'] : $model->ncffechavencimiento;
            $model->ncftipopago = isset($formData['ncftipopago']) ? (string)$formData['ncftipopago'] : $model->ncftipopago;
            $model->ncftipomovimiento = isset($formData['ncftipomovimiento']) ? (string)$formData['ncftipomovimiento'] : $model->ncftipomovimiento;
            $model->ncftipoanulacion = isset($formData['ncftipoanulacion']) ? (string)$formData['ncftipoanulacion'] : $model->ncftipoanulacion;
        }
    }

    public function assets(): void
    {
    }

    public function newBtnFields(): array
    {
        return [];
    }

    public function newFields(): array
    {
        return ['numeroncf', 'tipocomprobante', 'ncffechavencimiento', 'ncftipopago', 'ncftipomovimiento', 'ncftipoanulacion'];
    }

    public function newModalFields(): array
    {
        return [];
    }

    public function renderField(SalesDocument $model, string $field): ?string
    {
        if ($model->modelClassName() === 'FacturaCliente') {
            $i18n = new Translator();
            switch ($field) {
                case "numeroncf":
                    return self::numeroncf($i18n, $model);
                case "tipocomprobante":
                    return self::tipoComprobante($i18n, $model);
                case "ncffechavencimiento":
                    return self::ncfFechaVencimiento($i18n, $model);
                case "ncftipopago":
                    return self::ncfTipoPago($i18n, $model);
                case "ncftipomovimiento":
                    return self::ncfTipoMovimiento($i18n, $model);
                case "ncftipoanulacion":
                    return self::ncfTipoAnulacion($i18n, $model);
                default:
                    return null;
            }
        }
        return null;
    }

    private static function infoCliente($codcliente)
    {
        $cliente = new Cliente();
        $actualCliente = $cliente::find($codcliente);
        if ('' !== $actualCliente) {
            return $actualCliente;
        }
        return null;
    }

    private static function tipoComprobante(Translator $i18n, SalesDocument $model): string
    {
        $tipoComprobante = NCFTipo::allVentas();
        if (count($tipoComprobante) === 0) {
            return '';
        }

        $cliente = self::infoCliente($model->codcliente);
        $cliente->tipocomprobante = ($cliente->tipocomprobante === null) ? "02" : $cliente->tipocomprobante;

        $invoiceTipoComprobante = ($model->tipocomprobante !== null) ? $model->tipocomprobante : $cliente->tipocomprobante;
        if (!$model->editable) {
            $invoiceTipoComprobante = $model->tipocomprobante;
        } elseif ($model->editable === true && ($cliente->tipocomprobante !== $model->tipocomprobante) && $model->tipocomprobante !== null) {
            $invoiceTipoComprobante = $model->tipocomprobante;
        } elseif ($model->editable === true && ($cliente->tipocomprobante === $model->tipocomprobante) && $model->tipocomprobante !== null) {
            $invoiceTipoComprobante = $cliente->tipocomprobante;
        }

        $options = ['<option value="">------</option>'];
        foreach ($tipoComprobante as $row) {
            $options[] = ($row->tipocomprobante === $invoiceTipoComprobante) ?
                '<option value="' . $row->tipocomprobante . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->tipocomprobante . '">' . $row->descripcion . '</option>';
        }

        $attributes = ($model->editable || $model->numeroncf === '') ?
            'id="tipocomprobante" name="tipocomprobante" required="" onChange="verificarCorrelativoNCF(this.value,\'Ventas\')"' :
            'disabled=""';

        return '<div class="col-sm-3">'
            . '<div class="mb-3">'
            . $i18n->trans('tipocomprobante')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoPago(Translator $i18n, SalesDocument $model): string
    {
        $NCFTipoPago = new NCFTipoPago();
        $tipoPago = $NCFTipoPago->findAllByTipopago('01');
        if (count($tipoPago) === 0) {
            return '';
        }

        $cliente = self::infoCliente($model->codcliente);

        if ($model->ncftipopago) {
            $invoiceTipoPago = $model->ncftipopago;
        } else {
            $invoiceTipoPago = ($cliente->ncftipopago !== '') ? $cliente->ncftipopago : "17";
        }

        $options = ['<option value="">------</option>'];
        foreach ($tipoPago as $row) {
            $options[] = ($row->codigo === $invoiceTipoPago) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="ncftipopago" required=""' : 'disabled=""';
        return '<div class="col-sm-2">'
            . '<div class="mb-3">'
            . $i18n->trans('ncf-payment-type')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoMovimiento(Translator $i18n, SalesDocument $model): string
    {
        $NCFTipoMovimiento = new NCFTipoMovimiento();
        $tipoMovimiento = $NCFTipoMovimiento->findAllByTipomovimiento('VEN');
        if (count($tipoMovimiento) === 0) {
            return '';
        }

        $invoiceTipoMovimiento = ($model->ncftipomovimiento) ?: "1";

        $options = ['<option value="">------</option>'];
        foreach ($tipoMovimiento as $row) {
            $options[] = ($row->codigo === $invoiceTipoMovimiento) ?
                '<option value="' . $row->codigo . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->codigo . '">' . $row->descripcion . '</option>';
        }

        $attributes = $model->editable ? 'name="ncftipomovimiento" required=""' : 'disabled=""';
        return '<div class="col-sm-3">'
            . '<div class="mb-3">'
            . $i18n->trans('ncf-movement-type')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfTipoAnulacion(Translator $i18n, SalesDocument $model): string
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
            . '<div class="mb-3">'
            . $i18n->trans('ncf-cancellation-type')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfFechaVencimiento(Translator $i18n, SalesDocument $model): string
    {
        $attributes = ($model->editable && $model->numero2 === '') ? 'name="ncffechavencimiento"' : 'name="ncffechavencimiento" disabled=""';
        $ncfFechaVencimiento = ($model->ncffechavencimiento)
            ? date('Y-m-d', strtotime($model->ncffechavencimiento))
            : '';
        return '<div class="col-sm-2">'
            . '<div class="mb-3">' . $i18n->trans('due-date')
            . '<input type="date" ' . $attributes . ' value="' . $ncfFechaVencimiento . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function numeroncf(Translator $i18n, SalesDocument $model): string
    {
        $attributes = ($model->editable) ? 'name="numeroncf" maxlength="20"' : 'disabled=""';
        return empty($model->codcliente) ? '' : '<div class="col-sm">'
            . '<div class="mb-3">'
            . $i18n->trans('desc-numeroncf-sales')
            . '<input type="text" ' . $attributes . ' value="' . $model->numeroncf . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }
}
