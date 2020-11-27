/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */


/*
 * Redefine 'window.alert' javascript function by a jquery-ui dialog equivalent (but prettier).
 */
window.old_alert = window.alert;
window.alert = function(message, caption) {
   // Don't apply methods on undefined objects... ;-) #3866
   if(typeof message == 'string') {
      message = message.replace("\n", '<br>');
   }
   caption = caption || _sn('Information', 'Information', 1);

   var buttons = [];
   buttons[__s('OK')] = function() {
      $(this).dialog('close');
   };

   $('<div></div>').html(message).dialog({
      title: caption,
      buttons: buttons,
      dialogClass: 'glpi_modal',
      open: function(event, ui) {
         $(this).parent().prev('.ui-widget-overlay').addClass('glpi_modal');
         $(this).next('div').find('button').focus();
      },
      close: function(){
         $(this).remove();
      },
      draggable: true,
      modal: true,
      resizable: false,
      width: 'auto'
   });
};



/**
 * Redefine 'window.confirm' javascript function by a jquery-ui dialog equivalent (but prettier).
 * This dialog is normally asynchronous and can't return a boolean like naive window.confirm.
 * We manage this behavior with a global variable 'confirmed' who watchs the acceptation of dialog.
 * In this case, we trigger a new click on element to return the value (and without display dialog).
 */
var confirmed = false;
var lastClickedElement;

// store last clicked element on dom
$(document).click(function(event) {
    lastClickedElement = $(event.target);
});

// asynchronous confirm dialog with jquery ui
var newConfirm = function(message, caption) {
   message = message.replace("\n", '<br>');
   caption = caption || '';

   var buttons = [];
   buttons[_x('button', 'Confirm')] = function () {
      $(this).dialog('close');
      confirmed = true;

      //trigger click on the same element (to return true value)
      lastClickedElement.click();

      // re-init confirmed (to permit usage of 'confirm' function again in the page)
      // maybe timeout is not essential ...
      setTimeout(function(){  confirmed = false; }, 100);
   };
   buttons[_x('button', 'Cancel')] = function () {
      $(this).dialog('close');
      confirmed = false;
   };

   $('<div></div>').html(message).dialog({
      title: caption,
      dialogClass: 'fixed glpi_modal',
      buttons: buttons,
      open: function(event, ui) {
         $(this).parent().prev('.ui-widget-overlay').addClass('glpi_modal');
      },
      close: function () {
          $(this).remove();
      },
      draggable: true,
      modal: true,
      resizable: false,
      width: 'auto'
   });
};

window.nativeConfirm = window.confirm;

// redefine native 'confirm' function
window.confirm = function (message, caption) {
   // if watched var isn't true, we can display dialog
   if(!confirmed) {
      // call asynchronous dialog
      newConfirm(message, caption);
   }

   // return early
   return confirmed;
};


displayAjaxMessageAfterRedirect = function() {
   // attach MESSAGE_AFTER_REDIRECT to body
   $('.message_after_redirect').remove();
   $('[id^=\"message_after_redirect_\"]').remove();
   $.ajax({
      url: CFG_GLPI.root_doc+ '/ajax/displayMessageAfterRedirect.php',
      success: function(html) {
         $('body').append(html);
      }
   });
}
