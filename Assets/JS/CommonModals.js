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
 * Modal Creation Functions
 * Code based on the gist https://gist.github.com/signalpoint/e6dda6b6220a157edbd170f3d2a6250e
 *
 * @example
 * Call the function executeModal with this parameters
 * executeModal(
 * 'completeNCFData', // This is the modalId
 * 'Modal Title', // This is the title param
 * '<b>Message</b>', // This is the message, can be plain text or html code.
 * 'default', // This is the contentType, based on the content type will be showed some butons and alert indicators.
 * 'saveFn', // If we need to process a javascript call we can set a function to execute code and process the modal.
 * 'medium' // This is the modal size (small, medium, full)
 * );
 */

/**
 * @param {string} modalId
 * @param {string} title
 * @param {string} content
 * @param {string} contentType
 * @param {string} saveButtonCallback
 * @param {string} size
 */
function executeModal(modalId, title, content, contentType = 'default', saveButtonCallback = '', size = 'medium')
{
    //Set the modal ID
    let actualModal = getModal(modalId);

    // Init the modal if it hasn't been already.
    actualModal = (!actualModal) ? initModal(modalId) : actualModal;

    //Set the modal size
    setModalSize(actualModal, size);

    //Set the modal content based on the type
    content = setModalContentType(contentType, content);

    //Choose the buttons to show based on the contentType
    let buttons = setModalButtons(contentType, saveButtonCallback);

    //Assemble all the pieces in the final modal html body
    let html = setModalHtml(modalId, title, content, buttons);

    //Put the modal content in the modal
    setModalContent(modalId, html);

    //Show the modal
    $(actualModal).modal('show');
}

/**
 * @param {HTMLElement} modalElement
 * @param {string} size
 */
function setModalSize(modalElement, size)
{
    let modalDialog = modalElement.querySelector('.modal-dialog');
    modalDialog.classList.remove('modal-sm', 'modal-lg', 'modal-xl', 'modal-fullscreen');

    switch (size) {
        case 'small':
            modalDialog.classList.add('modal-sm');
            break;
        case 'full':
            modalDialog.classList.add('modal-fullscreen');
            break;
    }
}

/**
 * @returns {HTMLElement}
 * @param {string} modalId
 */
function getModal(modalId)
{
    return document.getElementById(modalId);
}

/**
 * @param {string} contentType
 * @param {string} content
 * @returns {string}
 */
function setModalContentType(contentType, content)
{
    if (contentType === 'default') {
        return content;
    } else {
        return '<div className="alert alert-' + contentType + '" role="alert"> ' + content + '</div>';
    }
}

/**
 * @param {string} modalId
 * @param {string} html
 */
function setModalContent(modalId, html)
{
    getModal(modalId).querySelector('.modal-content').innerHTML = html;
}

/**
 * @param {string} modalId
 * @param {string} title
 * @param {string} content
 * @param {string} buttons
 * @returns {string}
 */
function setModalHtml(modalId, title, content, buttons)
{
    var html =
        '<div class="modal-header">' +
        '<h5 class="modal-title" id="'+modalId+'-ModalLabel">'+title+'</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">' +
        '' +
        '</button>' +
        '</div>' +
        '<div class="modal-body">' +
        content +
        '</div>' +
        '<div class="modal-footer">' +
        buttons +
        '</div>';
    return html;
}

/**
 * @param {string} contentType
 * @param {string} saveButtonCallback
 * @returns {string}  {string}
 */
function setModalButtons(contentType, saveButtonCallback)
{
    let buttons;

    const onClick = (saveButtonCallback) ? ' onClick=' + saveButtonCallback + '(this)' : '';
    const cancelButton  = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>';
    const warningButton  = '<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>';
    const saveButton = '<button type="button" class="btn btn-primary"'+onClick+'>Guardar</button>';
    const pickupButton  = '<button type="button" class="btn btn-primary"' +onClick+'>Usar</button>';

    switch (contentType) {
        case 'warning':
            buttons = warningButton;
            break;
        case 'pickup':
            buttons = cancelButton + pickupButton;
            break;
        case 'default':
            buttons = cancelButton + saveButton;
            break;
        default:
            buttons = cancelButton + saveButton;
            break;
    }
    return buttons;
}

/**

 * @param {string} modalId
 * @returns {HTMLDivElement}
 */
function initModal(modalId)
{
    let modal = document.createElement('div');
    modal.classList.add('modal', 'fade');
    modal.setAttribute('id', modalId);
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-labelledby', modalId+'-ModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML =
        '<div class="modal-dialog" role="document">' +
        '<div class="modal-content"></div>' +
        '</div>';
    document.body.appendChild(modal);
    return modal;
}