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
 * Campos de impuestos y notas en los documentos: selector con búsqueda para el ISC, monto manual
 * en compras y campos de la nota de débito electrónica. Requiere fsrd-core.js.
 */
(function (window, $) {
    'use strict';

    const fsRD = window.fsRD = window.fsRD || {};
    const ISC_PENDING = 'select[data-isc-select]:not(.select2-hidden-accessible)';

    /**
     * Muestra «Factura a modificar» y «Código de modificación» solo para el tipo 33.
     * @param {string} tipoComprobante
     */
    function toggleNotaDebitoFields(tipoComprobante) {
        const isDebitNote = tipoComprobante === '33';
        ['divIdFacturaRect', 'divNcfCodigoModificacion'].forEach(function (id) {
            const element = document.getElementById(id);
            if (element) {
                element.classList.toggle('d-none', !isDebitNote);
            }
        });
        if (!isDebitNote) {
            document.querySelectorAll("select[name='idfacturarect'], select[name='ncfcodigomodificacion']")
                .forEach(function (select) { select.value = ''; });
        }
    }

    /**
     * Convierte los selectores de ISC en selectores con búsqueda (select2). Dentro del modal de
     * la línea el desplegable se crea en el propio modal; si no, la trampa de foco de Bootstrap
     * impide escribir en el buscador.
     * @param {Element|Document} [root]
     */
    function initIscSelect2(root) {
        if (typeof $.fn.select2 === 'undefined') {
            return;
        }
        $(root || document).find(ISC_PENDING).each(function () {
            const select = $(this);
            const options = {theme: 'bootstrap-5', width: '100%', allowClear: true, placeholder: select.data('placeholder')};
            const modal = select.closest('.modal');
            if (modal.length > 0) {
                options.dropdownParent = modal;
            }
            select.select2(options);
        });
    }

    /**
     * Las líneas se dibujan de nuevo al recalcular. Se agrupan las mutaciones en un solo
     * fotograma y solo se busca si realmente hay un selector pendiente.
     */
    function watchLines() {
        if (fsRD.operation() === '' || typeof MutationObserver === 'undefined') {
            return;
        }
        let scheduled = false;
        new MutationObserver(function () {
            if (scheduled) {
                return;
            }
            scheduled = true;
            window.requestAnimationFrame(function () {
                scheduled = false;
                if (document.querySelector(ISC_PENDING)) {
                    initIscSelect2(document);
                }
            });
        }).observe(document.body, {childList: true, subtree: true});
    }

    $(function () {
        initIscSelect2(document);
        watchLines();
    });
    $(document).on('shown.bs.modal', function (event) {
        initIscSelect2(event.target);
    });

    // Compras: al marcar «monto manual» se pueden digitar los montos del ISC de la línea.
    document.addEventListener('change', function (event) {
        const toggle = event.target;
        if (!toggle.matches || !toggle.matches('input.isc-manual-toggle')) {
            return;
        }
        document.querySelectorAll('input.isc-manual-amount[data-idlinea="' + toggle.dataset.idlinea + '"]')
            .forEach(function (input) { input.readOnly = !toggle.checked; });
    });

    fsRD.toggleNotaDebitoFields = toggleNotaDebitoFields;
    fsRD.initIscSelect2 = initIscSelect2;

    // función global usada en el atributo onChange generado por PHP
    window.toggleNotaDebitoFields = toggleNotaDebitoFields;
})(window, window.jQuery);
