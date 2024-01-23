/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/* global tinymce_editor_configs */

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
     * Is this form a draft?
     * @type {boolean}
     */
     #is_draft;

    /**
     * Create a new GlpiFormEditorController instance for the given target.
     * The target must be a valid form.
     *
     * @param {string} target
     * @param {string} defaultQuestionType
     * @param {string} templates
     */
    constructor(target, defaultQuestionType, templates, is_draft) {
        this.#target              = target;
        this.#defaultQuestionType = defaultQuestionType;
        this.#templates           = templates;
        this.#is_draft            = is_draft;

        // Validate target
        if ($(this.#target).prop("tagName") != "FORM") {
            console.error("Target must be a valid form");
        }

        // Validate default question type
        if (this.#getQuestionTemplate(this.#defaultQuestionType).length == 0) {
            console.error(
                `Invalid default question type: ${defaultQuestionType}`
            );
        }

        // Adjust container height and init handlers
        this.#adjustContainerHeight();
        this.#initEventHandlers();

        // Adjust dynamics inputs size
        $(this.#target)
            .find("[data-glpi-form-editor-dynamic-input]")
            .each((index, input) => {
                this.#computeDynamicInputSize($(input));
            });

        // Enable sortable
        this.#enableSortable(
            $(this.#target).find("[data-glpi-form-editor-sections]")
        );
    }

    /**
     * Handle backend response
     * @param {Object} response
     */
    handleBackendUpdateResponse(response) {
        // Item can no longer be draft after the first backend update
        if (this.#is_draft) {
            this.#removeDraftStatus();
        }

        // Handle newly added questions ids, they must be inserted into their
        // form so they can be updated correctly instead of re-added on the next
        // submit
        if (response.added_questions !== undefined) {
            // Data contains two keys: input_index and id
            response.added_questions.forEach((data) => {
                // Update ID input
                $(`input[name='_questions[${data.section_index}][${data.question_index}][id]']`).val(data.id);
            });
        }

        // Handle newly added sections ids, they must be inserted into their
        // form so they can be updated correctly instead of re-added on the next
        // submit
        if (response.added_sections !== undefined) {
            // Data contains two keys: input_index and id
            response.added_sections.forEach(function(data) {
                // Update ID input
                $("input[name='_sections[" + data.input_index + "][id]']").val(data.id);
            });
        }
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
     * Init event handlers for each possible editors actions (identified by the
     *  "data-glpi-form-editor-on-xxx" data attributes) and external events.
     *
     * The following external events are handled:
     *  - resize (window): recompute the idea height of the container
     *  - glpi-form-renderer-submit-success (document): close modal and show
     *  toast with link to created item
     *  - TinyMCEChange (document): handle tincemce changes
     */
    #initEventHandlers() {
        // Register throttled version of the adjustContainerHeight() function
        const adjust_container_height_throttled = _.throttle(
            () => this.#adjustContainerHeight(),
            100
        );

        // Register handlers for each possible data attributes and its
        // respective event
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

        // Compute correct height when the window is resized
        $(window).on('resize', () => adjust_container_height_throttled());

        // Handle form preview successful submit
        $(document)
            .on(
                'glpi-form-renderer-submit-success',
                (e, data) => this.#handleFormPreviewSubmitSuccess(data)
            );

        // Handle tinymce change event
        $(document)
            .on(
                'tinyMCEChange',
                (e, original_event) => this.#handleTinyMCEChange(original_event)
            );
    }

    /**
     * The available actions are:
     *  - "set-active": mark the target item as active
     *  - "add-question": add a new question at the end of the current section
     *  - "show-preview": show the preview of the current form in a modal
     *  - "add-section": add a new section at the end of the form
     *  - "delete-section": delete the target section
     *  - "delete-question": delete the target question
     *  - "toggle-mandatory-question": toggle the mandatory class on the target
     *  question
     *  - "compute-dynamic-input": compute the ideal width of the given input
     *  based on its content
     *  - "change-question-parent-type": change the parent type of the target
     *  question
     *  - "change-question-type": change the parent type of the target question
     *  - "build-move-section-modal-content": build the "move section modal"
     *  content
     * - "reorder-sections": reorder the sections based on the "move section
     * modal" content
     *
     * @param {string} action Action to perform
     * @param {jQuery} target Element that triggered the action
     */
    #handleEditorAction(action, target) {
        switch (action) {
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

            // Show the preview of the current form in a modal
            case "show-preview":
                this.#showPreview();
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
                this.#computeDynamicInputSize(target);
                break;

            // Change the parent type of the target question
            case "change-question-parent-type":
                this.#changeQuestionParentType(
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

            // Build the "move section modal" content
            case "build-move-section-modal-content":
                this.#buildMoveSectionModalContent();
                break;

            // Reorder the sections based on the "move section modal" content
            case "reorder-sections":
                this.#reorderSections();
                break;

            // Unknown action
            default:
                console.error(`Unknown action: ${action}`);
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
     * Add a new question at the end of the given section.
     * @param {jQuery} section
     */
    #addQuestion(section) {
        // Get template content
        const template_content = this.#getQuestionTemplate(
            this.#defaultQuestionType
        ).children();

        // Compute form indexes
        const section_index = section.data("glpi-form-editor-section-index");
        const question_index = this.#getNextQuestionIndexForSection(section);

        // Insert the new template into the questions area of the current section
        const new_question = this.#copy_template(
            template_content,
            section.find("[data-glpi-form-editor-section-questions]"),
            section_index,
            question_index,
        );

        // Set question index (needed in case the question type is modified
        // later on and we need to re-render part of the question)
        new_question.attr("data-glpi-form-editor-question-index", question_index);

        // Set correct FK (parent section) in the questions form data
        const sections_id = this.#getSectionInput(section, "id");
        this.#setQuestionInput(new_question, "forms_sections_id", sections_id);

        // Update UX
        this.#setActiveItem(new_question);
        this.#updateAddSectionActionVisiblity();

        // Update ranks
        this.#computeRanks();
    }

    /**
     * Show the preview of the current form in a modal
     */
    #showPreview() {
        const id = $(this.#target).find("input[name=id]").val();
        $("#glpi_form_editor_preview_modal .modal-content").load(
            CFG_GLPI.root_doc + "/ajax/form/render_form.php?id=" + id,
        );
    }

    /**
     * Add a new section at the end of the form.
     */
    #addSection() {
        // Compute next section index
        const index = this.#getNextSectionIndex();

        // Find the section template
        const template = $(this.#templates)
            .find("[data-glpi-form-editor-section-template]")
            .children();

        // Copy the new section template into the sections area
        const copy = this.#copy_template(
            template,
            $(this.#target).find("[data-glpi-form-editor-sections]"),
            index,
        );

        // Set section index
        copy.attr("data-glpi-form-editor-section-index", index);

        // Update UX
        this.#updateSectionsDetailsVisiblity();

        // Make the new section sortable
        this.#enableSortable(copy);

        // Update ranks
        this.#computeRanks();
    }

    /**
     * Delete the given section.
     * @param {jQuery} section
     */
    #deleteSection(section) {
        // Remove question and update UX
        section.remove();
        this.#updateSectionsDetailsVisiblity();

        // Update ranks
        this.#computeRanks();
    }

    /**
     * Delete the given question.
     * @param {jQuery} question
     */
    #deleteQuestion(question) {
        // Remove question and update UX
        question.remove();
        this.#updateAddSectionActionVisiblity();

        // Update ranks
        this.#computeRanks();
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
     * Compute the ideal width of the given input based on its content.
     * @param {jQuery} input
     */
    #computeDynamicInputSize(input) {
        input.css("width", getRealInputWidth(input, "1.2rem"));
    }

    /**
     * Change the parent type of the given question.
     * @param {jQuery} question    Question to update
     * @param {string} parent_type New parent type
     */
    #changeQuestionParentType(question, parent_type) {
        // Find types available in the new parent type
        const eparent_type = $.escapeSelector(parent_type);
        const new_options = $(this.#templates)
            .find(`option[data-glpi-form-editor-question-type=${eparent_type}]`);

        // Remove current types options
        const types_select = question
            .find("[data-glpi-form-editor-question-type-selector]");
        types_select.children().remove();

        // Find parent section
        const section = question.closest("[data-glpi-form-editor-section]");

        // Copy the new types options into the dropdown
        this.#copy_template(
            new_options,
            types_select,
            section.data("glpi-form-editor-section-index"),
            question.data("glpi-form-editor-question-index"),
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
     * @param {jQuery} question    Question to update
     * @param {string} type New parent type
     */
    #changeQuestionType(question, type) {
        // Clear the specific form of the question
        const specific = question
            .find("[data-glpi-form-editor-question-type-specific]");
        specific.children().remove();

        // Find parent section
        const section = question.closest("[data-glpi-form-editor-section]");

        // Find the specific content of the given type
        const new_specific_content = this
            .#getQuestionTemplate(type)
            .find("[data-glpi-form-editor-question-type-specific]");

        // Copy the specific form of the new question type into the question
        this.#copy_template(
            new_specific_content,
            specific,
            section.data("glpi-form-editor-section-index"),
            question.data("glpi-form-editor-question-index"),
        );
    }

    /**
     * Build the "move section modal" content.
     */
    #buildMoveSectionModalContent() {
        // Clear modal content
        const modal_content = $(this.#target)
            .find("[data-form-editor-move-section-modal-items]");

        modal_content.children().remove();

        // Find all sections and insert them into the modal
        $(this.#target)
            .find("[data-glpi-form-editor-section]")
            .each((index, section) => {
                const name = this.#getSectionInput($(section), "name");
                const sindex = $(section).data("glpi-form-editor-section-index");

                // Copy template
                const copy = $("[data-form-editor-move-section-modal-item-template]")
                    .clone();

                // Set section index
                copy
                    .find("[data-form-editor-move-section-modal-item-section-index]")
                    .attr(
                        "data-form-editor-move-section-modal-item-section-index",
                        sindex
                    );

                // Set section name
                copy
                    .find("[data-form-editor-move-section-modal-item-section-name]")
                    .html(name);

                // Remove template tag
                copy.removeAttr("data-form-editor-move-section-modal-item-template");

                modal_content.append(copy);
            });

        sortable($("[data-form-editor-move-section-modal-items]"), {
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
        const modal_content = $(this.#target)
            .find("[data-form-editor-move-section-modal-items]")
            .children()
            .each((index, item) => {
                // Find section index
                const sindex = $(item)
                    .find("[data-form-editor-move-section-modal-item-section-index]")
                    .data("form-editor-move-section-modal-item-section-index");

                // Find section by index
                const section = $(this.#target)
                    .find(`[data-glpi-form-editor-section-index=${sindex}]`);

                // Move section at the end of the form
                // This will naturally sort all sections as there are moved one
                // by one at the end
                section
                    .remove()
                    .appendTo(
                        $(this.#target).find("[data-glpi-form-editor-sections]")
                    );
            })

        // Reinit tiynmce on all inputs
        $(this.#target)
            .find("textarea")
            .each((index, textarea) => {
                const id = $(textarea).prop("id");
                const editor = tinymce.get(id);
                editor.destroy();
                tinymce.init(window.tinymce_editor_configs[id])
            });

        // Update ranks
        this.#computeRanks();
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

        // TODO: footer should be at the bottom of the page without scrolling
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
     * Copy the given template into the given destination.
     *
     * @param {jQuery} target         Template to copy
     * @param {jQuery} destination    Destination to copy the template into
     * @param {number} section_index  Index of the parent section
     * @param {number} question_index Index of the question
     * @returns {jQuery} Copy of the template
     */
    #copy_template(target, destination, section_index, question_index) {
        const copy = target.clone();

        // Keep track of rich text editors that will need to be initialized
        const tiny_mce_to_init = [];

        // Compute base input name
        const base_input_name = this.#buildInputIndex(
            section_index,
            question_index
        );

        // Apply base input index to ensure input name uniqueness
        copy.find("select, input, textarea").each(function() {
            const input_name = $(this).attr("name");
            $(this).attr("name", `${base_input_name}[${input_name}]`);

            // Special action for tinymce
            if ($(this).prop("tagName") == "TEXTAREA") {
                // Get editor config for this field
                let id = $(this).attr("id");
                const config = window.tinymce_editor_configs[id];

                // Rename id to ensure it is unique
                const uid = uniqid();
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
            }
        });

        // Insert the new question and init the editors
        copy.appendTo(destination);
        tiny_mce_to_init.forEach((config) => tinyMCE.init(config));

        return copy;
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
     * Get next available question index for the given section.
     * @param {jQuery} section
     * @returns {number}
     */
    #getNextQuestionIndexForSection(section) {
        return section.find("[data-glpi-form-editor-question]").length;
    }

    /**
     * Get next available section index.
     * @returns {number}
     */
    #getNextSectionIndex() {
        return $(this.#target)
            .find("[data-glpi-form-editor-section]")
            .length;
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
     * @param {jQuery} question
     * @param {string} field
     * @returns {string|number}
     */
    #getQuestionInput(question, field) {
        const sindex = question
            .closest("[data-glpi-form-editor-section]")
            .data("glpi-form-editor-section-index");
        const qindex = question.data("glpi-form-editor-question-index");
        const base_input_name = this.#buildInputIndex(sindex, qindex);

        return question
            .find(`input[name='${base_input_name}[${field}]']`)
            .val();
    }

    /**
     * Set input value for the given question.
     * @param {jQuery} question
     * @param {string} field
     * @param {string|number} value
     * @returns {string|number}
     */
    #setQuestionInput(question, field, value) {
        const sindex = question
            .closest("[data-glpi-form-editor-section]")
            .data("glpi-form-editor-section-index");
        const qindex = question.data("glpi-form-editor-question-index");
        const base_input_name = this.#buildInputIndex(sindex, qindex);

        return question
            .find(`input[name='${base_input_name}[${field}]']`)
            .val(value);
    }

    /**
     * Get input value for the given section.
     * @param {jQuery} question
     * @param {string} field
     * @returns {string|number}
     */
    #getSectionInput(section, field) {
        const index = section.data("glpi-form-editor-section-index");
        const base_input_name = this.#buildInputIndex(index);

        return section
            .find(`input[name='${base_input_name}[${field}]']`)
            .val();
    }

    /**
     * Set input value for the given section.
     * @param {jQuery} question
     * @param {string} field
     * @param {string|number} value
     * @returns {string|number}
     */
    #setSectionInput(section, field, value) {
        const index = section.data("glpi-form-editor-section-index");
        const base_input_name = this.#buildInputIndex(index);

        return section
            .find(`input[name='${base_input_name}[${field}]']`)
            .val(value);
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
    };

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
        } else {
            // Mutliple sections, display all details
            $(this.#target)
                .find("[data-glpi-form-editor-section-form-container]")
                .removeClass("d-none");
        }
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
                // TinyMCE does no like being moved around and must be
                // reinitialized by destroying the editor instance and
                // recreating it
                $(e.detail.item).find("textarea").each((index, textarea) => {
                    const id = $(textarea).prop("id");
                    const editor = tinymce.get(id);
                    editor.destroy();
                    tinymce.init(window.tinymce_editor_configs[id])
                });

                // Update ranks
                this.#computeRanks();
            });
    }

    /**
     * Compute ranks for each sections and questions.
     * The value is set in the "rank" input of each section/question.
     */
    #computeRanks() {
        // Compute ranks for each sections and questions
        $(this.#target)
            .find("[data-glpi-form-editor-section]")
            .each((index, section) => {
                // Update rank input base on section index
                this.#setSectionInput($(section), "rank", index);

                // Update rank of each questions of this section
                $(section)
                    .find("[data-glpi-form-editor-question]")
                    .each((index, question) => {
                        // Update rank input base on question index
                        this.#setQuestionInput($(question), "rank", index);
                    });
            });
    }
}
