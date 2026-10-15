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
 * Núcleo compartido del plugin. Define el espacio de nombres `fsRD` y utilidades usadas por
 * fsrd-ncf.js, fsrd-ecf.js, fsrd-taxes.js y BusquedaRNCDGII.js.
 *
 * Los textos se pueden reemplazar definiendo `window.fsRDMessages` antes de cargar este archivo.
 */
(function (window, $) {
    'use strict';

    const fsRD = window.fsRD = window.fsRD || {};

    fsRD.messages = Object.assign({
        errorRequest: 'Ha ocurrido un error al comunicarse con el servidor',
        noSequenceTitle: 'No hay Correlativo de NCF Disponible',
        noSequenceBody: 'No hay correlativos disponibles para el Tipo de NCF {type} <br/>Por favor revise su maestro de NCFs',
        fillDataTitle: 'Complete los datos',
        fillDataBody: 'Seleccione un Proveedor y un NCF para validar el documento',
        ncfExistsTitle: 'NCF Ya registrado',
        ncfExistsBody: 'el NCF ya ha sido registrado con la {detail}',
        notXml: 'El archivo seleccionado no parece ser un XML.',
        xmlParseError: 'No se pudo analizar el XML: {detail}',
        xmlProcessError: 'Ocurrió un error al analizar el XML: {detail}',
        rncMismatchTitle: '¡RNC de Emisor no coincide!',
        rncMismatchBody: 'El RNC del documento no concuerda con el RNC del Proveedor.<br>'
            + 'Verifique el documento cargado e intentelo nuevamente.',
        xmlResultTitle: 'Resultado de XML'
    }, window.fsRDMessages || {});

    /**
     * @param {string} key
     * @param {Object} [vars] valores para los marcadores {nombre}
     * @returns {string}
     */
    fsRD.t = function (key, vars) {
        let text = fsRD.messages[key] || key;
        Object.keys(vars || {}).forEach(function (name) {
            text = text.split('{' + name + '}').join(vars[name]);
        });
        return text;
    };

    /**
     * Escribe en la consola solo cuando está activa la barra de depuración de FacturaScripts.
     * @param {*} value
     * @param {string} [description]
     */
    fsRD.log = function (value, description) {
        if ($('.debugbar').length > 0) {
            console.log(description || 'data', value);
        }
    };

    /**
     * @param {*} value
     * @returns {string}
     */
    fsRD.escapeHtml = function (value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    /**
     * @returns {'Venta'|'Compra'|''} tipo de documento de la pantalla actual
     */
    fsRD.operation = function () {
        if ($('#purchasesFormHeader').length > 0) {
            return 'Compra';
        }
        return $('#salesFormHeader').length > 0 ? 'Venta' : '';
    };

    /**
     * Acepta 'Venta'/'Ventas' y 'Compra'/'Compras'.
     * @param {string} operation
     * @returns {'Venta'|'Compra'}
     */
    fsRD.normalizeOperation = function (operation) {
        return /^venta/i.test(operation || '') ? 'Venta' : 'Compra';
    };

    /**
     * Controlador de FacturaScripts que atiende las acciones AJAX del plugin.
     * @param {string} operation
     * @returns {string}
     */
    fsRD.controller = function (operation) {
        return fsRD.normalizeOperation(operation) === 'Venta' ? 'EditFacturaCliente' : 'EditFacturaProveedor';
    };

    /**
     * Muestra un aviso en el modal del plugin (o en consola si CommonModals.js no está cargado).
     * @param {string} id
     * @param {string} title
     * @param {string} body HTML ya seguro
     * @param {string} [type]
     */
    fsRD.notify = function (id, title, body, type) {
        if (typeof window.executeModal === 'function') {
            window.executeModal(id, title, body, type || 'warning');
        } else {
            console.warn(title, body);
        }
    };

    /**
     * POST a una acción del plugin. Devuelve el JSON, o null si la respuesta está vacía o no es JSON.
     * @param {string} operation 'Venta' | 'Compra'
     * @param {string} action
     * @param {Object} data
     * @returns {Promise<Object|null>}
     */
    fsRD.post = function (operation, action, data) {
        return new Promise(function (resolve) {
            $.ajax({
                url: fsRD.controller(operation),
                type: 'POST',
                dataType: 'text',
                data: Object.assign({action: action}, data || {})
            }).done(function (response) {
                const text = (response || '').trim();
                if (text === '') {
                    resolve(null);
                    return;
                }
                try {
                    resolve(JSON.parse(text));
                } catch (error) {
                    fsRD.log(text, 'respuesta no válida de ' + action + ': ' + error.message);
                    resolve(null);
                }
            }).fail(function (xhr, status) {
                console.error(fsRD.t('errorRequest'), action, status);
                fsRD.notify('fsrdRequestError', fsRD.t('errorRequest'), fsRD.escapeHtml(status), 'danger');
                resolve(null);
            });
        });
    };

    /**
     * Convierte DD-MM-YYYY (formato de la DGII y de FacturaScripts) o YYYY-MM-DD a YYYY-MM-DD.
     * @param {string} value
     * @returns {string}
     */
    fsRD.toIsoDate = function (value) {
        const parts = String(value || '').trim().split('-');
        if (parts.length !== 3) {
            return '';
        }
        return parts[0].length === 4 ? parts.join('-') : parts[2] + '-' + parts[1] + '-' + parts[0];
    };

    const autoFilled = {};

    /**
     * Rellena un campo desde el sistema solo si no lo ha modificado el usuario: queda vacío, o aún
     * contiene el último valor que puso el propio sistema. Lo que digite el contador se conserva.
     * @param {string} name nombre del input
     * @param {string} value
     * @returns {boolean} true si se escribió el valor
     */
    fsRD.autoFill = function (name, value) {
        const input = $("input[name='" + name + "']");
        if (input.length === 0 || !value) {
            return false;
        }
        const current = input.val();
        if (current !== '' && current !== autoFilled[name]) {
            return false;
        }
        input.val(value);
        autoFilled[name] = value;
        return true;
    };

    // compatibilidad con código que llama a la función global
    window.logConsole = fsRD.log;
})(window, window.jQuery);
