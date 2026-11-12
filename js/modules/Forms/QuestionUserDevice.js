/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

export class GlpiFormQuestionTypeUserDevice {
    /**
     * Create a new GlpiFormQuestionTypeUserDevice instance.
     */
    constructor() {
        document.addEventListener('change', (e) => {
            const input = e.target.closest('[data-glpi-form-question-user-device-multiple-toggle]');
            if (input === null) {
                return;
            }

            const selects = $(input).closest('section[data-glpi-form-editor-question]')
                .find('div .devices-dropdown');

            // Toggle all selects visibility
            selects.toggleClass('d-none');

            // Handle hidden input for multiple devices
            selects.find('input[type="hidden"]').prop('disabled', !input.checked);
        });
    }
}
