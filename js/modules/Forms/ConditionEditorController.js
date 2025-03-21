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


export class GlpiFormConditionEditorController
{
    /**
     * Target containerthat will display the condition editor
     * @type {HTMLElement}
     */
    #container;

    /**
     * Known form questions
     * @type {array<{uuid: string, name: string, type: string, extra_data: object}>}
     */
    #form_questions;

    /** @type {?string} */
    #item_uuid;

    /** @type {?string} */
    #item_type;

    constructor(container, item_uuid, item_type, form_questions)
    {
        this.#container = container;
        if (this.#container.dataset.glpiConditionsEditorContainer === undefined) {
            console.error(this.#container); // Help debugging by printing the node.
            throw new Error("Invalid container");
        }

        // Load item on which the condition will be defined
        this.#item_uuid = item_uuid;
        this.#item_type = item_type;

        // Load linked form questions
        this.#form_questions = form_questions;
        this.#initEventHandlers();

        // Enable actions
        const disabled_items = this.#container.querySelectorAll(
            '[data-glpi-conditions-editor-enable-on-ready]'
        );
        for (const disabled_item of disabled_items) {
            disabled_item.removeAttribute('disabled');
        }
    }

    async renderEditor()
    {
        const data = this.#computeData();
        await this.#doRenderEditor(data);
    }

    /**
     * In a dynamic environement such as the form editor, it might be necessary
     * to redefine the known list of available questions.
     */
    setFormQuestions(form_questions)
    {
        this.#form_questions = form_questions;
    }

    async #doRenderEditor(data)
    {
        const content = await $.post('/Form/Condition/Editor', {
            form_data: data,
        });

        // Note: must use `$().html` to make sure we trigger scripts
        $(this.#container.querySelector('[data-glpi-conditions-editor]')).html(content);
    }

    #initEventHandlers()
    {
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
            '[data-glpi-conditions-editor-item]',
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
                    .querySelector(`[data-glpi-conditions-editor-display-for-${value}]`)
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

    async #addNewEmptyCondition()
    {
        const data = this.#computeData();
        data.conditions.push({'item': ''});
        await this.#doRenderEditor(data);
    }

    async #deleteCondition(condition_index)
    {
        const data = this.#computeData();
        data.conditions = data.conditions.filter((_condition, index) => {
            return index != condition_index;
        });
        await this.#doRenderEditor(data);
    }

    #computeData()
    {
        return {
            questions: this.#form_questions,
            conditions: this.#computeDefinedConditions(),
            selected_item_uuid: this.#item_uuid,
            selected_item_type: this.#item_type,
        };
    }

    #computeDefinedConditions()
    {
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
            if (condition_value.length > 0) {
                condition_data.value = condition_value.val();
            }

            conditions_data.push(condition_data);
        }

        return conditions_data;
    }
}
