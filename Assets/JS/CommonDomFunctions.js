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
    if (tipoOperacion === 'Compras' && !ArrayTipoNCFCompras.includes($("#doc_codsubtipodoc").val())) {
        return true;
    }

    return $.ajax({
        url: 'ListNCFRango',
        async: true,
        data: {'action': 'busca_correlativo', 'tipocomprobante': tipoComprobante},
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
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' + text
    );
}

function setBusinessDocViewModalSave(readOnlySelects, selectOptionsPagos,selectOptionsMovimientos)
{
    let message = '<div class="form-content">\n' +
        '      <form class="form" role="form">\n' +
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
    data['ncftipopago'] = $('form #ncftipopago').val();
    data['ncftipomovimiento'] = $('form #ncftipomovimiento').val();
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
    if ($('#formEditFacturaProveedor').length > 0) {
        businessDocument = 'Compra';
    } else if ($('#formEditFacturaCliente').length > 0) {
        businessDocument = 'Venta';
    }
    return businessDocument;
}


$(document).ready(function () {
    let tipoOperacion = isBusinessDocumentPage();
    let varCodSubtipoDoc = $("#doc_codsubtipodoc");
    if (varCodSubtipoDoc.val() !== '' && tipoOperacion !== '') {
        verificarCorrelativoNCF($("#doc_codsubtipodoc").val(), tipoOperacion);
    }

    varCodSubtipoDoc.change(function () {
        logConsole(varCodSubtipoDoc.val(),"#doc_codsubtipodoc val");
        if (tipoOperacion !== '') {
            verificarCorrelativoNCF(varCodSubtipoDoc.val(), tipoOperacion);
        }
    });
});