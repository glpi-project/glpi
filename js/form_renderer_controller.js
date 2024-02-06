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
 * Client code to handle users actions on the form_renderer template
 */
class GlpiFormRendererController
{
    /**
     * Target form (jquery selector)
     * @type {string}
     */
    #target;

    /**
     * Create a new GlpiFormRendererController instance for the given target.
     * The target must be a valid form.
     *
     * @param {string} target
     */
    constructor(target) {
        // Target must be a valid form
        this.#target = target;
        if ($(this.#target).prop("tagName") != "FORM") {
            console.error("Target must be a valid form");
        }

        // Init event handlers
        this.#initEventHandlers();
    }

    /**
     * Init event handlers for each possible actions, identified by the data
     * attribute "data-glpi-form-renderer-action".
     */
    #initEventHandlers() {
        const action_attribute = "data-glpi-form-renderer-action";

        // Submit form action
        $(this.#target)
            .find(`[${action_attribute}=submit]`)
            .on("click", () => this.#submitForm());
    }

    /**
     * Submit the target form using an AJAX request.
     *
     * The event "glpi-form-renderer-submit-success" is triggered on success,
     * with the response as argument.
     *
     * The event "glpi-form-renderer-submit-failed" is triggered on failure,
     * with the response as argument.
     */
    async #submitForm() {
        // Form will be sumitted using an AJAX request instead
        try {
            // Submit form using AJAX
            const response = await $.post({
                url: $(this.#target).prop("action"),
                data: $(this.#target).serialize(),
            });

            // Success event
            $(document).trigger('glpi-form-renderer-submit-success', response);
        } catch (e) {
            // Failure event
            $(document).trigger('glpi-form-renderer-submit-failed', response);
        }
    }
}
