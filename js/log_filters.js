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
   var bindShowFiltersBtn = function () {
      $('.show_log_filters').on('click', showFilters);
   };

   var showFilters = function (event) {
      event.preventDefault();
      reloadTab('filters[active]=1');
   };

   var bindFilterChange = function () {
      // Workaround to prevent opening of dropdown when removing item using the "x" button.
      // Without this workaround, orphan dropdowns remains in page when reloading tab.
      $('.log_history_filter_row .select2-hidden-accessible').on('select2:unselecting', function(ev) {
         if (ev.params.args.originalEvent) {
             ev.params.args.originalEvent.stopPropagation();
         }
      });

      $('[name^="filters\["]').on('input', handleFilterChange);
      $('select[name^="filters\["]').on('change', handleFilterChange);
   };

   var handleFilterChange = function (event) {
      // Prevent dropdown to remain in page after tab has been reload.
      $('.log_history_filter_row .select2-hidden-accessible').select2('close');

      reloadTab($('[name^="filters\["]').serialize());
   }

   $('.glpi_tabs').on('tabsload', function(event) {
       bindShowFiltersBtn();
       bindFilterChange();
   });
});
