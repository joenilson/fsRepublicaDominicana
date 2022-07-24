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
                    var formNumProveedorType = ncf.slice(-10, -8);
                    $("input[name='tipocomprobante']").val(formNumProveedorType);
                    if (formNumProveedorType !== '02') {
                        $("input[name='ncffechavencimiento']").val(ncfDueDate);
                        $("input[name='ncffechavencimiento']").focus();
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
});