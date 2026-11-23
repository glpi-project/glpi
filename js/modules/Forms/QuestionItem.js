/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

            // Both the single and multiple item dropdowns must be reloaded so
            // they reflect the newly selected itemtype. The multiple dropdown's
            // name ends with "[]", so it has to be matched explicitly.
            const selects = question.find([
                'select[name="default_value"]',
                'select[name="default_value[]"]',
                'select[data-glpi-form-editor-original-name="default_value"]',
                'select[data-glpi-form-editor-original-name="default_value[]"]',
            ].map((selector) => `[data-glpi-form-editor-question-type-specific] ${selector}`).join(', '));

            selects.each((index, element) => {
                const select = $(element);
                const container = select.parent();
                const name = select.data('glpi-form-editor-original-name') || select.attr('name');
                const is_multiple = name.endsWith('[]');
                const is_disabled = select.prop('disabled');

                // Add a flag to all children to mark them as to be removed
                container.children().attr('data-to-remove', 'true');

                // Load the new dropdown
                container.load(
                    `${CFG_GLPI.root_doc}/ajax/dropdownAllItems.php`,
                    {
                        'idtable'            : sub_type,
                        'width'              : '100%',
                        'name'               : name,
                        'aria_label'         : select.attr('aria-label'),
                        'display_emptychoice': 0,
                        'multiple'           : is_multiple ? 1 : 0,
                        'value'              : -1,
                        'valuename'          : empty_label,
                        'toadd'              : {
                            '-1': empty_label
                        },
                    },
                    () => {
                        container.find('[data-to-remove]').remove();

                        // Restore the enabled/disabled state lost on reload
                        container.find('select, input[type="hidden"]')
                            .prop('disabled', is_disabled);
                    }
                );
            });
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
