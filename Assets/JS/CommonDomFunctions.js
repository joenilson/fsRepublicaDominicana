/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
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
 * @param {string} tipoComprobante
 * @returns {Promise<*>}
 */
async function verificarCorrelativoNCF(tipoComprobante, tipoOperacion)
{
    let pagina = (tipoOperacion === 'Venta') ? 'EditFacturaCliente' : 'EditFacturaProveedor';
    let ArrayTipoNCFCompras = ['11','12','16','17'];
    if (tipoOperacion === 'Compra' && !ArrayTipoNCFCompras.includes(tipoComprobante)) {
        return true;
    }
    $("select[name='tipocomprobante']").val(tipoComprobante);
    return $.ajax({
        url: pagina,
        async: true,
        data: {'action': 'busca_correlativo', 'tipocomprobante': tipoComprobante },
        type: 'POST',
        datatype: 'json',
        success: function (response) {
            let data = JSON.parse(response);
            logConsole(data.existe, 'resultado');
            if ( data.existe === false ) {
                executeModal(
                    'verificaNCF',
                    'No hay Correlativo de NCF Disponible',
                    'No hay correlativos disponibles para el Tipo de NCF ' +
                    tipoComprobante + ' <br/>Por favor revise su maestro de NCFs',
                    'warning'
                );
            } else {
                if (tipoComprobante !== '02') {
                    var fecha = data.existe[0].fechavencimiento.split("-");
                    var AnhoVencimiento = fecha[2];
                    var MesVencimiento = fecha[1];
                    var DiaVencimiento = fecha[0];
                    logConsole(fecha+': '+AnhoVencimiento+'-'+MesVencimiento+'-'+DiaVencimiento, 'fVen');
                    $("input[name='ncffechavencimiento']").val(AnhoVencimiento+'-'+MesVencimiento+'-'+DiaVencimiento);
                    $("input[name='ncffechavencimiento']").focus();
                }
            }
        },
        failure: function (response) {
            alert('Ha ocurrido algún tipo de falla ' + response);
        },
        error: function (xhr, status) {
            alert('Ha ocurrido algún tipo de error ' + status);
        }
    });
}

async function actualizarInformacionParaNCF()
{
    var infoCliente = await cargarInfoCliente();
    logConsole(infoCliente, 'infoCliente');
    var datosCliente = JSON.parse(infoCliente);
    var tipoPago = await cargarTipoPago();
    var datosPago = JSON.parse(tipoPago);
    var tipoNCFs = await cargarTipoNCF('Ventas');
    logConsole(tipoNCFs, 'tipoNCFs');
    var datosTipoNCFs = JSON.parse(tipoNCFs);
    let selectTiposNCF = "";
    var descInfoClienteTipoComprobante = '';
    var tipoMovimiento = await cargarTipoMovimiento();
    var datosMovimiento = JSON.parse(tipoMovimiento);

}

/**
 * logConsole in Debug mode
 * @param  {string|Object|boolean} value
 * @param {string} description
 */
function logConsole(value, description ='data')
{
    if ($(".debugbar") !== undefined) {
        console.log(description, value);
    }
}

/********
 * Util Functions
 */
function isBusinessDocumentPage()
{
    let businessDocument = '';
    if ($('#purchaseFormHeader').length > 0) {
        businessDocument = 'Compra';
    } else if ($('#salesFormHeader').length > 0) {
        businessDocument = 'Venta';
    }
    return businessDocument;
}

async function cargarTipoNCF(businessDocument, tipoOperacion)
{
    let pagina = (businessDocument === 'Venta') ? 'EditFacturaCliente' : 'EditFacturaProveedor';
    return $.ajax({
        url: pagina,
        async: true,
        data: {'action': 'busca_tipo', 'tipodocumento': tipoOperacion.toLowerCase() },
        type: 'POST',
        datatype: 'json',
        success: function (response) {
            let data = JSON.parse(response);
            return data;
        },
        error: function (xhr, status) {
            alert('Ha ocurrido algún tipo de error ' + status);
        }
    });
}

async function cargarInfoCliente()
{
    return $.ajax({
        url: 'EditFacturaCliente',
        async: true,
        data: {'action': 'busca_infocliente', 'codcliente': $("input[name=codcliente]").val()},
        type: 'POST',
        datatype: 'json',
        success: function (response) {
            let data = JSON.parse(response);
            return data;
        },
        error: function (xhr, status) {
            alert('Ha ocurrido algún tipo de error ' + status);
        }
    });
}

async function cargarTipoPago(businessDocument)
{
    let pagina = (businessDocument === 'Venta') ? 'EditFacturaCliente' : 'EditFacturaProveedor';
    let tipoPago = (businessDocument === 'Venta') ? '01' : '02';
    return $.ajax({
        url: pagina,
        async: true,
        data: {'action': 'busca_pago', 'tipopago': tipoPago},
        type: 'POST',
        datatype: 'json',
        success: function (response) {
            let data = JSON.parse(response);
            return data;
        },
        error: function (xhr, status) {
            alert('Ha ocurrido algún tipo de error ' + status);
        }
    });
}

async function purchasesNCFVerify()
{
    let arrayTipoECF = ['31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47'];
    let ncf = $("input[name='numeroncf']").val();
    logConsole(ncf, 'NCF');
    let proveedor = $("input[name='codproveedor']").val();
    let now = new Date();
    let ncfDueDate = now.getFullYear()+'-12-31';
    if (proveedor === '' || ncf === '') {
        executeModal(
            'proveedorOrNcfEmpty',
            'Complete los datos',
            'Seleccione un Proveedor y un NCF para validar el documento',
            'warning'
        );
        return undefined;
    } else {
        return $.ajax({
            url: 'EditFacturaProveedor',
            async: true,
            data: {'action': 'verifica_documento', 'ncf': ncf, 'proveedor': proveedor},
            type: 'POST',
            datatype: 'json',
            success: function (response) {
                let data = JSON.parse(response);
                if (data.success) {
                    $("#btnVerifyNCF").attr('class', '').addClass("btn btn-success btn-spin-action");
                    $("#iconBtnVerify").attr('class', '').addClass("fas fa-check-circle fa-fw");
                    var formNumProveedorType = ncf.slice(1, 3);
                    $("select[name='tipocomprobante']").val(formNumProveedorType);
                    if (formNumProveedorType !== '02') {
                        $("input[name='ncffechavencimiento']").val(ncfDueDate).focus();
                        //$("input[name='ncffechavencimiento']").focus();
                    }
                    if (arrayTipoECF.includes(formNumProveedorType)) {
                        $("input[name='ecf_fecha_firma']").val(ncfDueDate+' 00:00:00');
                        $("input[name='ecf_codigo_seguridad']").focus();
                    }
                }
                if (data.error) {
                    $("#btnVerifyNCF").attr('class', '').addClass("btn btn-danger btn-spin-action");
                    $("#iconBtnVerify").attr('class', '').addClass("fas fa-exclamation-circle fa-fw");
                    executeModal(
                        'ncfExists',
                        'NCF Ya registrado',
                        'el NCF ya ha sido registrado con la '+data.message,
                        'warning'
                    );
                }
                return data;
            },
            error: function (xhr, status) {
                alert('Ha ocurrido algún tipo de error ' + status);
            }
        });
    }
}

async function cargarTipoMovimiento(businessDocument)
{
    let pagina = (businessDocument === 'Venta') ? 'EditFacturaCliente' : 'EditFacturaProveedor';
    let tipoMovimiento = (businessDocument === 'Venta') ? 'VEN' : 'COM';
    return $.ajax({
        url: pagina,
        async: true,
        data: {'action': 'busca_movimiento', 'tipomovimiento': tipoMovimiento},
        type: 'POST',
        datatype: 'json',
        success: function (response) {
            let data = JSON.parse(response);
            return data;
        },
        error: function (xhr, status) {
            alert('Ha ocurrido algún tipo de error ' + status);
        }
    });
}

function ecfExpirationDateCalc(dateString)
{
    // Split the string into day, month, and year parts
    const dateParts = dateString.split('-');

    // Extract day, month, and year
    const dayPart = parseInt(dateParts[0], 10);
    const monthPart = parseInt(dateParts[1], 10) - 1; // Subtract 1 for 0-indexed month
    const yearPart = parseInt(dateParts[2], 10);

    let year = yearPart + 1;
    // Create the new date string
    return `${year}-12-31`;
}

function ecfEmisionDateCalc(dateString)
{
    // Split the string into day, month, and year parts
    const dateParts = dateString.split('-');

    // Extract day, month, and year
    const dayPart = dateParts[0];
    const monthPart = dateParts[1];
    const yearPart = dateParts[2];
    // Create the new date string
    return `${yearPart}-${monthPart}-${dayPart}`;
}

function useEcfXMLData()
{
    // Ensure we have parsed data; if not, try to process now
    const assignFrom = async () => {
        if (!lastEcfParse) {
            try {
                await processEcfXmlFile();
            } catch (e) {
                console.error('Error al procesar XML en useEcfXMLData:', e);
            }
        }
        return lastEcfParse ? lastEcfParse.data : null;
    };

    assignFrom().then((data) => {
        if (!data) return false;
        var numeroncf = document.querySelector('input[name="numeroncf"]');
        var fecha_factura = document.querySelector('input[name="fecha"]');
        var tipocomprobante = document.querySelector('select[name="tipocomprobante"]');
        var ncffechavencimiento = document.querySelector('input[name="ncffechavencimiento"]');
        //TO-DO a verificar
        // var ncftipopago = document.querySelector('select[name="ncftipopago"]');
        // var ncftipomovimiento = document.querySelector('select[name="ncftipomovimiento"]');
        var ecf_fecha_firma = document.querySelector('input[name="ecf_fecha_firma"]');
        var ecf_codigo_seguridad = document.querySelector('input[name="ecf_codigo_seguridad"]');
        logConsole(data.EMISOR_FechaEmision, 'Fecha Emisión');
        logConsole(ecfExpirationDateCalc(data.EMISOR_FechaEmision), 'Fecha Vencimiento');
        logConsole(ecfEmisionDateCalc(data.EMISOR_FechaEmision), 'Fecha Emisión Fixed');
        if (numeroncf) numeroncf.value = data.IDOC_eNCF || '';
        if (fecha_factura) fecha_factura.value = ecfEmisionDateCalc(data.EMISOR_FechaEmision) || '';
        if (tipocomprobante) tipocomprobante.value = data.IDOC_TipoeCF || '';
        // if (ncftipopago) ncftipopago.value = data.IDOC_TipoPago || '';
        // if (ncftipomovimiento) ncftipomovimiento.value = data.IDOC_TipoIngresos || '';
        if (ecf_fecha_firma) ecf_fecha_firma.value = data.EMISOR_FechaHoraFirma || '';
        if (ecf_codigo_seguridad) ecf_codigo_seguridad.value = (lastEcfParse && lastEcfParse.data && lastEcfParse.data.SIGNATURE_Value) ? lastEcfParse.data.SIGNATURE_Value.slice(0, 6) : '';
        if (ncffechavencimiento) ncffechavencimiento.value = data.EMISOR_FechaEmision ? ecfExpirationDateCalc(data.EMISOR_FechaEmision) : '';
        $('#xmlResultModal').modal('hide');
        return false;
    });

    return false;
}

// Holds the last successful parsed result to be reused by other functions (e.g., useEcfXMLData)
let lastEcfParse = null;

/**
 * Process the selected XML file from input#xmlFile and return structured data.
 * - Validates file
 * - Parses XML (namespace-agnostic)
 * - Extracts main e-CF fields and items
 * - Verifies provider RNC matches current form provider (when available)
 * @returns {Promise<{data: Object, items: Array}>|null}
 */
async function processEcfXmlFile()
{
    const xmlInput = document.getElementById('xmlFile');
    if (!xmlInput || !xmlInput.files || xmlInput.files.length === 0) return null;

    const file = xmlInput.files[0];
    if (!/\.xml$/i.test(file.name) && !['text/xml','application/xml'].includes(file.type)) {
        alert('El archivo seleccionado no parece ser un XML.');
        return null;
    }

    const text = await file.text();
    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(text, 'application/xml');
    const parserError = xmlDoc.getElementsByTagName('parsererror')[0];
    if (parserError) {
        alert('No se pudo analizar el XML: ' + parserError.textContent);
        return null;
    }

    // Helpers
    const findFirst = (doc, names) => {
        const set = Array.isArray(names) ? names : [names];
        for (const name of set) {
            const el = doc.querySelector(`${name}, *|${name}`) || Array.from(doc.getElementsByTagName('*')).find(n => n.localName === name);
            if (el) return el;
        }
        return null;
    };
    const findText = (ctx, names) => {
        if (!ctx) return '';
        const el = findFirst(ctx, names);
        return el ? (el.textContent || '').trim() : '';
    };
    const findAllByLocal = (ctx, name) => Array.from(ctx.getElementsByTagName('*')).filter(n => n.localName === name);

    // Root node
    const rootCandidates = ['ECF','ecf','eCF','Ecf'];
    let root = null;
    for (const r of rootCandidates) {
        root = findFirst(xmlDoc, r);
        if (root) break;
    }
    if (!root) root = xmlDoc.documentElement;

    // Sections
    const encabezado = findFirst(root, ['Encabezado','encabezado','ENCABEZADO']);
    const documento  = findFirst(root, ['IdDoc','Iddoc','IDDOC']);
    const emisor     = findFirst(root, ['Emisor','emisor','EMISOR']);
    const comprador  = findFirst(root, ['Comprador','comprador','COMPRADOR']);
    const totales    = findFirst(root, ['Totales','totales','TOTALES']);
    const detallesItems = findFirst(root, ['DetallesItems','detallesitems','DETALLESITEMS']);
    const fechaHoraFirma = findFirst(root, ['FechaHoraFirma','fechahorafirma','FECHAHORAFIRMA']);
    const signature  = findFirst(root, ['Signature','signature','SIGNATURE']);
    logConsole(fechaHoraFirma, 'fechaHoraFirma');
    // Basic emisor RNC validation (when form has provider RNC field)
    const emisorRnc = findText(emisor,  ['RNCEmisor','rncemisor','RNCEMISOR']);
    const formRncEl = document.querySelector('input[name="cifnif"]');
    if (formRncEl && formRncEl.value && formRncEl.value !== emisorRnc) {
        executeModal(
            'AlertaRNCEmisor',
            '¡RNC de Emisor no coincide!',
            `<div class="alert alert-warning" role="alert">`+
            `El RNC del documento no concuerda con el RNC del Proveedor.<br>`+
            `Verifique el documento cargado e intentelo nuevamente.<br>`+
            `</div>`,
            'warning'
        );
        return null;
    }

    // Extract fields
    const data = {
        IDOC_TipoeCF: findText(documento, ['TipoeCF', 'tipoecf', 'TipoEcf']),
        IDOC_eNCF: findText(documento, ['encf', 'ENCF', 'eNCF']),
        IDOC_IndicadorMontoGravado: findText(documento, ['IndicadorMontoGravado', 'indicadormontogravado', 'INDICADORMONTOGRABADO']),
        IDOC_TipoIngresos: findText(documento, ['TipoIngresos', 'tipoingresos', 'TIPOINGRESOS']),
        IDOC_TipoPago: findText(documento, ['TipoPago', 'tipopago', 'TIPOPAGO']),
        IDOC_FormasPago: (() => {
            const tabla = findFirst(documento, ['TablaFormasPago']);
            if (!tabla) return [];
            const nodos = findAllByLocal(tabla, 'FormaDePago');
            return nodos.map(n => ({
                FormaPago: findText(n, ['FormaPago']),
                MontoPago: findText(n, ['MontoPago'])
            }));
        })(),
        EMISOR_RNC: emisorRnc,
        EMISOR_RazonSocial: findText(emisor, ['RazonSocialEmisor','razonsocialemisor','RAZONSOCIALEMISOR']),
        EMISOR_Direccion: findText(emisor, ['DireccionEmisor','direccionemisor','DIRECCIONEMISOR']),
        EMISOR_Municipio: findText(emisor, ['Municipio','municipio','MUNICIPIO']),
        EMISOR_Provincia: findText(emisor, ['Provincia','provincia','PROVINCIA']),
        EMISOR_Telefonos: (() => {
            let phones = [];
            const tablaTel = findFirst(emisor, ['TablaTelefonoEmisor', 'tablatelefonoemisor','TABLATELEFONOEMISOR']);
            if (tablaTel) {
                const nodos = findAllByLocal(tablaTel, 'TelefonoEmisor');
                phones = nodos.map(n => (n.textContent || '').trim()).filter(Boolean);
            }
            return phones;
        })(),
        EMISOR_Correo: findText(emisor, ['CorreoEmisor','correoemisor','CORREOEMISOR']),
        EMISOR_FechaEmision: findText(emisor, ['FechaEmision','fechaemision','FECHAEMISION']),
        EMISOR_FechaHoraFirma: fechaHoraFirma.innerHTML,

        COMPRADOR_RazonSocial: findText(comprador, ['RazonSocialComprador','razonsocialcomprador','RAZONSOCIALCOMPRADOR']),
        COMPRADOR_Correo: findText(comprador, ['CorreoComprador','correoComprador','CORREOCOMPRADOR']),
        COMPRADOR_Direccion: findText(comprador, ['DireccionComprador','direccioncomprador','DIRECCIONCOMPRADOR']),
        COMPRADOR_Municipio: findText(comprador, ['Municipio','municipio','MUNICIPIO']),
        COMPRADOR_Provincia: findText(comprador, ['Provincia','provincia','PROVINCIA']),
        COMPRADOR_CodigoInternoComprador: findText(comprador, ['CodigoInternoComprador','codigointernocomprador','CODIGOINTERNOCOMPRADOR']),

        TOTALES_MontoGravadoTotal: findText(totales, ['MontoGravadoTotal','montogravadototal','MONTOGRAVADOTOTAL']),
        TOTALES_MontoGravadoI1: findText(totales, ['MontoGravadoI1','montogravadoi1','MONTOGRAVADOI1']),
        TOTALES_MontoExento: findText(totales, ['MontoExento','montoexento','MONTOEXENTO']),
        TOTALES_ITBIS1: findText(totales, ['ITBIS1','itbis1','Itbis1']),
        TOTALES_TotalITBIS: findText(totales, ['TotalITBIS','totalitbis','TOTALITBIS']),
        TOTALES_TotalITBIS1: findText(totales, ['TotalITBIS1','totalitbis1','TOTALITBIS1']),
        TOTALES_MontoImpuestoAdicional: findText(totales, ['MontoImpuestoAdicional','montoimpuestoadicional','MONTOIMPUESTOADICIONAL']),
        TOTALES_ImpuestosAdicionales: (() => {
            const tabla = findFirst(totales, ['ImpuestosAdicionales']);
            if (!tabla) return [];
            const nodos = findAllByLocal(tabla, 'ImpuestoAdicional');
            return nodos.map(n => ({
                TipoImpuesto: findText(n, ['TipoImpuesto']),
                TasaImpuestoAdicional: findText(n, ['TasaImpuestoAdicional']),
                OtrosImpuestosAdicionales: findText(n, ['OtrosImpuestosAdicionales'])
            }));
        })(),
        TOTALES_MontoTotal: findText(totales, ['MontoTotal','montototal','MONTOTOTAL']),
        TOTALES_ValorPagar: findText(totales, ['ValorPagar','valorpagar','VALORPAGAR']),

        SIGNATURE_Info: findText(signature, ['DigestValue']),
        SIGNATURE_Value: findText(signature, ['SignatureValue'])
    };

    // Items
    let itemNodes = [];
    if (detallesItems) {
        const possibleItemNames = ['Item','item','ITEM'];
        for (const n of possibleItemNames) {
            itemNodes = findAllByLocal(detallesItems, n);
            if (itemNodes.length) break;
        }
        if (!itemNodes.length) {
            for (const n of ['Item','item','ITEM']) {
                itemNodes = Array.from(root.getElementsByTagName('*')).filter(x => x.localName === n);
                if (itemNodes.length) break;
            }
        }
    }

    const items = itemNodes.slice(0, 100).map((node, idx) => ({
        index: idx + 1,
        numeroLinea: findText(node, ['NumeroLinea','numerolinea','NUMEROLINEA']),
        indicadorFacturacion: findText(node,  ['IndicadorFacturacion','indicadorfacturacion', 'INDICADORFACTURACION']),
        nombreItem: findText(node, ['NombreItem','nombreitem','NOMBREITEM']),
        IndicadorBienoServicio: findText(node, ['IndicadorBienoServicio','indicadorbienoservicio','INDICADORBIENOSERVICIO']),
        cantidadItem: findText(node, ['CantidadItem','cantidaditem','CANTIDADITEM']),
        unidadMedida: findText(node, ['UnidadMedida','unidadmedida','UNIDADMEDIDA']),
        precioUnitarioItem: findText(node, ['PrecioUnitarioItem','preciounitarioitem','PRECIOUNITARIOITEM']),
        ImpuestosAdicionales: (() => {
            const tabla = findFirst(node, ['TablaImpuestoAdicional']);
            if (!tabla) return [];
            const nodos = findAllByLocal(tabla, 'ImpuestoAdicional');
            return nodos.map(n => ({
                TipoImpuesto: findText(n, ['TipoImpuesto'])
            }));
        })(),
        montoItem: findText(node, ['MontoItem','montoitem','MONTOITEM'])
    }));

    lastEcfParse = { data, items };
    return lastEcfParse;
}

/**
 * Create HTML snippets for summary and items using parsed e-CF data
 * @param {Object} data
 * @param {Array} items
 * @returns {string} html content to show inside modal
 */
function buildEcfHtml(data, items)
{
    // Display for additional taxes
    const totalImpAdicionalDisplay = (() => {
        const arr = data && data.TOTALES_ImpuestosAdicionales;
        if (!Array.isArray(arr) || arr.length === 0) return '-';
        try {
            return arr.map(x => {
                const parts = [];
                if (x && x.TipoImpuesto) parts.push(`Imp: ${String(x.TipoImpuesto).trim()}`);
                if (x && x.TasaImpuestoAdicional) parts.push(`${String(x.TasaImpuestoAdicional).trim()}%`);
                if (x && x.OtrosImpuestosAdicionales) parts.push(`Monto: ${String(x.OtrosImpuestosAdicionales).trim()}`);
                return parts.join(' | ');
            }).join(', ');
        } catch (e) {
            return '-';
        }
    })();

    const summaryHtml = `
            <table class="table table-sm table-bordered mb-0 align-middle">
                <tbody>
                    <tr><th style="width: 220px;">e-CF</th><td>${(data && data.IDOC_eNCF) || '-'}</td></tr>
                    <tr><th>Tipo e-CF</th><td>${(data && data.IDOC_TipoeCF) || '-'}</td></tr>
                    <tr><th>Fecha Emisión</th><td>${(data && data.EMISOR_FechaEmision) || '-'}</td></tr>
                    <tr><th>Fecha Firma</th><td>${(data && data.EMISOR_FechaHoraFirma) || '-'}</td></tr>
                    <tr><th>Emisor (RNC)</th><td>${(data && data.EMISOR_RazonSocial) || '-'} (${(data && data.EMISOR_RNC) || '-'})</td></tr>
                    <tr><th>Comprador</th><td>${(data && data.COMPRADOR_RazonSocial) || '-'}</td></tr>
                    <tr><th>Monto Gravado</th><td class="text-end">${(data && data.TOTALES_MontoGravadoTotal) || '-'}</td></tr>
                    <tr><th>Monto Exento</th><td class="text-end">${(data && data.TOTALES_MontoExento) || '-'}</td></tr>
                    <tr><th>Total ITBIS</th><td class="text-end">${(data && data.TOTALES_TotalITBIS) || '-'}</td></tr>
                    <tr><th>Total Imp. Adicional</th><td class="text-end">${totalImpAdicionalDisplay}</td></tr>
                    <tr><th>Total</th><td class="fw-bold text-end">${(data && data.TOTALES_MontoTotal) || '-'}</td></tr>
                </tbody>
            </table>`;

    let itemsHtml = '';
    if (Array.isArray(items) && items.length) {
        const rows = items.map(it => `
                        <tr>
                            <td class="text-muted">${it.numeroLinea || ''}</td>
                            <td>${it.nombreItem || ''}</td>
                            <td>${it.indicadorFacturacion || ''}</td>
                            <td>${it.IndicadorBienoServicio || ''}</td>
                            <td class="text-end">${it.cantidadItem || ''}</td>
                            <td class="text-end">${it.precioUnitarioItem || ''}</td>
                            <td class="text-end">${it.montoItem || ''}</td>
                        </tr>
                    `).join('');
        itemsHtml = `
                        <h6 class="mt-3">Detalle de Ítems</h6>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Descripción</th>
                                    <th>Ind. Fact.</th>
                                    <th>Es Servicio</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>`;
    }

    return `<div id="xmlSummary">${summaryHtml}</div><div id="xmlItems">${itemsHtml}</div>`;
}

/**
 * Show the modal with the provided HTML and prepare to pickup (useEcfXMLData)
 * @param {string} html
 */
function showEcfModalWithData(html)
{
    executeModal(
        'xmlResultModal',
        'Resultado de XML',
        html,
        'pickup',
        `useEcfXMLData`
    );
}

async function btnParseClick() {
    try {
        const parsed = await processEcfXmlFile();
        if (!parsed) return;
        const html = buildEcfHtml(parsed.data, parsed.items);
        showEcfModalWithData(html);
    } catch (err) {
        console.error(err);
        alert('Ocurrió un error al analizar el XML: ' + (err && err.message ? err.message : err));
    }
}

function enableParseIfReady() {
    const xmlInput = document.getElementById('xmlFile');
    const btnParse = document.getElementById('btnParseXml');
    logConsole(xmlInput, 'boton Xml');
    btnParse.disabled = !(xmlInput && xmlInput.files && xmlInput.files.length > 0);
}

$(document).ready(function () {
    logConsole($("input[name=codcliente]").val(), 'codcliente');
    let tipoOperacion = isBusinessDocumentPage();
    let varNCFTipoComprobante = $("select[name='tipocomprobante']");
    logConsole($("select[name='tipocomprobante']").val(), 'tipocomprobante');

    if (varNCFTipoComprobante.length !== 0 && varNCFTipoComprobante.val() !== '' && tipoOperacion !== '') {
        verificarCorrelativoNCF($("select[name='tipocomprobante']").val(), tipoOperacion);
    }

    $("#findCustomerModal").on('hidden.bs.modal', function () {
        setTimeout(async function () {
            var infoCliente = await cargarInfoCliente();
            logConsole(infoCliente, 'infoCliente');
            var datosCliente = JSON.parse(infoCliente);
            logConsole(datosCliente, 'datosCliente');
            let varNCFTipoComprobante = datosCliente.infocliente.tipocomprobante;
            logConsole($("input[name=codcliente]").val(), 'codcliente 2');
            logConsole(varNCFTipoComprobante, 'tipocomprobante 2');
            logConsole(tipoOperacion, 'tipoOperacion 2');
            if (varNCFTipoComprobante.length !== 0 && varNCFTipoComprobante !== '' && tipoOperacion !== '') {
                verificarCorrelativoNCF(varNCFTipoComprobante, tipoOperacion);
                $("select[name='ncftipopago']").val(datosCliente.infocliente.ncftipopago);
            }
        },300);
    });

    varNCFTipoComprobante.change(function () {
        logConsole(varNCFTipoComprobante.val(),"tipocomprobante val");
        if (tipoOperacion !== '') {
            verificarCorrelativoNCF(varNCFTipoComprobante.val(), tipoOperacion);
        }
    });

    const xmlInput = document.getElementById('xmlFile');
    const btnParse = document.getElementById('btnParseXml');

    if (xmlInput) xmlInput.addEventListener('change', enableParseIfReady);

});