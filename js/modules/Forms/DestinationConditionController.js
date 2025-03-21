/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
 * Controller to handle destination visibility badges update
 */
export class GlpiFormDestinationConditionController {
    /**
     * Initialize the controller and event listeners
     */
    constructor() {
        this.#initEventHandlers();
    }

    /**
     * Initialize event listeners for strategy changes
     */
    #initEventHandlers() {
        // Listen for strategy change events
        document.addEventListener('updated_strategy', (e) => {
            if (e.detail && e.detail.container) {
                // Get the destination container
                const container = $(e.detail.container).closest('.accordion-item');
                if (container.length > 0) {
                    this.#updateConditionBadge(container, e.detail.strategy);
                }
            }
        });
    }

    /**
     * Update the creation condition badge
     *
     * @param {jQuery} container The destination container
     * @param {string} value The selected strategy value
     */
    #updateConditionBadge(container, value) {
        // Show/hide badges in the container
        container.find('[data-glpi-editor-condition-badge]')
            .removeClass('d-flex')
            .addClass('d-none')
        ;
        container.find(`[data-glpi-editor-condition-badge=${value}]`)
            .removeClass('d-none')
            .addClass('d-flex')
        ;
    }
}
