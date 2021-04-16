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

window.GLPI.Search.GenericView = class GenericView {

   constructor(element_id) {
      this.element_id = element_id;

      if (this.getElement()) {
         this.registerListeners();
      }
   }

   postInit() {}

   getElement() {
      return $('#'+this.element_id);
   }

   getResultsView() {
      return this.getElement().closest('.ajax-container.search-display-data').data('js_class');
   }

   showLoadingSpinner() {
      const el = this.getElement();
      const container = el.parent();
      let loading_overlay = container.find('div.spinner-overlay');

      if (loading_overlay.length === 0) {
         container.append(`
            <div class="spinner-overlay text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">${__('Loading...')}</span>
                </div>
            </div>`);
         loading_overlay = container.find('div.spinner-overlay');
      } else {
         loading_overlay.css('visibility', 'visible');
      }
   }

   hideLoadingSpinner() {
      const loading_overlay = this.getElement().parent().find('div.spinner-overlay');
      loading_overlay.css('visibility', 'hidden');
   }

   registerListeners() {}

   onSearch() {
      this.refreshResults();
   }

   refreshResults() {}
};
export default window.GLPI.Search.GenericView;
