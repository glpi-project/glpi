/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* global tinymce */

export class GlpiFormDestinationAutoConfigController
{
    constructor() {
        this.#watchForAutoConfigToggle();
    }

    #watchForAutoConfigToggle() {
        const checkboxes = $('[data-glpi-itildestination-toggle-auto-config]');

        $(checkboxes).on('change', (e) => {
            const is_auto_config_enabled = $(e.target).is(":checked");
            const container = $(e.target).closest('[data-glpi-itildestination-field]');

            this.#toggleInputs(container, is_auto_config_enabled);
            this.#toggleRichTextEditors(container, is_auto_config_enabled);
        });
    }

    #toggleInputs(container, is_auto_config_enabled) {
        const inputs = container.find('input');

        inputs.each((i, input) => {
            input = $(input);

            // Prevent disabling the checkbox itself
            const is_excluded = input.data('glpi-itildestination-toggle-do-not-disable') !== undefined;
            if (is_excluded) {
                return;
            }

            input.prop('disabled', is_auto_config_enabled);
        });
    }

    #toggleRichTextEditors(container, is_auto_config_enabled) {
        const textareas = container.find('textarea');

        textareas.each((i, textarea) => {
            textarea = $(textarea);
            const editor = tinymce.get(textarea.prop("id"));

            if (is_auto_config_enabled) {
                editor.mode.set("readonly");
                textarea.attr('disabled', 'disabled');
            } else {
                editor.mode.set("design");
                textarea.removeAttr('disabled');
            }
        });
    }
}
