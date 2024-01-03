/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/* global bootstrap */

/**
 * Create a dialog window
 *
 * @param {Object} dialog - options
 * @param {string} dialog.title - string to display in the header of the dialog
 * @param {string} dialog.body - html string to display in the body of the dialog
 * @param {string} dialog.footer - html string to display in the footer of the dialog
 * @param {string} dialog.modalclass - append a class to div.modal
 * @param {string} dialog.dialogclass - append a class to div.modal-dialog
 * @param {string} dialog.id - id attribute of the modal
 * @param {string} dialog.appendTo - where to insert in the dom (default to body)
 * @param {boolean} dialog.autoShow - default true, do we show directly the dialog
 * @param {function} dialog.show - callback function called after the dialog is shown
 * @param {function} dialog.close - callback function called after the dialog is hidden
 * @param {array} dialog.buttons - add a set of button to the dialog footer, can be declared like:
 *                                  [{
 *                                     label: 'my title',
 *                                     class: 'additional class',
 *                                     id: 'id attribute',
 *                                     click: function(event) {...}
 *                                  }, {
 *                                     ...
 *                                  }]
 * @param {boolean} dialog.bs_focus - Data-bs-focus value for the modal
 */
var glpi_html_dialog = function({
    title       = "",
    body        = "",
    footer      = "",
    modalclass  = "",
    dialogclass = "",
    id          = "modal_" + Math.random().toString(36).substring(7),
    appendTo    = "body",
    autoShow    = true,
    show        = () => {},
    close       = () => {},
    buttons     = [],
    bs_focus    = true,
} = {}) {
    if (buttons.length > 0) {
        var buttons_html = "";
        buttons.forEach(button => {
            var bid    = ("id" in button)    ? button.id    : "button_"+Math.random().toString(36).substring(7);
            var label  = ("label" in button) ? button.label : __("OK");
            var bclass = ("class" in button) ? button.class : 'btn-secondary';

            buttons_html+= `
            <button type="button" id="${bid}"
                    class="btn ${bclass}" data-bs-dismiss="modal">
               ${label}
            </button>`;

            // add click event on button
            if ('click' in button) {
                $(document).on('click', '#'+bid, function(event) {
                    button.click(event);
                });
            }
        });

        footer+= buttons_html;
    }

    if (footer.length > 0) {
        footer = `<div class="modal-footer">
         ${footer}
      </div>`;
    }

    const data_bs_focus = !bs_focus ? 'data-bs-focus="false"' : '';

    var modal = `<div class="modal fade ${modalclass}" id="${id}" role="dialog" ${data_bs_focus}>
         <div class="modal-dialog ${dialogclass}">
            <div class="modal-content">
               <div class="modal-header">
                  <h4 class="modal-title">${title}</h4>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"
                           aria-label="${__("Close")}"></button>
               </div>
               <div class="modal-body">${body}</div>
               ${footer}
            </div>
         </div>
      </div>`;

    // remove old modal
    glpi_close_all_dialogs();

    // create new one
    $(appendTo).append(modal);

    var myModalEl = document.getElementById(id);
    var myModal = new bootstrap.Modal(myModalEl, {});

    // show modal
    if (autoShow) {
        myModal.show();
    }

    // create global events
    myModalEl.addEventListener('shown.bs.modal', function(event) {
        // focus first element in modal
        $('#'+id).find("input, textearea, select").first().trigger("focus");

        // call show event
        show(event);
    });
    myModalEl.addEventListener('hidden.bs.modal', function(event) {
        // call close event
        close(event);

        if ($('div.modal.show').length === 0) {
            $('div.modal-backdrop').remove();
        }

        // remove html on modal close
        $('#'+id).remove();
    });

    return id;
};


/**
 * Create a dialog window from an ajax query
 *
 * @param {Object} dialog - options
 * @param {string} dialog.url - the url where to call the ajax query
 * @param {string} dialog.title - string to display in the header of the dialog
 * @param {Object} dialog.params - data to pass to ajax query
 * @param {string} dialog.method - send a get or post query
 * @param {string} dialog.footer - html string to display in the footer of the dialog
 * @param {string} dialog.modalclass - append a class to div.modal
 * @param {string} dialog.dialogclass - append a class to div.modal-dialog
 * @param {string} dialog.id - id attribute of the modal
 * @param {string} dialog.appendTo - where to insert in the dom (default to body)
 * @param {boolean} dialog.autoShow - default true, do we show directly the dialog
 * @param {function} dialog.done - callback function called after the ajax call is done
 * @param {function} dialog.fail - callback function called after the ajax call is fail
 * @param {function} dialog.show - callback function called after the dialog is shown
 * @param {function} dialog.close - callback function called after the dialog is hidden
 * @param {array} dialog.buttons - add a set of button to the dialog footer, can be declared like:
 *                                  [{
 *                                     label: 'my title',
 *                                     class: 'additional class',
 *                                     id: 'id attribute',
 *                                     click: function(event) {...}
 *                                  }, {
 *                                     ...
 *                                  }]
 * @param {boolean} dialog.bs_focus - Data-bs-focus value for the modal
 */
var glpi_ajax_dialog = function({
    url         = "",
    params      = {},
    method      = 'post',
    title       = "",
    footer      = "",
    modalclass  = "",
    dialogclass = "",
    id          = "modal_" + Math.random().toString(36).substring(7),
    appendTo    = 'body',
    autoShow    = true,
    done        = () => {},
    fail        = () => {},
    show        = () => {},
    close       = () => {},
    buttons     = [],
    bs_focus    = true,
} = {}) {
    if (url.length == 0) {
        return;
    }

    // remove old modal
    glpi_close_all_dialogs();

    // AJAX request
    $.ajax({
        url: url,
        type: method,
        data: params,
        success: function(response){
            glpi_html_dialog({
                title: title,
                body: response,
                footer: footer,
                id: id,
                appendTo: appendTo,
                modalclass: modalclass,
                dialogclass: dialogclass,
                autoShow: autoShow,
                buttons: buttons,
                show: show,
                close: close,
                bs_focus: bs_focus
            });
        }
    }).done(function(data) {
        done(data);
    }).fail(function (jqXHR, textStatus) {
        fail(jqXHR, textStatus);
    });

    return id;
};


/**
 * Create an alert dialog (with ok button)
 *
 * @param {Object} alert - options
 * @param {string} alert.title - string to display in the header of the dialog
 * @param {string} alert.message - html string to display in the body of the dialog
 * @param {string} alert.id - id attribute of the modal
 * @param {function} alert.ok_callback - callback function called when "ok" button called
 */
var glpi_alert = function({
    title    = _n('Information', 'Information', 1),
    message  = "",
    id       = "modal_" + Math.random().toString(36).substring(7),
    ok_callback = () => {},
} = {}) {
    glpi_html_dialog({
        title: title,
        body: message,
        id: id,
        buttons: [{
            label: __("OK"),
            click: function(event) {
                ok_callback(event);
            }
        }]
    });

    return id;
};


/**
 * Create an alert dialog (with ok button)
 *
 * @param {Object} alert - options
 * @param {string} alert.title - string to display in the header of the dialog
 * @param {string} alert.message - html string to display in the body of the dialog
 * @param {string} alert.id - id attribute of the modal
 * @param {function} alert.confirm_callback - callback function called when "confirm" button called
 * @param {string} alert.confirm_label - change "confirm" button label
 * @param {function} alert.cancel_label - callback function called when "cancel" button called
 * @param {string} alert.cancel_label - change "cancel" button label
 */
var glpi_confirm = function({
    title         = _n('Information', 'Information', 1),
    message       = "",
    id            = "modal_" + Math.random().toString(36).substring(7),
    confirm_callback = () => {},
    confirm_label = _x('button', 'Confirm'),
    cancel_callback  = () => {},
    cancel_label  = _x('button', 'Cancel'),
} = {}) {

    glpi_html_dialog({
        title: title,
        body: message,
        id: id,
        buttons: [{
            label: confirm_label,
            click: function(event) {
                confirm_callback(event);
            }
        }, {
            label: cancel_label,
            click: function(event) {
                cancel_callback(event);
            }
        }]
    });

    return id;
};


/**
 * Remove from dom all opened glpi dialog
 */
var glpi_close_all_dialogs = function() {
    $('.modal.show').modal('hide').remove();
};

var toast_id = 0;

/**
 * @typedef {{delay: number, animated: boolean, animation: string, animation_extra_classes: string}} ToastOptions
 */
/**
 * Create and show a "toast" (https://getbootstrap.com/docs/5.0/components/toasts/)
 *
 * @param {string} title         Header of the toast
 * @param {string} message       Body of the toast
 * @param {string} css_class     Css class to apply to the toasts
 * @param {ToastOptions} options Toast options
 */
const glpi_toast = (title, message, css_class, options = {}) => {
    toast_id++;

    options = Object.assign({
        delay: 10000,
        animated: true,
        animation: 'animate__tada',
        animation_extra_classes: 'animate__delay-2s animate__slow'
    }, options);

    const animation_classes = options.animated ? `animate__animated ${options.animation} ${options.animation_extra_classes}` : '';
    const html = `<div class='toast-container bottom-0 end-0 p-3 messages_after_redirect'>
      <div id='toast_js_${toast_id}' class='toast ${animation_classes}' role='alert' aria-live='assertive' aria-atomic='true'>
         <div class='toast-header ${css_class}'>
            <strong class='me-auto'>${title}</strong>
            <button type='button' class='btn-close' data-bs-dismiss='toast' aria-label='${__('Close')}'></button>
         </div>
         <div class='toast-body'>
            ${message}
         </div>
      </div>
   </div>`;
    $('body').append(html);

    const toast = new bootstrap.Toast(document.querySelector('#toast_js_' + toast_id), {
        delay: options.delay,
    });
    toast.show();
};

/**
 * Display a success message toast
 *
 * @param {string} message       Message to display
 * @param {string} caption       Caption for the toast
 * @param {ToastOptions} options Toast options
 */
const glpi_toast_success = (message, caption, options = {}) => {
    glpi_toast(caption || __('Success'), message, 'bg-success text-white border-0', options);
};

/**
 * Display an information toast
 *
 * @param {string} message       Message to display
 * @param {string} caption       Caption for the toast
 * @param {ToastOptions} options Toast options
 */
const glpi_toast_info = function(message, caption, options = {}) {
    glpi_toast(caption || _n("Information", "Informations", 1), message, 'bg-info text-white border-0', options);
};

/**
 * Display a warning toast
 *
 * @param {string} message       Message to display
 * @param {string} caption       Caption for the toast
 * @param {ToastOptions} options Toast options
 */
const glpi_toast_warning = (message, caption, options = {}) => {
    glpi_toast(caption || __('Warning'), message, 'bg-warning text-white border-0', options);
};

/**
 * Display an error toast
 *
 * @param {string} message       Message to display
 * @param {string} caption       Caption for the toast
 * @param {ToastOptions} options Toast options
 */
const glpi_toast_error = (message, caption, options = {}) => {
    glpi_toast(caption || __('Error'), message, 'bg-danger text-white border-0', options);
};

