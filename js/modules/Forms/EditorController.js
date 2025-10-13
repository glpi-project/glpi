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

/* global _, tinymce_editor_configs, getUUID, sortable, tinymce, glpi_toast_info, glpi_toast_error, bootstrap, setupAjaxDropdown, setupAdaptDropdown, setHasUnsavedChanges, hasUnsavedChanges */

import { GlpiFormConditionVisibilityEditorController } from '/js/modules/Forms/ConditionVisibilityEditorController.js';
import { GlpiFormConditionValidationEditorController } from '/js/modules/Forms/ConditionValidationEditorController.js';

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
     * Destination conditions
     * @type {array}
     */
    #destination_conditions;

    /**
     * Options for each question type
     * @type {Object}
     */
    #options;

    /**
     * Subtypes options for each question type
     * @type {Object}
     */
    #question_subtypes_options;

    /**
     * @type {array<GlpiFormConditionVisibilityEditorController>}
     */
    #conditions_editors_controllers;

    /**
     * @type {boolean}
     */
    #do_preview_after_save = false;

    /**
     * @type {boolean}
     */
    #is_readonly = false;

    /**
     * Create a new GlpiFormEditorController instance for the given target.
     * The target must be a valid form.
     *
     * @param {string}  target
     * @param {boolean} is_draft
     * @param {string} defaultQuestionType
     * @param {string} templates
     * @param {object} destination_conditions
     */
    constructor(target, is_draft, defaultQuestionType, templates, destination_conditions, is_readonly) {
        this.#target                         = target;
        this.#is_draft                       = is_draft;
        this.#defaultQuestionType            = defaultQuestionType;
        this.#templates                      = templates;
        this.#destination_conditions         = destination_conditions;
        this.#options                        = {};
        this.#question_subtypes_options      = {};
        this.#conditions_editors_controllers = [];
        this.#is_readonly = is_readonly;

        // Validate target
        if ($(this.#target).prop("tagName") != "FORM") {
            throw new Error("Target must be a valid form");
        }


        // Adjust container height and init handlers
        this.#adjustContainerHeight();
        this.#initEventHandlers();
        this.#refreshUX();

        // These computations are only needed if the form will be edited.
        if (!this.#is_readonly) {
            // Validate default question type
            if (this.#getQuestionTemplate(this.#defaultQuestionType).length == 0) {
                throw new Error(`Invalid default question type: ${defaultQuestionType}`);
            }

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

        this.computeState();

        // Some radios wont be displayed correclty as checked as they share the same name.
        // This is fixed by re-checking them after the state has been computed.
        // Not sure if there is a better solution for this, it doesn't feel great.
        this.#refreshCheckedInputs();
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


        // Handle visiblity editor dropdowns
        // The dropdown content will be re-rendered each time it is opened.
        // This ensure the selectable data is always up to date (i.e. the
        // question selector has up to date questions names, contains all newly
        // added questions and do not include deleted questions).
        $(document)
            .on(
                'show.bs.dropdown',
                '[data-glpi-form-editor-visibility-dropdown]',
                (e) => this.#renderVisibilityEditor(
                    $(e.target)
                        .parent()
                        .find('[data-glpi-conditions-editor-container]')
                ),
            );

        // Handle validation editor dropdowns
        // The dropdown content will be re-rendered each time it is opened.
        // This ensure the selectable data is always up to date (i.e. the
        // question selector has up to date questions names, contains all newly
        // added questions and do not include deleted questions).
        $(document)
            .on(
                'show.bs.dropdown',
                '[data-glpi-form-editor-validation-dropdown]',
                (e) => this.#renderValidationEditor(
                    $(e.target)
                        .parent()
                        .find('[data-glpi-conditions-editor-container]')
                ),
            );

        // Compute state before submitting the form
        $(this.#target).on('submit', (event) => {
            try {
                // If a dropdown was closed due to clicking the save button,
                // the focus is not placed on the save button but on the dropdown trigger.
                // We need to simulate the focus on the save button.
                event.originalEvent.submitter.focus();

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
            const save_and_preview_button = $(this.#target).find(
                '[data-glpi-form-editor-save-and-preview-action]'
            );

            // Reset unsaved changes
            this.#updatePreviewButton();

            // Check if a preview action was queued
            if (this.#do_preview_after_save) {
                // Open the preview page in a new tab
                window.open(
                    save_and_preview_button.data('glpi-form-editor-preview-url'),
                    '_blank'
                );
                this.#do_preview_after_save = false;
            }
        });

        $(document).on('glpiFormChangeEvent', () => {
            this.#updatePreviewButton();
        });

        // Handle conditions strategy changes
        document.addEventListener('updated_strategy', (e) => {
            this.#updateConditionBadge(
                $(e.detail.container).closest(
                    '[data-glpi-form-editor-block],[data-glpi-form-editor-section-details],[data-glpi-form-editor-container]'
                ),
                e.detail.strategy
            );
        });

        // Handle conditions count changes
        document.addEventListener('conditions_count_changed', (e) => {
            this.#updateConditionsCount(
                $(e.detail.container).closest('[data-glpi-form-editor-question-extra-details]'),
                e.detail.conditions_count
            );
        });

        // Store previous values for select elements to allow rollback
        $(document).on('select2:selecting', (e) => {
            $(e.target).data('previous-value', $(e.target).val());
        });

        // Register handlers for each possible editor actions using custom
        // data attributes
        const events = ["click", "change", "input"];
        events.forEach((event) => {
            const attribute = `data-glpi-form-editor-on-${event}`;
            $(document)
                .on(event, `${this.#target} [${attribute}]`, async (e) => {
                    // Get action and a jQuery wrapper for the target
                    const target = $(e.currentTarget);
                    const action = target.attr(attribute);

                    try {
                        await this.#handleEditorAction(action, target, e);
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
     * Register new subtypes options for the given question type.
     *
     * @param {string} type    Question type
     * @param {Object} options Subtypes options for the question type
     */
    registerQuestionSubTypesOptions(type, options) {
        this.#question_subtypes_options[type] = options;
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
    async #handleEditorAction(action, target, event) {
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

            // Add a question
            case "add-question":
                this.#addQuestion(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-horizontal-blocks],
                        [data-glpi-form-editor-active-question],
                        [data-glpi-form-editor-active-comment],
                        [data-glpi-form-editor-horizontal-block-placeholder]
                    `),
                );
                break;

            // Delete the target question
            case "delete-question":
                this.#deleteQuestion(
                    target.closest("[data-glpi-form-editor-question]")
                );
                break;

            // Change the type category of the target question
            case "change-question-type-category":
                await this.#changeQuestionTypeCategory(
                    target.closest("[data-glpi-form-editor-question]"),
                    target.val()
                );
                break;

            // Change the type of the target question
            case "change-question-type":
                await this.#changeQuestionType(
                    target.closest("[data-glpi-form-editor-question]"),
                    target.val()
                );
                break;

            case "change-question-sub-type":
                this.#changeQuestionSubType(
                    target.closest("[data-glpi-form-editor-question]"),
                    target.val()
                );
                break;

            // Add a section at the end of the form
            case "add-section":
                this.#addSection(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-horizontal-blocks],
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
            // like refreshUX().
            case "question-sort-update":
                break;

            // Add a comment
            case "add-comment":
                this.#addComment(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-horizontal-blocks],
                        [data-glpi-form-editor-active-question],
                        [data-glpi-form-editor-active-comment],
                        [data-glpi-form-editor-horizontal-block-placeholder]
                    `),
                );
                break;

            // Delete the target comment
            case "delete-comment":
                this.#deleteComment(
                    target.closest("[data-glpi-form-editor-comment]")
                );
                break;

            case "show-visibility-dropdown":
                this.#showVisibilityDropdown(
                    target.closest('[data-glpi-form-editor-block],[data-glpi-form-editor-section-details]')
                );
                break;

            case "show-validation-dropdown":
                this.#showValidationDropdown(
                    target.closest('[data-glpi-form-editor-block],[data-glpi-form-editor-section-details]')
                );
                break;

            case "add-horizontal-layout":
                this.#addHorizontalLayout(
                    target.closest(`
                        [data-glpi-form-editor-active-form],
                        [data-glpi-form-editor-active-section],
                        [data-glpi-form-editor-active-horizontal-blocks],
                        [data-glpi-form-editor-active-question],
                        [data-glpi-form-editor-active-comment]
                    `)
                );
                break;

            case "delete-horizontal-layout":
                this.#deleteHorizontalLayout(
                    target.closest("[data-glpi-form-editor-horizontal-blocks-container]")
                );
                break;

            case "add-horizontal-layout-slot":
                this.#addHorizontalLayoutSlot(
                    target.closest("[data-glpi-form-editor-horizontal-blocks]")
                );
                break;

            case "remove-horizontal-layout-slot":
                this.#removeHorizontalLayoutSlot(
                    target.closest("[data-glpi-form-editor-horizontal-block-placeholder]")
                );
                break;

            case "copy-uuid":
                this.#copyQuestionUuidToClipboard(
                    target.closest('[data-glpi-form-editor-question')
                );
                break;

            case "queue-preview":
                this.#do_preview_after_save = true;
                break;

            case "stop-propagation":
                // Dummy event, do nothing.
                break;

            // Unknown action
            default:
                throw new Error(`Unknown action: ${action}`);
        }

        if (unsaved_changes) {
            setHasUnsavedChanges(true);
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
        const global_block_indices = { 'question': 0, 'comment': 0 };

        // Find all sections
        const sections = $(this.#target).find("[data-glpi-form-editor-section]");
        sections.each((s_index, section) => {
            // Compute state for each sections
            this.#formatInputsNames(
                $(section).find("[data-glpi-form-editor-section-details]"),
                'section',
                s_index
            );
            this.#setSectionRank($(section), s_index);
            this.#setUuid($(section));

            // Find all items for this section (both questions and comments)
            const items = $(section).find('[data-glpi-form-editor-section-blocks]').children("[data-glpi-form-editor-block], [data-glpi-form-editor-horizontal-blocks-container]");

            items.each((vertical_rank, item) => {
                let blocks = $(item);
                const is_horizontal_block = $(item).is("[data-glpi-form-editor-horizontal-blocks-container]");

                // If the item is a horizontal block, we need to find all questions and comments
                if (is_horizontal_block) {
                    blocks = $(item).find("[data-glpi-form-editor-block], [data-glpi-form-editor-horizontal-block-placeholder]");
                }

                blocks.each((horizontal_rank, block) => {
                    if ($(block).is("[data-glpi-form-editor-horizontal-block-placeholder]")) {
                        return;
                    }

                    // Determine the type of the block
                    const itemType = $(block).is("[data-glpi-form-editor-question]") ? 'question' : 'comment';

                    // Compute state for each block
                    this.#formatInputsNames(
                        $(block),
                        itemType,
                        global_block_indices[itemType]
                    );
                    this.#setQuestionRank($(block), vertical_rank, is_horizontal_block ? horizontal_rank : -1);
                    this.#setUuid($(block));
                    this.#setParentSection($(block), $(section));

                    // Increment the index for this item type
                    global_block_indices[itemType]++;
                });
            });
        });
    }

    /**
     * Refresh all UX items that may be modified by mulitple actions.
     */
    #refreshUX() {
        this.#updateAddSectionActionVisiblity();
        this.#addFakeDivToEmptySections();
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
            } else if (type === "temp") {
                // We need to format the input name temporarily
                base_input_index = `_temp[${item_index}]`;
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
                `${base_input_index}[${field}]${postfix}`
            );
        });
    }

    /**
     * Must not be called directly, use computeState() instead.
     *
     * Set the rank of the given item
     *
     * @param {item} section Section
     * @param {number} rank  Rank of the item
     */
    #setSectionRank(section, rank) {
        this.#setItemInput(section, "rank", rank);
    }

    /**
     * Must not be called directly, use computeState() instead.
     *
     * Set the rank of the given item
     *
     * @param {item} question          Question
     * @param {number} vertical_rank   Vertical rank of the item
     * @param {number|null} horizontal_rank Horizontal rank of the item
     */
    #setQuestionRank(question, vertical_rank, horizontal_rank = null) {
        this.#setItemInput(question, "vertical_rank", vertical_rank);
        this.#setItemInput(question, "horizontal_rank", horizontal_rank);

        // Disable horizontal rank input if the question is not in a horizontal block
        const horizontal_rank_input = question.find("input[name='horizontal_rank'], input[data-glpi-form-editor-original-name='horizontal_rank']");
        horizontal_rank_input.prop("disabled", horizontal_rank === null);
    }

    /**
     * Must not be called directly, use computeState() instead.
     *
     * Generate a UUID for each newly created questions and sections.
     * This UUID will be used by the backend to handle updates for news items.
     *
     * @param {jQuery} item Section or question
     */
    #setUuid(item) {
        const uuid = this.#getItemInput(item, "uuid");

        if (uuid == '') {
            // Replace by UUID
            this.#setItemInput(item, "uuid", getUUID());
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
        const uuid = this.#getItemInput(section, "uuid");
        this.#setItemInput(question, "forms_sections_uuid", uuid);
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
        // The event target will either be tinymce's iframe html or body tag.
        // We need to make sure to target the body.
        const body = $(e.target).closest('html').find('body');

        // The body expose its relevant textarea in a `data-id` property
        const id = body.closest("#tinymce").data("id");
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
        const window_height = document.body.offsetHeight;
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
        add_button.find('.add-label').text(_.unescape(__('Save')));
        add_button.prop("title", _.unescape(__('Save')));

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
        const possible_active_items = ['form', 'section', 'question', 'comment', 'horizontal-blocks', 'horizontal-block-placeholder'];

        // Remove current active item
        possible_active_items.forEach((type) => {
            $(this.#target)
                .find(`[data-glpi-form-editor-active-${type}]`)
                .filter((index, element) => {
                    if (type === 'form' || type === 'section') {
                        return true;
                    }

                    return item_container === null
                        || (!$(element).is(item_container)
                        && $(element).has(item_container).length === 0);
                })
                .removeAttr(`data-glpi-form-editor-active-${type}`);
        });

        // Nothing selected, stop here to avoid triggering lazy loading on null.
        if (item_container === null) {
            return;
        }

        // Lazy load dropdowns
        item_container.find('select[data-glpi-loaded=false]').each(function() {
            // Get editor config for this field
            const id = $(this).attr("id");
            const config = window.select2_configs[id];
            if (config.type === "ajax") {
                setupAjaxDropdown(config);
            } else if (config.type === "adapt") {
                setupAdaptDropdown(config);
            }
            $(this).attr('data-glpi-loaded', "true");
        });

        /**
         * Delay the activation of the new item to avoid a rendering bug.
         * I can't explain it, but without this delay,
         * the elements contained in a horizontal layout do not collapse.
         */
        setTimeout(() => {
            // Set new active item if specified
            if (item_container !== null) {
                possible_active_items.forEach((type) => {
                    type = CSS.escape(type);

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

                const horizontal_blocks = item_container.closest("section[data-glpi-form-editor-horizontal-blocks]");
                if (horizontal_blocks.length > 0) {
                    // Set active the horizontal container
                    horizontal_blocks.closest("section[data-glpi-form-editor-horizontal-blocks-container]")
                        .attr("data-glpi-form-editor-active-horizontal-blocks", "");
                }

                if (item_container.length > 0) {
                    this.#scrollToItemIfNeeded(item_container);
                }
            }
        });
    }

    /**
     * Add a block next to the target.
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
        } else if (target.data('glpi-form-editor-horizontal-blocks-container') !== undefined) {
            // Adding a new block after an existing horizontal block
            destination = target;
            action = "after";
        } else if (target.data('glpi-form-editor-horizontal-blocks') !== undefined) {
            // Adding a block at the end of a horizontal block
            destination = target;
            action = "append";
        } else if (target.data('glpi-form-editor-horizontal-block-placeholder') !== undefined) {
            // Adding a block just after the horizontal layout placeholder
            destination = target;
            action = "after";

            // Remove the placeholder just after adding the block
            setTimeout(() => this.#removeHorizontalLayoutSlot(target), 0);
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
     * Add a question at the end of the form
     * @param {jQuery} target   Current position in the form
     */
    #addQuestion(target) {
        // Get template content
        const template = this.#getQuestionTemplate(
            this.#defaultQuestionType
        ).children();

        const new_question = this.#addBlock(target, template);

        // Set UUID
        this.#setUuid(new_question);

        // Mark as active
        this.#setActiveItem(new_question);

        // Focus question's name
        new_question
            .find("[data-glpi-form-editor-question-details-name]")[0]
            .focus();

        // Enable sortable on the new question
        this.#enableSortable(new_question);
    }

    /**
     * Delete the given question.
     * @param {jQuery} question
     */
    #deleteQuestion(question) {
        if (!this.#checkItemConditionDependenciesForDeletion('question', question)) {
            return;
        }

        // Dispose all tooltips and popovers
        question.find('[data-bs-toggle="tooltip"]').tooltip('dispose');

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

        const question_container = question.parent();

        // Remove question and update UX
        question.remove();

        // Remove horizontal layout if needed
        if (
            question_container.is("[data-glpi-form-editor-horizontal-blocks]")
            && question_container.find("[data-glpi-form-editor-block], [data-glpi-form-editor-horizontal-block-placeholder]").length === 0
        ) {
            this.#deleteHorizontalLayout(question_container);
        }
    }

    /**
     * Get the conditions using a specific item
     *
     * @param {string} type Type of item ('question', 'comment', 'section')
     * @param {jQuery} item The element to check
     * @returns {array} Array of condition elements using the item
     */
    #getItemConditionDependencies(type, item) {
        const uuid = this.#getItemInput(item, "uuid");
        if (!uuid) {
            return { // New item without UUID can always be deleted
                conditionsUsingItem: [],
                destinationsUsingItem: []
            };
        }

        const itemIdentifier = `${type}-${uuid}`;

        // Find elements using this item in their conditions
        const conditionsUsingItem = $('[data-glpi-conditions-editor-item]')
            .filter((_index, element) => {
                if (element.value !== itemIdentifier) {
                    return false;
                }

                // Do not report dependencies on itself
                let parent_item;
                if (type === "section") {
                    parent_item = $(element).closest(
                        '[data-glpi-form-editor-section]'
                    );
                } else {
                    parent_item = $(element).closest(
                        '[data-glpi-form-editor-block]'
                    );
                }
                if (parent_item.length !== 1) {
                    return false; // Unexpected
                }
                if (this.#getItemInput(parent_item, "uuid") === uuid) {
                    return false;
                }

                return true;
            })
        ;

        // Find destinations using this item in their conditions
        const destinationsUsingItem = Object.values(this.#destination_conditions)
            .filter(destination =>
                destination.conditions &&
                Object.values(destination.conditions).some(condition =>
                    condition.item === itemIdentifier
                )
            );

        return {
            conditionsUsingItem: conditionsUsingItem,
            destinationsUsingItem: destinationsUsingItem
        };
    }

    /**
     * Check if an item is used in conditions and show delete modal if needed
     *
     * @param {string} type Type of item ('question', 'comment', 'section')
     * @param {jQuery} item The element to check
     * @returns {boolean} True if the item can be deleted, false otherwise
     */
    #checkItemConditionDependenciesForDeletion(type, item) {
        const dependencies = this.#getItemConditionDependencies(type, item);

        // If the item is used in conditions, show modal and prevent deletion
        if (dependencies.conditionsUsingItem.length > 0 || dependencies.destinationsUsingItem.length > 0) {
            this.#showItemHasConditionsModal(
                type,
                dependencies.conditionsUsingItem,
                dependencies.destinationsUsingItem,
                'deletion'
            );
            return false;
        }

        return true;
    }

    /**
     * Get supported value operators for a question type via an API call.
     *
     * @param {Object} questionData The complete question data
     * @returns {Promise<Array>} Promise resolving to array of supported value operators
     */
    async #getSupportedValueOperators(questionData) {
        try {
            const response = await $.ajax({
                url: `${CFG_GLPI.root_doc}/Form/Condition/Editor/SupportedValueOperators`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(questionData),
            });
            return response.operators || [];
        } catch (error) {
            console.error('Error fetching supported value operators:', error);
            return [];
        }
    }

    /**
     * Check if an item is used in conditions and show update modal if needed
     *
     * @param {jQuery} item The element to check
     * @param {string} new_question_type The new question type
     * @returns {Promise<boolean>} Promise resolving to true if the item can be updated, false otherwise
     */
    async #checkItemConditionDependenciesForNewQuestionType(item, new_question_type) {
        const dependencies = this.#getItemConditionDependencies('question', item);

        // Prepare question data for the API call
        const questionData = {
            type: new_question_type,
            // Add any additional question data that might be needed by the API
            uuid: this.#getItemInput(item, "uuid"),
            name: this.#getItemInput(item, "name") || "",
            // Include extra data that might affect supported operators
            extra_data: this.#getQuestionExtraData(item[0])
        };

        // Get supported operators for the new question type
        const supported_value_operators = await this.#getSupportedValueOperators(questionData);

        const unsupported_conditions = dependencies.conditionsUsingItem
            .filter((index, element) => !supported_value_operators.includes($(element)
                .closest('[data-glpi-conditions-editor-condition]')
                .find('[data-glpi-conditions-editor-value-operator]').val()
            ));

        const unsupported_destinations_conditions = dependencies.destinationsUsingItem;

        if (unsupported_conditions.length > 0 || unsupported_destinations_conditions.length > 0) {
            this.#showItemHasConditionsModal(
                'question',
                dependencies.conditionsUsingItem,
                dependencies.destinationsUsingItem,
                'new_question_type'
            );
            return false;
        }

        return true;
    }

    /**
     * Show the modal displaying all items that use the target item in their conditions
     *
     * @param {string} type Type of item ('question', 'comment', 'section')
     * @param {jQuery} conditionsUsingItem jQuery object containing condition elements
     * @param {array} destinationsUsingItem Array of destination objects
     */
    #showItemHasConditionsModal(type, conditionsUsingItem, destinationsUsingItem, modal_name) {
        // Show only the relevant header for this item type
        $(`[data-glpi-form-editor-item-has-conditions-modal="${CSS.escape(modal_name)}"] [data-glpi-form-editor-item-has-conditions-modal-header]`)
            .addClass('d-none')
            .filter(`[data-glpi-form-editor-item-has-conditions-modal-header="${CSS.escape(type)}"]`)
            .removeClass('d-none');

        // Collect all elements using this item in their conditions
        const elementsWithConditions = [];

        // Process form elements (questions and sections)
        conditionsUsingItem.each((_index, element) => {
            // Check if condition is in a question
            const parentItem = $(element).closest('[data-glpi-form-editor-block]');
            if (parentItem.length > 0) {
                elementsWithConditions.push({
                    name: this.#getItemInput(parentItem, "name"),
                    uuid: this.#getItemInput(parentItem, "uuid"),
                    type: 'question',
                    element: parentItem
                });
            } else {
                // Check if condition is in a section
                const parentSection = $(element).closest('[data-glpi-form-editor-section]');
                if (parentSection.length > 0) {
                    elementsWithConditions.push({
                        name: this.#getItemInput(parentSection, "name"),
                        uuid: this.#getItemInput(parentSection, "uuid"),
                        type: 'section',
                        element: parentSection
                    });
                }
            }
        });

        // Add destinations to the list
        destinationsUsingItem.forEach(destination => {
            elementsWithConditions.push({
                name: destination.name,
                type: 'destination'
            });
        });

        // Render the list of elements in the modal
        const modalList = $(`[data-glpi-form-editor-item-has-conditions-modal="${CSS.escape(modal_name)}"] [data-glpi-form-editor-item-has-conditions-list]`);
        modalList.empty();

        const template = $(`[data-glpi-form-editor-item-has-conditions-modal="${CSS.escape(modal_name)}"] [data-glpi-form-editor-item-has-conditions-item-template]`).html();

        // Add each element to the list
        elementsWithConditions.forEach(data => {
            const item = $(template);
            const nameElement = item.find('[data-glpi-form-editor-item-has-conditions-item-name]');

            nameElement.text(data.name);

            if (data.uuid) {
                nameElement.attr('data-glpi-form-editor-item-has-conditions-item-uuid', data.uuid);
            }

            nameElement.attr('data-glpi-form-editor-item-has-conditions-item-type', data.type);

            // For destinations, link to the destinations tab
            if (data.type === 'destination') {
                const tab = $('[data-bs-target^="#tab-Glpi_Form_Destination_FormDestination_"]');
                nameElement.attr('href', tab.attr('href'));
            }

            modalList.append(item);
        });

        // Set up click handlers for the items
        modalList.find('[data-glpi-form-editor-item-has-conditions-item-selector][href="#"]').on('click', e => {
            e.preventDefault();

            // Hide modal
            $(`[data-glpi-form-editor-item-has-conditions-modal="${CSS.escape(modal_name)}"]`).modal('hide');

            // Get the UUID and type
            const clickedElement = $(e.currentTarget);
            const uuid = clickedElement.data('glpi-form-editor-item-has-conditions-item-uuid');
            const type = clickedElement.data('glpi-form-editor-item-has-conditions-item-type');

            // Find and scroll to the element with matching UUID
            this.#findAndHighlightElement(type, uuid);
        });

        // Show the modal
        $(`[data-glpi-form-editor-item-has-conditions-modal="${CSS.escape(modal_name)}"]`).modal('show');
    }

    /**
     * Find an element by type and UUID, make it visible and highlight it
     *
     * @param {string} type The type of element ('question' or 'section')
     * @param {string} uuid The UUID of the element
     */
    #findAndHighlightElement(type, uuid) {
        if (!uuid || !type) {
            return;
        }

        let targetElement;

        if (type === 'question') {
            // Find question with matching UUID
            targetElement = $(this.#target)
                .find('[data-glpi-form-editor-question]')
                .filter((_index, item) => this.#getItemInput($(item), "uuid") === uuid)
                .first();
        } else if (type === 'section') {
            // Find section with matching UUID
            targetElement = $(this.#target)
                .find('[data-glpi-form-editor-section-details]')
                .filter((_index, section) => this.#getItemInput($(section), "uuid") === uuid)
                .first();
        }

        // Make sure we found the element before proceeding
        if (targetElement && targetElement.length > 0) {
            // Make sure parent section is not collapsed
            const parentSection = targetElement.closest('[data-glpi-form-editor-section]');
            if (parentSection.hasClass('section-collapsed')) {
                this.#collaspeSection(parentSection);
            }

            // Set as active
            this.#setActiveItem(targetElement);

            // Scroll to the element
            targetElement.get(0).scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'nearest'
            });
        }
    }

    /**
     * Get the template for the given question type.
     * @param {string} question_type
     * @returns {jQuery}
     */
    #getQuestionTemplate(question_type) {
        return $(this.#templates)
            .find(`[data-glpi-form-editor-question-template="${CSS.escape(question_type)}"]`);
    }

    /**
     * Copy the given template into the given destination.
     *
     * @param {jQuery} target                    Template to copy
     * @param {jQuery} destination               Destination to copy the template into
     * @param {string} action                    How to insert the template (append, prepend, after)
     * @param {boolean} is_from_duplicate_action Is this target is from a question to duplicate
     * @returns {jQuery} Copy of the template
     */
    #copy_template(
        target,
        destination,
        action = "append",
        is_from_duplicate_action = false
    ) {
        const copy = target.clone();

        // Keep track of rich text editors that will need to be initialized
        const tiny_mce_to_init = [];

        // Keep track of select2 that will need to be initialized
        const select2_to_init = [];

        // Keep track of select2 values to restore
        const select2_values_to_restore = [];

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
            config.selector = `#${CSS.escape(id)}`;

            // Store config with udpated ID in case we need to re render
            // this question
            window.tinymce_editor_configs[id] = config;

            // Update on demand id if needed
            const div = $(this).parent().find(
                'div[data-glpi-tinymce-init-on-demand-render]'
            );
            if (div.length > 0) {
                div.attr(
                    'data-glpi-tinymce-init-on-demand-render',
                    id,
                );
            } else {
                tiny_mce_to_init.push(config);
            }
        });

        // Look for select2 to init
        copy.find("select").each(function() {
            let selected_values;
            if (is_from_duplicate_action) {
                // Retrieve selected values
                selected_values = $(this).select2("data");

                // Remove select2 class
                $(this).removeClass("select2-hidden-accessible");

                // Remove data-select2-id
                $(this).removeAttr("data-select2-id");

                // Remove old select2 container
                $(this).next(".select2-container").remove();

                // Add the target select to the select2_to_init list
                target.find(`#${CSS.escape($(this).attr("id"))}`).each(function() {
                    const id = $(this).attr("data-glpi-form-editor-original-id") ?? $(this).attr("id");
                    const config = { ...window.select2_configs[id] };

                    config.field_id = $(this).attr("id");
                    select2_to_init.push(config);
                    select2_values_to_restore[$(this).attr("id")] = selected_values;

                    $(this).select2('destroy');
                });
            }

            const id = $(this).attr("data-glpi-form-editor-original-id") ?? $(this).attr("id");
            const config = { ...window.select2_configs[id] };

            if (id !== undefined && config !== undefined) {
                // Rename id to ensure it is unique
                const uid = getUUID();
                const new_id = `_config_${uid}`;
                $(this).attr("id", new_id);
                $(this).attr("data-glpi-form-editor-original-id", id);

                // Check if label is set for this select2
                if (copy.find(`label[for="${CSS.escape(id)}"]`).length > 0) {
                    // Update label for attribute to match the new ID
                    copy.find(`label[for="${CSS.escape(id)}"]`).attr("for", new_id);
                }

                // Check if a select2 isn't already initialized
                // and if a configuration is available
                if (
                    $(this).hasClass("select2-hidden-accessible") === false
                    && config !== undefined
                ) {
                    config.field_id = new_id;
                    select2_to_init.push(config);

                    if (selected_values) {
                        select2_values_to_restore[new_id] = selected_values;
                    }
                }
            }
        });

        if (is_from_duplicate_action) {
            // We need to temporarily format the inputs names to avoid conflicts with source question
            this.#formatInputsNames(
                copy,
                "temp",
                getUUID()
            );
        }

        // When an input/label are coupled using id/for properties, we must update
        // them to make sure they are unique too.
        copy.find('input[id]').each(function() {
            const id = $(this).attr('id');
            const labels = copy.find(`label[for="${CSS.escape(id)}"]`);
            if (labels.length == 0) {
                return;
            }

            const rand = getUUID();
            const new_id = `${id}_${rand}`;

            $(this).attr('id', new_id);
            labels.each(function() {
                $(this).attr('for', new_id);
            });
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
                const select2_el = setupAjaxDropdown(config);
                if (select2_values_to_restore[config.field_id]) {
                    // Remove options before restoring them
                    select2_el.find("option").remove();

                    for (const value of select2_values_to_restore[config.field_id]) {
                        const option = new Option(value.text, value.id, true, true);
                        select2_el.append(option).trigger("change");
                        select2_el.trigger("select2:select", { data: value });
                    }
                }
            } else if (config.type === "adapt") {
                const select2_el = setupAdaptDropdown(config);
                if (select2_values_to_restore[config.field_id]) {
                    for (const value of select2_values_to_restore[config.field_id]) {
                        select2_el.val(value.id).trigger("change");
                    }
                }
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

        if (is_from_duplicate_action) {
            // Compute state to update inputs names
            this.computeState();
        }

        return copy;
    }

    /**
     * Extract extra data from a question element by collecting values from specific input elements.
     *
     * @param {HTMLElement} question - The question DOM element to extract data from
     * @returns {Object.<string, string>} An object containing name-value pairs of extra data
     *    where keys are the original input names (from data-glpi-form-editor-original-name attribute or the input's name)
     *    and values are the input values. Unchecked checkboxes are excluded.
     * @private
     */
    #getQuestionExtraData(question) {
        const extra_data = {};

        const inputs = question.querySelectorAll(
            "[data-glpi-form-editor-specific-question-extra-data]"
        );
        /** @var {HTMLInputElement} input */
        for (const input of inputs) {
            // Ignore unchecked checkboxes
            if (input.type === "checkbox" && input.checked === false) {
                continue;
            }

            // Try to load the original name of the input.
            let name = input.dataset.glpiFormEditorOriginalName;
            if (name === undefined) {
                name = input.name;
            }

            const is_map = name.indexOf("[") !== -1
                && name.indexOf("]") !== -1
                && name.indexOf("[]") === -1
            ;

            if (is_map) {
                // Handle complex arrays with key and values
                const matches = name.match(/^(.*)\[(.*)\]$/);
                if (matches === null) {
                    throw new Error(`Unexpected input name: ${name}`);
                }
                if (extra_data[matches[1]] === undefined) {
                    extra_data[matches[1]] = {};
                }
                extra_data[matches[1]][matches[2]] = input.value;
            } else {
                // Simple value
                extra_data[name] = input.value;
            }
        }

        return extra_data;
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
        let input = item.find(`input[name="${CSS.escape(field)}"]`);
        if (input.length > 0) {
            return item
                .find(`input[name="${CSS.escape(field)}"]`)
                .val();
        }

        // Input name after computation
        input = item.find(`input[data-glpi-form-editor-original-name="${CSS.escape(field)}"]`);
        if (input.length > 0) {
            return item
                .find(`input[data-glpi-form-editor-original-name="${CSS.escape(field)}"]`)
                .val();
        }

        throw new Error(`Field not found: ${field}`);
    }

    /**
     * Set input value for the given question.
     * @param {jQuery} item Question or section
     * @param {string} field
     * @param {string|number|null} value
     * @returns {jQuery}
     */
    #setItemInput(item, field, value) {
        // Reduce scope when working with a section as we don't want to target
        // its sub-questions inputs
        if (item.data("glpi-form-editor-section") !== undefined) {
            item = item.find("[data-glpi-form-editor-section-details]");
        }

        // Input name before state was computed by #formatInputsNames()
        let input = item.find(`input[name="${CSS.escape(field)}"]`);
        if (input.length > 0) {
            return item
                .find(`input[name="${CSS.escape(field)}"]`)
                .val(value);
        }

        // Input name after computation
        input = item.find(`input[data-glpi-form-editor-original-name="${CSS.escape(field)}"]`);
        if (input.length > 0) {
            return item
                .find(`input[data-glpi-form-editor-original-name="${CSS.escape(field)}"]`)
                .val(value);
        }

        throw new Error(`Field not found: ${field}`);
    }

    /**
     * Set or remove loading state for question type specific content.
     * This makes the content appear disabled and non-interactive during condition checks.
     * @param {jQuery} question Question element
     * @param {boolean} isLoading Whether to set or remove loading state
     */
    #setQuestionTypeSpecificLoadingState(question, isLoading) {
        const specificContent = question.find("[data-glpi-form-editor-question-type-specific]");

        if (isLoading) {
            // Create loading overlay if it doesn't exist
            if (specificContent.find('.glpi-form-editor-loading-overlay').length === 0) {
                const loadingOverlay = $(`
                    <div class="glpi-form-editor-loading-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">${__('Loading...')}</span>
                        </div>
                    </div>
                `);
                specificContent.css('position', 'relative').append(loadingOverlay);
            }

            specificContent
                .css({
                    'opacity': '0.7',
                    'pointer-events': 'none'
                })
                .attr('data-glpi-loading', 'true');
        } else {
            // Remove loading overlay
            specificContent.find('.glpi-form-editor-loading-overlay').remove();

            specificContent
                .css({
                    'opacity': '',
                    'pointer-events': '',
                    'position': ''
                })
                .removeAttr('data-glpi-loading');
        }
    }

    /**
     * Change the type category of the given question.
     * @param {jQuery} question  Question to update
     * @param {string} category  New category
     */
    async #changeQuestionTypeCategory(question, category) {
        // Get the current category
        const old_category = this.#getItemInput(question, "category");

        // Nothing to do if the category is the same
        if (old_category === category) {
            return;
        }

        // Find types available in the new category
        const new_options = $(this.#templates)
            .find(`option[data-glpi-form-editor-question-type="${CSS.escape(category)}"]`);

        // Set loading state for question type specific content
        this.#setQuestionTypeSpecificLoadingState(question, true);

        // Check if the change is allowed based on existing conditions
        if (!(await this.#checkItemConditionDependenciesForNewQuestionType(question, new_options.first().val()))) {
            // Remove loading state before reverting
            this.#setQuestionTypeSpecificLoadingState(question, false);

            // Revert to previous value if change is not allowed
            const previous_category = question.find('[data-glpi-form-editor-on-change="change-question-type-category"]').data('previous-value');
            if (previous_category !== undefined) {
                question.find('[data-glpi-form-editor-on-change="change-question-type-category"]').val(previous_category).trigger('change.select2');
            }

            return false;
        }

        // Remove loading state after successful check
        this.#setQuestionTypeSpecificLoadingState(question, false);

        // Remove current types options
        const types_select = question
            .find("[data-glpi-form-editor-question-type-selector]");
        types_select.children().remove();

        // Copy the new types options into the dropdown
        this.#copy_template(
            new_options,
            types_select,
        );

        // Update the question category
        this.#setItemInput(question, "category", category);

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
    async #changeQuestionType(question, type) {
        // Get the current question type
        const old_type = this.#getItemInput(question, "type");

        // Nothing to do if the type is the same
        if (old_type === type) {
            return;
        }

        // Set loading state for question type specific content
        this.#setQuestionTypeSpecificLoadingState(question, true);

        // Check if the change is allowed based on existing conditions
        if (!(await this.#checkItemConditionDependenciesForNewQuestionType(question, type))) {
            // Remove loading state before reverting
            this.#setQuestionTypeSpecificLoadingState(question, false);

            // Revert to previous value if change is not allowed
            const previous_type = question.find('[data-glpi-form-editor-on-change="change-question-type"]').data('previous-value');
            if (previous_type !== undefined) {
                question.find('[data-glpi-form-editor-on-change="change-question-type"]').val(previous_type).trigger('change.select2');
            }

            return;
        }

        // Remove loading state after successful check
        this.#setQuestionTypeSpecificLoadingState(question, false);

        // Extracted default value
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

        // Update sub question types
        if (this.#question_subtypes_options[type] !== undefined) {
            const sub_types_select = question.find("[data-glpi-form-editor-question-sub-type-selector]");

            // Show sub question type selector
            sub_types_select.closest("div").removeClass("d-none");
            sub_types_select.attr('disabled', false);

            // Remove current sub types options
            sub_types_select.find('optgroup, option').remove();

            // Find sub types available for the new type
            const new_sub_types = this.#question_subtypes_options[type].subtypes;

            // Copy the new sub types options into the dropdown
            for (const category in new_sub_types) {
                const optgroup = $(`<optgroup label="${_.escape(category)}"></optgroup>`);
                for (const [sub_type, label] of Object.entries(new_sub_types[category])) {
                    const option = $(`<option value="${_.escape(sub_type)}">${_.escape(label)}</option>`);
                    optgroup.append(option);
                }
                sub_types_select.append(optgroup);
            }

            // Set the default sub type
            if (this.#question_subtypes_options[type].default_value) {
                sub_types_select.val(this.#question_subtypes_options[type].default_value);
            }

            // Update the field name and aria-label
            sub_types_select.attr("name", this.#question_subtypes_options[type].field_name);
            sub_types_select.attr("aria-label", this.#question_subtypes_options[type].field_aria_label);

            // Remove the "original-name" data attribute to avoid conflicts
            sub_types_select.removeAttr("data-glpi-form-editor-original-name");

            // Trigger sub type change
            sub_types_select.trigger("change");
        } else {
            // Hide sub question type selector
            question.find("[data-glpi-form-editor-question-sub-type-selector]")
                .attr('disabled', true)
                .closest("div").addClass("d-none");
        }

        $(document).trigger('glpi-form-editor-question-type-changed', [question, type]);
    }

    /**
     * Handle the change of the sub type of the given question.
     * @param {jQuery} question Question to update
     * @param {string} sub_type New sub type
     */
    #changeQuestionSubType(question, sub_type) {
        $(document).trigger('glpi-form-editor-question-sub-type-changed', [question, sub_type]);
    }

    /**
     * Add a section at the end of the form.
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
        } else if (target.data('glpi-form-editor-horizontal-blocks-container') !== undefined) {
            // Adding a new section after an existing horizontal block
            // For the existing sections, any questions AFTER the target will
            // be moved into the new section
            destination = target;
            action = "after";
            to_move = $(target).nextAll();
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
        if (!this.#checkItemConditionDependenciesForDeletion('section', section)) {
            return;
        }

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
     * Add a comment block.
     * @param {jQuery} target   Current position in the form
     */
    #addComment(target) {
        // Find the comment template
        const template = $(this.#templates)
            .find("[data-glpi-form-editor-comment-template]")
            .children();

        const new_comment = this.#addBlock(target, template);

        // Set UUID
        this.#setUuid(new_comment);

        // Mark as active
        this.#setActiveItem(new_comment);

        // Focus title's name
        new_comment
            .find("[data-glpi-form-editor-comment-details-name]")[0]
            .focus();

        // Enable sortable on the new comment
        this.#enableSortable(new_comment);
    }

    /**
     * Delete the given comment.
     *
     * @param {jQuery} comment
     */
    #deleteComment(comment) {
        if (!this.#checkItemConditionDependenciesForDeletion('comment', comment)) {
            return;
        }

        // Dispose all tooltips and popovers
        comment.find('[data-bs-toggle="tooltip"]').tooltip('dispose');

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

        const question_container = comment.parent();

        // Remove comment and update UX
        comment.remove();

        // Remove horizontal layout if needed
        if (
            question_container.is("[data-glpi-form-editor-horizontal-blocks]")
            && question_container.find("[data-glpi-form-editor-block], [data-glpi-form-editor-horizontal-block-placeholder]").length === 0
        ) {
            this.#deleteHorizontalLayout(question_container);
        }
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
        } else {
            // Mutliple sections, display all details
            $(this.#target)
                .find("[data-glpi-form-editor-section-details]")
                .removeClass("d-none");
        }
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
     * Enable sortable on the blocks of each section.
     *
     * @param {jQuery} sections jQuery collection of one or more sections
     */
    #enableSortable(sections) {
        // Sortable instance must be unique for each section
        sections.each((index, section) => {
            const blocks_container = $(section)
                .find("[data-glpi-form-editor-section-blocks], [data-glpi-form-editor-horizontal-blocks], [data-glpi-form-editor-question-drag-merge], [data-glpi-form-editor-horizontal-block-placeholder]");

            blocks_container.each((index, container) => {
                const $container = $(container);

                // Common sortable configuration
                const sortableConfig = {
                    // Drag and drop handle selector
                    handle: '[data-glpi-form-editor-question-handle]',

                    // Restrict sortable items
                    items: '[data-glpi-form-editor-block], [data-glpi-form-editor-horizontal-block-placeholder]',

                    // Accept from others sections
                    acceptFrom: '[data-glpi-form-editor-section-blocks], [data-glpi-form-editor-horizontal-blocks]',

                    // Placeholder class
                    placeholder: '<section class="glpi-form-editor-drag-question-placeholder"></section>',
                };


                // Add specific configuration based on container type
                if ($container.is("[data-glpi-form-editor-horizontal-blocks]")) {
                    sortableConfig.maxItems = 4; // Limit the number of blocks in horizontal blocks
                }

                // Initialize sortable with the configuration
                sortable($container, sortableConfig);
            });
        });

        // Keep track on unsaved changes if the sort order was updated
        sections
            .find("[data-glpi-form-editor-section-blocks], [data-glpi-form-editor-horizontal-blocks], [data-glpi-form-editor-question-drag-merge], [data-glpi-form-editor-horizontal-block-placeholder]")
            .on('sortupdate', (e) => {
                // Trigger an action to make sure we use the main entry point
                // where common action related functions are excuted
                this.#handleEditorAction('question-sort-update', null, e);
            });

        // Add a special class while a drag and drop is happening
        sections
            .find("[data-glpi-form-editor-section-blocks], [data-glpi-form-editor-horizontal-blocks], [data-glpi-form-editor-question-drag-merge], [data-glpi-form-editor-horizontal-block-placeholder]")
            .on('sortstart', (e) => {
                // Prevent the "merge" area from being shown for the current item.
                // It prevent some issue in chrome and we don't want to be able
                // to merge the item into itself anyway.
                $(e.detail.item).addClass('form-editor-no-merge');

                // Prevent chrome engine issue where dragend event is triggered if the
                // dom is modified immediatly after dragstart was emitted
                // See: https://groups.google.com/a/chromium.org/g/chromium-bugs/c/YHs3orFC8Dc/m/ryT25b7J-NwJ
                setTimeout(() => {
                    // Html5selectable try to applies 'display: none' to the
                    // current item but it doesn't work because we have a "d-flex"
                    // class that takes over. Manually adding "d-none" get us the
                    // desired effect.
                    $(e.detail.item).addClass('d-none');
                }, 0);

                $(this.#target).addClass("disable-focus").attr('data-glpi-form-editor-sorting', '');

                // If dragged item is active, store it to restore it later
                if ($(e.detail.item).is('[data-glpi-form-editor-active-question],[data-glpi-form-editor-active-comment]')) {
                    $(e.detail.item).attr('data-glpi-form-editor-restore-active-state', '');
                }

                // Remove active states
                this.#setActiveItem(null);
            });

        // Run the post move process if any item was dragged, even if it was not
        // moved in the end (= dragged on itself)
        sections
            .find("[data-glpi-form-editor-section-blocks], [data-glpi-form-editor-horizontal-blocks], [data-glpi-form-editor-question-drag-merge], [data-glpi-form-editor-horizontal-block-placeholder]")
            .on('sortstop', (e) => {
                // The 'sortstop' event trigger twice for a single drag and drop
                // action.
                // The first iteration will have the 'sortable-dragging' class,
                // which we can check to filter it out.
                if ($(e.detail.item).hasClass("sortable-dragging")) {
                    return;
                }

                if (
                    $(e.detail.origin.container).data('glpi-form-editor-horizontal-blocks') !== undefined
                    && $(e.detail.origin.container).find("[data-glpi-form-editor-block], [data-glpi-form-editor-horizontal-block-placeholder]").length === 0
                ) {
                    this.#deleteHorizontalLayout(
                        $(e.detail.origin.container).parent('[data-glpi-form-editor-horizontal-blocks-container]')
                    );
                }

                // Handle case where the item was dragged in a placeholder
                // This is a special case where the item is not moved but replace the placeholder
                if ($(e.detail.item).parent().data('glpi-form-editor-horizontal-block-placeholder') !== undefined) {
                    const placeholder = $(e.detail.item).parent();
                    $(e.detail.item).insertAfter(placeholder);
                    placeholder.remove();
                }

                // Handle case where the item was dragged in a drag and merge area
                // This is a special case where the item is not moved but merged
                // with the question into a horizontal block
                if ($(e.detail.item).parent().data('glpi-form-editor-question-drag-merge') !== undefined) {
                    const blocks = $(e.detail.item).parents('[data-glpi-form-editor-block]').addBack();
                    this.#mergeBlocksIntoHorizontalBlock(blocks);
                }

                this.#handleItemMove($(e.detail.item));

                // Prevent tinymce from stealing focus when dragging someting
                // over it.
                // It seems to be caused by the fact that tinymce expect files
                // to be dragged into it, thus we have to manually disable focus
                // until our drag operation is over.
                $(this.#target).removeClass("disable-focus").removeAttr('data-glpi-form-editor-sorting');
                $('.content-editable-tinymce').removeClass('simulate-focus');

                // Restore active state if needed
                const restore_active_state = $(e.detail.item).attr('data-glpi-form-editor-restore-active-state');
                if (restore_active_state !== undefined) {
                    $(e.detail.item).removeAttr('data-glpi-form-editor-restore-active-state');
                    this.#setActiveItem($(e.detail.item));
                }

                // Remove temporary classes
                $(e.detail.item).removeClass('form-editor-no-merge');
                $(e.detail.item).removeClass('d-none');
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
                    .attr("aria-label", _.unescape(__('Move section: %1$d')).replace("%1$d", name));

                // Set section name
                copy
                    .find("[data-glpi-form-editor-move-section-modal-item-section-name]")
                    .text(name);

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
                    .find(`[data-glpi-form-editor-move-section-modal-uuid="${CSS.escape(section_key)}"]`);

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

        // Update the block count
        this.#updateSectionBlockCount(section);
    }

    /**
     * Update the number of blocks for the given section
     * @param {jQuery} section
     */
    #updateSectionBlockCount(section) {
        const blocks = section
            .find("[data-glpi-form-editor-block]")
            .length;

        // Update the badge with the new block count
        const badge = section.find('span[data-glpi-form-editor-section-block-badge]');
        badge.html(badge.html().trim().replace(/^\d+\s/, `${blocks} `));
    }

    /**
     * Duplicate the given section
     * @param {jQuery} section
     */
    #duplicateSection(section) {
        // TinyMCE must be disabled before we can duplicate the section DOM
        const ids = this.#disableTinyMce(section);
        const new_section = this.#copy_template(section, section, "after", true);
        this.#enableTinyMce(ids);

        this.#setItemInput(new_section, "uuid", '');
        new_section
            .find("[data-glpi-form-editor-question]")
            .each((index, question) => {
                this.#setItemInput($(question), "uuid", '');
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
        const new_question = this.#copy_template(question, question, "after", true);
        this.#enableTinyMce(ids);

        this.#setItemInput(new_question, "uuid", '');
        this.#setActiveItem(new_question);

        // Remove the placeholder if it exists
        question.closest("[data-glpi-form-editor-horizontal-blocks]")
            .find("[data-glpi-form-editor-horizontal-block-placeholder]").first().remove();

        $(document).trigger('glpi-form-editor-question-duplicated', [question, new_question]);
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

        this.#setItemInput(new_comment, "uuid", '');
        this.#setActiveItem(new_comment);

        // Remove the placeholder if it exists
        comment.closest("[data-glpi-form-editor-horizontal-blocks]")
            .find("[data-glpi-form-editor-horizontal-block-placeholder]").first().remove();
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
        if (hasUnsavedChanges()) {
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

    #showVisibilityDropdown(container) {
        container
            .find('[data-glpi-form-editor-visibility-dropdown-container]')
            .removeClass('d-none')
        ;

        const dropdown = container
            .find('[data-glpi-form-editor-visibility-dropdown-container]')
            .find('[data-glpi-form-editor-visibility-dropdown]')
        ;
        bootstrap.Dropdown.getOrCreateInstance(dropdown[0]).show();
    }

    #updateConditionBadge(container, value) {
        // Determine which type of badge we're updating based on the container
        let badgeType = null;
        if (container.find(`[data-glpi-editor-visibility-badge="${CSS.escape(value)}"]`).length > 0) {
            badgeType = 'visibility';
        } else if (container.find(`[data-glpi-editor-validation-badge="${CSS.escape(value)}"]`).length > 0) {
            badgeType = 'validation';
        }

        // Hide all badges of this type
        container.find(`[data-glpi-editor-${CSS.escape(badgeType)}-badge]`)
            .removeClass('d-flex')
            .addClass('d-none');

        // Show only the specific badge for the current value
        container.find(`[data-glpi-editor-${CSS.escape(badgeType)}-badge="${CSS.escape(value)}"]`)
            .removeClass('d-none')
            .addClass('d-flex');
    }

    #updateConditionsCount(container, value) {
        container.find('[data-glpi-editor-validation-conditions-count-badge], [data-glpi-editor-visibility-conditions-count-badge]')
            .html(value);
    }

    #showValidationDropdown(container) {
        container
            .find('[data-glpi-form-editor-validation-dropdown-container]')
            .removeClass('d-none')
        ;

        const dropdown = container
            .find('[data-glpi-form-editor-validation-dropdown-container]')
            .find('[data-glpi-form-editor-validation-dropdown]')
        ;
        bootstrap.Dropdown.getOrCreateInstance(dropdown[0]).show();
    }

    /**
     * To render the condition editor, the unsaved state must be computed
     * and sent to the server.
     *
     * This method compute the available sections of the forms
     */
    #getSectionStateForConditionEditor() {
        this.computeState();
        const sections = [];

        // Extract all sections
        $(this.#target)
            .find("[data-glpi-form-editor-section]")
            .each((_index, section) => {
                sections.push({
                    'uuid': this.#getItemInput($(section), "uuid"),
                    'name': this.#getItemInput($(section), "name"),
                });
            })
        ;

        return sections;
    }

    /**
     * To render the condition editor, the unsaved state must be computed
     * and sent to the server.
     *
     * This method compute the available questions of the forms, the defined
     * conditions and the current selected item.
     *
     * @returns {array<{uuid: string, name: string, type: string, extra_data: object}>}
     */
    #getQuestionStateForConditionEditor() {
        this.computeState();
        const questions = [];

        // Extract all questions
        $(this.#target)
            .find("[data-glpi-form-editor-question]")
            .each((_index, question) => {
                questions.push({
                    'uuid': this.#getItemInput($(question), "uuid"),
                    'name': this.#getItemInput($(question), "name"),
                    'type': this.#getItemInput($(question), "type"),
                    'extra_data': this.#getQuestionExtraData(question),
                });
            })
        ;

        return questions;
    }

    /**
     * To render the condition editor, the unsaved state must be computed
     * and sent to the server.
     *
     * This method compute the available comments of the forms
     */
    #getCommentStateForConditionEditor() {
        this.computeState();
        const comments = [];

        // Extract all comments
        $(this.#target)
            .find("[data-glpi-form-editor-comment]")
            .each((_index, comment) => {
                comments.push({
                    'uuid': this.#getItemInput($(comment), "uuid"),
                    'name': this.#getItemInput($(comment), "name"),
                });
            })
        ;

        return comments;
    }

    async #renderVisibilityEditor(container) {
        let controller = this.#getConditionEditorController(container);

        // Controller lazy loading
        if (controller === null) {
            // Read selected item uuid and type
            const uuid = this.#getItemInput(
                container.closest(
                    '[data-glpi-form-editor-block], [data-glpi-form-editor-section-details], [data-glpi-form-editor-container]'
                ),
                'uuid',
            );
            const type = container.closest(
                '[data-glpi-form-editor-condition-type]'
            ).data('glpi-form-editor-condition-type');

            // Init and register controller
            controller = new GlpiFormConditionVisibilityEditorController(
                container[0],
                uuid,
                type,
                this.#getSectionStateForConditionEditor(),
                this.#getQuestionStateForConditionEditor(),
                this.#getCommentStateForConditionEditor(),
            );
            container.attr(
                'data-glpi-editor-condition-controller-index',
                this.#conditions_editors_controllers.length,
            );
            this.#conditions_editors_controllers.push(controller);
        } else {
            // Refresh form data to make sure it is up to date
            controller.setFormSections(this.#getSectionStateForConditionEditor());
            controller.setFormQuestions(this.#getQuestionStateForConditionEditor());
            controller.setFormComments(this.#getCommentStateForConditionEditor());
        }

        controller.renderEditor();
    }

    async #renderValidationEditor(container) {
        let controller = this.#getConditionEditorController(container);

        // Controller lazy loading
        if (controller === null) {
            // Read selected item uuid and type
            const uuid = this.#getItemInput(
                container.closest(
                    '[data-glpi-form-editor-block], [data-glpi-form-editor-section-details], [data-glpi-form-editor-container]'
                ),
                'uuid',
            );
            const type = container.closest(
                '[data-glpi-form-editor-condition-type]'
            ).data('glpi-form-editor-condition-type');

            // Init and register controller
            controller = new GlpiFormConditionValidationEditorController(
                container[0],
                uuid,
                type,
                this.#getSectionStateForConditionEditor(),
                this.#getQuestionStateForConditionEditor(),
                this.#getCommentStateForConditionEditor(),
            );
            container.attr(
                'data-glpi-editor-condition-controller-index',
                this.#conditions_editors_controllers.length,
            );
            this.#conditions_editors_controllers.push(controller);
        } else {
            // Refresh form data to make sure it is up to date
            controller.setFormSections(this.#getSectionStateForConditionEditor());
            controller.setFormQuestions(this.#getQuestionStateForConditionEditor());
            controller.setFormComments(this.#getCommentStateForConditionEditor());
        }

        controller.renderEditor();
    }

    #getConditionEditorController(container) {
        const controller_index = container.data('glpi-editor-condition-controller-index');
        return this.#conditions_editors_controllers[controller_index] ?? null;
    }

    #refreshCheckedInputs() {
        $(this.#target)
            .find('[data-glpi-editor-refresh-checked]')
            .removeProp('checked')
        ;
        $(this.#target)
            .find('[data-glpi-editor-refresh-checked]')
            .prop('checked', true)
        ;
    }

    #addHorizontalLayout(target) {
        // Find the horizontal block template
        const template = $(this.#templates)
            .find("[data-glpi-form-editor-horizontal-block-template]")
            .children();

        const new_horizontal_block = this.#addBlock(target, template);

        // Enable sortable on the new horizontal block
        this.#enableSortable(new_horizontal_block);
    }

    /**
     * Delete the given horizontal block.
     * @param {jQuery} target Horizontal block to delete
     */
    #deleteHorizontalLayout(target) {
        // Dispose all tooltips and popovers
        target.find('[data-bs-toggle="tooltip"]').tooltip('dispose');

        // If the horizontal block contains blocks, move them just after the horizontal block
        const blocks = target.find('[data-glpi-form-editor-block]');
        blocks.insertAfter(target);

        // Remove horizontal block specific elements
        target.prev('[data-glpi-form-editor-horizontal-blocks-fix-sortable-issue]').remove();
        target.next('[data-glpi-form-editor-horizontal-blocks-fix-sortable-issue]').remove();

        // Remove horizontal block
        target.remove();
    }

    /**
     * Add a placeholder to the horizontal block.
     * @param {jQuery} target Horizontal block
     */
    #addHorizontalLayoutSlot(target) {
        // Find the horizontal block placeholder template
        const template = $(this.#templates)
            .find("[data-glpi-form-editor-horizontal-block-placeholder-template]")
            .children();

        const new_placeholder = this.#addBlock(target, template);

        // Enable sortable
        this.#enableSortable(target);

        // Set new placeholder as active
        this.#setActiveItem(new_placeholder);

        // Dispose all tooltips and popovers
        target.find('[data-bs-toggle="tooltip"]').tooltip('dispose');
    }

    /**
     * Delete the given placeholder from the horizontal block.
     * @param {jQuery} target Placeholder to remove
     */
    #removeHorizontalLayoutSlot(target) {
        // Dispose all tooltips and popovers
        target.find('[data-bs-toggle="tooltip"]').tooltip('dispose');

        // If the placeholder is the last element of the horizontal block, remove the horizontal block
        if (target.parent().find('[data-glpi-form-editor-block], [data-glpi-form-editor-horizontal-block-placeholder]').length == 1) {
            this.#deleteHorizontalLayout(target.parent());
        } else {
            // Remove placeholder
            target.remove();
        }
    }

    /**
     * Merge the given blocks into a horizontal block.
     * @param {jQuery} blocks
     */
    #mergeBlocksIntoHorizontalBlock(blocks) {
        // Find the horizontal block template
        const template = $(this.#templates)
            .find("[data-glpi-form-editor-horizontal-block-template]")
            .children();

        // Copy the new horizontal block template just after the first block
        const horizontal_block = this.#copy_template(
            template,
            blocks.first(),
            "after"
        );

        // Move the blocks into the horizontal block
        blocks.detach().appendTo(
            horizontal_block.find("[data-glpi-form-editor-horizontal-blocks]")
        );

        // Enable sortable on the new horizontal block
        this.#enableSortable(horizontal_block);

        // Remove default template placeholders
        horizontal_block.find('[data-glpi-form-editor-horizontal-block-placeholder]').remove();
    }

    #copyQuestionUuidToClipboard(question) {
        // Generate missing UUIDs
        this.computeState();

        // Read UUID
        const uuid = this.#getItemInput(question, 'uuid');

        // Write to clipbaord and show info toast
        navigator.clipboard.writeText(uuid);
        glpi_toast_info(__("UUID copied successfully to clipboard."));
    }

    #scrollToItemIfNeeded(item_container) {
        const scroll_options = {
            behavior: 'smooth',
            block: 'nearest',
        };
        item_container[0].scrollIntoView(scroll_options);

        // Tinymce is slow to initialize and will mess with the
        // item container height.
        // This mean that our item might no longer be in the
        // scroll view once tinymce is ready and that we need to
        // scroll again.
        // To fight this, we trigger a new scroll a few more time
        // at fixed intervals.
        // We could be more precise by relying on tinymce events
        // but it would be quite complex as there are a lot of cases
        // to think about.
        // Keeping something simpler seems good enough for now.
        setTimeout(() => {
            item_container[0].scrollIntoView(scroll_options);
        }, 100);
        setTimeout(() => {
            item_container[0].scrollIntoView(scroll_options);
        }, 200);
        setTimeout(() => {
            item_container[0].scrollIntoView(scroll_options);
        }, 500);
    }
}
