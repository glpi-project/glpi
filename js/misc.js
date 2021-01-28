/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
   caption = caption || _n('Information', 'Information', 1);

   glpi_alert({
      title: caption,
      message: message,
   });
};

window.displayAjaxMessageAfterRedirect = function() {
   // attach MESSAGE_AFTER_REDIRECT to body
   $('.message_after_redirect').remove();
   $('[id^="message_after_redirect_"]').remove();
   $.ajax({
      url: CFG_GLPI.root_doc+ '/ajax/displayMessageAfterRedirect.php',
      success: function(html) {
         $('body').append(html);
      }
   });
};
