/*
 * Copyright (C) 2021-2026 Joe Nilson <joenilson@gmail.com>
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

/**
 * NCF en facturas de venta y compra: verificación de correlativo, verificación del NCF del
 * proveedor y cambio de cliente. Requiere fsrd-core.js.
 */
(function (window, $) {
    'use strict';

    const fsRD = window.fsRD = window.fsRD || {};
    const SELECT_TIPO = "select[name='tipocomprobante']";
    const ECF_TYPES = ['31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47'];

    /**
     * Tipos de comprobante que emite la propia empresa en compras. Los define PHP
     * (NCFTipo::EMISION_PROPIA_COMPRAS) y llegan en el atributo data-own-types del selector.
     * @returns {string[]}
     */
    function ownPurchaseTypes() {
        const raw = $(SELECT_TIPO).first().attr('data-own-types') || '';
        return raw.split(',').map(function (item) { return item.trim(); }).filter(Boolean);
    }

    /**
     * Verifica que haya correlativo disponible para el tipo y completa la fecha de vencimiento.
     * @param {string} tipoComprobante
     * @param {string} tipoOperacion 'Venta' | 'Compra' (también acepta 'Ventas'/'Compras')
     * @returns {Promise<*>}
     */
    async function verificarCorrelativoNCF(tipoComprobante, tipoOperacion) {
        const operation = fsRD.normalizeOperation(tipoOperacion);
        if (operation === 'Compra' && !ownPurchaseTypes().includes(tipoComprobante)) {
            return true;
        }
        $(SELECT_TIPO).val(tipoComprobante);
        const data = await fsRD.post(operation, 'busca_correlativo', {tipocomprobante: tipoComprobante});
        if (data === null) {
            return null;
        }
        fsRD.log(data.existe, 'resultado');
        if (data.existe === false) {
            fsRD.notify(
                'verificaNCF',
                fsRD.t('noSequenceTitle'),
                fsRD.t('noSequenceBody', {type: fsRD.escapeHtml(tipoComprobante)}),
                'warning'
            );
        } else if (tipoComprobante !== '02' && data.existe && data.existe[0]) {
            // La fecha del rango solo se propone si el campo está vacío o sin modificar a mano
            fsRD.autoFill('ncffechavencimiento', fsRD.toIsoDate(data.existe[0].fechavencimiento));
        }
        return data;
    }

    /**
     * Compras: verifica si el NCF del proveedor ya fue registrado y completa los datos del e-CF.
     * @returns {Promise<Object|undefined|null>}
     */
    async function purchasesNCFVerify() {
        const ncf = $("input[name='numeroncf']").val();
        const proveedor = $("input[name='codproveedor']").val();
        fsRD.log(ncf, 'NCF');
        if (!proveedor || !ncf) {
            fsRD.notify('proveedorOrNcfEmpty', fsRD.t('fillDataTitle'), fsRD.t('fillDataBody'), 'warning');
            return undefined;
        }

        const data = await fsRD.post('Compra', 'verifica_documento', {ncf: ncf, proveedor: proveedor});
        if (data === null) {
            return null;
        }
        const button = $('#btnVerifyNCF');
        const icon = $('#iconBtnVerify');
        if (data.success) {
            const type = ncf.slice(1, 3);
            button.attr('class', 'btn btn-success btn-spin-action');
            icon.attr('class', 'fas fa-check-circle fa-fw');
            $(SELECT_TIPO).val(type);
            // No se propone vencimiento ni fecha de firma: vienen del XML o los digita el contador
            if (ECF_TYPES.includes(type)) {
                $("input[name='ecf_codigo_seguridad']").focus();
            }
        }
        if (data.error) {
            button.attr('class', 'btn btn-danger btn-spin-action');
            icon.attr('class', 'fas fa-exclamation-circle fa-fw');
            fsRD.notify(
                'ncfExists',
                fsRD.t('ncfExistsTitle'),
                fsRD.t('ncfExistsBody', {detail: fsRD.escapeHtml(data.message)}),
                'warning'
            );
        }
        return data;
    }

    /**
     * Ventas: al cambiar de cliente se toma su tipo de comprobante y tipo de pago.
     */
    async function onCustomerChanged() {
        const codcliente = $('input[name=codcliente]').val();
        if (!codcliente) {
            return;
        }
        const data = await fsRD.post('Venta', 'busca_infocliente', {codcliente: codcliente});
        const info = data && data.infocliente;
        fsRD.log(info, 'infocliente');
        if (!info || !info.tipocomprobante) {
            return;
        }
        await verificarCorrelativoNCF(info.tipocomprobante, 'Venta');
        if (info.ncftipopago) {
            $("select[name='ncftipopago']").val(info.ncftipopago);
        }
    }

    // El pie de la factura se vuelve a dibujar al recalcular, por eso los eventos son delegados.
    $(document).on('change', SELECT_TIPO, function () {
        const operation = fsRD.operation();
        if (typeof fsRD.toggleNotaDebitoFields === 'function') {
            fsRD.toggleNotaDebitoFields(this.value);
        }
        if (operation !== '') {
            verificarCorrelativoNCF(this.value, operation);
        }
    });

    $(document).on('hidden.bs.modal', '#findCustomerModal', function () {
        setTimeout(onCustomerChanged, 300);
    });

    $(function () {
        const operation = fsRD.operation();
        const type = $(SELECT_TIPO).val();
        if (operation !== '' && type) {
            verificarCorrelativoNCF(type, operation);
        }
    });

    fsRD.verificarCorrelativoNCF = verificarCorrelativoNCF;
    fsRD.purchasesNCFVerify = purchasesNCFVerify;

    // funciones globales usadas en atributos onclick/onchange generados por PHP
    window.verificarCorrelativoNCF = verificarCorrelativoNCF;
    window.purchasesNCFVerify = purchasesNCFVerify;
})(window, window.jQuery);
