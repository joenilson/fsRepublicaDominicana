
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

function businessDocViewSubjectChanged()
{
    var data = {};
    $.each($("#" + businessDocViewFormName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "subject-changed";
    logConsole(data,"subject-changed");

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

async function actualizarDatos(codcliente) {

}

async function businessDocViewSave()
{
    if ($("#codclienteAutocomplete").val() === '') {
        executeModal('errorNoClienteDetectado','No hay Cliente','Debe seleccionar un cliente primero!', 'warning', '');
    } else {
        $("#btn-document-save").prop("disabled", true);
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
        $.each(datosTipoNCFs.tipocomprobantes, function (i, value) {
            let defaultSelected = (datosCliente.infocliente.tipocomprobante === value.tipocomprobante) ? 'selected' : '';
            descInfoClienteTipoComprobante = (datosCliente.infocliente.tipocomprobante === value.tipocomprobante)
                ? value.descripcion : descInfoClienteTipoComprobante;
            selectTiposNCF += '<option value="'+value.tipocomprobante+'"'+defaultSelected+'>'+value.descripcion+'</option>';
        });

        var ncfTipoPagoCliente = datosCliente.infocliente.ncftipopago;
        var readOnlySelects = ($("#formSalesDocumentLine #doc_idestado").val() === '11');
        var descInfoClienteTipoPago = '';
        let selectOptionsPagos = "";
        logConsole(ncfTipoPagoCliente, 'ncfTipoPagoCliente');
        $.each(datosPago.pagos, function (i, value) {
            let defaultSelected = ((value.codigo === '17' && ncfTipoPagoCliente === '') || ncfTipoPagoCliente === value.codigo) ? 'selected' : '';
            descInfoClienteTipoPago = (datosCliente.infocliente.ncftipopago === value.codigo)
                ? value.descripcion : descInfoClienteTipoPago;
            let noSelected = ($("#formSalesDocumentLine #doc_idestado").val() === '11' && defaultSelected !== 'selected') ? ' disabled' : '';
            selectOptionsPagos += '<option value="'+value.codigo+'"'+defaultSelected+noSelected+'>'+value.descripcion+'</option>';
        });

        var tipoMovimiento = await cargarTipoMovimiento();
        var datosMovimiento = JSON.parse(tipoMovimiento);

        let selectOptionsMovimientos = "";
        $.each(datosMovimiento.movimientos, function (i, value) {
            let defaultSelected = (value.codigo === '1') ? 'selected' : '';
            let noSelected = ($("#formSalesDocumentLine #doc_idestado").val() === '11' && defaultSelected !== 'selected') ? ' disabled' : '';
            selectOptionsMovimientos += '<option value="'+value.codigo+'"'+defaultSelected+noSelected+'>'+value.descripcion+'</option>';
        });

        let message = setBusinessDocViewModalSave(
            'Cliente',
            readOnlySelects,
            descInfoClienteTipoComprobante,
            descInfoClienteTipoPago,
            selectTiposNCF,
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
}

function salesFormSave(action, selectedLine) {
    animateSpinner('add');

    document.forms['salesForm']['action'].value = action;
    document.forms['salesForm']['selectedLine'].value = selectedLine;

    const formData = new FormData(document.forms['salesForm']);
    const plainFormData = Object.fromEntries(formData.entries());
    const formDataJsonString = JSON.stringify(plainFormData);

    let data = new FormData();
    data.append('action', action);
    data.append('code', document.forms['salesForm']['code'].value);
    data.append('multireqtoken', document.forms['salesForm']['multireqtoken'].value);
    data.append('selectedLine', document.forms['salesForm']['selectedLine'].value);
    data.append('data', formDataJsonString);
    console.log(data);

    fetch('{{ fsc.url() }}', {
        method: 'POST',
        body: data
    }).then(function (response) {
        animateSpinner('remove', true);
        if (response.ok) {
            return response.json();
        }
        return Promise.reject(response);
    }).then(function (data) {
        console.log(data);
        if (Array.isArray(data.messages)) {
            data.messages.forEach(item => alert(item.message));
        }
        if (data.ok) {
            window.location.replace(data.newurl);
        }
    }).catch(function (error) {
        alert('error');
        console.warn(error);
        animateSpinner('remove', false);
    });

    return false;
}
