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

/* global _, tinymce_editor_configs, getUUID, getRealInputWidth, sortable, tinymce */

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

        // Enable sortable on questions
        this.#enableSortable(
            $(this.#target).find("[data-glpi-form-editor-sections]")
        );

        // Focus the form's name input if there are no questions
        if (this.#getQuestionsCount() === 0) {
            $(this.#target)
                .find("[data-glpi-form-editor-form-details-name]")[0]
                .select();
        }
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

        // Compute state before submitting the form
        $(this.#target).on('submit', () => {
            try {
                this.#computeState();
            } catch (e) {
                // Do not submit the form if the state isn't computed
                e.preventDefault();
                e.preventPropagation();
            }
        });

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

                    this.#handleEditorAction(action, target, e);
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
     * @param {Event}  event  Event
     */
    #handleEditorAction(action, target, event) {
        /**
         * Some unsaved changes are not tracked by the native `data-track-changes`
         * attribute.
         *
         * By default, any editor actions will be considered as unsaved changes.
         *
         * Actions that do not represent an actual data change must manually
         * set this variable to `false`.
         * This make sure we don't forget to track changes when needed.
         */
        let unsaved_changes = true;

        switch (action) {
            // Mark the target item as active
            case "set-active":
                this.#setActiveItem(target);
                unsaved_changes = false;
                break;

            // Add a new question
            case "add-question":
                event.stopPropagation(); // We don't want to trigger the "set-active" action for this item
                this.#addQuestion(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-question]
                    `),
                );
                break;

            // Delete the target question
            case "delete-question":
                event.stopPropagation(); // We don't want to trigger the "set-active" action for this item
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
                event.stopPropagation(); // We don't want to trigger the "set-active" action for this item
                this.#addSection(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-question]
                    `),
                );
                break;

            // Delete the target section
            case "delete-section":
                event.stopPropagation(); // We don't want to trigger the "set-active" action for this item
                this.#deleteSection(
                    target.closest("[data-glpi-form-editor-section]")
                );
                break;

            // Build the "move section modal" content
            case "build-move-section-modal-content":
                this.#buildMoveSectionModalContent();
                unsaved_changes = false;
                break;

            // Reorder the sections based on the "move section modal" content
            case "reorder-sections":
                this.#reorderSections();
                break;

            // Merge current section with the previous section
            case "merge-with-previous-section":
                this.#mergeWithPreviousSection(
                    target.closest("[data-glpi-form-editor-section]")
                );
                break;

            // Collapse/uncollapse target section
            case "collapse-section":
                this.#collaspeSection(
                    target.closest("[data-glpi-form-editor-section]")
                );
                break;

            // Unknown action
            default:
                throw new Error(`Unknown action: ${action}`);
        }

        if (unsaved_changes) {
            window.glpiUnsavedFormChanges = true;
        }
    }

    /**
     * Compute the state of the form editor (= inputs names and values).
     * Must be executed after each actions.
     */
    #computeState() {
        let global_q_index = 0;

        // Find all sections
        const sections = $(this.#target).find("[data-glpi-form-editor-section]");
        sections.each((s_index, section) => {
            // Compute state for each sections
            this.#formatInputsNames(
                $(section).find("[data-glpi-form-editor-section-details]"),
                'section',
                s_index
            );
            this.#setItemRank($(section), s_index);
            this.#remplaceEmptyIdByUuid($(section));
            this.#setKey($(section));

            // Find all questions for this section
            const questions = $(section).find("[data-glpi-form-editor-question]");
            questions.each((q_index, question) => {
                // Compute state for each questions
                this.#formatInputsNames(
                    $(question),
                    'question',
                    global_q_index,
                );
                this.#setItemRank($(question), q_index);
                this.#remplaceEmptyIdByUuid($(question));
                this.#setParentSection($(question), $(section));
                this.#setKey($(question));

                global_q_index++;
            });
        });
    }

    /**
     * Must not be called directly, use #computeState() instead.
     *
     * Inputs names of questions and sections must be formatted to match the
     * expected format, which is:
     * - Sections: _sections[section_index][field]
     * - Questions: _questions[question_index][field]
     *
     * @param {jQuery} item       Section or question form container
     * @param {string} type       Item type: "question" or "section"
     * @param {number} item_index Item index
     */
    #formatInputsNames(item, type, item_index) {
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
            let base_input_index = "";
            if (type === "section") {
                // The input is for the section itself
                base_input_index = `_sections[${item_index}]`;
            } else if (type === "question") {
                // The input is for a question
                base_input_index =  `_questions[${item_index}]`;
            } else {
                throw new Error(`Unknown item type: ${type}`);
            }

            // Update input name
            $(input).attr(
                "name",
                base_input_index + `[${field}]`
            );
        });
    }

    /**
     * Must not be called directly, use #computeState() instead.
     *
     * Set the rank of the given item
     *
     * @param {item} item   Section or question
     * @param {number} rank Rank of the item
     */
    #setItemRank(item, rank) {
        this.#setItemInput(item, "rank", rank);
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
        const possible_active_items = ['form', 'section', 'question'];

        // Remove current active item
        possible_active_items.forEach((type) => {
            $(this.#target)
                .find(`[data-glpi-form-editor-active-${type}]`)
                .removeAttr(`data-glpi-form-editor-active-${type}`);
        });


        // Set new active item if specified
        if (item_container !== null) {
            possible_active_items.forEach((type) => {
                // Can be set active from the container itself or the sub "details" container
                if (item_container.data(`glpi-form-editor-${type}-details`) !== undefined) {
                    item_container
                        .closest(`[data-glpi-form-editor-${type}]`)
                        .attr(`data-glpi-form-editor-active-${type}`, "");
                } else if (item_container.data(`glpi-form-editor-${type}`) !== undefined) {
                    item_container
                        .attr(`data-glpi-form-editor-active-${type}`, "");
                }
            });

            // An item can't be active if its parent section is collapsed
            const section = item_container.closest("[data-glpi-form-editor-section]");
            if (section.hasClass("section-collapsed")) {
                return;
            }

            item_container.addClass("active");
        }
    }

    /**
     * Add a new question at the end of the form
     * @param {jQuery} target   Current position in the form
     */
    #addQuestion(target) {
        let destination;
        let action;

        // Find the context using the target
        if (target.data('glpi-form-editor-question') !== undefined) {
            // Adding a new question after an existing question
            destination = target;
            action = "after";
        } else if (target.data('glpi-form-editor-section') !== undefined) {
            // Adding a question at the start of a section
            destination = target
                .closest("[data-glpi-form-editor-section]")
                .find("[data-glpi-form-editor-section-questions]");
            action = "prepend";
        } else if (target.data('glpi-form-editor-form') !== undefined) {
            // Add a question at the end of the form
            destination = $(this.#target)
                .find("[data-glpi-form-editor-section]:last-child")
                .find("[data-glpi-form-editor-section-questions]:last-child");
            action = "append";
        } else {
            throw new Error('Unexpected target');
        }

        // Get template content
        const template_content = this.#getQuestionTemplate(
            this.#defaultQuestionType
        ).children();

        // Insert the new template into the questions area of the current section
        const new_question = this.#copy_template(
            template_content,
            destination,
            action
        );

        // Update UX
        this.#setActiveItem(new_question);
        this.#updateAddSectionActionVisiblity();

        // Focus question's name
        new_question
            .find("[data-glpi-form-editor-question-details-name]")[0]
            .focus();
    }

    /**
     * Delete the given question.
     * @param {jQuery} question
     */
    #deleteQuestion(question) {
        if (
            $(this.#target).find("[data-glpi-form-editor-question]").length == 1
            && $(this.#getSectionCount()) == 1
        ) {
            // If the last questions is going to be deleted and there is only one section
            // set the form itself as active to show its toolbar
            this.#setActiveItem(
                $(this.#target).find("[data-glpi-form-editor-form-details]")
            );
        } else {
            // Set active the previous question/section
            if (question.prev().length > 0) {
                this.#setActiveItem(question.prev());
            } else {
                this.#setActiveItem(question.closest("[data-glpi-form-editor-section]"));
            }
        }

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
     * @param {string} action         How to insert the template (append, prepend, after)
     * @returns {jQuery} Copy of the template
     */
    #copy_template(target, destination, action = "append") {
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

        // Insert the new question
        switch (action) {
            case "append":
                copy.appendTo(destination);
                break;
            case "prepend":
                copy.prependTo(destination);
                break;
            case "before":
                copy.insertBefore(destination);
                break;
            case "after":
                copy.insertAfter(destination);
                break;
            default:
                throw new Error(`Unknown action: ${action}`);
        }

        // Init the editors
        tiny_mce_to_init.forEach((config) => tinyMCE.init(config));

        return copy;
    }

    /**
     * Get input value for the given question.
     * @param {jQuery} item Question or section
     * @param {string} field
     * @returns {string|number}
     */
    #getItemInput(item, field) {
        // Reduce scope when working with a section as we don't want to target
        // its sub-questions inputs
        if (item.data("glpi-form-editor-section") !== undefined) {
            item = item.find("[data-glpi-form-editor-section-details]");
        }

        // Input name before state was computed by #formatInputsNames()
        let input = item.find(`input[name=${field}]`);
        if (input.length > 0) {
            return item
                .find(`input[name=${field}]`)
                .val();
        }

        // Input name after computation
        input = item.find(`input[data-glpi-form-editor-original-name=${field}]`);
        if (input.length > 0) {
            return item
                .find(`input[data-glpi-form-editor-original-name=${field}]`)
                .val();
        }

        throw new Error(`Field not found: ${field}`);
    }

    /**
     * Set input value for the given question.
     * @param {jQuery} item Question or section
     * @param {string} field
     * @param {string|number} value
     * @returns {jQuery}
     */
    #setItemInput(item, field, value) {
        // Reduce scope when working with a section as we don't want to target
        // its sub-questions inputs
        if (item.data("glpi-form-editor-section") !== undefined) {
            item = item.closest("[data-glpi-form-editor-section]");
        }

        // Input name before state was computed by #formatInputsNames()
        let input = item.find(`input[name=${field}]`);
        if (input.length > 0) {
            return item
                .find(`input[name=${field}]`)
                .val(value);
        }

        // Input name after computation
        input = item.find(`input[data-glpi-form-editor-original-name=${field}]`);
        if (input.length > 0) {
            return item
                .find(`input[data-glpi-form-editor-original-name=${field}]`)
                .val(value);
        }

        throw new Error(`Field not found: ${field}`);
    }

    /**
     * Compute the ideal width of the given input based on its content.
     * @param {HTMLElement} input
     */
    #computeDynamicInputSize(input) {
        $(input).css("width", getRealInputWidth(input, "1.2rem"));
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
     * @param {jQuery} target Current position in the form
     */
    #addSection(target) {
        let destination;
        let action;
        let to_move;

        // Find the context using the target
        if (target.data('glpi-form-editor-question') !== undefined) {
            // Adding a new section after an existing question
            // For the existing sections, any questions AFTER the target will
            // be moved into the new section
            destination = target
                .closest("[data-glpi-form-editor-section]");
            action = "after";
            to_move = $(target).nextAll();
        } else if (target.data('glpi-form-editor-section') !== undefined) {
            // Adding a new section at the start of an existing section
            // All questions of the existing section will be moved into the new
            // section, leaving it empty
            destination = target
                .closest("[data-glpi-form-editor-section]");
            action = "after";
            to_move = $(target)
                .closest("[data-glpi-form-editor-section]")
                .find("[data-glpi-form-editor-question]");
        } else if (target.data('glpi-form-editor-form') !== undefined) {
            // Adding a section at the end of the form
            // The new section will be empty
            destination = target
                .closest("[data-glpi-form-editor-form]")
                .find("[data-glpi-form-editor-section]:last-child");
            action = "after";
            to_move = null;
        } else {
            throw new Error('Unexpected target');
        }

        // Find the section template
        const template = $(this.#templates)
            .find("[data-glpi-form-editor-section-template]")
            .children();

        // Copy the new section template into the sections area
        const section = this.#copy_template(
            template,
            destination,
            action
        );

        // Move questions into their new sections if needed
        if (to_move !== null && to_move.length > 0) {
            to_move.detach().appendTo(
                section.find("[data-glpi-form-editor-section-questions]")
            );
            to_move.each((index, question) => {
                this.#handleItemMove($(question));
            });
        }

        // Update UX
        this.#updateSectionCountLabels();
        this.#updateSectionsDetailsVisiblity();
        this.#updateMergeSectionActionVisibility();

        this.#setActiveItem(
            section.find("[data-glpi-form-editor-section-details]")
        );

        // Focus section's name
        section
            .find("[data-glpi-form-editor-section-details-name]")[0]
            .focus();
    }

    /**
     * Delete the given section.
     * @param {jQuery} section
     */
    #deleteSection(section) {
        if (section.prev().length == 0) {
            // If this is the first section of the form, set the next section as active
            this.#setActiveItem(section.next());
        } else {
            // Else, set the previous section last question (if it exist) as active
            const prev_questions = section.prev().find("[data-glpi-form-editor-question]");
            if (prev_questions.length > 0) {
                this.#setActiveItem(prev_questions.last());
            } else {
                if (this.#getSectionCount() == 2) {
                    // If there is only one section left after this one is deleted,
                    // set the form itself as active as the remaining section will not be displayed
                    this.#setActiveItem(
                        $(this.#target).find("[data-glpi-form-editor-form-details]")
                    );
                } else {
                    this.#setActiveItem(section.prev());
                }
            }
        }

        // Remove question and update UX
        section.remove();
        this.#updateSectionCountLabels();
        this.#updateSectionsDetailsVisiblity();
        this.#updateMergeSectionActionVisibility();
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
     * Count the number of sections in the form.
     * @returns {number}
     */
    #getSectionCount() {
        return $(this.#target)
            .find("[data-glpi-form-editor-section]")
            .length;
    }

    /**
     * Count the number of questions in the form.
     * @returns {number}
     */
    #getQuestionsCount() {
        return $(this.#target)
            .find("[data-glpi-form-editor-question]")
            .length;
    }

    /**
     * Update the visibility of the sections details.
     * The details are hidden if there is only one section.
     */
    #updateSectionsDetailsVisiblity() {
        if (this.#getSectionCount() <= 1) {
            // Only one section, do not display its details
            $(this.#target)
                .find("[data-glpi-form-editor-section-details]")
                .addClass("d-none");
            $(this.#target)
                .find("[data-glpi-form-editor-section-number-display]")
                .addClass("d-none");
        } else {
            // Mutliple sections, display all details
            $(this.#target)
                .find("[data-glpi-form-editor-section-details]")
                .removeClass("d-none");
            $(this.#target)
                .find("[data-glpi-form-editor-section-number-display]")
                .removeClass("d-none");
        }
    }

    /**
     * Update "Step X of Y" labels
     */
    #updateSectionCountLabels() {
        const sections = $(this.#target).find("[data-glpi-form-editor-section]");
        sections.each((s_index, section) => {
            const display = $(section)
                .find("[data-glpi-form-editor-section-number-display]");

            display.html(
                __("Step %1$d of %2$d")
                    .replace("%1$d", s_index + 1)
                    .replace("%2$d", sections.length)
            );
        });
    }

    /**
     * Update the visibility of the "merge with previous section" action.
     * The action is hidden for the first section.
     */
    #updateMergeSectionActionVisibility() {
        // Reset hidden actions
        $(this.#target)
            .find(`[data-glpi-form-editor-on-click="merge-with-previous-section"]`)
            .removeClass("d-none");

        // Hide first section's action
        $(this.#target)
            .find(`[data-glpi-form-editor-section]:first-child`)
            .find(`[data-glpi-form-editor-on-click="merge-with-previous-section"]`)
            .addClass("d-none");
    }

    /**
     * Enable sortable on the questions of each section.
     *
     * @param {jQuery} sections jQuery collection of one or more sections
     */
    #enableSortable(sections) {
        // Sortable instance must be unique for each section
        sections
            .each((index, section) => {
                const questions_container = $(section)
                    .find("[data-glpi-form-editor-section-questions]");

                sortable(questions_container, {
                    // Drag and drop handle selector
                    handle: '[data-glpi-form-editor-question-handle]',

                    // Accept from others sections
                    acceptFrom: '[data-glpi-form-editor-section-questions]',

                    // Placeholder class
                    placeholderClass: 'glpi-form-editor-drag-question-placeholder mb-3',
                });
            });


        sections
            .find("[data-glpi-form-editor-section-questions]")
            .on('sortupdate', (e) => {
                this.#handleItemMove($(e.detail.item));

                // Would be nice to not have a specific case here where we need
                // to manually call this
                // TODO: this event handler should use the main handleEditorAction
                // method rather than defining its code directly
                window.glpiUnsavedFormChanges = true;
            });
    }

    /**
     * Build the "move section modal" content.
     */
    #buildMoveSectionModalContent() {
        // Clear modal content
        const modal_content = $(this.#target)
            .find("[data-glpi-form-editor-move-section-modal-items]");

        modal_content.children().remove();

        // Find all sections and insert them into the modal
        $(this.#target)
            .find("[data-glpi-form-editor-section]")
            .each((index, section) => {
                const name = this.#getItemInput($(section), "name");
                const section_key = $(section).data("glpi-form-editor-key");

                // Copy template
                const copy = $("[data-glpi-form-editor-move-section-modal-item-template]")
                    .clone();

                // Set section index
                copy
                    .find("[data-glpi-form-editor-move-section-modal-item-section-key]")
                    .attr(
                        "data-glpi-form-editor-move-section-modal-item-section-key",
                        section_key
                    );

                // Set section name
                copy
                    .find("[data-glpi-form-editor-move-section-modal-item-section-name]")
                    .html(name);

                // Remove template tag
                copy.removeAttr("data-glpi-form-editor-move-section-modal-item-template");

                modal_content.append(copy);
            });

        sortable($("[data-glpi-form-editor-move-section-modal-items]"), {
            // Drag and drop handle selector
            handle: '[data-glpi-form-editor-section-handle]',

            // Placeholder class
            placeholderClass: 'glpi-form-editor-drag-section-placeholder',
        });
    }

    /**
     * Reorder the sections based on the "move section modal" content.
     */
    #reorderSections() {
        // Temporary bugfix: state must be manually computed before we can reorder
        // the sections as we use the data-glpi-form-editor-key parameter to
        // select the correct section
        this.#computeState();
        // TODO: #computeState should not generate keys, it should be handled
        // somewhere else

        // Close modal
        $(this.#target)
            .find("[data-glpi-form-editor-move-section-modal]")
            .modal('hide');

        $(this.#target)
            .find("[data-glpi-form-editor-move-section-modal-items]")
            .children()
            .each((index, item) => {
                // Find section key
                const section_key = $(item)
                    .find("[data-glpi-form-editor-move-section-modal-item-section-key]")
                    .data("glpi-form-editor-move-section-modal-item-section-key");

                // Find section by index
                const by_key_selector = `[data-glpi-form-editor-key=${section_key}]`;
                const section = $(this.#target)
                    .find(`[data-glpi-form-editor-section]${by_key_selector}`);

                // Move section at the end of the form
                // This will naturally sort all sections as there are moved one
                // by one at the end
                section
                    .remove()
                    .appendTo(
                        $(this.#target).find("[data-glpi-form-editor-sections]")
                    );
            });

        // Reinit tiynmce for all richtext inputs
        // TODO: use #handleItemMove and only reinit the moved sections, not everything
        $(this.#target)
            .find("textarea")
            .each((index, textarea) => {
                const id = $(textarea).prop("id");
                const editor = tinymce.get(id);

                if (editor) {
                    editor.destroy();
                    tinymce.init(window.tinymce_editor_configs[id]);
                }
            });

        // Relabel sections
        this.#updateSectionCountLabels();
        this.#updateMergeSectionActionVisibility();
    }

    /**
     * Some libraries like TinyMCE does not like being moved around and need
     * to be reinitialized after being moved.
     */
    #handleItemMove(item) {
        // Reinit tiynmce for all richtext inputs
        item
            .find("textarea")
            .each((index, textarea) => {
                const id = $(textarea).prop("id");
                const editor = tinymce.get(id);

                if (editor) {
                    editor.destroy();
                    tinymce.init(window.tinymce_editor_configs[id]);
                }
            });
    }

    /**
     * Merge the given section with the previous section.
     * @param {jQuery} section Section to merge
     */
    #mergeWithPreviousSection(section) {
        // Find previous section
        const previous_section = section.prev();

        // Move questions into the previous section
        const to_move = section
            .find("[data-glpi-form-editor-section-questions]")
            .children();
        to_move
            .detach()
            .appendTo(
                previous_section.find("[data-glpi-form-editor-section-questions]")
            );

        // Fix complex inputs like tinymce that don't like to be moved
        to_move.each((index, question) => {
            this.#handleItemMove($(question));
        });

        // Remove the section
        section.remove();

        // Update UX
        this.#updateSectionCountLabels();
        this.#updateSectionsDetailsVisiblity();
        this.#updateMergeSectionActionVisibility();
    }

    /**
     * Collaspe target section
     * @param {jQuery} section
     */
    #collaspeSection(section) {
        // Simple class toggle, hiding the correct parts is handled by CSS rules
        section.toggleClass("section-collapsed");
    }
}
