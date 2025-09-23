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

export class BaseConditionEditorController {
    /**
     * Target container that will display the condition editor
     * @type {HTMLElement}
     */
    #container;

    /**
     * Known form sections
     * @type {array<{uuid: string, name: string}>}
     */
    #form_sections;

    /**
     * Known form questions
     * @type {array<{uuid: string, name: string, type: string, extra_data: object}>}
     */
    #form_questions;

    /**
     * Known form comments
     * @type {array<{uuid: string, name: string}>}
     */
    #form_comments;

    /** @type {?string} */
    #item_uuid;

    /** @type {?string} */
    #item_type;

    /** @type {string} */
    #editorEndpoint;

    constructor(container, item_uuid, item_type, forms_sections, form_questions, form_comments, editorEndpoint) {
        this.#container = container;
        if (this.#container.dataset.glpiConditionsEditorContainer === undefined) {
            console.error(this.#container); // Help debugging by printing the node.
            throw new Error("Invalid container");
        }

        // Load item on which the condition will be defined
        this.#item_uuid = item_uuid;
        this.#item_type = item_type;

        // Set the editor endpoint URL
        this.#editorEndpoint = editorEndpoint;

        // Load form sections
        this.#form_sections = forms_sections;

        // Load linked form questions
        this.#form_questions = form_questions;
        this.#initEventHandlers();

        // Load linked form comments
        this.#form_comments = form_comments;

        // Enable actions
        const disabled_items = this.#container.querySelectorAll(
            '[data-glpi-conditions-editor-enable-on-ready]'
        );
        for (const disabled_item of disabled_items) {
            disabled_item.removeAttribute('disabled');
        }
    }

    async renderEditor() {
        const data = this.#computeData();
        await this.#doRenderEditor(data);
    }

    /**
     * In a dynamic environement such as the form editor, it might be necessary
     * to redefine the known list of available sections.
     */
    setFormSections(form_sections) {
        this.#form_sections = form_sections;
    }

    /**
     * In a dynamic environement such as the form editor, it might be necessary
     * to redefine the known list of available questions.
     */
    setFormQuestions(form_questions) {
        this.#form_questions = form_questions;
    }

    /**
     * In a dynamic environement such as the form editor, it might be necessary
     * to redefine the known list of available comments.
     */
    setFormComments(form_comments) {
        this.#form_comments = form_comments;
    }

    async #doRenderEditor(data) {
        const url = this.#editorEndpoint;
        const content = await $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
        });

        // Note: must use `$().html` to make sure we trigger scripts
        $(this.#container.querySelector('[data-glpi-conditions-editor]')).html(content);

        // The number of conditions may have changed, notify
        this.#notifyConditionsCountChanged(
            data.conditions.length
        );
    }

    #initEventHandlers() {
        // Handle add and delete conditions
        this.#container.addEventListener('click', (e) => {
            const target = e.target;

            // Available buttons
            const add_condition = '[data-glpi-condition-editor-add-condition]';
            const delete_condition = '[data-glpi-condition-editor-delete-condition]';

            if (target.closest(add_condition) !== null) {
                this.#addNewEmptyCondition();
                return;
            } else if (target.closest(delete_condition) !== null) {
                const index = target
                    .closest('[data-glpi-conditions-editor-condition]')
                    .dataset
                    .glpiConditionsEditorConditionIndex
                ;
                this.#deleteCondition(index);
            }
        });

        // Handle change on selected condition items
        // Note: need to be jquery else select2 wont work
        $(this.#container).on(
            'change',
            '[data-glpi-conditions-editor-item], [data-glpi-conditions-editor-value-operator]',
            () => this.renderEditor()
        );

        // Handle strategy changes
        const strategy_inputs = this.#container.querySelectorAll(
            '[data-glpi-conditions-editor-strategy]'
        );
        for (const strategy_input of strategy_inputs) {
            strategy_input.addEventListener('change', (e) => {
                const value = e.target.value;
                const should_displayed_editor = (this.#container
                    .querySelector(`[data-glpi-conditions-editor-display-for-${CSS.escape(value)}]`)
                ) !== null;
                this.#container
                    .querySelector(`[data-glpi-conditions-editor]`)
                    .classList
                    .toggle('d-none', !should_displayed_editor)
                ;
                const event = new CustomEvent("updated_strategy", {
                    detail: {
                        container: this.#container,
                        strategy: value,
                    }
                });
                document.dispatchEvent(event);
            });
        }
    }

    async #addNewEmptyCondition() {
        const data = this.#computeData();
        data.conditions.push({'item': ''});
        await this.#doRenderEditor(data);
    }

    async #deleteCondition(condition_index) {
        const data = this.#computeData();
        data.conditions = data.conditions.filter((_condition, index) => {
            return index != condition_index;
        });
        await this.#doRenderEditor(data);
    }

    #computeData() {
        return {
            sections: this.#form_sections,
            questions: this.#form_questions,
            comments: this.#form_comments,
            conditions: this.#computeDefinedConditions(),
            selected_item_uuid: this.#item_uuid,
            selected_item_type: this.#item_type,
        };
    }

    #computeDefinedConditions() {
        const conditions_data = [];
        const conditions = this.#container.querySelectorAll(
            '[data-glpi-conditions-editor-condition]'
        );

        for (const condition of conditions) {
            const condition_data = {};

            // Try to find a selected logic operator
            const condition_logic_operator = $(condition).find(
                '[data-glpi-conditions-editor-logic-operator]'
            );
            if (condition_logic_operator.length > 0) {
                condition_data.logic_operator = condition_logic_operator.val();
            }

            // Try to find a selected item
            const condition_item = $(condition).find(
                '[data-glpi-conditions-editor-item]'
            );
            if (condition_item.length > 0) {
                condition_data.item = condition_item.val();
            }

            // Try to find a selected value operator
            const condition_value_operator = $(condition).find(
                '[data-glpi-conditions-editor-value-operator]'
            );
            if (condition_value_operator.length > 0) {
                condition_data.value_operator = condition_value_operator.val();
            }

            // Try to find a selected value
            const condition_value = $(condition).find(
                '[data-glpi-conditions-editor-value]'
            );
            if (condition_value.length === 1) {
                condition_data.value = condition_value.val();
            } else if (condition_value.length > 1) {
                condition_data.value = {};
                condition_value.each((index, element) => {
                    const name_parts = element.name.split(/[[\]]+/);
                    const last_part = name_parts[name_parts.length - 2]; // Get the last non-empty part
                    condition_data.value[last_part] = element.value;
                });
            }

            conditions_data.push(condition_data);
        }

        return conditions_data;
    }

    /**
     * Notify that conditions count has changed
     * @param {number} count - Current number of conditions
     * @private
     */
    #notifyConditionsCountChanged(count) {
        const event = new CustomEvent("conditions_count_changed", {
            detail: {
                container: this.#container,
                conditions_count: count,
            }
        });
        document.dispatchEvent(event);
    }
}
