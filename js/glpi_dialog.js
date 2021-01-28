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
} = {}) {
   if (buttons.length > 0) {
      buttons_html = "";
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

   var modal = `<div class="modal fade ${modalclass}" id="${id}" role="dialog">';
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

   // show modal
   if (autoShow) {
      $('#'+id).modal('show');
   }

   // create global events
   var myModal = document.getElementById(id);
   myModal.addEventListener('shown.bs.modal', function(event) {
      // focus first element in modal
      $('#'+id).find("input, textearea, select").first().trigger("focus");

      // call show event
      show(event);
   });
   myModal.addEventListener('hidden.bs.modal', function(event) {
      // call close event
      close(event);

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
         })
      }
   })
   .done(function(data) {
      done(data);
   })
   .fail(function (jqXHR, textStatus) {
      fail(jqXHR, textStatus)
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
 * Close and remode from dom all opened glpi dialog
 */
var glpi_close_all_dialogs = function() {
   $('.modal.show').modal('hide').remove();
};
