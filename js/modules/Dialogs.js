/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

import GLPIModule from "./GLPIModule.js";

/* global bootstrap */

/**
 * @typedef DialogButton
 * @property {string} [label]
 * @property {string} [class]
 * @property {string} [id]
 * @property {function} [click]
 */

/**
 * @typedef DialogOptions
 * @property {string} [title] String to display in the header of the dialog
 * @property {string} [body] HTML string to display in the body of the dialog
 * @property {string} [footer] HTML string to display in the footer of the dialog
 * @property {string} [modalclass] Append a class to div.modal
 * @property {string} [dialogclass] Append a class to div.modal-dialog
 * @property {string} [id] ID attribute of the modal
 * @property {string} [appendTo] Where to insert in the dom (default to body)
 * @property {boolean} [autoShow] Default true, do we show directly the dialog
 * @property {function} [show] Callback function called after the dialog is shown
 * @property {function} [close] Callback function called after the dialog is hidden
 * @property {DialogButton[]} [buttons] Array of buttons to display in the footer of the dialog
 * @property {boolean} [bs_focus] Data-bs-focus value for the modal. Default to true.
 */

/**
 * @typedef {DialogOptions & {
 *     url: string,
 *     method: string,
 *     params: {},
 *     [done]: function,
 *     [fail]: function,
 * }} AjaxDialogOptions
 */

/**
 * @typedef {{delay: number, animated: boolean, animation: string, animation_extra_classes: string}} ToastOptions
 */

export default class Dialogs extends GLPIModule {

    constructor() {
        super();
        /** {number} Incremented value used to create unique toast IDs */
        this.toast_id = 0;
    }

    initialize() {
        this.redefineAlert();
        this.redefineConfirm();
    }

    static getDefaultDialogOptions() {
        return {
            title: '',
            body: '',
            footer: '',
            modalclass: '',
            dialogclass: '',
            id: "modal_" + Math.random().toString(36).substring(7),
            appendTo: 'body',
            autoShow: true,
            show: () => {},
            close: () => {},
            buttons: [],
            bs_focus: true,
        };
    }

    static getDefaultAJAXDialogOptions() {
        const base_options = Dialogs.getDefaultDialogOptions();
        return Object.assign(base_options, {
            url: '',
            method: 'post',
            params: {},
            fail: () => {},
        });
    }

    redefineAlert() {
        window.old_alert = window.alert;
        /**
         * @param {string} message
         * @param {string} caption
         */
        window.alert = (message, caption) => {
            // Don't apply methods on undefined objects... ;-) #3866
            if (typeof message == 'string') {
                message = message.replace('\\n', '<br>');
            }
            caption = caption || _n('Information', 'Information', 1);

            this.showAlert({
                title: caption,
                message: message,
            });
        };
    }

    redefineConfirm() {
        window.confirmed = false;
        window.lastClickedElement = undefined;

        // store last clicked element on dom
        $(document).click((event) => {
            window.lastClickedElement = $(event.target);
        });

        // asynchronous confirm dialog with jquery ui
        const newConfirm = (message, caption) => {
            message = message.replace('\\n', '<br>');
            caption = caption || '';

            this.showConfirm({
                title: caption,
                message: message,
                confirm_callback: () => {
                    window.confirmed = true;

                    //trigger click on the same element (to return true value)
                    window.lastClickedElement.click();

                    // re-init confirmed (to permit usage of 'confirm' function again in the page)
                    // maybe timeout is not essential ...
                    setTimeout(() => {
                        window.confirmed = false;
                    }, 100);
                }
            });
        };

        window.nativeConfirm = window.confirm;

        // redefine native 'confirm' function
        window.confirm = (message, caption) => {
            // if watched var isn't true, we can display dialog
            if(!window.confirmed) {
                // call asynchronous dialog
                newConfirm(message, caption);
            }

            // return early
            return window.confirmed;
        };
    }

    /**
     * Create a dialog window with static HTML content.
     * @param {DialogOptions} options
     * @returns {string} The id of the dialog
     */
    createHtmlDialog(options = {}) {
        options = Object.assign({}, Dialogs.getDefaultDialogOptions(), options);

        if (options.buttons.length > 0) {
            let buttons_html = "";
            options.buttons.forEach(button => {
                const bid    = ("id" in button)    ? button.id    : "button_"+Math.random().toString(36).substring(7);
                const label  = ("label" in button) ? button.label : __("OK");
                const bclass = ("class" in button) ? button.class : 'btn-secondary';

                buttons_html += `
            <button type="button" id="${bid}"
                    class="btn ${bclass}" data-bs-dismiss="modal">
               ${label}
            </button>`;

                // add click event on button
                if ('click' in button) {
                    $(document).on('click', '#'+bid, (event) => {
                        button.click(event);
                    });
                }
            });

            options.footer += buttons_html;
        }

        if (options.footer.length > 0) {
            options.footer = `<div class="modal-footer">${options.footer}</div>`;
        }

        const data_bs_focus = !options.bs_focus ? 'data-bs-focus="false"' : '';
        const modal = `
            <div class="modal fade ${options.modalclass}" id="${options.id}" role="dialog" ${data_bs_focus}>
                <div class="modal-dialog ${options.dialogclass}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">${options.title}</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${__("Close")}"></button>
                        </div>
                        <div class="modal-body">${options.body}</div>
                        ${options.footer}
                    </div>
                </div>
            </div>`;

        // remove old modal
        this.closeAllDialogs();

        // create new one
        $(options.appendTo).append(modal);

        const myModalEl = document.getElementById(options.id);
        const myModal = new bootstrap.Modal(myModalEl, {});

        // show modal
        if (options.autoShow) {
            myModal.show();
        }

        // create global events
        myModalEl.addEventListener('shown.bs.modal', (event) => {
            // focus first element in modal
            $('#'+options.id).find("input, textarea, select").first().trigger("focus");

            // call show event
            options.show(event);
        });
        myModalEl.addEventListener('hidden.bs.modal', (event) => {
            // call close event
            options.close(event);

            // remove html on modal close
            $('#'+options.id).remove();
        });

        return options.id;
    }

    /**
     * Create a dialog window with static HTML content.
     * @param {AjaxDialogOptions} options
     * @returns {string|null} The id of the dialog
     */
    createAjaxDialog(options = {}) {
        options = Object.assign({}, Dialogs.getDefaultAJAXDialogOptions(), options);

        if (options.url.length === 0) {
            return null;
        }

        // remove old modal
        this.closeAllDialogs();

        // AJAX request
        $.ajax({
            url: options.url,
            type: options.method,
            data: options.params,
            success: (response) =>{
                this.createHtmlDialog({
                    title: options.title,
                    body: response,
                    footer: options.footer,
                    id: options.id,
                    appendTo: options.appendTo,
                    modalclass: options.modalclass,
                    dialogclass: options.dialogclass,
                    autoShow: options.autoShow,
                    buttons: options.buttons,
                    show: options.show,
                    close: options.close,
                    bs_focus: options.bs_focus
                });
            }
        }).done((data) => {
            options.done(data);
        }).fail((jqXHR, textStatus) => {
            options.fail(jqXHR, textStatus);
        });

        return options.id;
    }

    /**
     * Create an alert dialog (with ok button)
     *
     * @param {Object} alert - options
     * @param {string} alert.title - string to display in the header of the dialog
     * @param {string} alert.message - html string to display in the body of the dialog
     * @param {string} alert.id - id attribute of the modal
     * @param {function} alert.ok_callback - callback function called when "ok" button called
     * @returns {string} The id of the dialog
     */
    showAlert({
        title    = _n('Information', 'Information', 1),
        message  = "",
        id       = "modal_" + Math.random().toString(36).substring(7),
        ok_callback = () => {},
    } = {}) {
        this.createHtmlDialog({
            title: title,
            body: message,
            id: id,
            buttons: [{
                label: __("OK"),
                click: (event) => {
                    ok_callback(event);
                }
            }],
        });

        return id;
    }

    /**
     * Create a confirm dialog (with confirm and cancel buttons)
     *
     * @param {Object} alert - options
     * @param {string} alert.title - string to display in the header of the dialog
     * @param {string} alert.message - html string to display in the body of the dialog
     * @param {string} alert.id - id attribute of the modal
     * @param {function} alert.confirm_callback - callback function called when "confirm" button called
     * @param {string} alert.confirm_label - change "confirm" button label
     * @param {function} alert.cancel_label - callback function called when "cancel" button called
     * @param {string} alert.cancel_label - change "cancel" button label
     * @returns {string} The id of the dialog
     */
    showConfirm({
        title         = _n('Information', 'Information', 1),
        message       = "",
        id            = "modal_" + Math.random().toString(36).substring(7),
        confirm_callback = () => {},
        confirm_label = _x('button', 'Confirm'),
        cancel_callback  = () => {},
        cancel_label  = _x('button', 'Cancel'),
    } = {}) {
        this.createHtmlDialog({
            title: title,
            body: message,
            id: id,
            buttons: [{
                label: confirm_label,
                click: (event) => {
                    confirm_callback(event);
                }
            }, {
                label: cancel_label,
                click: (event) => {
                    cancel_callback(event);
                }
            }]
        });

        return id;
    }

    /**
     * Close all opened dialogs by removing them from the DOM.
     */
    closeAllDialogs() {
        $('.modal.show').modal('hide').remove();
    }

    /**
     * Create and show a "toast" (https://getbootstrap.com/docs/5.0/components/toasts/)
     *
     * @param {string} title         Header of the toast
     * @param {string} message       Body of the toast
     * @param {string} css_class     Css class to apply to the toasts
     * @param {ToastOptions} options Toast options
     */
    showToast(title, message, css_class, options = {}) {
        this.toast_id++;

        options = Object.assign({
            delay: 10000,
            animated: true,
            animation: 'animate__tada',
            animation_extra_classes: 'animate__delay-2s animate__slow'
        }, options);

        const animation_classes = options.animated ? `animate_animated ${options.animation} ${options.animation_extra_classes}` : '';
        let location = CFG_GLPI.toast_location || 'bottom-right';
        const valid_locations = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];
        // If location is not valid, change it to bottom-right
        if (!valid_locations.includes(location)) {
            location = 'bottom-right';
        }
        const html = `<div class='toast-container ${location} p-3 messages_after_redirect'>
            <div id='toast_js_${this.toast_id}' class='toast ${css_class} ${animation_classes}' role='alert' aria-live='assertive' aria-atomic='true'>
                <div class='toast-header'>
                    <strong class='me-auto'>${title}</strong>
                    <button type='button' class='btn-close' data-bs-dismiss='toast' aria-label='${__('Close')}'></button>
                </div>
                <div class='toast-body'>${message}</div>
            </div>
        </div>`;
        $('body').append(html);

        const toast = new bootstrap.Toast(document.querySelector('#toast_js_' + this.toast_id), {
            delay: options.delay,
        });
        toast.show();
    }

    showSuccessToast(message, caption, options = {}) {
        this.showToast(caption || __('Success'), message, 'bg-success text-white border-0', options);
    }

    showInfoToast(message, caption, options = {}) {
        this.showToast(caption || _n("Information", "Informations", 1), message, 'bg-info text-white border-0', options);
    }

    showWarningToast(message, caption, options = {}) {
        this.showToast(caption || __('Warning'), message, 'bg-warning text-white border-0', options);
    }

    showErrorToast(message, caption, options = {}) {
        this.showToast(caption || __('Error'), message, 'bg-danger text-white border-0', options);
    }

    getLegacyGlobals() {
        return {
            'glpi_html_dialog': 'createHtmlDialog',
            'glpi_ajax_dialog': 'createAjaxDialog',
            'glpi_close_all_dialogs': 'closeAllDialogs',
            'glpi_alert': 'showAlert',
            'glpi_confirm': 'showConfirm',
            'glpi_toast': 'showToast',
            'glpi_toast_success': 'showSuccessToast',
            'glpi_toast_info': 'showInfoToast',
            'glpi_toast_warning': 'showWarningToast',
            'glpi_toast_error': 'showErrorToast',
        };
    }
}
