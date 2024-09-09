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

/* global _, tinymce_editor_configs, getUUID, getRealInputWidth, sortable, tinymce, glpi_toast_error, bootstrap, setupAjaxDropdown, setupAdaptDropdown */

/**
 * Client code to handle users actions on the form_editor template
 */
export class GlpiFormEditorController
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
     * Options for each question type
     * @type {Object}
     */
    #options;

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
        this.#options             = {};

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
        this.#refreshUX();

        // Adjust dynamics inputs size
        $(this.#target)
            .find("[data-glpi-form-editor-dynamic-input]")
            .each((index, input) => {
                this.#computeDynamicInputSize(input);
            });

        // Enable sortable on questions
        this.#enableSortable(
            $(this.#target).find("[data-glpi-form-editor-blocks]")
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

        // Handle clicks inside the form editor, remove the active item
        $(document)
            .on(
                'click',
                '[data-glpi-form-editor]',
                () => {
                    this.#setFormDetailsAsActive();
                    $('.simulate-focus').removeClass('simulate-focus');
                }
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
        $(this.#target).on('submit', (event) => {
            try {
                this.computeState();
            } catch (e) {
                // Do not submit the form if the state isn't computed
                event.preventDefault();
                event.stopPropagation();
                glpi_toast_error(__("An unexpected error occurred"));
                throw e;
            }
        });

        // Handle form submit success event
        $(this.#target).on('glpi-ajax-controller-submit-success', () => {
            // Reset unsaved changes
            this.#updatePreviewButton();

            const save_and_preview_button = $(this.#target).find('[data-glpi-form-editor-save-and-preview-action');
            if (save_and_preview_button.get(0) === $(document.activeElement).get(0)) {
                // Open the preview page in a new tab
                window.open(save_and_preview_button.data('glpi-form-editor-preview-url'), '_blank');
            }
        });

        let last_form_changes = window.glpiUnsavedFormChanges;
        setInterval(() => {
            if (last_form_changes !== window.glpiUnsavedFormChanges) {
                this.#updatePreviewButton();
            }
            last_form_changes = window.glpiUnsavedFormChanges;
        }, 500);

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

                    try {
                        this.#handleEditorAction(action, target, e);
                    } catch (e) {
                        glpi_toast_error(__("An unexpected error occurred"));
                        throw e;
                    }
                });
        });
    }

    /**
     * Register new options for the given question type.
     *
     * @param {string} type    Question type
     * @param {Object} options Options for the question type
     */
    registerQuestionTypeOptions(type, options) {
        this.#options[type] = options;
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

        // Events should only be handled here once.
        event.stopPropagation();

        switch (action) {
            // Mark the target item as active
            case "set-active":
                this.#setActiveItem(target);
                unsaved_changes = false;
                break;

            // Add a new question
            case "add-question":
                this.#addQuestion(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-question],
                        [data-glpi-form-editor-active-comment]
                    `),
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
                this.#addSection(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-question],
                        [data-glpi-form-editor-active-comment]
                    `),
                );
                break;

            // Delete the target section
            case "delete-section":
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

            // Duplicate target section
            case "duplicate-section":
                this.#duplicateSection(
                    target.closest("[data-glpi-form-editor-section]")
                );
                break;

            // Duplicate target question
            case "duplicate-question":
                this.#duplicateQuestion(
                    target.closest("[data-glpi-form-editor-question]")
                );
                break;

            // Duplicate target comment
            case "duplicate-comment":
                this.#duplicateComment(
                    target.closest("[data-glpi-form-editor-comment]")
                );
                break;

            // No specific instructions for these events.
            // They must still be kept here as they benefits from the common code
            // like refreshUX() and glpiUnsavedFormChanges.
            case "question-sort-update":
                break;

            // Add a new comment
            case "add-comment":
                this.#addComment(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-question],
                        [data-glpi-form-editor-active-comment]
                    `),
                );
                break;
            // Delete the target comment
            case "delete-comment":
                this.#deleteComment(
                    target.closest("[data-glpi-form-editor-comment]")
                );
                break;

            // Unknown action
            default:
                throw new Error(`Unknown action: ${action}`);
        }

        if (unsaved_changes) {
            window.glpiUnsavedFormChanges = true;
        }

        // Refresh all dynamic UX components after every action.
        // It is a bit less effecient than refreshing only the needed components
        // per action, but it is much simpler and safer.
        this.#refreshUX();
    }

    /**
     * Compute the state of the form editor (= inputs names and values).
     * Must be executed after each actions.
     */
    computeState() {
        let global_block_indices = { 'question': 0, 'comment': 0 };

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

            // Find all items for this section (both questions and comments)
            const items = $(section).find("[data-glpi-form-editor-question], [data-glpi-form-editor-comment]");

            items.each((index, item) => {
                // Determine the type of the item
                const itemType = $(item).is("[data-glpi-form-editor-question]") ? 'question' : 'comment';

                // Compute state for each item
                this.#formatInputsNames(
                    $(item),
                    itemType,
                    global_block_indices[itemType]
                );
                this.#setItemRank($(item), index);
                this.#remplaceEmptyIdByUuid($(item));
                this.#setParentSection($(item), $(section));

                // Increment the index for this item type
                global_block_indices[itemType]++;
            });
        });
    }

    /**
     * Refresh all UX items that may be modified by mulitple actions.
     */
    #refreshUX() {
        this.#updateAddSectionActionVisiblity();
        this.#addFakeDivToEmptySections();
        this.#updateSectionCountLabels();
        this.#updateSectionsDetailsVisiblity();
        this.#updateMergeSectionActionVisibility();
    }

    /**
     * Must not be called directly, use computeState() instead.
     *
     * Inputs names of questions and sections must be formatted to match the
     * expected format, which is:
     * - Sections: _sections[section_index][field]
     * - Questions: _questions[question_index][field]
     * - Comment blocks: _comments[comment_index][field]
     *
     * @param {jQuery} item       Section or question form container
     * @param {string} type       Item type: "question" or "section"
     * @param {number} item_index Item index
     */
    #formatInputsNames(item, type, item_index) {
        // Find all inputs for this section
        const inputs = item.find("input[name], select[name], textarea[name]");

        // Find all section inputs and update their names to match the
        // "_section[section_index][field]" format
        inputs.each((index, input) => {
            const name = $(input).attr("name");

            // Input was never parsed before, store its original name
            if (!$(input).data("glpi-form-editor-original-name")) {
                $(input).attr("data-glpi-form-editor-original-name", name);
            }

            // Format input name
            let field = $(input).data("glpi-form-editor-original-name");
            let base_input_index = "";
            if (type === "section") {
                // The input is for the section itself
                base_input_index = `_sections[${item_index}]`;
            } else if (type === "question") {
                // The input is for a question
                base_input_index =  `_questions[${item_index}]`;

                // Check if the input is an option (has the data-glpi-form-editor-specific-question-extra-data attribute)
                const is_option = $(input).attr("data-glpi-form-editor-specific-question-extra-data") !== undefined;

                if (is_option) {
                    base_input_index += `[extra_data]`;
                }
            } else if (type === "comment") {
                // The input is for a comment block
                base_input_index = `_comments[${item_index}]`;
            } else {
                throw new Error(`Unknown item type: ${type}`);
            }

            // Update input name
            let postfix = "";
            const postfix_pattern = new RegExp(/\[([\w-[\]]*)\]$/, 'g');
            if (typeof field === 'string' && postfix_pattern.test(field)) {
                postfix = field.match(postfix_pattern);
                field = field.replace(postfix, "");
            }

            $(input).attr(
                "name",
                base_input_index + `[${field}]${postfix}`
            );
        });
    }

    /**
     * Must not be called directly, use computeState() instead.
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
     * Must not be called directly, use computeState() instead.
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
            this.#setItemInput(item, "_use_uuid", 1);
        }
    }

    /**
     * Must not be called directly, use computeState() instead.
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
        // The event target expose its relevant textarea in a `data-id` property
        const id = $(e.target).closest("#tinymce").data("id");
        const textarea = $(`#${id}`);

        // Handle 'set-active' action for clicks inside tinymce
        this.#setActiveItem(
            textarea
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
        let height = (window_height - editor_height - tab_content_border);

        if ($("#debug-toolbar").length > 0) {
            // If the debug toolbar is present, we need to take it into account
            const debug_toolbar_height = $("#debug-toolbar").height();
            height -= debug_toolbar_height;
        }

        $(this.#target).css('height', `${height}`);
    }

    /**
     * Update UX to reflect the fact that the form is no longer a draft.
     */
    #removeDraftStatus() {
        // Turn the "Add" button into "Save"
        const add_button = $('#main-form button[name=update]');
        add_button
            .find('.ti-plus')
            .removeClass('ti-plus')
            .addClass('ti-device-floppy');
        add_button.find('.add-label').text(__('Save'));
        add_button.prop("title", __('Save'));

        // Show the delete button
        const del_button = $('#main-form button[name=delete]');
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
        const possible_active_items = ['form', 'section', 'question', 'comment'];

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
     * Add a new block next to the target.
     * @param {jQuery} target
     * @param {jQuery} template
     * @returns
     */
    #addBlock(target, template) {
        let destination;
        let action;

        // Find the context using the target
        if (
            target.data('glpi-form-editor-question') !== undefined
            || target.data('glpi-form-editor-comment') !== undefined
        ) {
            // Adding a new block after an existing question
            destination = target;
            action = "after";
        } else if (target.data('glpi-form-editor-section') !== undefined) {
            // Adding a block at the start of a section
            destination = target
                .closest("[data-glpi-form-editor-section]")
                .find("[data-glpi-form-editor-section-blocks]");
            action = "prepend";
        } else if (target.data('glpi-form-editor-form') !== undefined) {
            // Add a block at the end of the form
            destination = $(this.#target)
                .find("[data-glpi-form-editor-section]:last-child")
                .find("[data-glpi-form-editor-section-blocks]:last-child");
            action = "append";
        } else {
            throw new Error('Unexpected target');
        }

        // Insert the new template into the questions area of the current section
        return this.#copy_template(
            template,
            destination,
            action
        );
    }

    /**
     * Add a new question at the end of the form
     * @param {jQuery} target   Current position in the form
     */
    #addQuestion(target) {
        // Get template content
        const template = this.#getQuestionTemplate(
            this.#defaultQuestionType
        ).children();

        const new_question = this.#addBlock(target, template);

        // Mark as active
        this.#setActiveItem(new_question);

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
            && this.#getSectionCount() == 1
        ) {
            // If the last questions is going to be deleted and there is only one section
            // set the form itself as active to show its toolbar
            this.#setFormDetailsAsActive();
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

        // Keep track of select2 that will need to be initialized
        const select2_to_init = [];

        // Look for tiynmce editor to init
        copy.find("textarea").each(function() {
            // Get editor config for this field
            let id = $(this).attr("id");

            // JS object are passed by reference, we need to clone the config
            // to avoid breaking previous instances
            const config = _.cloneDeep(window.tinymce_editor_configs[id]);

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

        // Look for select2 to init
        copy.find("select").each(function() {
            let id = $(this).attr("id");
            const config = window.select2_configs[id];

            if (id !== undefined && config !== undefined) {
                // Rename id to ensure it is unique
                const uid = getUUID();
                $(this).attr("id", uid);

                // Check if a select2 isn't already initialized
                // and if a configuration is available
                if (
                    $(this).hasClass("select2-hidden-accessible") === false
                    && config !== undefined
                ) {
                    config.field_id = uid;
                    select2_to_init.push(config);
                }
            }
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

        // Init the select2
        select2_to_init.forEach((config) => {
            if (config.type === "ajax") {
                setupAjaxDropdown(config);
            } else if (config.type === "adapt") {
                setupAdaptDropdown(config);
            }
        });

        // Init tooltips
        const tooltip_trigger_list = copy.find('[data-bs-toggle="tooltip"]');
        [...tooltip_trigger_list].map(
            tooltip_trigger_el => new bootstrap.Tooltip(tooltip_trigger_el)
        );

        // Init popovers
        const popover_trigger_list = copy.find('[data-bs-toggle="popover"]');
        [...popover_trigger_list].map(
            popover_trigger_el => new bootstrap.Popover(popover_trigger_el)
        );

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
            item = item.find("[data-glpi-form-editor-section-details]");
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
        const types_select_container = types_select.parent();
        if (new_options.length <= 1) {
            types_select_container.addClass("d-none");
        } else {
            types_select_container.removeClass("d-none");
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
        // Get the current question type and extracted default value
        const old_type = this.#getItemInput(question, "type");
        const extracted_default_value = this.#options[old_type].extractDefaultValue(question);

        // Clear the specific form of the question
        const specific = question
            .find("[data-glpi-form-editor-question-type-specific]");
        specific.children().remove();

        // Clear the extra data of the question
        const extra_data = question
            .find("[data-glpi-form-editor-specific-question-options]");
        extra_data.children().remove();

        // Find the specific content of the given type
        const new_specific_content = this
            .#getQuestionTemplate(type)
            .find("[data-glpi-form-editor-question-type-specific]")
            .children();

        // Find the extra data of the given type
        const new_extra_data = this
            .#getQuestionTemplate(type)
            .find("[data-glpi-form-editor-specific-question-options]")
            .children();

        // Copy the specific form of the new question type into the question
        this.#copy_template(
            new_specific_content,
            specific,
        );

        // Copy the extra data of the new question type into the question
        this.#copy_template(
            new_extra_data,
            extra_data,
        );

        // Update the question type
        this.#setItemInput(question, "type", type);

        // Handle blacklisted question type warning visibility
        const allow_anonymous = this.#getQuestionTemplate(type).find("[data-glpi-form-editor-question-details]").data("glpi-form-editor-allow-anonymous");
        question.find("[data-glpi-form-editor-blacklisted-question-type-warning]")
            .toggleClass("d-none", allow_anonymous == 1);

        // Convert the default value to match the new type
        this.#options[type].convertDefaultValue(
            question,
            extracted_default_value
        );

        $(document).trigger('glpi-form-editor-question-type-changed', [question, type]);
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
        if (
            target.data('glpi-form-editor-question') !== undefined
            || target.data('glpi-form-editor-comment') !== undefined
        ) {
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
                section.find("[data-glpi-form-editor-section-blocks]")
            );
            to_move.each((index, question) => {
                this.#handleItemMove($(question));
            });
        }

        // Mark new serction as active
        this.#setActiveItem(
            section.find("[data-glpi-form-editor-section-details]")
        );

        // Enable sortable
        this.#enableSortable(section);

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
            // If this is the first section of the form, set the next section as active if it exists
            if (section.next().length > 0 && this.#getSectionCount() > 2) {
                this.#setActiveItem(section.next());
            } else {
                this.#setFormDetailsAsActive();
            }
        } else {
            // Else, set the previous section last question (if it exist) as active
            const prev_questions = section.prev().find("[data-glpi-form-editor-question]");
            if (prev_questions.length > 0) {
                this.#setActiveItem(prev_questions.last());
            } else {
                if (this.#getSectionCount() == 2) {
                    // If there is only one section left after this one is deleted,
                    // set the form itself as active as the remaining section will not be displayed
                    this.#setFormDetailsAsActive();
                } else {
                    this.#setActiveItem(section.prev());
                }
            }
        }

        // Remove question and update UX
        section.remove();
    }

    /**
     * Add a new comment block.
     * @param {jQuery} target   Current position in the form
     */
    #addComment(target) {
        // Find the comment template
        const template = $(this.#templates)
            .find("[data-glpi-form-editor-comment-template]")
            .children();

        const new_comment = this.#addBlock(target, template);

        // Mark as active
        this.#setActiveItem(new_comment);

        // Focus title's name
        new_comment
            .find("[data-glpi-form-editor-comment-details-name]")[0]
            .focus();
    }

    /**
     * Delete the given comment.
     *
     * @param {jQuery} comment
     */
    #deleteComment(comment) {
        if (
            $(this.#target).find("[data-glpi-form-editor-comment]").length == 1
            && this.#getSectionCount() == 1
        ) {
            // If the last comments is going to be deleted and there is only one section
            // set the form itself as active to show its toolbar
            this.#setFormDetailsAsActive();
        } else {
            // Set active the previous comment/section
            if (comment.prev().length > 0) {
                this.#setActiveItem(comment.prev());
            } else {
                this.#setActiveItem(comment.closest("[data-glpi-form-editor-section]"));
            }
        }

        // Remove comment and update UX
        comment.remove();
    }

    /**
     * Update the visibility of the "add section" action.
     * The action is hidden if there are no questions in the form.
     */
    #updateAddSectionActionVisiblity() {
        const block_count = $(this.#target)
            .find("[data-glpi-form-editor-block]")
            .length;

        // Hide the "add section" action unless there is at least one question
        if (block_count == 0) {
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
                    .find("[data-glpi-form-editor-section-blocks]");

                sortable(questions_container, {
                    // Drag and drop handle selector
                    handle: '[data-glpi-form-editor-question-handle]',

                    // Accept from others sections
                    acceptFrom: '[data-glpi-form-editor-section-blocks]',

                    // Placeholder class
                    placeholderClass: 'glpi-form-editor-drag-question-placeholder mb-3',
                });
            });

        // Keep track on unsaved changes if the sort order was updated
        sections
            .find("[data-glpi-form-editor-section-blocks]")
            .on('sortupdate', (e) => {
                // Trigger an action to make sure we use the main entry point
                // where common action related functions are excuted
                this.#handleEditorAction('question-sort-update', null, e);
            });

        // Add a special class while a drag and drop is happening
        sections
            .find("[data-glpi-form-editor-section-blocks]")
            .on('sortstart', () => {
                $(this.#target).addClass("disable-focus");
            });

        // Run the post move process if any item was dragged, even if it was not
        // moved in the end (= dragged on itself)
        sections
            .find("[data-glpi-form-editor-section-blocks]")
            .on('sortstop', (e) => {
                // The 'sortstop' event trigger twice for a single drag and drop
                // action.
                // The first iteration will have the 'sortable-dragging' class,
                // which we can check to filter it out.
                if ($(e.detail.item).hasClass("sortable-dragging")) {
                    return;
                }

                this.#handleItemMove($(e.detail.item));

                // Prevent tinymce from stealing focus when dragging someting
                // over it.
                // It seems to be caused by the fact that tinymce expect files
                // to be dragged into it, thus we have to manually disable focus
                // until our drag operation is over.
                $(this.#target).removeClass("disable-focus");
                $('.content-editable-tinymce').removeClass('simulate-focus');
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

                // Copy template
                const copy = $("[data-glpi-form-editor-move-section-modal-item-template]")
                    .clone();

                // Set an unique identifier on both the section and its modal counter part
                // This will allow us to find the matching sections for each modal list items
                const uuid = getUUID();
                $(section).attr("data-glpi-form-editor-move-section-modal-uuid", uuid);
                copy
                    .find("[data-glpi-form-editor-move-section-modal-item-section-key]")
                    .attr(
                        "data-glpi-form-editor-move-section-modal-item-section-key",
                        uuid
                    );
                copy
                    .find("[data-glpi-form-editor-move-section-modal-item-section-key]")
                    .attr("aria-label", __('Move section: %1$d').replace("%1$d", name));

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
        // Close modal
        $(this.#target)
            .find("[data-glpi-form-editor-move-section-modal]")
            .modal('hide');

        $(this.#target)
            .find("[data-glpi-form-editor-move-section-modal-items]")
            .children()
            .each((index, item) => {
                // Get the UUID defined in the buildMoveSectionModalContent process
                const section_key = $(item)
                    .find("[data-glpi-form-editor-move-section-modal-item-section-key]")
                    .data("glpi-form-editor-move-section-modal-item-section-key");

                // Find section by index
                const section = $(this.#target)
                    .find(`[data-glpi-form-editor-move-section-modal-uuid=${section_key}]`);

                // Move section at the end of the form
                // This will naturally sort all sections as they are moved one
                // by one at the end
                section
                    .remove()
                    .appendTo(
                        $(this.#target).find("[data-glpi-form-editor-blocks]")
                    );
            });

        // Handle the move for each sections
        $(this.#target).find("[data-glpi-form-editor-section]").each((index, section) => {
            this.#handleItemMove($(section));
        });
    }

    /**
     * Some libraries like TinyMCE does not like being moved around and need
     * to be reinitialized after being moved.
     */
    #handleItemMove(item) {
        // Reinit tiynmce for all richtext inputs
        const ids = this.#disableTinyMce(item);
        this.#enableTinyMce(ids);
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
            .find("[data-glpi-form-editor-section-blocks]")
            .children();
        to_move
            .detach()
            .appendTo(
                previous_section.find("[data-glpi-form-editor-section-blocks]")
            );

        // Fix complex inputs like tinymce that don't like to be moved
        to_move.each((index, question) => {
            this.#handleItemMove($(question));
        });

        // Remove the section
        section.remove();
    }

    /**
     * Collaspe target section
     * @param {jQuery} section
     */
    #collaspeSection(section) {
        // Simple class toggle, hiding the correct parts is handled by CSS rules
        section.toggleClass("section-collapsed");
    }

    /**
     * Duplicate the given section
     * @param {jQuery} section
     */
    #duplicateSection(section) {
        // TinyMCE must be disabled before we can duplicate the section DOM
        const ids = this.#disableTinyMce(section);
        const new_section = this.#copy_template(section, section, "after");
        this.#enableTinyMce(ids);

        this.#setItemInput(new_section, "id", 0);
        new_section
            .find("[data-glpi-form-editor-question]")
            .each((index, question) => {
                this.#setItemInput($(question), "id", 0);
            })
        ;

        this.#setActiveItem(new_section);
        this.#enableSortable(new_section);
    }

    /**
     * Duplicate the given question
     * @param {jQuery} section
     */
    #duplicateQuestion(question) {
        // TinyMCE must be disabled before we can duplicate the question DOM
        const ids = this.#disableTinyMce(question);
        const new_question = this.#copy_template(question, question, "after");
        this.#enableTinyMce(ids);

        this.#setItemInput(new_question, "id", 0);
        this.#setActiveItem(new_question);
    }

    /**
     * Duplicate the given comment
     * @param {jQuery} section
     */
    #duplicateComment(comment) {
        // TinyMCE must be disabled before we can duplicate the comment DOM
        const ids = this.#disableTinyMce(comment);
        const new_comment = this.#copy_template(comment, comment, "after");
        this.#enableTinyMce(ids);

        this.#setItemInput(new_comment, "id", 0);
        this.#setActiveItem(new_comment);
    }

    /**
     * Add fake div to empty sections to allow drag and drop.
     * This is needed because sortable require at least one item in a list to
     * enable drag and drop.
     */
    #addFakeDivToEmptySections() {
        // Clear fake divs
        $(this.#target)
            .find("[data-glpi-form-editor-empty-div]")
            .remove();

        // Add fake divs to empty sections
        const sections = $(this.#target).find("[data-glpi-form-editor-section]");
        sections.each((index, section) => {
            const questions = $(section).find("[data-glpi-form-editor-section-blocks]");
            if (questions.children().length == 0) {
                questions.append('<div data-glpi-form-editor-empty-div style="height: 1px"></div>');
            }
        });
    }

    /**
     * Disable all TinyMCE input for the given item
     *
     * @param {jQuery} item Section or question
     * @returns {array}
     */
    #disableTinyMce(item) {
        const ids = [];
        item
            .find("textarea")
            .each((index, textarea) => {
                const id = $(textarea).prop("id");
                const editor = tinymce.get(id);

                if (editor) {
                    editor.destroy();
                    ids.push(id);
                }
            });

        return ids;
    }

    /**
     * Enable tinymce for the given items
     *
     * @param {array} ids
     */
    #enableTinyMce(ids) {
        ids.forEach((id) => {
            tinymce.init(window.tinymce_editor_configs[id]);
        });
    }

    #updatePreviewButton() {
        if (window.glpiUnsavedFormChanges) {
            $(this.#target).find('[data-glpi-form-editor-preview-actions]')
                .find('[data-glpi-form-editor-preview-action]').addClass('d-none');
            $(this.#target).find('[data-glpi-form-editor-preview-actions]')
                .find('[data-glpi-form-editor-save-and-preview-action]').removeClass('d-none');
        } else {
            $(this.#target).find('[data-glpi-form-editor-preview-actions]')
                .find('[data-glpi-form-editor-preview-action]').removeClass('d-none');
            $(this.#target).find('[data-glpi-form-editor-preview-actions]')
                .find('[data-glpi-form-editor-save-and-preview-action]').addClass('d-none');
        }
    }

    #setFormDetailsAsActive() {
        const form_details = $(this.#target).find("[data-glpi-form-editor-form-details]");
        this.#setActiveItem(form_details);
    }
}
