/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
$(function() {
   // set a function to track drag hover event
   $(document).on("click", ".copy_to_clipboard_wrapper", function(event) {

      // find the good element
      var target = $(event.target);
      if (target.attr('class') == 'copy_to_clipboard_wrapper') {
         target = target.find('*');
      }

      // copy text
      target.select();
      var succeed;
      try {
         succeed = document.execCommand("copy");
      } catch (e) {
         succeed = false;
      }
      target.blur();

      // indicate success
      if (succeed) {
         $('.copy_to_clipboard_wrapper.copied').removeClass('copied');
         target.parent('.copy_to_clipboard_wrapper').addClass('copied');
      } else {
         target.parent('.copy_to_clipboard_wrapper').addClass('copyfail');
      }
   });
});
