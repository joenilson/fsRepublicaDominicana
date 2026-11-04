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

/**
 * Selector «Tipo de comprobante» en el modal de nuevo cliente de los documentos de venta.
 *
 * El núcleo no ofrece un punto de extensión para ese formulario: SalesFooterHTMLMod entrega las opciones
 * en un <template id="fsrdNewCustomerTipos"> y aquí se arma el selector al abrir el modal. El campo viaja
 * con el formulario como `newcustomer_tipocomprobante` y SalesFooterHTMLMod lo guarda en el cliente nuevo.
 */
(function (window, $) {
    'use strict';

    const MODAL_ID = 'newCustomerModal';
    const SELECT_ID = 'newCustomerTipoComprobante';

    function buildField() {
        const modal = document.getElementById(MODAL_ID);
        const source = document.getElementById('fsrdNewCustomerTipos');
        if (modal === null || source === null) {
            return;
        }

        const row = modal.querySelector('.row');
        if (row === null) {
            return;
        }

        // el pie se vuelve a pintar con cada cambio del documento: las opciones se refrescan, el selector se conserva
        let select = document.getElementById(SELECT_ID);
        if (select === null) {
            const column = document.createElement('div');
            column.className = 'col-sm-6';

            const label = document.createElement('label');
            label.className = 'form-label';
            label.setAttribute('for', SELECT_ID);
            label.textContent = source.getAttribute('data-label') || 'Tipo de comprobante';

            select = document.createElement('select');
            select.id = SELECT_ID;
            select.name = 'newcustomer_tipocomprobante';
            select.className = 'form-select';
            select.required = true;

            column.appendChild(label);
            column.appendChild(select);
            row.appendChild(column);
        }

        const current = select.value;
        select.innerHTML = source.innerHTML;
        if (current !== '' && select.querySelector('option[value="' + current + '"]') !== null) {
            select.value = current;
        }
    }

    $(function () {
        $('#' + MODAL_ID).on('show.bs.modal', buildField);
    });
}(window, window.jQuery));
