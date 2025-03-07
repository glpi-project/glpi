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
        container = document.querySelector(container);

        try {
            // Send data to server for computation and apply results.
            return await this.#computeVisibilityOnBackend({
                answers: this.#getQuestionsData(container)
            });
        } catch (e) {
            console.error(e);
            glpi_toast_error(
                __("An unexpected error occurred.")
            );
        }
    }

    #getQuestionsData(container)
    {
        const questions_data = new Map();
        const array_values = new Map(); // Store array values temporarily

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
            if (key.indexOf('answers_') !== 0) {
                continue;
            }

            if (key.includes('[')) {
                // Handle array values: answers_questionId[] or answers_questionId[key]
                const array_key_regex = /^answers_([^[]+)(\[\d*\]|\[[^\]]*\])$/;
                const match = array_key_regex.exec(key);

                if (match && match[1]) {
                    const question_id = match[1];

                    // Check if this question is a criteria
                    if (questions_criteria_ids.indexOf(question_id) !== -1) {
                        // Initialize array for this question if needed
                        if (!array_values.has(question_id)) {
                            array_values.set(question_id, []);
                        }

                        // Add value to the array
                        if (value !== '') {
                            array_values.get(question_id).push(value);
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
        }

        // Add collected array values to questions_data
        for (const [question_id, values] of array_values.entries()) {
            if (values.length > 0) {
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
        for (const [question_id, value] of data.answers.entries()) {
            if (Array.isArray(value)) {
                // Handle array values
                for (const item of value) {
                    form_data.append(`answers[${question_id}][]`, item);
                }
            } else {
                // Handle single values
                form_data.append(`answers[${question_id}]`, value);
            }
        }

        // Included direct access token if needed.
        // Not great to have to do this normally, TOOD: find a better way.
        const url_params = new URLSearchParams(window.location.search);
        if (url_params.has('token')) {
            form_data.append('token', url_params.get('token'));
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
}

