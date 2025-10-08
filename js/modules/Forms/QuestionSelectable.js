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

/* global sortable, getUUID */

export class GlpiFormQuestionTypeSelectable {

    /**
     * The selectable input type.
     *
     * @type {string}
     */
    _inputType;

    /**
     * The options container.
     *
     * @type {JQuery<HTMLElement>}
     */
    _container;

    /**
     * Create a new GlpiFormQuestionTypeSelectable instance.
     *
     * @param {JQuery<HTMLElement>} container
     */
    constructor(inputType = null, container = null, is_from_template = false) {
        this._inputType = inputType;
        this._container = $(container);

        if (this._container !== null) {
            // Register listeners for existing options
            this._container.children()
                .each((index, option) => this._registerOptionListeners($(option)));

            // Register listeners for the empty option
            this._container
                .siblings('div[data-glpi-form-editor-question-extra-details]')
                .each((index, option) => this._registerOptionListeners($(option)));

            if (is_from_template) {
                // From template = new question added after the initial rendering.
                // We only compute the state in this case as it would be useful
                // during the initial rendering as nothing was changed yet.
                this.#getFormController().computeState();
            }

            // Register sortable event
            this._container.on('sortupdate', () => this._handleSortableUpdate());

            // Restore the checked state
            if (this._inputType === 'radio') {
                this._container
                    .find('input[type="radio"][checked]')
                    .prop('checked', true);
            }

            this.#enableOptionsSortable();
        }
    }

    #getFormController() {
        return this._container.closest('form[data-glpi-form-editor-container]').data('controller');
    }

    /**
     * Get the options from the container.
     *
     * @returns {Array<{value: string, checked: boolean, uuid: string, order: number}>}
     */
    getOptions() {
        const options = [];

        this._container.children().each((index, option) => {
            const input      = $(option).find('input[type="text"]');
            const selectable = $(option).find(`input[type="${CSS.escape(this._inputType)}"]`);
            const order      = $(option).find('input[data-glpi-form-editor-question-option-order]');

            options[index] = {
                value: input.val(),
                checked: selectable.is(':checked'),
                uuid: selectable.val(),
                order: parseInt(order.val()),
            };
        });

        return options;
    }

    /**
     * Set the options.
     *
     * @param {Array<{value: string, checked: boolean, uuid: string, order: number}>} options
     */
    setOptions(options) {
        this._container.empty();

        for (const [, value] of Object.entries(options)) {
            const template = this._container.closest('div[data-glpi-form-editor-question-type-specific]').find('template').get(0);
            const clone = template.content.cloneNode(true);
            const uuid = getUUID(); // Generate a new UUID to avoid duplicates

            $(clone).find('input[type="text"]')
                .val(value.value)
                .attr('name', `options[${uuid}]`);
            $(clone).find(`input[type="${CSS.escape(this._inputType)}"]`)
                .val(uuid)
                .prop('checked', value.checked);
            $(clone).find('input[data-glpi-form-editor-question-option-order]')
                .val(value.order)
                .attr('name', `options_order[${uuid}]`);

            const insertedElement = $(clone).children().appendTo(this._container);

            // Make option visible
            this.#showOption($(insertedElement).find('input[type="text"]'));

            // Register the new option listeners
            this._registerOptionListeners($(insertedElement));

            // Call the onAddOption method
            this.onAddOption($(insertedElement));
        }

        this.#reindexOptions();
        this.#getFormController().computeState();
        this.#enableOptionsSortable();
    }

    /**
     * Called when an option is added.
     *
     * @param {JQuery<HTMLElement>} option
     */
    onAddOption(option) { } // eslint-disable-line no-unused-vars

    /**
     * Called when an option is edited.
     *
     * @param {JQuery<HTMLElement>} option
     */
    onEditOption(option) { } // eslint-disable-line no-unused-vars

    /**
     * Called when an option is removed.
     *
     * @param {JQuery<HTMLElement>} option
     */
    onRemoveOption(option) { } // eslint-disable-line no-unused-vars

    /**
     * Register listeners for the option elements.
     *
     * @param {JQuery<HTMLElement>} option
     */
    _registerOptionListeners(option) {
        option
            .find('input[type="text"]')
            .on('input', (event) => this.#handleOptionChange(event))
            .on('keydown', (event) => this.#handleKeydown(event));

        option
            .find('button[data-glpi-form-editor-question-option-remove]')
            .on('click', (event) => this.#removeOption(event));
    }

    /**
     * Enable sortable functionality for the options container.
     */
    #enableOptionsSortable() {
        sortable(this._container, {
            // Drag and drop handle selector
            handle: '[data-glpi-form-editor-question-option-handle]',

            // Accept from others questions
            acceptFrom: '[data-glpi-form-editor-selectable-question-options]',

            // Placeholder class
            placeholderClass: 'glpi-form-editor-drag-question-option-placeholder mb-1',
        });
    }

    /**
     * Add a option after the specified input element.
     *
     * @param {HTMLElement} input - The input element after which to add the new option.
     * @param {boolean} focus - Whether to focus the new option.
     * @param {boolean} grab_visibility - Whether to show the grab handle for the new option.
     */
    #addOption(input, focus = false, grab_visibility = false) {
        const template = this._container.closest('div[data-glpi-form-editor-question-type-specific]').find('template').get(0);
        const clone = template.content.cloneNode(true);

        $(input).parent().after(clone);

        // Register the new option listeners
        this._registerOptionListeners($(input).parent().next());

        if (focus) {
            $(input).parent().next().find('input[type="text"]').trigger('focus');
        }

        if (grab_visibility) {
            $(input).parent().next().find('i').removeClass('d-none');
            $(input).parent().next().find('i[data-glpi-form-editor-question-option-handle]').css('visibility', 'visible');
        }

        // Update the uuid with a new random value (random number like mt_rand)
        const uuid = getUUID();
        $(input).parent().next().find('input[type="radio"], input[type="checkbox"]').val(uuid);
        $(input).parent().next().find('input[type="text"]').attr('name', `options[${uuid}]`);
        $(input).parent().next().find('input[data-glpi-form-editor-question-option-order]').attr('name', `options_order[${uuid}]`);
        $(input).parent().next().find('input[data-glpi-form-editor-question-option-order]').val(this._container.children().length + 1);

        /**
         * Compute the state to update the input names
         * Required to link radio inputs between them in the same question
         * and unlink them between questions
         */
        this.#getFormController().computeState();

        // Call the onAddOption method
        this.onAddOption($(input).parent().next());
    }

    /**
     * Remove the specified option.
     *
     * @param {Event} event - The click event.
     */
    #removeOption(event) {
        event.target.closest('div').remove();
        event.stopPropagation();

        // Call the onRemoveOption method
        this.onRemoveOption(event.target.closest('div'));
    }

    /**
     * Focus the previous option relative to the specified input element.
     *
     * @param {HTMLElement} input - The input element.
     */
    #focusPreviousOption(input) {
        if ($(input).parent().prev() !== undefined) {
            $(input).parent().prev().find('input[type="text"]').trigger('focus');
        } else {
            const previous = $(input).closest('div[data-glpi-form-editor-question-type-specific]')
                .find('div[data-glpi-form-editor-selectable-question-options]')
                .find('input[type="text"]').last();

            if (previous !== undefined) {
                previous.trigger('focus');
            }
        }
    }

    /**
     * Focus the next option relative to the specified input element.
     *
     * @param {HTMLElement} input - The input element.
     */
    #focusNextOption(input) {
        if ($(input).parent().next().length > 0) {
            $(input).parent().next().find('input[type="text"]').trigger('focus');
        } else {
            const next = $(input).closest('div[data-glpi-form-editor-question-type-specific]')
                .find('input[type="text"]').last();

            if (next !== undefined) {
                next.trigger('focus');
            }
        }
    }

    /**
     * Show the option when the question is focused.
     *
     * @param {HTMLElement} input - The input element.
     */
    #showOption(input) {
        $(input).siblings('i[data-glpi-form-editor-question-option-handle]').css('visibility', 'visible');
        $(input).siblings('input[type="radio"], input[type="checkbox"]').prop('disabled', false);
        $(input).parent().removeAttr('data-glpi-form-editor-question-extra-details');
        $(input).siblings('button[data-glpi-form-editor-question-option-remove]').removeClass('d-none');
    }

    /**
     * Add a option if needed.
     *
     * @param {HTMLElement} input - The input element.
     */
    #addNewOptionIfNeeded(input) {
        const isLast = $(input).closest('div[data-glpi-form-editor-question-type-specific]')
            .find('div[data-glpi-form-editor-selectable-question-options]').parent()
            .children('div').last()
            .find('input[type="text"]').get(0) === input;

        if (isLast) {
            this.#addOption(input);

            // Move the current option in the drag and drop container
            $(input).parent().appendTo($(input).parent().siblings().filter('div[data-glpi-form-editor-selectable-question-options]').last());

            // Focus the new option
            $(input).trigger('focus');

            /**
             * Compute the state to update the input names
             * Required to link radio inputs between them in the same question
             * and unlink them between questions
             */
            this.#getFormController().computeState();
        }
    }

    /**
     * Hide the option when the question is unfocused.
     *
     * @param {HTMLElement} input - The input element.
     */
    #hideOption(input) {
        $(input).parent().attr('data-glpi-form-editor-question-extra-details', '');
        $(input).siblings('input[type="radio"], input[type="checkbox"]').prop('disabled', true);
        $(input).siblings('input[type="radio"], input[type="checkbox"]').prop('checked', false);
    }

    /**
     * Remove the last option if needed.
     * Also remove all previous empty options.
     *
     * @param {HTMLElement} input - The input element.
     */
    #removeLastOptionIfNeeded(input) {
        const isLast = $(input).closest('div[data-glpi-form-editor-selectable-question-options]')
            .children('div').last()
            .find('input[type="text"]').get(0) === input;

        // Remove the last option if the value is empty and if the option is the last
        if (isLast) {
            // Remove all previous empty options
            while ($(input).parent().siblings('div').last().find('input[type="text"]').get(0).value === '') {
                // Call the onRemoveOption method
                this.onRemoveOption($(input).parent());

                $(input).parent().siblings('div').last().remove();
            }

            // Focus the empty option
            $(input).closest('div[data-glpi-form-editor-question-type-specific]')
                .find('input[type="text"]').last().trigger('focus');

            // Call the onRemoveOption method
            this.onRemoveOption($(input).parent());

            // Remove current option
            $(input).parent().remove();
        }
    }

    /**
     * Reindex the order of the options.
     */
    #reindexOptions() {
        // Reindex the order of the options
        this._container.children().each((index, option) => {
            $(option).find('input[data-glpi-form-editor-question-option-order]').val(index);
        });

        // Reindex the order of the empty option
        this._container.closest('div[data-glpi-form-editor-question-type-specific]')
            .find('div[data-glpi-form-editor-question-extra-details]')
            .find('input[data-glpi-form-editor-question-option-order]')
            .val(this._container.children().length);
    }

    /**
     * Handle the input event.
     *
     * @param {InputEvent} event - The input event.
     */
    #handleOptionChange(event) {
        const input = event.target;
        const container = $(input).closest('div[data-glpi-form-editor-question-type-specific]')
            .find('div[data-glpi-form-editor-selectable-question-options]');

        if (input.value) {
            this.#showOption(input);
            this.#addNewOptionIfNeeded(input);
        } else {
            this.#hideOption(input);
            this.#removeLastOptionIfNeeded(input);
        }

        // Call the onEditOption method
        if ($(input).get(0) !== undefined) {
            this.onEditOption($(input).parent());
        }

        // Reload sortable
        sortable(container);
    }

    /**
     * Handle the sortable update event.
     */
    _handleSortableUpdate() {
        this.#reindexOptions();
    }

    /**
     * Handle the keydown event.
     *
     * Enter: Add a option after the current one and focus it.
     * Backspace: Remove the option if the value is empty.
     * Arrow Up or Shift + Tab: Focus the previous option.
     * Arrow Down or Tab: Focus the next option.
     *
     * @param {KeyboardEvent} event - The keydown event.
     */
    #handleKeydown(event) {
        const input = event.target;
        const container = $(input).closest('div[data-glpi-form-editor-selectable-question-options]');

        if (event.key === 'Enter') {
            event.preventDefault();

            // Add a option after the current one and focus it
            if (input.value) {
                // Focus the next option if the current one is not the last and if the next one is empty
                if (
                    $(input).parent().next().length > 0
                    && $(input).parent().next().find('input[type="text"]').get(0).value === ''
                ) {
                    $(input).parent().next().find('input[type="text"]').trigger('focus');
                    return;
                } else if ($(input).parent().next().length == 0) {
                    $(input).closest('div[data-glpi-form-editor-question-type-specific]')
                        .find('input[type="text"]').last().trigger('focus');
                    return;
                }

                this.#addOption(input, true, true);
            }
        } else if (event.key === 'Backspace') {
            const is_last = $(input).closest('div[data-glpi-form-editor-question-type-specific]')
                .find('div[data-glpi-form-editor-selectable-question-options]').parent()
                .children('div').last().find('input[type="text"]').get(0) === input;

            // Remove the option
            if (input.value === '' && !is_last) {
                event.preventDefault();

                this.#focusPreviousOption(input);
                this.#removeOption(event);
            }
        } else if (
            event.key === 'ArrowUp'
            || (event.key === 'Tab' && event.shiftKey)
        ) {
            event.preventDefault();

            this.#focusPreviousOption(input);
        } else if (
            event.key === 'ArrowDown'
            || event.key === 'Tab'
        ) {
            event.preventDefault();

            this.#focusNextOption(input);
        }

        // Reload sortable
        sortable(container);
    }
}
