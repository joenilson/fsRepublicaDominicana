
/*
 * This file is part of FacturaScripts - Dominican Republic Plugin
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 * Copyright (C) 2019-2020 Joe Nilson <joenilson@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

function businessDocViewSubjectChanged() {
    var data = {};
    $.each($("#" + businessDocViewFormName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "subject-changed";
    console.log("data", data);

    $.ajax({
        type: "POST",
        url: businessDocViewUrl,
        dataType: "json",
        data: data,
        success: function (results) {
            $("#doc_codpago").val(results.codpago);
            $("#doc_codserie").val(results.codserie);
            $("#formEditFacturaCliente select[name=ncftipopago]").val(results.ncftipopago);

            /**
             * Review the doc_codsubtipodoc existence,
             * if it exist we put the value from the customer data
             */
            if($("#doc_codsubtipodoc").length !== 0) {
                $("#doc_codsubtipodoc").val(results.codsubtipodoc);
            }
            /**
             * Review the doc_codopersaciondoc existence,
             * if it exist we put the value from the customer data
             */
            if($("#doc_codoperaciondoc").length !== 0) {
                $("#doc_codoperaciondoc").val(results.codoperaciondoc);
            }
            
            console.log("results", results);

            businessDocViewRecalculate();
        },
        error: function (xhr, status, error) {
            alert(xhr.responseText);
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

async function businessDocViewSave()
{
    $("#btn-document-save").prop("disabled", true);

    var tipoPago = await cargarTipoPago();
    var datosPago = JSON.parse(tipoPago);
    var ncfTipoPagoCliente = $("#formEditFacturaCliente select[name=ncftipopago]").val();
    var readOnlySelects = ($("#formSalesDocumentLine #doc_idestado").val() === '11')?true:false;
    let selectOptionsPagos = "";
    $.each(datosPago.pagos, function(i, value) {
        let defaultSelected = ((value.codigo === '17' && ncfTipoPagoCliente === '') || ncfTipoPagoCliente === value.codigo) ? 'selected' : '';
        let noSelected = ($("#formSalesDocumentLine #doc_idestado").val() === '11' && defaultSelected !== 'selected') ? ' disabled' : '';
        selectOptionsPagos += '<option value="'+value.codigo+'"'+defaultSelected+noSelected+'>'+value.descripcion+'</option>';
    });

    var tipoMovimiento = await cargarTipoMovimiento();
    var datosMovimiento = JSON.parse(tipoMovimiento);

    let selectOptionsMovimientos = "";
    $.each(datosMovimiento.movimientos, function(i, value) {
        let defaultSelected = (value.codigo === '1') ? 'selected' : '';
        let noSelected = ($("#formSalesDocumentLine #doc_idestado").val() === '11' && defaultSelected !== 'selected') ? ' disabled' : '';
        selectOptionsMovimientos += '<option value="'+value.codigo+'"'+defaultSelected+noSelected+'>'+value.descripcion+'</option>';
    });


    bootbox.dialog({
        title: "Complete la información faltante",
        message: '<div class="form-content">\n' +
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
            '    </div>',
        buttons: [
            {
                label: "Guardar",
                className: "btn btn-primary",
                callback: function() {
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
            },
            {
                label: "Cancelar",
                className: "btn btn-danger",
                callback: function() {
                    return true;
                }
            }
        ],
    });

    $("#btn-document-save").prop("disabled", false);
}