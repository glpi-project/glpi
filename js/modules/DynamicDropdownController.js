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

/**
 * Helper class to easily manage sub dropdowns (i.e. you have a main dropdown
 * and a few secondary dropdown that will only be displayed depending on the
 * values of the main dropdown).
 *
 * The children dropdown must define two data attributes:
 * - data-glpi-parent-dropdown: the name of the parent dropdown
 * - data-glpi-parent-dropdown-condition: the value that the parent dropdown
 *  must have for this dropdown to be displayed
 */
export class DynamicDropdownController
{
    constructor() {
        this.#watchForDropdownChanges();
    }

    #watchForDropdownChanges() {
        const dropdowns = $('select[data-select2-id]');

        $(dropdowns).on('change', (e) => {
            this.#updateChildrenDropdownsVisiblity($(e.target));
        });
    }

    #updateChildrenDropdownsVisiblity(select) {
        const name = $.escapeSelector(select.prop("name"));
        const child_dropdowns = $(`[data-glpi-parent-dropdown='${name}']`);
        const value = select.val();

        child_dropdowns.each((i, dropdown) => {
            const expected_value = $(dropdown).data(
                'glpi-parent-dropdown-condition'
            );
            $(dropdown).toggleClass('d-none', expected_value !== value);
        });
    }
}
