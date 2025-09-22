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

/* global getUUID */

export class GlpiFormQuestionTypeItem {
    /**
     * The question type.
     *
     * @type {string}
     */
    #question_type;

    /**
     * Create a new GlpiFormQuestionTypeItem instance.
     *
     * @param {string} question_type The question type.
     */
    constructor(question_type, empty_label) {
        this.#question_type = question_type;

        $(document).on('glpi-form-editor-question-type-changed', (event, question, type) => {
            if (this.#question_type === type) {
                const question_details = question.find('[data-glpi-form-editor-question-type-specific]');
                this.#updateItemsIdDropdownID(question_details);
            }
        });

        $(document).on('glpi-form-editor-question-sub-type-changed', (event, question, sub_type) => {
            if (question.find('[name="type"], [data-glpi-form-editor-original-name="type"]').val() !== this.#question_type) {
                return;
            }

            const select = question.find('[data-glpi-form-editor-question-type-specific] select[name="default_value"], [data-glpi-form-editor-question-type-specific] select[data-glpi-form-editor-original-name="default_value"]');
            const container = select.parent();

            // Add a flag to all children to mark them as to be removed
            container.children().attr('data-to-remove', 'true');

            // Load the new dropdown
            container.load(
                `${CFG_GLPI.root_doc}/ajax/dropdownAllItems.php`,
                {
                    'idtable'            : sub_type,
                    'width'              : '100%',
                    'name'               : select.data('glpi-form-editor-original-name') || select.attr('name'),
                    'aria_label'         : select.attr('aria-label'),
                    'display_emptychoice': 0,
                    'value'              : -1,
                    'valuename'          : empty_label,
                    'toadd'              : {
                        '-1': empty_label
                    },
                },
                () => container.find('[data-to-remove]').remove()
            );
        });
    }

    #updateItemsIdDropdownID(question_details) {
        const id = getUUID();
        question_details.find('span[id^="show_default_value"]')
            .attr('id', `show_default_value${id}`);

        // Replace all occurence of previous id by the new one in script tags
        question_details.find('div script').each((index, script) => {
            // Replace the old itemtype select id by the new one
            const itemtype_select_id = question_details.find('select[name="itemtype"]').attr('id');
            script.text = script.text.replace(/dropdown_itemtype[0-9]+/g, itemtype_select_id);

            // Replace the old id by the new one
            script.text = script.text.replace(/show_default_value[0-9]+/g, `show_default_value${id}`);
            script.text = script.text.replace(/rand:[0-9]+/g, `rand:'${id}'`);

            // Execute the script
            $.globalEval(script.text);
        });
    }
}
