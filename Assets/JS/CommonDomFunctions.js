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
    let ArrayTipoNCFCompras = ['11','12','16','17'];
    if (tipoOperacion === 'Compras' && !ArrayTipoNCFCompras.includes($("#ncftipocomprobante").val())) {
        return true;
    }
    logConsole(tipoComprobante, 'tipoComprobante');
    return $.ajax({
        url: 'ListNCFRango',
        async: true,
        data: {'action': 'busca_correlativo', 'tipocomprobante': tipoComprobante },
        type: 'POST',
        datatype: 'json',
        success: function (response) {
            let data = JSON.parse(response);
            if ( data.existe === false ) {
                executeModal(
                    'verificaNCF',
                    'No hay Correlativo de NCF Disponible',
                    'No hay correlativos disponibles para el Tipo de NCF ' +
                    tipoComprobante + ' <br/>Por favor revise su maestro de NCFs',
                    'warning'
                );
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

/**
 *
 * @param {object} btn
 * @param {string} text
 */
function setLoadingButton(btn, text)
{
    $(btn).prop("disabled", true);
    $(btn).html(
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span class="sr-only">' + text +'</span>'
    );
}

function setBusinessDocViewModalSave(
    tipoDeEntidad,
    readOnlySelects,
    infoEntidadTipoNCF,
    infoEntidadTipoPago,
    selectTiposNCF,
    selectOptionsPagos,
    selectOptionsMovimientos
)
{
    let tipoOperacion = isBusinessDocumentPage();
    let message = '<div class="form-content">\n' +
        '      <form class="form" role="form">\n' +
        '        <div class="form-group">\n' +
        '          <label for="infoclientetiponcf">Tipo de NCF del '+tipoDeEntidad+':</label>\n' +
        '           <span style="font-weight: bold;" id="infoclientetiponcf">'+infoEntidadTipoNCF+'</span>'+
        '        </div>\n' +
        '        <div class="form-group">\n' +
        '          <label for="infoclientetiponcf">Tipo de Pago del'+tipoDeEntidad+':</label>\n' +
        '           <span style="font-weight: bold;" id="infoclientetiponcf">'+infoEntidadTipoPago+'</span>'+
        '        </div>\n' +
        '        <div class="form-group">\n' +
        '          <label for="ncftipocomprobante">Tipo de NCF</label>\n' +
        '          <select class="custom-select" id="ncftipocomprobante" name="ncftipocomprobante"'+
                        readOnlySelects+' onChange="verificarCorrelativoNCF(this.value, \''+tipoOperacion+'\')">\n' +
                        selectTiposNCF +
        '          </select>\n' +
        '        </div>\n' +
        '        <div class="form-group">\n' +
        '          <label for="ncftipopago">Tipo de Pago</label>\n' +
        '          <select class="custom-select" id="ncftipopago" name="ncftipopago"'+readOnlySelects+'>\n' +
                        selectOptionsPagos +
        '          </select>\n' +
        '        </div>\n' +
        '        <div class="form-group">\n' +
        '          <label for="ncftipomovimiento">Tipo de Movimiento</label>\n' +
        '          <select class="custom-select" id="ncftipomovimiento" name="ncftipomovimiento"'+readOnlySelects+'>\n' +
                        selectOptionsMovimientos +
        '          </select>\n' +
        '        </div>\n' +
        '      </form>\n' +
        '    </div>';
    return message;
}


function saveBussinessDocument(btn)
{
    //Set the save button as loading
    setLoadingButton(btn,'Guardando...');

    var data = {};
    $.each($("#" + businessDocViewFormName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data['ncftipopago'] = $('#ncftipopago').val();
    data['ncftipomovimiento'] = $('#ncftipomovimiento').val();
    data['tipocomprobante'] = $('#ncftipocomprobante').val();
    data.action = "save-document";
    data.lines = getGridData();

    $.ajax({
        type: "POST",
        url: businessDocViewUrl,
        dataType: "text",
        data: data,
        success: function (results) {
            if (results.substring(0, 3) === "OK:") {
                $("#" + businessDocViewFormName + " :input[name=\"action\"]").val('save-ok');
                $("#" + businessDocViewFormName).attr('action', results.substring(3)).submit();
            } else {
                alert(results);
                $("#" + businessDocViewFormName + " :input[name=\"multireqtoken\"]").val(randomString(20));
            }
        },
        error: function (msg) {
            alert(msg.status + " " + msg.responseText);
        }
    });
}

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

async function cargarTipoNCF(tipoOperacion)
{
    return $.ajax({
        url: 'ListNCFTipo',
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
        url: 'ListNCFTipo',
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

async function cargarTipoPago()
{
    return $.ajax({
        url: 'ListNCFTipoPago',
        async: true,
        data: {'action': 'busca_pago', 'tipopago': '01'},
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

async function cargarTipoMovimiento()
{
    return $.ajax({
        url: 'ListNCFTipoMovimiento',
        async: true,
        data: {'action': 'busca_movimiento', 'tipomovimiento': 'VEN'},
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
    if (varNCFTipoComprobante.length !== 0 && varNCFTipoComprobante.val() !== '' && tipoOperacion !== '') {
        verificarCorrelativoNCF($("select[name='tipocomprobante']").val(), tipoOperacion);
    }

    // if (varNCFTipoComprobante.length === 0 && $("input[name=codcliente]").val() !== '') {
    //     logConsole($("input[name=codcliente]").val(), 'codcliente');
    //     actualizarInformacionParaNCF();
    // }
    //
    // $("input[name=codcliente]").ready( function () {
    //     logConsole($("input[name=codcliente]").val(), 'codcliente ready');
    // });
    //
    // $("input[name=codcliente]").change(function () {
    //     logConsole('','Actualizando datos');
    //     actualizarInformacionParaNCF();
    // });

    varNCFTipoComprobante.change(function () {
        logConsole(varNCFTipoComprobante.val(),"#doc_codsubtipodoc val");
        if (tipoOperacion !== '') {
            verificarCorrelativoNCF(varNCFTipoComprobante.val(), tipoOperacion);
        }
    });
});