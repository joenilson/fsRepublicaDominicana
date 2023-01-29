/*
 * Copyright (C) 2022 Joe Nilson <joenilson@gmail.com>
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

function ChangeCIFNIFButton(actualValue) {
    newButton = '<div class="input-group">' +
        '   <input type="text" class="form-control" name="cifnif" value="'+actualValue+'"> ' +
        '   <div className="input-group-append"> ' +
        '       <button class="btn btn-secondary" type="button" id="buscar_rnc" onclick="btnVerificarRNC()"><i class="fas fa-search fa-fw"></i></button> ' +
        '   </div> ' +
        '</div> ';
    return newButton;
}

async function searchRNC(rnc) {
    var pathArray = window.location.pathname.split('/');
    var PageName = pathArray[pathArray.length-1];
    return $.ajax({
        url: PageName,
        async: true,
        data: {'action': 'busca_rnc', 'cifnif': rnc},
        type: 'POST',
        datatype: 'json',
        success: function (response) {
            let data = JSON.parse(response);
            if (data.RGE_ERROR === undefined) {
                showInfoDGII(data);
            } else {
                executeModal(
                    'alertaRNCNoEncontrado',
                    'Busqueda RNC',
                    data.message,
                    'warning'
                );
            }
        },
        error: function (xhr, status) {
            alert('Ha ocurrido algún tipo de error ' + status);
        }
    });
}

function verificarRNC() {
    var selectTipo = $('select[name="personafisica"]');
    logConsole(selectTipo, 'selectTipo');
    logConsole(selectTipo.val(), 'selectTipo Value');
    if (selectTipo.val() === '1') {
        executeModal(
            'alertaTipoCliente',
            'Tipo de Cliente',
            'No se hace verificaciones de Personas Físicas',
            'warning'
        );
    } else {
        logConsole('Buscamos el RNC en el WebService de la DGII');
        var rnc = $('input[name="cifnif"]').val();
        searchRNC(rnc);
    }
}

function btnVerificarRNC() {
    verificarRNC();
}

function tablaInformacionDGII(data) {
    var color = (data.ESTATUS !== 'ACTIVO') ? ' class="alert alert-danger"' : '';
    var tabla = '<div class="container">\n' +
        '  <div class="row">\n' +
        '    <div class="col-6 col-sm-4"><b>RNC: </b><span id="RGE_RUC">' + data.RGE_RUC +'</span></div>\n' +
    '    <div class="col-6 col-sm-8"><b>Nombre: </b><span id="RGE_NOMBRE">'+ data.RGE_NOMBRE +'</span></div>\n' +
        '\n' +
        '    <!-- Force next columns to break to new line at md breakpoint and up -->\n' +
        '    <div class="w-100 d-none d-md-block"></div>\n' +
        '\n' +
        '    <div class="col-6 col-sm-4"><b>Estatus: </b><span id="RGE_ESTATUS"+color+>'+ data.ESTATUS +'</span></div>\n' +
    '    <div class="col-6 col-sm-8"><b>Razón Social: </b><span id="RGE_NOMBRE_COMERCIAL">'+ data.NOMBRE_COMERCIAL +'</span></div>\n' +
        '  </div>\n' +
        '</div>';
    return tabla;
}

function showInfoDGII(data) {
    var contentType = (data.ESTATUS !== 'ACTIVO') ? 'warning' : 'pickup';
    var info = tablaInformacionDGII(data);
    executeModal(
        'modalDgiiResultados',
        'Resultados DGII',
        info,
        contentType,
        'usarInformacionDGII',
    );
}

function usarInformacionDGII(data) {
    logConsole(data, 'btn Info');
    logConsole($('#RGE_ESTATUS').text(), 'ESTATUS INFO');
    $('input[name="razonsocial"]').val($('#RGE_NOMBRE').text());
    $('input[name="cifnif"]').val($('#RGE_RUC').text());
    $('select[name="tipoidfiscal"]').val('RNC');
    if ($('#RGE_ESTATUS').text() !== 'Inactivo') {
        if ($('#RGE_NOMBRE_COMERCIAL').text() !== '') {
            $('input[name="nombre"]').val($('#RGE_NOMBRE_COMERCIAL').text());
        } else {
            $('input[name="nombre"]').val($('#RGE_NOMBRE').text());
        }
    } else {
        executeModal(
            'advertenciaClienteinactivo',
            'Cliente Inactivo',
            'No se puede usar un cliente inactivo.',
            'warning'
        );
    }
    $('#modalDgiiResultados').modal('hide');
    //$('#modalDgiiResultados').remove();
}

$(document).ready(function () {
    var actualInput = $('input[name="cifnif"]');
    var actualInputValue = actualInput.val();
    logConsole(actualInput, 'actual Input');
    logConsole(actualInputValue, 'actual Input Value');
    var parentDOM = actualInput.parent();
    logConsole(parentDOM, 'parentNode');
    actualInput.replaceWith(ChangeCIFNIFButton(actualInputValue));
});