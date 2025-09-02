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

/* global glpi_toast_error, getAjaxCsrfToken */

export class GlpiFormConditionEngine
{
    #form_id;

    constructor(form_id)
    {
        this.#form_id = form_id;
    }

    async computeVisiblity(container)
    {
        try {
            // Send data to server for computation and apply results.
            return await this.#computeVisibilityOnBackend({
                answers: this.#getQuestionsData(container)
            });
        } catch (e) {
            console.error(e);
            glpi_toast_error(
                __("An unexpected error occurred")
            );
        }
    }

    #getQuestionsData(container)
    {
        const questions_data = new Map();
        const array_values = new Map(); // Store array values temporarily
        const keyed_array_values = new Map(); // Store array values with keys

        // Map questions that can be used as condition critera by others items.
        const questions_criteria_ids = [];
        const questions_criteria = container.querySelectorAll(
            '[data-glpi-form-renderer-criteria][data-glpi-form-renderer-question]'
        );
        questions_criteria.forEach((node) => {
            questions_criteria_ids.push(node.dataset.glpiFormRendererId);
        });

        // Iterate on the container data
        const data = new FormData(container);
        for (const entry of data.entries()) {
            const key = entry[0];
            const value = entry[1];

            // Skip data unrelated to form answers
            if (key.indexOf('answers_') !== 0 && key.indexOf('_answers_') !== 0) {
                continue;
            }

            if (key.includes('[')) {
                // Handle array values: answers_questionId[] or answers_questionId[key]
                const array_key_regex = /^_?answers_([^[]+)(\[(\d*|[^\]]*)\])$/;
                const match = array_key_regex.exec(key);

                if (match && match[1]) {
                    const question_id = match[1];
                    const array_key = match[3];  // Extract the key inside brackets

                    // Check if this question is a criteria
                    if (questions_criteria_ids.indexOf(question_id) !== -1) {
                        if (array_key === '') {
                            // For answers_questionId[]
                            if (!array_values.has(question_id)) {
                                array_values.set(question_id, []);
                            }
                            if (value !== '') {
                                array_values.get(question_id).push(value);
                            }
                        } else {
                            // For answers_questionId[key]
                            if (!keyed_array_values.has(question_id)) {
                                keyed_array_values.set(question_id, {});
                            }
                            if (value !== '') {
                                keyed_array_values.get(question_id)[array_key] = value;
                            }
                        }
                    }
                }
            } else {
                // Handle simple values: answers_questionId
                const simple_key_regex = /^answers_(.*)$/;
                const match = simple_key_regex.exec(key);

                if (match && match[1]) {
                    const question_id = match[1];

                    // Extra value if it is from a criteria
                    if (questions_criteria_ids.indexOf(question_id) !== -1) {
                        questions_data.set(question_id, value);
                    }
                }
            }
        };

        // Add collected array values to questions_data
        for (const [question_id, values] of array_values.entries()) {
            if (values.length > 0) {
                questions_data.set(question_id, values);
            }
        }

        // Add collected keyed array values to questions_data
        for (const [question_id, values] of keyed_array_values.entries()) {
            if (Object.keys(values).length > 0) {
                questions_data.set(question_id, values);
            }
        }

        return questions_data;
    }

    async #computeVisibilityOnBackend(data)
    {
        // Build POST data
        const form_data = new FormData();
        form_data.append('form_id', this.#form_id);

        // Process answers with proper handling of nested structures
        for (const [question_id, value] of data.answers.entries()) {
            this.#appendFormDataValue(form_data, `answers[${question_id}]`, value);
        }

        // Send request
        const url = `${CFG_GLPI.root_doc}/Form/Condition/Engine`;
        const response = await fetch(url, {
            method: 'POST',
            body: form_data,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
            }
        });

        // Handle server errors
        if (!response.ok) {
            throw new Error(response.status);
        }

        return response.json();
    }

    /**
     * Recursively append values to FormData with proper key formatting
     * @param {FormData} form_data - The FormData object
     * @param {string} key - The current key
     * @param {*} value - The value to append
     */
    #appendFormDataValue(form_data, key, value) {
        if (value === null || value === undefined) {
            return;
        }

        if (Array.isArray(value)) {
            // Handle array values with empty bracket notation
            for (const item of value) {
                this.#appendFormDataValue(form_data, `${key}[]`, item);
            }
        } else if (typeof value === 'object' && value !== null) {
            // Handle object values with key notation
            for (const [objKey, objValue] of Object.entries(value)) {
                this.#appendFormDataValue(form_data, `${key}[${objKey}]`, objValue);
            }
        } else {
            // Handle primitive values
            form_data.append(key, value);
        }
    }
}

