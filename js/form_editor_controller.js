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

/* global _ */

/**
 * Client code to handle users actions on the form_editor template
 */
class GlpiFormEditorController
{
    /**
     * Target form editor (jquery selector)
     * @type {string}
     */
    #target;

    /**
     * Create a new GlpiFormEditorController instance for the given target.
     * The target must be a valid form.
     *
     * @param {string} target
     * @param {string} defaultQuestionType
     * @param {string} templates
     */
    constructor(target) {
        this.#target = target;

        // Validate target
        if ($(this.#target).prop("tagName") != "FORM") {
            console.error("Target must be a valid form");
        }

        // Adjust container height and init handlers
        this.#adjustContainerHeight();
        this.#initEventHandlers();
    }

    /**
     * Init event handlers for each possible editors actions (identified by the
     *  "data-glpi-form-editor-on-xxx" data attributes) and external events.
     */
    #initEventHandlers() {
        // Register throttled version of the adjustContainerHeight() function
        const adjust_container_height_throttled = _.throttle(
            () => this.#adjustContainerHeight(),
            100
        );

        // Compute correct height when the window is resized
        $(window).on('resize', () => adjust_container_height_throttled());

        // Register handlers for each possible editor actions using custom
        // data attributes
        const events = ["click", "change", "input"];
        events.forEach((event) => {
            const attribute = `data-glpi-form-editor-on-${event}`;
            $(document)
                .on(event, `${this.#target} [${attribute}]`, (e) => {
                    // Get action and a jQuery wrapper for the target
                    const target = $(e.currentTarget);
                    const action = target.attr(attribute);

                    this.#handleEditorAction(action);
                });
        });
    }

    /**
     * This method should be the unique entry point for any action on the editor.
     *
     * @param {string} action Action to perform
     */
    #handleEditorAction(action) {
        switch (action) {
            // Show the preview of the current form in a modal
            case "show-preview":
                this.#showPreview();
                break;

            // Unknown action
            default:
                console.error(`Unknown action: ${action}`);
        }
    }

    /**
     * Adjust height using javascript
     * This is the only reliable way to make our content use the remaining
     * height of the page as the parent container doesn't define a height
     */
    #adjustContainerHeight() {
        // Get window and editor height
        const window_height = document.body.offsetHeight ;
        const editor_height = $(this.#target).offset().top;

        // Border added at the bottom of the page, must be taken into account
        const tab_content_border = 1;

        // Compute and apply ideal height
        const height = (window_height - editor_height - tab_content_border);
        $(this.#target).css('height', `${height}`);
    }

    /**
     * Show the preview of the current form in a modal
     */
    #showPreview() {
        const id = $(this.#target).find("input[name=id]").val();
        $("#glpi_form_editor_preview_modal .modal-content").load(
            CFG_GLPI.root_doc + "/ajax/form/form_renderer.php?id=" + id,
        );
    }
}
