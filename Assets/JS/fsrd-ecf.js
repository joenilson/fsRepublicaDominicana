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
 * Carga del XML de un e-CF: lectura en el navegador, vista previa en un modal y copia de los
 * datos al formulario de la factura o de la devolución. Requiere fsrd-core.js y CommonModals.js.
 */
(function (window, $) {
    'use strict';

    const fsRD = window.fsRD = window.fsRD || {};
    const MODAL_ID = 'xmlResultModal';

    /** Último XML leído con éxito; lo reutiliza useEcfXMLData(). */
    let lastEcfParse = null;

    // ---- fechas -----------------------------------------------------------------------------

    /**
     * Da formato compatible con input[type="datetime-local"].
     * @param {string} value
     * @returns {string}
     */
    function formatDateTimeLocal(value) {
        if (!value) {
            return '';
        }
        let [datePart, timePart] = String(value).trim().replace(' ', 'T').split('T');
        datePart = fsRD.toIsoDate(datePart) || datePart;
        if (timePart === undefined) {
            return datePart;
        }
        // el input no acepta zona horaria ni milisegundos
        timePart = timePart.replace(/(Z|[+-]\d{2}:?\d{2})$/, '').split('.')[0];
        return datePart + 'T' + timePart;
    }

    // ---- lectura del XML ----------------------------------------------------------------------

    /**
     * Índice de elementos por nombre local en minúsculas, calculado una sola vez por documento.
     * @param {Document} xmlDoc
     * @returns {Map<string, Element[]>}
     */
    function buildIndex(xmlDoc) {
        const index = new Map();
        xmlDoc.querySelectorAll('*').forEach(function (element) {
            const key = element.localName.toLowerCase();
            if (!index.has(key)) {
                index.set(key, []);
            }
            index.get(key).push(element);
        });
        return index;
    }

    function makeFinder(index) {
        const inside = function (context, element) {
            return !context || context === element || context.contains(element);
        };
        const all = function (context, name) {
            return (index.get(name.toLowerCase()) || []).filter(function (element) {
                return inside(context, element) && element !== context;
            });
        };
        const first = function (context, name) {
            return all(context, name)[0] || null;
        };
        const text = function (context, name) {
            if (!context) {
                return '';
            }
            const element = first(context, name);
            return element ? (element.textContent || '').trim() : '';
        };
        return {all: all, first: first, text: text};
    }

    /**
     * @param {string|null} inputId
     * @returns {HTMLInputElement|null}
     */
    function findFileInput(inputId) {
        if (inputId) {
            return document.getElementById(inputId);
        }
        const input = document.getElementById('xmlFile');
        if (input && input.files && input.files.length > 0) {
            return input;
        }
        // formulario de devoluciones (xmlFileR)
        return document.getElementById('xmlFileR');
    }

    /**
     * Lee el XML seleccionado y devuelve sus datos principales.
     * @param {string|null} inputId
     * @returns {Promise<{data: Object, items: Object[]}|null>}
     */
    async function processEcfXmlFile(inputId) {
        const xmlInput = findFileInput(inputId || null);
        if (!xmlInput || !xmlInput.files || xmlInput.files.length === 0) {
            return null;
        }

        const file = xmlInput.files[0];
        if (!/\.xml$/i.test(file.name) && !['text/xml', 'application/xml'].includes(file.type)) {
            fsRD.notify('xmlNotValid', fsRD.t('xmlResultTitle'), fsRD.escapeHtml(fsRD.t('notXml')), 'warning');
            return null;
        }

        const xmlDoc = new DOMParser().parseFromString(await file.text(), 'application/xml');
        const parserError = xmlDoc.getElementsByTagName('parsererror')[0];
        if (parserError) {
            fsRD.notify(
                'xmlNotValid',
                fsRD.t('xmlResultTitle'),
                fsRD.escapeHtml(fsRD.t('xmlParseError', {detail: parserError.textContent})),
                'warning'
            );
            return null;
        }

        const find = makeFinder(buildIndex(xmlDoc));
        const root = find.first(null, 'ECF') || xmlDoc.documentElement;
        const documento = find.first(root, 'IdDoc');
        const emisor = find.first(root, 'Emisor');
        const comprador = find.first(root, 'Comprador');
        const totales = find.first(root, 'Totales');
        const detalles = find.first(root, 'DetallesItems');
        const signature = find.first(root, 'Signature');
        const fechaHoraFirma = find.first(root, 'FechaHoraFirma');
        const emisorRnc = find.text(emisor, 'RNCEmisor');

        // En ventas el emisor debe ser la empresa; en compras, el proveedor del formulario.
        const isSales = document.forms.salesForm !== undefined;
        const formRnc = isSales
            ? document.getElementById('cifnif_empresa')
            : document.querySelector('input[name="cifnif"]');
        fsRD.log(formRnc, 'formRncEl');
        if (formRnc && formRnc.value && formRnc.value !== emisorRnc) {
            fsRD.notify(
                'AlertaRNCEmisor',
                fsRD.t('rncMismatchTitle'),
                '<div class="alert alert-warning" role="alert">' + fsRD.t('rncMismatchBody') + '</div>',
                'warning'
            );
            return null;
        }

        const listOf = function (context, tableName, rowName, mapper) {
            const table = find.first(context, tableName);
            return table ? find.all(table, rowName).map(mapper) : [];
        };

        const data = {
            IDOC_TipoeCF: find.text(documento, 'TipoeCF'),
            IDOC_eNCF: find.text(documento, 'eNCF'),
            IDOC_FechaVencimientoSecuencia: find.text(documento, 'FechaVencimientoSecuencia'),
            IDOC_IndicadorMontoGravado: find.text(documento, 'IndicadorMontoGravado'),
            IDOC_TipoIngresos: find.text(documento, 'TipoIngresos'),
            IDOC_TipoPago: find.text(documento, 'TipoPago'),
            IDOC_FormasPago: listOf(documento, 'TablaFormasPago', 'FormaDePago', function (node) {
                return {FormaPago: find.text(node, 'FormaPago'), MontoPago: find.text(node, 'MontoPago')};
            }),
            EMISOR_RNC: emisorRnc,
            EMISOR_RazonSocial: find.text(emisor, 'RazonSocialEmisor'),
            EMISOR_Direccion: find.text(emisor, 'DireccionEmisor'),
            EMISOR_Municipio: find.text(emisor, 'Municipio'),
            EMISOR_Provincia: find.text(emisor, 'Provincia'),
            EMISOR_Telefonos: listOf(emisor, 'TablaTelefonoEmisor', 'TelefonoEmisor', function (node) {
                return (node.textContent || '').trim();
            }).filter(Boolean),
            EMISOR_Correo: find.text(emisor, 'CorreoEmisor'),
            EMISOR_FechaEmision: find.text(emisor, 'FechaEmision'),
            EMISOR_FechaHoraFirma: fechaHoraFirma ? (fechaHoraFirma.textContent || '').trim() : '',

            COMPRADOR_RazonSocial: find.text(comprador, 'RazonSocialComprador'),
            COMPRADOR_Correo: find.text(comprador, 'CorreoComprador'),
            COMPRADOR_Direccion: find.text(comprador, 'DireccionComprador'),
            COMPRADOR_Municipio: find.text(comprador, 'Municipio'),
            COMPRADOR_Provincia: find.text(comprador, 'Provincia'),
            COMPRADOR_CodigoInternoComprador: find.text(comprador, 'CodigoInternoComprador'),

            TOTALES_MontoGravadoTotal: find.text(totales, 'MontoGravadoTotal'),
            TOTALES_MontoGravadoI1: find.text(totales, 'MontoGravadoI1'),
            TOTALES_MontoExento: find.text(totales, 'MontoExento'),
            TOTALES_ITBIS1: find.text(totales, 'ITBIS1'),
            TOTALES_TotalITBIS: find.text(totales, 'TotalITBIS'),
            TOTALES_TotalITBIS1: find.text(totales, 'TotalITBIS1'),
            TOTALES_MontoImpuestoAdicional: find.text(totales, 'MontoImpuestoAdicional'),
            TOTALES_ImpuestosAdicionales: listOf(totales, 'ImpuestosAdicionales', 'ImpuestoAdicional', function (node) {
                return {
                    TipoImpuesto: find.text(node, 'TipoImpuesto'),
                    TasaImpuestoAdicional: find.text(node, 'TasaImpuestoAdicional'),
                    OtrosImpuestosAdicionales: find.text(node, 'OtrosImpuestosAdicionales')
                };
            }),
            TOTALES_MontoTotal: find.text(totales, 'MontoTotal'),
            TOTALES_ValorPagar: find.text(totales, 'ValorPagar'),

            SIGNATURE_Info: find.text(signature, 'DigestValue'),
            SIGNATURE_Value: find.text(signature, 'SignatureValue')
        };

        const itemNodes = find.all(detalles || root, 'Item').slice(0, 100);
        const items = itemNodes.map(function (node, idx) {
            return {
                index: idx + 1,
                numeroLinea: find.text(node, 'NumeroLinea'),
                indicadorFacturacion: find.text(node, 'IndicadorFacturacion'),
                nombreItem: find.text(node, 'NombreItem'),
                IndicadorBienoServicio: find.text(node, 'IndicadorBienoServicio'),
                cantidadItem: find.text(node, 'CantidadItem'),
                unidadMedida: find.text(node, 'UnidadMedida'),
                precioUnitarioItem: find.text(node, 'PrecioUnitarioItem'),
                ImpuestosAdicionales: listOf(node, 'TablaImpuestoAdicional', 'ImpuestoAdicional', function (n) {
                    return {TipoImpuesto: find.text(n, 'TipoImpuesto')};
                }),
                montoItem: find.text(node, 'MontoItem')
            };
        });

        lastEcfParse = {data: data, items: items};
        return lastEcfParse;
    }

    // ---- vista previa -------------------------------------------------------------------------

    /**
     * Todo texto que viene del XML se escapa: el archivo es de un tercero.
     * @param {Object} data
     * @param {Object[]} items
     * @returns {string}
     */
    function buildEcfHtml(data, items) {
        const e = fsRD.escapeHtml;
        const value = function (key) {
            return e((data && data[key]) || '-');
        };

        const additional = (data && data.TOTALES_ImpuestosAdicionales) || [];
        const additionalDisplay = additional.length === 0 ? '-' : additional.map(function (tax) {
            const parts = [];
            if (tax.TipoImpuesto) {
                parts.push('Imp: ' + e(tax.TipoImpuesto));
            }
            if (tax.TasaImpuestoAdicional) {
                parts.push(e(tax.TasaImpuestoAdicional) + '%');
            }
            if (tax.OtrosImpuestosAdicionales) {
                parts.push('Monto: ' + e(tax.OtrosImpuestosAdicionales));
            }
            return parts.join(' | ');
        }).join(', ');

        const summary = '<table class="table table-sm table-bordered mb-0 align-middle"><tbody>'
            + '<tr><th style="width: 220px;">e-CF</th><td>' + value('IDOC_eNCF') + '</td></tr>'
            + '<tr><th>Tipo e-CF</th><td>' + value('IDOC_TipoeCF') + '</td></tr>'
            + '<tr><th>Fecha Emisión</th><td>' + value('EMISOR_FechaEmision') + '</td></tr>'
            + '<tr><th>Vencimiento secuencia</th><td>' + value('IDOC_FechaVencimientoSecuencia') + '</td></tr>'
            + '<tr><th>Fecha Firma</th><td>' + value('EMISOR_FechaHoraFirma') + '</td></tr>'
            + '<tr><th>Emisor (RNC)</th><td>' + value('EMISOR_RazonSocial') + ' (' + value('EMISOR_RNC') + ')</td></tr>'
            + '<tr><th>Comprador</th><td>' + value('COMPRADOR_RazonSocial') + '</td></tr>'
            + '<tr><th>Monto Gravado</th><td class="text-end">' + value('TOTALES_MontoGravadoTotal') + '</td></tr>'
            + '<tr><th>Monto Exento</th><td class="text-end">' + value('TOTALES_MontoExento') + '</td></tr>'
            + '<tr><th>Total ITBIS</th><td class="text-end">' + value('TOTALES_TotalITBIS') + '</td></tr>'
            + '<tr><th>Total Imp. Adicional</th><td class="text-end">' + additionalDisplay + '</td></tr>'
            + '<tr><th>Total</th><td class="fw-bold text-end">' + value('TOTALES_MontoTotal') + '</td></tr>'
            + '</tbody></table>';

        let itemsHtml = '';
        if (Array.isArray(items) && items.length > 0) {
            const rows = items.map(function (item) {
                return '<tr>'
                    + '<td class="text-muted">' + e(item.numeroLinea) + '</td>'
                    + '<td>' + e(item.nombreItem) + '</td>'
                    + '<td>' + e(item.indicadorFacturacion) + '</td>'
                    + '<td>' + e(item.IndicadorBienoServicio) + '</td>'
                    + '<td class="text-end">' + e(item.cantidadItem) + '</td>'
                    + '<td class="text-end">' + e(item.precioUnitarioItem) + '</td>'
                    + '<td class="text-end">' + e(item.montoItem) + '</td>'
                    + '</tr>';
            }).join('');
            itemsHtml = '<h6 class="mt-3">Detalle de Ítems</h6>'
                + '<table class="table table-sm table-striped"><thead><tr>'
                + '<th>#</th><th>Descripción</th><th>Ind. Fact.</th><th>Es Servicio</th>'
                + '<th class="text-end">Cantidad</th><th class="text-end">Precio</th><th class="text-end">Monto</th>'
                + '</tr></thead><tbody>' + rows + '</tbody></table>';
        }

        return '<div id="xmlSummary">' + summary + '</div><div id="xmlItems">' + itemsHtml + '</div>';
    }

    // ---- aplicar al formulario ----------------------------------------------------------------

    /**
     * Botón «Usar» del modal: copia los datos del XML al formulario.
     */
    async function useEcfXMLData() {
        const modal = document.getElementById(MODAL_ID);
        const inputId = modal ? modal.dataset.inputId : null;

        if (!lastEcfParse) {
            try {
                await processEcfXmlFile(inputId);
            } catch (e) {
                console.error('Error al procesar XML en useEcfXMLData:', e);
            }
        }
        const data = lastEcfParse && lastEcfParse.data;
        if (!data) {
            return false;
        }

        const xmlInput = inputId ? document.getElementById(inputId) : null;
        const form = xmlInput ? xmlInput.closest('form') : null;
        // El formulario de devolución usa campos con sufijo «r»; buscar solo en él evita que los
        // datos terminen en la factura original.
        const isRefundForm = !!(form && form.querySelector('input[name="action"][value="new-refund"]'));
        const field = function (name) {
            if (form) {
                const element = form.querySelector('[name="' + name + '"]');
                if (element || isRefundForm) {
                    return element;
                }
            }
            return document.querySelector('[name="' + name + '"]');
        };

        const numeroncf = field('numeroncf') || field('numeroncfr');
        const fechaFactura = field('fecha');
        const tipo = field('tipocomprobante') || field('tipocomprobanter');
        const vencimiento = field('ncffechavencimiento') || field('ncffechavencimientor');
        const fechaFirma = field('ecf_fecha_firma') || field('ecf_fecha_firmar');
        const codigoSeguridad = field('ecf_codigo_seguridad') || field('ecf_codigo_seguridadr');

        if (numeroncf) {
            numeroncf.value = data.IDOC_eNCF || '';
        }
        if (fechaFactura) {
            fechaFactura.value = fsRD.toIsoDate(data.EMISOR_FechaEmision);
        }
        if (tipo) {
            tipo.value = data.IDOC_TipoeCF || '';
            if (tipo.name === 'tipocomprobante' && typeof fsRD.toggleNotaDebitoFields === 'function') {
                fsRD.toggleNotaDebitoFields(tipo.value);
            }
        }
        if (fechaFirma) {
            fechaFirma.value = formatDateTimeLocal(data.EMISOR_FechaHoraFirma);
        }
        if (codigoSeguridad) {
            codigoSeguridad.value = data.SIGNATURE_Value ? data.SIGNATURE_Value.slice(0, 6) : '';
        }
        // Solo el XML trae el vencimiento de la secuencia (no existe en 32 ni 34). Si no viene,
        // se deja el valor que ya tenga el formulario.
        if (vencimiento && data.IDOC_FechaVencimientoSecuencia) {
            vencimiento.value = fsRD.toIsoDate(data.IDOC_FechaVencimientoSecuencia);
        }

        $('#' + MODAL_ID).modal('hide');
        return false;
    }

    /**
     * Botón «Cargar XML».
     * @param {string|Event|null} inputId id del input de archivo (se ignora si es un evento)
     */
    async function btnParseClick(inputId) {
        const id = typeof inputId === 'string' ? inputId : null;
        try {
            const parsed = await processEcfXmlFile(id);
            if (!parsed) {
                return;
            }
            window.executeModal(MODAL_ID, fsRD.t('xmlResultTitle'), buildEcfHtml(parsed.data, parsed.items), 'pickup', 'useEcfXMLData');
            const modalEl = document.getElementById(MODAL_ID);
            if (modalEl && id) {
                modalEl.dataset.inputId = id;
            }
        } catch (err) {
            console.error(err);
            fsRD.notify(
                'xmlNotValid',
                fsRD.t('xmlResultTitle'),
                fsRD.escapeHtml(fsRD.t('xmlProcessError', {detail: err && err.message ? err.message : err})),
                'warning'
            );
        }
    }

    /**
     * Habilita el botón «Cargar XML» cuando hay un archivo elegido.
     * @param {string} [inputName]
     * @param {string} [btnName]
     */
    function enableParseIfReady(inputName, btnName) {
        const inputId = typeof inputName === 'string' ? inputName : 'xmlFile';
        const buttonId = typeof inputName === 'string' && btnName ? btnName : 'btnParseXml';
        const xmlInput = document.getElementById(inputId);
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = !(xmlInput && xmlInput.files && xmlInput.files.length > 0);
        }
    }

    fsRD.ecf = {
        formatDateTimeLocal: formatDateTimeLocal,
        process: processEcfXmlFile,
        buildHtml: buildEcfHtml
    };

    // funciones globales usadas en atributos onclick/onchange generados por PHP y Twig
    window.useEcfXMLData = useEcfXMLData;
    window.btnParseClick = btnParseClick;
    window.enableParseIfReady = enableParseIfReady;
})(window, window.jQuery);
