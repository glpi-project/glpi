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

export class GlpiFormItemAdvancedConfig {
    // Static instance for singleton pattern
    static instance = null;

    #common_tree_dropdown_itemtypes = [];

    /**
     * Constructor for the Item Advanced Configuration
     *
     * @param {Array} common_tree_dropdown_itemtypes - List of itemtypes that are CommonTreeDropdown
     */
    constructor(common_tree_dropdown_itemtypes = []) {
        // Prevent multiple initializations
        if (GlpiFormItemAdvancedConfig.instance !== null) {
            return GlpiFormItemAdvancedConfig.instance;
        }

        // Register event listener for question sub-type changes
        this.registerEventListeners();

        // Store instance
        GlpiFormItemAdvancedConfig.instance = this;

        // Store the itemtypes that are CommonTreeDropdown
        this.#common_tree_dropdown_itemtypes = common_tree_dropdown_itemtypes;
    }

    /**
     * Find the container element for the dropdown advanced configuration
     *
     * @param {jQuery} question The question element
     * @returns {jQuery|null} The container element or null if not found
     */
    findContainer(question) {
        const container = question.find(
            `[data-glpi-form-editor-item-dropdown-advanced-configuration]`
        );

        return container.length > 0 ? container : null;
    }

    /**
     * Register all necessary event listeners
     */
    registerEventListeners() {
        $(document).on('glpi-form-editor-question-sub-type-changed',
            (event, question, sub_type) => {
                // Ensure the event is for an Item question
                if (
                    question.find('[data-glpi-form-editor-original-name="type"], [name="type"]').length === 0
                    || question.find('[data-glpi-form-editor-original-name="type"], [name="type"]').val() !== 'Glpi\\Form\\QuestionType\\QuestionTypeItem'
                ) {
                    return;
                }

                const container = this.findContainer(question);
                if (!container) {
                    return;
                }

                this.updateAdvancedConfigVisibility(container, sub_type);
            }
        );
    }

    updateAdvancedConfigVisibility(container, new_sub_type) {
        const dropdown_container = container.closest('[data-glpi-form-editor-advanced-question-configuration]')
            .parents('[data-glpi-form-editor-question-extra-details]');

        // Show button only for sub-type that are CommonTreeDropdown
        if (this.#common_tree_dropdown_itemtypes.includes(new_sub_type)) {
            dropdown_container.show();
            dropdown_container.attr('data-glpi-form-editor-advanced-question-configuration-visible', 'true');
        } else {
            dropdown_container.hide();
            dropdown_container.removeAttr('data-glpi-form-editor-advanced-question-configuration-visible');
        }
    }
}
