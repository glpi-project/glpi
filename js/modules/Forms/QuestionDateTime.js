/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

export class GlpiFormQuestionTypeDateTime {
    /**
     * Placeholders for different input types
     *
     * @type {Object}
     */
    #placeholders;

    /**
     * Create a new GlpiFormQuestionTypeDateTime instance.
     *
     * @param {Object} placeholders Placeholders for inputs and default values
     */
    constructor(placeholders) {
        this.#placeholders = placeholders;

        // Initialize event listeners for checkboxes
        $(document).on('change', 'input[id^="is_default_value_current_time_"]', (e) => {
            const questionSection = $(e.target).closest('section[data-glpi-form-editor-question]');
            this.updateDateAndTimeInputType(questionSection);
        });

        $(document).on('change', 'input[id^="is_date_enabled_"], input[id^="is_time_enabled_"]', (e) => {
            const input = e.target;
            const isChecked = $(input).is(':checked');
            const questionSection = $(input).closest('section[data-glpi-form-editor-question]');
            const otherInput = questionSection.find('input[id^="is_date_enabled_"], input[id^="is_time_enabled_"]')
                .not(`[name="${CSS.escape(input.name)}"]`);

            // Ensure at least one option is checked
            if (!isChecked && otherInput.not(':checked').length) {
                otherInput.prop('checked', true);
            }

            this.updateDateAndTimeInputType(questionSection);
        });

        // Make the update function globally accessible (needed for templates)
        window.updateDateAndTimeInputType = (questionSection) => this.updateDateAndTimeInputType(questionSection);
    }

    /**
     * Convert value to appropriate format based on the target input type
     *
     * @param {string} currentValue The current input value
     * @param {boolean} isDateEnabled Whether date input is enabled
     * @param {boolean} isTimeEnabled Whether time input is enabled
     * @returns {string} The converted value
     */
    #convertValue(currentValue, isDateEnabled, isTimeEnabled) {
        if (!currentValue) {
            return '';
        }

        try {
            const hasDate = currentValue.includes('T') ? currentValue.split('T')[0] :
                !currentValue.includes(':') ? currentValue : null;
            const hasTime = currentValue.includes('T') ? currentValue.split('T')[1] :
                currentValue.includes(':') ? currentValue : null;

            if (isDateEnabled && isTimeEnabled) {
                // datetime-local format
                const date = hasDate || new Date().toISOString().split('T')[0];
                const time = hasTime || new Date().toTimeString().substr(0, 5);
                return `${date}T${time}`;
            } else if (isTimeEnabled) {
                return hasTime || '';
            } else if (isDateEnabled) {
                return hasDate || '';
            }
        } catch (e) {
            console.warn('Error converting datetime value', e);
        }

        return '';
    }

    /**
     * Update the input type and placeholder based on selected options
     *
     * @param {jQuery} questionSection The question section element
     */
    updateDateAndTimeInputType(questionSection) {
        const dateInput = questionSection.find('input[id^="date_input_"]');
        const isDefaultValueCurrentTime = questionSection
            .find('input[id^="is_default_value_current_time_"]');
        const isDateEnabled = questionSection
            .find('input[id^="is_date_enabled_"]')
            .is(':checked');
        const isTimeEnabled = questionSection
            .find('input[id^="is_time_enabled_"]')
            .is(':checked');

        // Store current value before changing type
        const currentValue = dateInput.val();

        // Determine input type and placeholders
        let inputType = 'date';
        let inputPlaceholder = this.#placeholders.input.date;
        let defaultValuePlaceholder = this.#placeholders.default_value.date;

        if (isDateEnabled && isTimeEnabled) {
            inputType = 'datetime-local';
            inputPlaceholder = this.#placeholders.input['datetime-local'];
            defaultValuePlaceholder = this.#placeholders.default_value['datetime-local'];
        } else if (isTimeEnabled) {
            inputType = 'time';
            inputPlaceholder = this.#placeholders.input.time;
            defaultValuePlaceholder = this.#placeholders.default_value.time;
        }

        // Update input attributes
        dateInput.prop('type', inputType);
        dateInput.prop('placeholder', inputPlaceholder);
        dateInput.prop('disabled', false);

        // Override type if "use current time" is checked
        if (isDefaultValueCurrentTime.is(':checked')) {
            dateInput.prop('type', 'text');
            dateInput.val(''); // Clear the value
            dateInput.prop('disabled', true);
        } else {
            // Convert value to match new input type
            const newValue = this.#convertValue(currentValue, isDateEnabled, isTimeEnabled);
            if (newValue) {
                dateInput.val(newValue);
            }
        }

        isDefaultValueCurrentTime.siblings('span').text(defaultValuePlaceholder);
    }
}
