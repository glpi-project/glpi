/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

// Explicitly bind to window so Jest tests work properly
window.GLPI = window.GLPI || {};
window.GLPI.Search = window.GLPI.Search || {};

window.GLPI.Search.ResultsView = class ResultsView {

    constructor(element_id, view_class) {
        this.element_id = element_id;

        if (this.getElement()) {
            this.getAJAXContainer().data('js_class', this);
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
