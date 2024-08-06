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

/* global glpi_toast_info */

/**
 * Client code to handle users actions on the form_renderer template
 */
export class GlpiFormRendererController
{
    /**
     * Target form (jquery selector)
     * @type {string}
     */
    #target;

    /**
     * Active section index
     * @type {number}
     */
    #section_index;

    /**
     * Total number of sections
     * @type {number}
     */
    #number_of_sections;

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
            throw new Error("Target must be a valid form");
        }

        // Init section data
        this.#section_index = 0;
        this.#number_of_sections = $(this.#target)
            .find("[data-glpi-form-renderer-section]")
            .length;

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

        // Next section form action
        $(this.#target)
            .find(`[${action_attribute}=next-section]`)
            .on("click", () => this.#goToNextSection());

        // Previous section form action
        $(this.#target)
            .find(`[${action_attribute}=previous-section]`)
            .on("click", () => this.#goToPreviousSection());
    }

    /**
     * Submit the target form using an AJAX request.
     */
    async #submitForm() {
        // Form will be sumitted using an AJAX request instead
        try {
            // Submit form using AJAX
            const response = await $.post({
                url: $(this.#target).prop("action"),
                data: $(this.#target).serialize(),
            });

            // Show toast with link to answers set
            glpi_toast_info(
                __("Item successfully created: %s").replace(
                    "%s",
                    response.links_to_created_items.join(", ")
                )
            );

            // Show final confirmation step
            $(this.#target)
                .find("[data-glpi-form-renderer-success]")
                .removeClass("d-none");

            // Hide everything else
            $(this.#target)
                .find(`
                    [data-glpi-form-renderer-form-header],
                    [data-glpi-form-renderer-section=${this.#section_index}],
                    [data-glpi-form-renderer-parent-section=${this.#section_index}],
                    [data-glpi-form-renderer-actions]
                `)
                .addClass("d-none");

        } catch (e) {
            // Failure (TODO)
        }
    }

    /**
     * Go to the next section of the form.
     */
    #goToNextSection() {
        // Hide current section and its questions
        $(this.#target)
            .find(`
                [data-glpi-form-renderer-section=${this.#section_index}],
                [data-glpi-form-renderer-parent-section=${this.#section_index}]
            `)
            .addClass("d-none");

        // Show next section and its questions
        this.#section_index++;
        $(this.#target)
            .find(`
                [data-glpi-form-renderer-section=${this.#section_index}],
                [data-glpi-form-renderer-parent-section=${this.#section_index}]
            `)
            .removeClass("d-none");

        // Update actions visibility
        this.#updateActionsVisiblity();
    }

    /**
     * Go to the previous section of the form.
     */
    #goToPreviousSection() {
        // Hide current section and its questions
        $(this.#target)
            .find(`
                [data-glpi-form-renderer-section=${this.#section_index}],
                [data-glpi-form-renderer-parent-section=${this.#section_index}]
            `)
            .addClass("d-none");

        // Show preview section and its questions
        this.#section_index--;
        $(this.#target)
            .find(`
                [data-glpi-form-renderer-section=${this.#section_index}],
                [data-glpi-form-renderer-parent-section=${this.#section_index}]
            `)
            .removeClass("d-none");

        // Update actions visibility
        this.#updateActionsVisiblity();
    }

    /**
     * Update the visibility of the actions buttons depending on the active
     * section of the form.
     */
    #updateActionsVisiblity() {
        if (this.#section_index == 0) {
            // First section, show next button
            $(this.#target)
                .find("[data-glpi-form-renderer-action=submit]")
                .addClass("d-none");

            $(this.#target)
                .find("[data-glpi-form-renderer-action=next-section]")
                .removeClass("d-none");

            $(this.#target)
                .find("[data-glpi-form-renderer-action=previous-section]")
                .addClass("d-none");

        } else if (this.#section_index == (this.#number_of_sections - 1)) { // Minus 1 because section_index is 0-based
            // Last section, show submit and previous button
            $(this.#target)
                .find("[data-glpi-form-renderer-action=submit]")
                .removeClass("d-none");

            $(this.#target)
                .find("[data-glpi-form-renderer-action=next-section]")
                .addClass("d-none");

            $(this.#target)
                .find("[data-glpi-form-renderer-action=previous-section]")
                .removeClass("d-none");

        } else {
            // Any middle section, show next and previous button
            $(this.#target)
                .find("[data-glpi-form-renderer-action=submit]")
                .addClass("d-none");

            $(this.#target)
                .find("[data-glpi-form-renderer-action=next-section]")
                .removeClass("d-none");

            $(this.#target)
                .find("[data-glpi-form-renderer-action=previous-section]")
                .removeClass("d-none");
        }
    }
}
