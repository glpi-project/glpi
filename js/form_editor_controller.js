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

/* global _, tinymce_editor_configs, getUUID, getRealInputWidth, glpi_toast_info */

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
     * Is this form a draft?
     * @type {boolean}
     */
    #is_draft;

    /**
     * Default question type to use when creating a new question
     * @type {string}
     */
    #defaultQuestionType;

    /**
     * Templates container (jquery selector)
     * @type {string}
     */
    #templates;

    /**
     * Create a new GlpiFormEditorController instance for the given target.
     * The target must be a valid form.
     *
     * @param {string}  target
     * @param {boolean} is_draft
     * @param {string} defaultQuestionType
     * @param {string} templates
     */
    constructor(target, is_draft, defaultQuestionType, templates) {
        this.#target              = target;
        this.#is_draft            = is_draft;
        this.#defaultQuestionType = defaultQuestionType;
        this.#templates           = templates;

        // Validate target
        if ($(this.#target).prop("tagName") != "FORM") {
            throw new Error("Target must be a valid form");
        }

        // Validate default question type
        if (this.#getQuestionTemplate(this.#defaultQuestionType).length == 0) {
            throw new Error(`Invalid default question type: ${defaultQuestionType}`);
        }

        // Adjust container height and init handlers
        this.#adjustContainerHeight();
        this.#initEventHandlers();

        // Adjust dynamics inputs size
        $(this.#target)
            .find("[data-glpi-form-editor-dynamic-input]")
            .each((index, input) => {
                this.#computeDynamicInputSize(input);
            });

        // Compute base state (keep at the end)
        this.#computeState();
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

        // Handle ajax controller submit event
        $(this.#target).on(
            "glpi-ajax-controller-submit-success",
            () => this.#handleBackendUpdateResponse()
        );

        // Handle tinymce change event
        $(document)
            .on(
                'tinyMCEChange',
                (e, original_event) => this.#handleTinyMCEChange(original_event)
            );

        // Handle tinymce click event
        $(document)
            .on(
                'tinyMCEClick',
                (e, original_event) => this.#handleTinyMCEClick(original_event)
            );

        // Handle form preview successful submit
        $(document)
            .on(
                'glpi-form-renderer-submit-success',
                (e, data) => this.#handleFormPreviewSubmitSuccess(data)
            );

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

                    this.#handleEditorAction(action, target);
                });
        });
    }

    /**
     * Handle backend response
     */
    #handleBackendUpdateResponse() {
        // Item can no longer be draft after the first backend update
        if (this.#is_draft) {
            this.#removeDraftStatus();
        }
    }

    /**
     * This method should be the unique entry point for any action on the editor.
     *
     * @param {string} action Action to perform
     * @param {jQuery} target Element that triggered the action
     */
    #handleEditorAction(action, target) {
        switch (action) {
            // Show the preview of the current form in a modal
            case "show-preview":
                this.#showPreview();
                break;

            // Mark the target item as active
            case "set-active":
                this.#setActiveItem(target);
                break;

            // Add a new question at the end of the current section
            case "add-question":
                this.#addQuestion(
                    target.closest("[data-glpi-form-editor-section]")
                );
                break;

            // Delete the target question
            case "delete-question":
                this.#deleteQuestion(
                    target.closest("[data-glpi-form-editor-question]")
                );
                break;

            // Toggle mandatory class on the target question
            case "toggle-mandatory-question":
                this.#toggleMandatoryClass(
                    target.closest("[data-glpi-form-editor-question]"),
                    target.prop("checked")
                );
                break;

            // Compute the ideal width of the given input based on its content
            case "compute-dynamic-input":
                this.#computeDynamicInputSize(target[0]);
                break;

            // Change the type category of the target question
            case "change-question-type-category":
                this.#changeQuestionTypeCategory(
                    target.closest("[data-glpi-form-editor-question]"),
                    target.val()
                );
                break;

            // Change the type of the target question
            case "change-question-type":
                this.#changeQuestionType(
                    target.closest("[data-glpi-form-editor-question]"),
                    target.val()
                );
                break;

            // Add a new section at the end of the form
            case "add-section":
                this.#addSection();
                break;

            // Delete the target section
            case "delete-section":
                this.#deleteSection(
                    target.closest("[data-glpi-form-editor-section]")
                );
                break;

            // Unknown action
            default:
                throw new Error(`Unknown action: ${action}`);
        }

        // Compute input dynamic names and values (keep at the end)
        this.#computeState();
    }

    /**
     * Compute the state of the form editor (= inputs names and values).
     * Must be executed after each actions.
     */
    #computeState() {
        // Find all sections
        const sections = $(this.#target).find("[data-glpi-form-editor-section]");
        sections.each((s_index, section) => {
            // Compute state for each sections
            this.#formatInputsNames(
                $(section).find("[data-glpi-form-editor-section-form-container]"),
                s_index
            );
            this.#remplaceEmptyIdByUuid($(section));
            this.#setKey($(section));

            // Find all questions for this section
            const questions = $(section).find("[data-glpi-form-editor-question]");
            questions.each((q_index, question) => {
                // Compute state for each questions
                this.#formatInputsNames(
                    $(question),
                    s_index,
                    q_index,
                );
                this.#remplaceEmptyIdByUuid($(question));
                this.#setParentSection($(question), $(section));
                this.#setKey($(question));
            });
        });
    }

    /**
     * Must not be called directly, use #computeState() instead.
     *
     * Inputs names of questions and sections must be formatted to match the
     * expected format, which is:
     * - Sections: _sections[section_index][field]
     * - Questions: _questions[section_index][question_index][field]
     *
     * @param {jQuery} item                Section or question form container
     * @param {number} section_index       Section index
     * @param {number|null} question_index Question index
     */
    #formatInputsNames(item, section_index, question_index = null) {
        // Find all inputs for this section
        const inputs = item.find("input, select, textarea");

        // Find all section inputs and update their names to match the
        // "_section[section_index][field]" format
        inputs.each((index, input) => {
            const name = $(input).attr("name");

            // Input was never parsed before, store its original name
            if (!$(input).data("glpi-form-editor-original-name")) {
                $(input).attr("data-glpi-form-editor-original-name", name);
            }

            // Format input name
            const field = $(input).data("glpi-form-editor-original-name");
            $(input).attr(
                "name",
                this.#buildInputIndex(section_index, question_index) + `[${field}]`
            );
        });
    }

    /**
     * Must not be called directly, use #computeState() instead.
     *
     * Generate a UUID for each newly created questions and sections.
     * This UUID will be used by the backend to handle updates for news items.
     *
     * @param {jQuery} item Section or question
     */
    #remplaceEmptyIdByUuid(item) {
        const id = this.#getItemInput(item, "id");

        if (id == 0) {
            // Replace by UUID
            this.#setItemInput(item, "id", getUUID());
        }
    }

    /**
     * Must not be called directly, use #computeState() instead.
     *
     * Set the parent section of the given question.
     *
     * @param {jQuery} question Target question
     * @param {jQuery} section  Parent section
     *
     */
    #setParentSection(question, section) {
        const id = this.#getItemInput(section, "id");
        this.#setItemInput(question, "forms_sections_id", id);

        // If parent is using a UUID, we need to indicate it in the question too
        this.#setItemInput(
            question,
            "_use_uuid_for_sections_id",
            this.#getItemInput(section, "_use_uuid")
        );
    }

    /**
     * Must not be called directly, use #computeState() instead.
     * @param {jQuery} item Section or question
     */
    #setKey(item) {
        item.attr(
            "data-glpi-form-editor-key",
            this.#getItemInput(item, "id")
        );
    }

    /**
     * Handle tinymce change event
     * @param {Object} e Event data
     */
    #handleTinyMCEChange(e) {
        // Check if the change is related to a question description
        const description_container = $(e.target.container)
            .closest("[data-glpi-form-editor-question-description]");

        if (description_container.length > 0) {
            // This is a question description, mark as extra details if empty
            this.#markQuestionDescriptionAsExtraDetailsIfEmpty(
                description_container,
                e.level.content
            );
        }
    }

    /**
     * Handle tinymce click event
     * @param {Object} e Event data
     */
    #handleTinyMCEClick(e) {
        // Handle 'set-active' action for clicks inside tinymce
        this.#setActiveItem(
            $(e.target.container)
                .closest('[data-glpi-form-editor-on-click="set-active"]')
        );
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

    /**
     * Update UX to reflect the fact that the form is no longer a draft.
     */
    #removeDraftStatus() {
        // Turn the "Add" button into "Save"
        const add_button = $('#form-form button[name=update]');
        add_button
            .find('.ti-plus')
            .removeClass('ti-plus')
            .addClass('ti-device-floppy');
        add_button.find('.add-label').text(__('Save'));
        add_button.prop("title", __('Save'));

        // Show the delete button
        const del_button = $('#form-form button[name=delete]');
        del_button.removeClass('d-none');

        // Mark as no longer a draft to avoid running this code again
        this.#is_draft = false;
    }

    /**
     * Mark question description as extra details if empty.
     *
     * @param {jQuery} container
     * @param {Object} content
     */
    #markQuestionDescriptionAsExtraDetailsIfEmpty(container, content) {
        // Compute raw text length
        const div = document.createElement("div");
        div.innerHTML = content;
        const raw_text = div.textContent || div.innerText || "";
        const length = raw_text.length;

        // Mark as secondary data if empty
        if (length == 0) {
            container
                .attr("data-glpi-form-editor-question-extra-details", "");
        } else {
            container
                .removeAttr("data-glpi-form-editor-question-extra-details");
        }
    }

    /**
     * Set the current active item.
     * An active item may have additionnal fields displayed, allowing more
     * complex customization.
     *
     * There can only be a single active item at once.
     *
     * A null value may be passed if there are no active item.
     *
     * @param {jQuery|null} item_container
     */
    #setActiveItem(item_container) {
        // Remove current active item
        $(this.#target)
            .find("\
                [data-glpi-form-editor-form-details], \
                [data-glpi-form-editor-question], \
                [data-glpi-form-editor-section-form-container] \
            ")
            .removeClass("active");

        // Set new active item if specified
        if (item_container !== null) {
            item_container.addClass("active");
        }
    }

    /**
     * Add a new question at the end of the form
     * @param {jQuery} section
     */
    #addQuestion(section) {
        // Get template content
        const template_content = this.#getQuestionTemplate(
            this.#defaultQuestionType
        ).children();

        // Insert the new template into the questions area of the current section
        const new_question = this.#copy_template(
            template_content,
            section.find("[data-glpi-form-editor-section-questions]"),
        );

        // Update UX
        this.#setActiveItem(new_question);
        this.#updateAddSectionActionVisiblity();
    }

    /**
     * Delete the given question.
     * @param {jQuery} question
     */
    #deleteQuestion(question) {
        // Remove question and update UX
        question.remove();
        this.#updateAddSectionActionVisiblity();
    }

    /**
     * Toggle the mandatory class for the given question.
     * @param {jQuery} question
     * @param {boolean} is_mandatory
     */
    #toggleMandatoryClass(question, is_mandatory) {
        if (is_mandatory) {
            question.addClass("mandatory-question");
        } else {
            question.removeClass("mandatory-question");
        }
    }

    /**
     * Get the template for the given question type.
     * @param {string} question_type
     * @returns {jQuery}
     */
    #getQuestionTemplate(question_type) {
        const type = $.escapeSelector(question_type);

        return $(this.#templates)
            .find(`[data-glpi-form-editor-question-template=${type}]`);
    }

    /**
     * Copy the given template into the given destination.
     *
     * @param {jQuery} target         Template to copy
     * @param {jQuery} destination    Destination to copy the template into
     * @returns {jQuery} Copy of the template
     */
    #copy_template(target, destination) {
        const copy = target.clone();

        // Keep track of rich text editors that will need to be initialized
        const tiny_mce_to_init = [];

        // Look for tiynmce editor to init
        copy.find("textarea").each(function() {
            // Get editor config for this field
            let id = $(this).attr("id");
            const config = window.tinymce_editor_configs[id];

            // Rename id to ensure it is unique
            const uid = getUUID();
            $(this).attr("id", `_tinymce_${uid}`);
            id = $(this).attr("id"); // Reload ID

            // Push config into init queue, needed because we can't init
            // the rich text editor until the template is inserted into
            // its final DOM destination
            config.selector = "#" + id;
            tiny_mce_to_init.push(config);

            // Store config with udpated ID in case we need to re render
            // this question
            window.tinymce_editor_configs[id] = config;
        });

        // Insert the new question and init the editors
        copy.appendTo(destination);
        tiny_mce_to_init.forEach((config) => tinyMCE.init(config));

        return copy;
    }

    /**
     * Build the input name prefix for the given section/question.
     * @param {number} section_index
     * @param {number|null} question_index
     * @returns {string}
     */
    #buildInputIndex(section_index, question_index = null) {
        if (question_index === null) {
            // The input is for the section itself
            return `_sections[${section_index}]`;
        } else {
            // The input is for a question
            return `_questions[${section_index}][${question_index}]`;
        }
    }

    /**
     * Get input value for the given question.
     * @param {jQuery} item Question or section
     * @param {string} field
     * @returns {string|number}
     */
    #getItemInput(item, field) {
        return item
            .find(`input[data-glpi-form-editor-original-name=${field}]`)
            .val();
    }

    /**
     * Set input value for the given question.
     * @param {jQuery} item Question or section
     * @param {string} field
     * @param {string|number} value
     * @returns {jQuery}
     */
    #setItemInput(item, field, value) {
        return item
            .find(`input[data-glpi-form-editor-original-name=${field}]`)
            .val(value);
    }

    /**
     * Compute the ideal width of the given input based on its content.
     * @param {HTMLElement} input
     */
    #computeDynamicInputSize(input) {
        $(input).css("width", getRealInputWidth(input, "1.2rem"));
    }

    /**
     * Handle form preview successful submit.
     * @param {Object} data Response data
     */
    #handleFormPreviewSubmitSuccess(data) {
        // Close modal
        $("#glpi_form_editor_preview_modal").modal('hide');

        // Show toast with link to answers set
        glpi_toast_info(
            __("Item successfully created: %s").replace(
                "%s",
                data.link_to_created_item
            )
        );
    }

    /**
     * Change the type category of the given question.
     * @param {jQuery} question  Question to update
     * @param {string} category  New category
     */
    #changeQuestionTypeCategory(question, category) {
        // Find types available in the new category
        const e_category = $.escapeSelector(category);
        const new_options = $(this.#templates)
            .find(`option[data-glpi-form-editor-question-type=${e_category}]`);

        // Remove current types options
        const types_select = question
            .find("[data-glpi-form-editor-question-type-selector]");
        types_select.children().remove();

        // Copy the new types options into the dropdown
        this.#copy_template(
            new_options,
            types_select,
        );

        // Hide type selector if only one type is available
        if (new_options.length <= 1) {
            types_select.addClass("d-none");
        } else {
            types_select.removeClass("d-none");
        }

        // Trigger type change
        types_select.trigger("change");
    }

    /**
     * Change the type of the given question.
     * @param {jQuery} question Question to update
     * @param {string} type     New type
     */
    #changeQuestionType(question, type) {
        // Clear the specific form of the question
        const specific = question
            .find("[data-glpi-form-editor-question-type-specific]");
        specific.children().remove();

        // Find the specific content of the given type
        const new_specific_content = this
            .#getQuestionTemplate(type)
            .find("[data-glpi-form-editor-question-type-specific]")
            .children();

        // Copy the specific form of the new question type into the question
        this.#copy_template(
            new_specific_content,
            specific,
        );
    }

    /**
     * Add a new section at the end of the form.
     */
    #addSection() {
        // Find the section template
        const template = $(this.#templates)
            .find("[data-glpi-form-editor-section-template]")
            .children();

        // Copy the new section template into the sections area
        const section = this.#copy_template(
            template,
            $(this.#target).find("[data-glpi-form-editor-sections]"),
        );

        // Update UX
        this.#updateSectionCountLabels();
        this.#updateSectionsDetailsVisiblity();
        this.#setActiveItem(
            section.find("[data-glpi-form-editor-section-form-container]")
        );
    }

    /**
     * Delete the given section.
     * @param {jQuery} section
     */
    #deleteSection(section) {
        // Remove question and update UX
        section.remove();
        this.#updateSectionCountLabels();
        this.#updateSectionsDetailsVisiblity();
    }

    /**
     * Update the visibility of the "add section" action.
     * The action is hidden if there are no questions in the form.
     */
    #updateAddSectionActionVisiblity() {
        const questions_count = $(this.#target)
            .find("[data-glpi-form-editor-question]")
            .length;

        // Hide the "add section" action unless there is at least one question
        if (questions_count == 0) {
            $("[data-glpi-form-editor-on-click='add-section']")
                .addClass("d-none");
        } else {
            $("[data-glpi-form-editor-on-click='add-section']")
                .removeClass("d-none");
        }
    }

    /**
     * Update the visibility of the sections details.
     * The details are hidden if there is only one section.
     */
    #updateSectionsDetailsVisiblity() {
        const sections_count = $(this.#target)
            .find("[data-glpi-form-editor-section]")
            .length;

        if (sections_count <= 1) {
            // Only one section, do not display its details
            $(this.#target)
                .find("[data-glpi-form-editor-section-form-container]")
                .addClass("d-none");
            $(this.#target)
                .find("[data-glpi-form-editor-section-number-display]")
                .addClass("d-none");
        } else {
            // Mutliple sections, display all details
            $(this.#target)
                .find("[data-glpi-form-editor-section-form-container]")
                .removeClass("d-none");
            $(this.#target)
                .find("[data-glpi-form-editor-section-number-display]")
                .removeClass("d-none");
        }
    }

    /**
     * Update section index and total number in the special section header
     * "Section X of Y".
     */
    #updateSectionCountLabels() {
        const sections = $(this.#target).find("[data-glpi-form-editor-section]");
        sections.each((s_index, section) => {
            const display = $(section)
                .find("[data-glpi-form-editor-section-number-display]");

            display.html(
                __("Section 1%d of 2%d")
                    .replace("1%d", s_index + 1)
                    .replace("2%d", sections.length)
            );
        });
    }
}
