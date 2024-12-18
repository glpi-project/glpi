/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/* global tinymce, getUUID, setupAjaxDropdown, setupAdaptDropdown */

export class GlpiFormFieldDestinationAssociatedItem {
    /**
     * Name of the itemtype field
     * @type {string}
     */
    #itemtype_name;

    /**
     * Name of the items id field
     * @type {string}
     */
    #items_id_name;

    /**
     * Container for associated items
     * @type {jQuery<HTMLElement>}
     */
    #associated_items_container;

    /**
     * Template for specific values (jquery selector)
     * @type {jQuery<HTMLElement>}
     */
    #specific_values_template;

    /**
     * Button to add associated items (jquery selector)
     * @type {jQuery<HTMLElement>}
     */
    #add_associated_item_button;

    /**
     * @param {jQuery<HTMLElement>} specific_values_extra_field
     */
    constructor(
        specific_values_extra_field,
        itemtype_name,
        items_id_name,
    ) {
        this.#itemtype_name = itemtype_name;
        this.#items_id_name = items_id_name;
        this.#associated_items_container = specific_values_extra_field.find('[data-glpi-specific-values-extra-field-container]');
        this.#specific_values_template = specific_values_extra_field.find('[data-glpi-specific-values-extra-field-template]');
        this.#add_associated_item_button = specific_values_extra_field.find('[data-glpi-add-associated-item-button]');

        this.#add_associated_item_button.on('click', () => {
            this.#addAssociatedItemField();
        });

        this.#associated_items_container.find('[data-glpi-remove-associated-item-button]')
            .each((index, button) => this.#registerOnRemoveAssociatedItem($(button)));
    }

    #addAssociatedItemField() {
        // Clone the template content and append it to the container
        const template_content = this.#specific_values_template.html();
        this.#associated_items_container.append(template_content);

        // Get the last item added
        const template = this.#associated_items_container.find('[data-glpi-specific-values-extra-field-item]').last();

        // Initialize dropdowns and register events
        this.#initDropdowns(template);
        this.#registerOnRemoveAssociatedItem(template.find('[data-glpi-remove-associated-item-button]'));
    }

    #registerOnRemoveAssociatedItem(button) {
        button.on('click', () => {
            button.closest('[data-glpi-specific-values-extra-field-item]').remove();
        });
    }

    #initDropdowns(field) {
        const itemtype_name = this.#itemtype_name;
        const items_id_name = this.#items_id_name;

        field.find("select").each(function () {
            const id = $(this).attr("id");
            const config = window.select2_configs[id];

            if (id !== undefined && config !== undefined) {
                // Rename id to ensure it is unique
                const uid = getUUID();
                $(this).attr("id", uid);

                // Check if a select2 isn't already initialized
                // and if a configuration is available
                if (
                    $(this).hasClass("select2-hidden-accessible") === false
                    && config !== undefined
                ) {
                    config.field_id = uid;
                    if (config.type === "ajax") {
                        setupAjaxDropdown(config);
                    } else if (config.type === "adapt") {
                        setupAdaptDropdown(config);
                    }
                }
            }
        });


        const id = getUUID();
        const itemtype_select_id = field.find(`select[name="${itemtype_name}"]`).attr('id');

        // Replace the old id by the new one
        field.find(`span[id^="show_${items_id_name.replace(/[[\]]/g, '_')}"]`)
            .attr('id', `show_${items_id_name.replace(/[[\]]/g, '_')}${id}`);

        // Replace all occurence of previous id by the new one in script tags
        field.find('script').each((index, script) => {
            // Replace the old itemtype select id by the new one
            script.text = script.text.replace(
                new RegExp(`dropdown_${itemtype_name.replace(/[[\]]/g, '_')}[0-9]+`, 'g'),
                itemtype_select_id
            );

            // Replace the old id by the new one
            script.text = script.text.replace(
                new RegExp(`show_${items_id_name.replace(/[[\]]/g, '_')}[0-9]+`, 'g'),
                `show_${items_id_name.replace(/[[\]]/g, '_')}${id}`
            );
            script.text = script.text.replace(/rand:[0-9]+/g, `rand:'${id}'`);

            // Execute the script
            $.globalEval(script.text);
        });
    }
}
