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

use FacturaScripts\Core\Contract\PurchasesModInterface;
use FacturaScripts\Core\Translator;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;

use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipo;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoAnulacion;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoMovimiento;
use FacturaScripts\Plugins\fsRepublicaDominicana\Model\NCFTipoPago;

class PurchasesFooterMod implements PurchasesModInterface
{
    public function apply(PurchaseDocument &$model, array $formData): void
    {
    }

    public function applyBefore(PurchaseDocument &$model, array $formData): void
    {
        if ($model->modelClassName() === 'FacturaProveedor') {
            $model->numeroncf = isset($formData['numeroncf']) ? (string)$formData['numeroncf'] : $model->numeroncf;
            $model->tipocomprobante = isset($formData['tipocomprobante']) ? (string)$formData['tipocomprobante'] : $model->tipocomprobante;
            $model->ncffechavencimiento = isset($formData['ncffechavencimiento']) ? (string)$formData['ncffechavencimiento'] : $model->ncffechavencimiento;
            $model->ncftipopago = isset($formData['ncftipopago']) ? (string)$formData['ncftipopago'] : $model->ncftipopago;
            $model->ncftipomovimiento = isset($formData['ncftipomovimiento']) ? (string)$formData['ncftipomovimiento'] : $model->ncftipomovimiento;
            $model->ncftipoanulacion = isset($formData['ncftipoanulacion']) ? (string)$formData['ncftipoanulacion'] : $model->ncftipoanulacion;
            $model->ecf_fecha_firma = isset($formData['ecf_fecha_firma']) ? (string)$formData['ecf_fecha_firma'] : $model->ecf_fecha_firma;
            $model->ecf_codigo_seguridad = isset($formData['ecf_codigo_seguridad']) ? (string)$formData['ecf_codigo_seguridad'] : $model->ecf_codigo_seguridad;
        }
    }

    public function assets(): void
    {
    }

    public function newBtnFields(): array
    {
        return ['btnLoadXmlEcf','btnLoadXmlAck','btnLoadPdfEcf'];
    }

    public function newFields(): array
    {
        return ['numeroncf', 'tipocomprobante', 'ncffechavencimiento', 'ncftipopago', 'ncftipomovimiento', 'ncftipoanulacion', 'ecf_xml_firmado','ecf_fecha_firma','ecf_codigo_seguridad'];
    }

    public function newModalFields(): array
    {
        return [];
    }

    public function renderField(PurchaseDocument $model, string $field): ?string
    {
        $i18n = new Translator();
        if ($model->modelClassName() === 'FacturaProveedor') {
            switch ($field) {
                case "numeroncf":
                    return self::numeroNCF($i18n, $model);
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
                case "ecf_fecha_firma":
                    return self::ecfFechaFirma($i18n, $model);
                case "ecf_codigo_seguridad":
                    return self::ecfCodigoSeguridad($i18n, $model);
                case "btnLoadXmlEcf":
                    return self::btnLoadXmlEcf($i18n, $model);
                default:
                    return null;
            }
        }
        return null;
    }

    private static function btnLoadXmlEcf(Translator $i18n, PurchaseDocument $model): string
    {
        $html = '<div class="row align-items-start">'
            . '<div class="col-sm-5 text-start">'
            . '<label for="xmlFile" class="form-label">'
            . '<i class="fa-solid fa-file-code me-1 text-primary"></i> Archivo XML de e-Factura'
            . '</label>'
            . '<div class="input-group">'
            . '    <input type="file" class="form-control" id="xmlFile" name="xmlFile" onchange="enableParseIfReady()" accept=".xml,text/xml,application/xml">'
//            . '    <label class="input-group-text" for="xmlFile"><i class="fa-solid fa-upload"></i></label>'
            . '    <div class="invalid-feedback">Seleccione un archivo XML válido.</div>'
//            . '</div>'
//            . '<div class="form-group text-start">'
            . '<button type="button" id="btnParseXml" onclick="btnParseClick()" class="btn btn-danger" disabled>'
            . '<i class="fas fa-fw fa-file-code"></i>'
            . $i18n->trans('btn-load-xml-ecf')
            . '</input>'
            . '</div>'
            . '</div></div>';

        return $html;
    }

    private static function infoProveedor($codproveedor)
    {
        $proveedor = new Proveedor();
        $actualProveedor = $proveedor::find($codproveedor);
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

        $invoiceTipoComprobante = ($model->tipocomprobante) ? $model->tipocomprobante : "";

        $options = ['<option value="">------</option>'];
        foreach ($tipoComprobante as $row) {
            $options[] = ($row->tipocomprobante === $invoiceTipoComprobante) ?
                '<option value="' . $row->tipocomprobante . '" selected="">' . $row->descripcion . '</option>' :
                '<option value="' . $row->tipocomprobante . '">' . $row->descripcion . '</option>';
        }

        $attributes = ($model->editable || $model->numeroncf === '') ? 'name="tipocomprobante" required=""' : 'disabled=""';
        return '<div class="col-sm-3">'
            . '<div class="mb-3">'
            . $i18n->trans('tipocomprobante')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
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
            . '<div class="mb-3">'
            . $i18n->trans('ncf-payment-type')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
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
            . '<div class="mb-3">'
            . $i18n->trans('ncf-movement-type')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
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

        $attributes = $model->editable ? 'name="ncftipoanulacion"' : 'name="ncftipoanulacion" readonly=""';
        return '<div class="col-sm-2">'
            . '<div class="mb-3">'
            . $i18n->trans('ncf-cancellation-type')
            . '<select ' . $attributes . ' class="form-select">' . implode('', $options) . '</select>'
            . '</div>'
            . '</div>';
    }

    private static function ncfFechaVencimiento(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = ($model->editable) ? 'name="ncffechavencimiento"' : 'disabled=""';
        $ncfFechaVencimiento = ($model->ncffechavencimiento)
            ? date('Y-m-d', strtotime($model->ncffechavencimiento))
            : '';
        return '<div class="col-sm-2">'
            . '<div class="mb-3">' . $i18n->trans('due-date')
            . '<input type="date" ' . $attributes . ' value="'
            . $ncfFechaVencimiento . '" class="form-control"/>'
            . '</div>'
            . '</div>';
    }

    private static function numeroNCF(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = ($model->editable) ? 'name="numeroncf" maxlength="20"' : 'disabled=""';
        $btnColor = (in_array($model->numeroncf, ['', null], true)) ? "btn-secondary" : "btn-success";
        return empty($model->codproveedor) ? '' : '<div class="col-sm">'
            . '<div class="mb-3">'
            . $i18n->trans('desc-numeroncf-purchases')
            . '<div class="input-group">'
            . '<input type="text" ' . $attributes . ' value="' . $model->numeroncf . '" class="form-control"/>'
            . '<button class="btn ' . $btnColor . ' btn-spin-action" id="btnVerifyNCF"'
            . 'onclick="purchasesNCFVerify()" '
            . 'title="' . $i18n->trans('verify-numproveedor')
            . '" type="button">'
            . '<i id="iconBtnVerify" class="fa-solid fa-search fa-fw"></i>'
            . '</button>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function ecfFechaFirma(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = ($model->editable) ? 'name="ecf_fecha_firma" maxlength="32"' : 'disabled=""';
        $btnColor = (in_array($model->ecf_recibido, ['', null], true)) ? "btn-secondary" : "btn-success";
        return '<div class="col-sm">'
            . '<div class="mb-4">'
            . $i18n->trans('desc-ecf_fecha_firma')
            . '<div class="input-group">'
            . '<input type="datetime-local" ' . $attributes . ' value="' . $model->ecf_fecha_firma . '" class="form-control"/>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    private static function ecfCodigoSeguridad(Translator $i18n, PurchaseDocument $model): string
    {
        $attributes = ($model->editable) ? 'name="ecf_codigo_seguridad" maxlength="64"' : 'disabled=""';
        $btnColor = (in_array($model->ecf_recibido, ['', null], true)) ? "btn-secondary" : "btn-success";
        return '<div class="col-sm">'
            . '<div class="mb-4">'
            . $i18n->trans('desc-ecf_codigo_seguridad')
            . '<div class="input-group">'
            . '<input type="text" ' . $attributes . ' value="' . $model->ecf_codigo_seguridad . '" class="form-control"/>'
            . '</div>'
            . '</div>'
            . '</div>';
    }
}