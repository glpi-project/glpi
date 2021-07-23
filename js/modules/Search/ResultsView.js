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

// Explicitly bind to window so Jest tests work properly
window.GLPI = window.GLPI || {};
window.GLPI.Search = window.GLPI.Search || {};

window.GLPI.Search.ResultsView = class ResultsView {

   constructor(element_id, view_class) {
      this.element_id = element_id;

      if (this.getElement()) {
         this.getElement().closest('div.ajax-container.search-display-data').data('js_class', this);
         this.view = new view_class(this.element_id);
      }
   }

   getElement() {
      return $('#'+this.element_id);
   }

   getAJAXContainer() {
      return this.getElement().closest('div.ajax-container.search-display-data');
   }

   getView() {
      return this.view;
   }
};
export default window.GLPI.Search.ResultsView;
