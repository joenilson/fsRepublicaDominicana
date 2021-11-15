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
function businessDocViewSubjectChanged()
{
    var data = {};
    $.each($("#" + businessDocViewFormName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "subject-changed";
    logConsole(data, "subject-changed");

    $.ajax({
        type: "POST",
        url: businessDocViewUrl,
        dataType: "json",
        data: data,
        success: function (results) {
            $("#doc_codpago").val(results.codpago);
            $("#doc_codserie").val(results.codserie);
            $("#formEditFacturaProveedor select[name=ncftipopago]").val(results.ncftipopago);

            /**
             * Review the doc_codsubtipodoc existence,
             * if it exist we put the value from the customer data
             */
            if ($("#doc_codsubtipodoc").length !== 0) {
                $("#doc_codsubtipodoc").val(results.codsubtipodoc);
            }
            logConsole(results.codsubtipodoc,"codsubtipodoc");
            /**
             * Review the doc_codopersaciondoc existence,
             * if it exist we put the value from the customer data
             */
            if ($("#doc_codoperaciondoc").length !== 0) {
                $("#doc_codoperaciondoc").val(results.codoperaciondoc);
            }
            logConsole(results.codoperaciondoc,"codoperaciondoc");

            logConsole(results,"results");

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
        data: {'action': 'busca_pago', 'tipopago': '02'},
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
        data: {'action': 'busca_movimiento', 'tipomovimiento': 'COM'},
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
    var ncfTipoPagoCliente = $("#formEditFacturaProveedor select[name=ncftipopago]").val();
    var readOnlySelects = ($("#formPurchaseDocumentLine #doc_idestado").val() === '11');
    let selectOptionsPagos = "";
    $.each(datosPago.pagos, function (i, value) {
        let defaultSelected = ((value.codigo === '04' && ncfTipoPagoCliente === '') || ncfTipoPagoCliente === value.codigo) ? 'selected' : '';
        let noSelected = ($("#formPurchaseDocumentLine #doc_idestado").val() === '11' && defaultSelected !== 'selected') ? ' disabled' : '';
        selectOptionsPagos += '<option value="'+value.codigo+'"'+defaultSelected+noSelected+'>'+value.descripcion+'</option>';
    });

    var tipoMovimiento = await cargarTipoMovimiento();
    var datosMovimiento = JSON.parse(tipoMovimiento);

    let selectOptionsMovimientos = "";
    $.each(datosMovimiento.movimientos, function (i, value) {
        let defaultSelected = (value.codigo === '09') ? 'selected' : '';
        let noSelected = ($("#formPurchaseDocumentLine #doc_idestado").val() === '11' && defaultSelected !== 'selected') ? ' disabled' : '';
        selectOptionsMovimientos += '<option value="'+value.codigo+'"'+defaultSelected+noSelected+'>'+value.descripcion+'</option>';
    });

    let message = setBusinessDocViewModalSave(
        'Proveedor',
        readOnlySelects,
        selectOptionsPagos,
        selectOptionsMovimientos
    );

    executeModal(
        'completeNCFData',
        'Complete la información faltante',
        message,
        'default',
        'saveBussinessDocument'
    );

    $("#btn-document-save").prop("disabled", false);
}
