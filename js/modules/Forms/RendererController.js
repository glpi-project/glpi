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

/* global glpi_toast_info, tinymce, glpi_toast_error, _ */

import { GlpiFormConditionEngine } from 'Forms/Condition/Engine';

/**
 * Client code to handle users actions on the form_renderer template
 */
export class GlpiFormRendererController
{
    /**
     * Target form
     * @type {HTMLFormElement}
     */
    #target;

    /**
     * Active section index
     * @type {number}
     */
    #section_index;

    /**
     * @type {GlpiFormConditionEngine}
     */
    #condition_engine;

    /**
     * Create a new GlpiFormRendererController instance for the given target.
     * The target must be a valid form.
     *
     * @param {string} target
     * @param {number} form_id
     */
    constructor(target, form_id) {
        // Target must be a valid form
        this.#target = document.querySelector(target);
        if ($(this.#target).prop("tagName") != "FORM") {
            throw new Error("Target must be a valid form");
        }

        // Init section data
        this.#section_index = 0;

        // Init event handlers
        this.#initEventHandlers();

        // Make "Send form" button clickable
        $(this.#target)
            .find("[data-glpi-form-renderer-action=submit]")
            .removeAttr("disabled");

        // Load condition engine
        this.#condition_engine = new GlpiFormConditionEngine(form_id);
        this.#enableActions();
        this.#updateActionsVisiblity();
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

        // Watch for any change on answers
        const debouncedComputeItemsVisibilities = _.debounce(
            () => this.#computeItemsVisibilities(),
            400,
        );
        $(document).on('input tinyMCEInput', this.#target, () => {
            // Disable actions immediately to avoid someone clicking on the actions
            // while the conditions have not been computed yet.
            this.#disableActions();

            debouncedComputeItemsVisibilities();
        });

        // Handle delegation form update
        $(this.#target).on(
            'change',
            '[data-glpi-form-renderer-delegation-container] select[name="delegation_users_id"]',
            (e) => this.#renderDelegation(e)
        );

        // Enable actions
        $(this.#target).removeClass('pointer-events-none');
    }

    #checkCurrentSectionValidity() {
        // Find all required inputs that are hidden and not already disabled.
        // They must be removed from the check as they are inputs from others
        // sections or input hidden by condition (thus they should not be
        // evaluated).
        // The easiest way to not evaluate these inputs is to disable them.
        const inputs = $(this.#target).find('[required]:hidden:not(:disabled)');
        for (const input of inputs) {
            input.disabled = true;
        }

        // Check validity and display browser feedback if needed.
        const is_valid = this.#target.checkValidity();
        if (!is_valid) {
            this.#target.reportValidity();
        }

        // Revert disabled inputs
        for (const input of inputs) {
            input.disabled = false;
        }

        return is_valid;
    }

    /**
     * Submit the target form using an AJAX request.
     */
    async #submitForm() {
        if (!this.#checkCurrentSectionValidity()) {
            return;
        }

        // Form will be sumitted using an AJAX request instead
        try {
            // Update tinymce values
            if (window.tinymce !== undefined) {
                tinymce.get().forEach(editor => {
                    editor.save();
                });
            }

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
                    [data-glpi-form-renderer-delegation-container],
                    [data-glpi-form-renderer-section=${this.#section_index}],
                    [data-glpi-form-renderer-parent-section=${this.#section_index}],
                    [data-glpi-form-renderer-actions]
                `)
                .addClass("d-none");

        } catch (e) {
            console.error(e);
            glpi_toast_error(
                __("Failed to submit form, please contact your administrator.")
            );
        }
    }

    /**
     * Go to the next section of the form.
     */
    #goToNextSection() {
        if (!this.#checkCurrentSectionValidity()) {
            return;
        }

        // Hide current section and its questions
        $(this.#target)
            .find(`
                [data-glpi-form-renderer-section=${this.#section_index}],
                [data-glpi-form-renderer-parent-section=${this.#section_index}]
            `)
            .addClass("d-none");

        // Show next visible section and its questions
        const next_section_index = this.#getNextVisibleSectionIndex();
        if (next_section_index === null) {
            throw new Error('Impossible to load the next section');
        }

        this.#section_index = next_section_index;
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

        // Show previous visible section and its questions
        const previous_section_index = this.#getPreviousVisibleSectionIndex();
        if (previous_section_index === null) {
            throw new Error('Impossible to load the previous section');
        }

        this.#section_index = previous_section_index;
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
        if (this.#hasOneVisibleSectionAfterCurrentIndex()) {
            // Show "next" button if at least one other following section is visible
            $(this.#target)
                .find("[data-glpi-form-renderer-action=submit]")
                .addClass("d-none");
            $(this.#target)
                .find("[data-glpi-form-renderer-action=next-section]")
                .removeClass("d-none");
        } else {
            // Show "submit" button instead
            $(this.#target)
                .find("[data-glpi-form-renderer-action=submit]")
                .removeClass("d-none");
            $(this.#target)
                .find("[data-glpi-form-renderer-action=next-section]")
                .addClass("d-none");
        }

        if (this.#hasOneVisibleSectionBeforeCurrentIndex()) {
            // Show "back" button if at least one previous section is visible
            $(this.#target)
                .find("[data-glpi-form-renderer-action=previous-section]")
                .removeClass("d-none");
        } else {
            $(this.#target)
                .find("[data-glpi-form-renderer-action=previous-section]")
                .addClass("d-none");
        }
    }

    #updateStepLabels() {
        const number_of_visible_sections = this.#getNumberOfVisibleSections();

        $(this.#target).find('[data-glpi-form-renderer-section]').each((_i, section) => {
            const $section = $(section);

            // If section if hidden, there is not label to display
            if (section.dataset.glpiFormRendererHiddenByCondition !== undefined) {
                $section
                    .find('[data-glpi-form-renderer-step-label]')
                    .html('')
                ;
                return;
            }

            const number_of_sections_after = $section.nextAll(
                '[data-glpi-form-renderer-section]'
            ).length;
            const number_of_hidden_sections_after = $section.nextAll(
                '[data-glpi-form-renderer-section][data-glpi-form-renderer-hidden-by-condition]'
            ).length;
            const number_of_visible_sections_after = number_of_sections_after - number_of_hidden_sections_after;

            $section
                .find('[data-glpi-form-renderer-step-label]')
                .html(
                    __("Step %1$d of %2$d")
                        .replace("%1$d", number_of_visible_sections - number_of_visible_sections_after)
                        .replace("%2$d", number_of_visible_sections)
                )
            ;
        });
    }

    #getNumberOfVisibleSections() {
        const total_sections = $(this.#target).find('[data-glpi-form-renderer-section]');
        const hidden_sections = $(this.#target).find(
            '[data-glpi-form-renderer-section][data-glpi-form-renderer-hidden-by-condition]'
        );
        return total_sections.length - hidden_sections.length;
    }

    async #computeItemsVisibilities() {
        const results = await this.#condition_engine.computeVisiblity(this.#target);
        this.#applyVisibilityResults(results);
        this.#enableActions();
    }

    #applyVisibilityResults(results)
    {
        const container = this.#target;

        // Apply submit button visibility
        const submit_button = container.querySelector(
            '[data-glpi-form-renderer-action=submit]'
        );
        if (submit_button !== null) {
            this.#applyVisibilityToItem(submit_button, results.form_visibility);
        }

        // Apply sections visibility
        for (const [id, must_be_visible] of Object.entries(
            results.sections_visibility
        )) {
            const section = container.querySelector(
                `[data-glpi-form-renderer-section][data-glpi-form-renderer-id="${id}"]`
            );
            if (section === null) {
                continue;
            }

            // Can't change the visibility of the current section
            if ($(section).data('glpi-form-renderer-section') == this.#section_index) {
                continue;
            }

            this.#applyVisibilityToItem(section, must_be_visible);
        };

        // Apply questions visibility
        for (const [id, must_be_visible] of Object.entries(
            results.questions_visibility
        )) {
            const question = container.querySelector(
                `[data-glpi-form-renderer-question][data-glpi-form-renderer-id="${id}"]`
            );
            if (question === null) {
                continue;
            }
            this.#applyVisibilityToItem(question, must_be_visible);
        };

        // Apply comments visibility
        for (const [id, must_be_visible] of Object.entries(
            results.comments_visibility
        )) {
            const comment = container.querySelector(
                `[data-glpi-form-renderer-comment][data-glpi-form-renderer-id="${id}"]`
            );
            if (comment === null) {
                continue;
            }
            this.#applyVisibilityToItem(comment, must_be_visible);
        };

        this.#updateActionsVisiblity();
        this.#updateStepLabels();
    }

    #applyVisibilityToItem(item, must_be_visible)
    {
        if (must_be_visible) {
            item.removeAttribute("data-glpi-form-renderer-hidden-by-condition");
        } else {
            item.setAttribute("data-glpi-form-renderer-hidden-by-condition", "");
        }
    }

    #getNextVisibleSectionIndex()
    {
        let index = null;

        const sections = $(this.#target).find('[data-glpi-form-renderer-section]');
        sections.each((_i, section) => {
            // Ignore previous and current section
            if (section.dataset.glpiFormRendererSection <= this.#section_index) {
                return;
            }

            // A visible section won't have the following data property
            if (section.dataset.glpiFormRendererHiddenByCondition === undefined) {
                index = section.dataset.glpiFormRendererSection;
                return false; // Break
            }
        });

        return index;
    }

    #getPreviousVisibleSectionIndex()
    {
        let index = null;

        const sections = $(this.#target).find('[data-glpi-form-renderer-section]');
        sections.each((_i, section) => {
            // Ignore next and current section
            if (section.dataset.glpiFormRendererSection >= this.#section_index) {
                return false; // Break
            }

            // A visible section won't have the following data property
            if (section.dataset.glpiFormRendererHiddenByCondition === undefined) {
                index = section.dataset.glpiFormRendererSection;
            }
        });

        return index;
    }

    #hasOneVisibleSectionAfterCurrentIndex()
    {
        return this.#getNextVisibleSectionIndex() !== null;
    }

    #hasOneVisibleSectionBeforeCurrentIndex()
    {
        return this.#getPreviousVisibleSectionIndex() !== null;
    }

    #disableActions()
    {
        // Do not use "disable" prop to avoid the button "flashing" back and
        // forth.
        $(this.#target)
            .find("button[data-glpi-form-renderer-action]")
            .addClass("pointer-events-none")
        ;
    }

    #enableActions()
    {
        $(this.#target)
            .find("button[data-glpi-form-renderer-action]")
            .removeClass("pointer-events-none")
        ;
    }

    async #renderDelegation()
    {
        const selected_user_id = $(this.#target)
            .find('[data-glpi-form-renderer-delegation-container]')
            .find('select[name="delegation_users_id"]')
            .val();

        const response = await $.get('/Form/Delegation', {
            'selected_user_id': selected_user_id,
        });

        // Replace only the inner content of the delegation container
        $(this.#target)
            .find('[data-glpi-form-renderer-delegation-container]')
            .html(response);
    }
}
