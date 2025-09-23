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

/* global sortable, hasUnsavedChanges, setHasUnsavedChanges */

import { GlpiFormQuestionTypeSelectable } from '/js/modules/Forms/QuestionSelectable.js';

export class GlpiFormQuestionTypeDropdown extends GlpiFormQuestionTypeSelectable {

    /**
     * Create a new GlpiFormQuestionTypeSelectable instance.
     *
     * @param {string} inputType
     * @param {JQuery<HTMLElement>} container
     */
    constructor(inputType = null, container = null, is_from_template = false) {
        super(inputType, container, is_from_template);

        this._container.closest('[data-glpi-form-editor-question-details]')
            .find('div[data-glpi-form-editor-specific-question-options]')
            .find('input[data-glpi-form-editor-original-name=is_multiple_dropdown]')
            .on('change', (event) => {
                this.#updateInputType(event.target.checked ? 'checkbox' : 'radio');
                this.#updateDropdownOptions();
            });

        this._container.closest('[data-glpi-form-editor-question-type-specific]')
            .find('[data-glpi-form-editor-preview-dropdown] select')
            .on('change', (event, data) => {
                // Skip the update if the event is triggered by the dropdown itself
                if (data && data.skip_update) {
                    return;
                }

                // Reset the selection if the empty choice is selected
                if (event.target.value == 0) {
                    this._container.find('input[type=checkbox], input[type=radio]').prop('checked', false);
                    return;
                }

                const selected = $(event.target).select2('data');
                this._container.find('input[type=checkbox], input[type=radio]')
                    .each((index, element) => {
                        const option = selected.filter((option) => option.element.value == $(element).val())[0];
                        $(element).prop('checked', option !== undefined);
                    });
            });

        // The question may have been cloned, so the options need to be updated
        this.#updateDropdownOptions();
    }

    /**
     * Update the input type of the options
     *
     * @param {string} inputType
     */
    #updateInputType(inputType) {
        this._inputType = inputType;
        this._container.closest('[data-glpi-form-editor-question-type-specific]')
            .find('input[type="checkbox"], input[type="radio"]').each((index, element) => {
                $(element).attr('type', inputType);
            });

        // Make visible the right preview dropdown
        this._container.closest('[data-glpi-form-editor-question-type-specific]')
            .find('[data-glpi-form-editor-preview-dropdown]')
            .children().toggleClass('d-none')
            .find('select').prop('disabled', function() {
                return !$(this).prop('disabled');
            });
    }

    /**
     * Update the input type of the options
     *
     * @param {JQuery<HTMLElement>} option
     */
    #updateOptionInputType(option) {
        option.find('input[type="checkbox"], input[type="radio"]').attr('type', this._inputType);
    }

    #updateDropdownOptions() {
        const dropdown = this._container.closest('[data-glpi-form-editor-question-type-specific]')
            .find('[data-glpi-form-editor-preview-dropdown] select');

        const has_changes = window.hasUnsavedChanges();

        // Remove all options, keep the empty choice (value=0)
        dropdown.find('option[value!=0]').remove();

        this._container.find('input[type=text]').each((index, element) => {
            const is_checked = $(element).closest('div').find('input[type=checkbox], input[type=radio]').prop('checked');
            const value = $(element).closest('div').find('input[type=checkbox], input[type=radio]').val();
            const option = $(element).val();

            dropdown.append($('<option>', {
                value: value,
                text: option,
                selected: is_checked,
            })).trigger('change', { skip_update: true });
        });

        // The code above will trigger some changes event but we don't want the
        // form to be set as unsaved if it was not yet modified
        if (!has_changes) {
            window.setHasUnsavedChanges(false);
        }
    }

    _registerOptionListeners(option) {
        super._registerOptionListeners(option);

        option
            .find('input[type="checkbox"], input[type="radio"]')
            .on('change', () => {
                this.#updateDropdownOptions();
            });
    }

    /**
     * Handle the sortable update event.
     */
    _handleSortableUpdate() {
        super._handleSortableUpdate();
        this.#updateDropdownOptions();
    }

    onAddOption(option) {
        this.#updateDropdownOptions();
        this.#updateOptionInputType(option);
    }

    onEditOption() {
        this.#updateDropdownOptions();
    }

    onRemoveOption() {
        this.#updateDropdownOptions();
    }
}
