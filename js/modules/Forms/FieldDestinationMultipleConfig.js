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

/* global getUUID, setupAjaxDropdown, setupAdaptDropdown */

export class GlpiFormFieldDestinationMultipleConfig {
    /** @type {jQuery<HTMLElement>} */
    #container;

    /** @type {jQuery<HTMLElement>} */
    #template;

    /** @type {jQuery<HTMLElement>} */
    #add_button;

    /** @type {Set<string>} */
    #reusable_strategies;

    /** @type {number} */
    #last_used_dropdown_index = 0;

    constructor(container, reusable_strategies = new Set()) {
        this.#container = container;
        this.#template = container.find('[data-glpi-itildestination-field-config-template]');
        this.#add_button = container.find('[data-glpi-itildestination-add-field-config]');
        this.#reusable_strategies = reusable_strategies instanceof Set ? reusable_strategies : new Set(reusable_strategies);

        // Register events
        this.#container.find('[data-glpi-itildestination-remove-field-config]')
            .on('click', (e) => this.#removeFieldConfig(e.target.closest('[data-glpi-itildestination-field-config]')));
        this.#add_button.on('click', () => this.#addFieldConfig());
        this.#container.find('[data-glpi-itildestination-field-config]')
            .each((index, field) => this.#getStrategySelect(field).on('change', (e) => this.#handleStrategyChange(e)));

        // Trigger change event to initialize the display
        this.#container.find('[data-glpi-itildestination-field-config]').each((index, field) => {
            this.#getStrategySelect(field).trigger('change');
        });

        this.#handleAddButtonVisibility();
    }

    /**
     * Check if a strategy can be reused
     * @param {string} strategy
     * @returns {boolean}
     */
    isStrategyReusable(strategy) {
        return this.#reusable_strategies.has(strategy);
    }

    /**
     * Add a field config
     */
    #addFieldConfig() {
        const selected_strategies = [];
        this.#container.find('[data-glpi-itildestination-field-config]').each((index, field) => {
            const strategy = this.#getStrategySelect(field).find('option').filter(':selected').val();
            // Only add to selected_strategies if it's not reusable
            if (!this.isStrategyReusable(strategy)) {
                selected_strategies.push(strategy);
            }
        });


        // Get template HTML and process scripts in place
        const template_html = this.#template.html();
        const temp_container = $('<div>').html(template_html);

        // Replace __INDEX__ placeholders with actual index
        const current_index = this.#container.find('[data-glpi-itildestination-field-config]').length;

        // Process scripts in place to replace __INDEX__ before inserting
        temp_container.find('script').each((index, script) => {
            script.innerHTML = script.innerHTML.replace(/__INDEX__/g, current_index);
        });

        // Insert the HTML with processed scripts
        const new_config_field = $(temp_container.html()).insertBefore(this.#add_button);

        new_config_field.find('[name*="__INDEX__"]').each((index, element) => {
            const name = $(element).attr('name');
            if (name) {
                $(element).attr('name', name.replace(/__INDEX__/g, current_index));
            }
        });

        // Replace __INDEX__ in id attributes, selects are handled separately
        new_config_field.find('[id*="__INDEX__"]').each((index, element) => {
            const id = $(element).attr('id');
            if (id) {
                $(element).attr('id', id.replace(/__INDEX__/g, current_index));
            }
        });

        new_config_field.find('[data-glpi-itildestination-remove-field-config]')
            .on('click', (e) => this.#removeFieldConfig(e.target.closest('[data-glpi-itildestination-field-config]')));

        new_config_field.find('select').find('option').each((index, option) => {
            if ($(option).val() != 0 && selected_strategies.includes($(option).val())) {
                $(option).prop('disabled', true);
            }
        });

        this.#getStrategySelect(new_config_field).on('change', (e) => this.#handleStrategyChange(e));

        // Dropdowns initialization must be done after the field is added to the DOM and the script tags are executed
        // to ensure that the select2_configs are available.
        setTimeout(() => this.#initDropdowns(new_config_field));
        this.#handleAddButtonVisibility();
    }

    /**
     * Remove a field config
     * @param {jQuery<HTMLElement>} field
     */
    #removeFieldConfig(field) {
        field.remove();

        this.#handleStrategyChange();
        this.#handleAddButtonVisibility();
    }

    #initDropdowns(field) {
        field.find("select").each(function () {
            const id = $(this).attr("id");
            const config = window.select2_configs[id];

            if (id !== undefined && config !== undefined) {
                // Rename id to ensure it is unique
                const uid = getUUID();
                const new_id = `_config_${uid}`;
                $(this).attr("id", new_id);

                // Check if a select2 isn't already initialized
                // and if a configuration is available
                if (
                    $(this).hasClass("select2-hidden-accessible") === false
                    && config !== undefined
                ) {
                    config.field_id = new_id;
                    if (config.type === "ajax") {
                        setupAjaxDropdown(config);
                    } else if (config.type === "adapt") {
                        setupAdaptDropdown(config);
                    }
                }
            }
        });

        field.find('[data-glpi-items-from-itemtypes-dropdown]').each((_index, dropdown) => {
            const id = this.#last_used_dropdown_index++;
            const itemtype_select = $(dropdown).find('select').first();

            // Replace the old id by the new one
            const items_id_select_container = $(dropdown).find(`span[id^="show_"]`);
            const items_id_name = items_id_select_container.attr('id');
            items_id_select_container.attr('id', `${items_id_name}${id}`);

            // Replace all occurence of previous id by the new one in script tags
            $(dropdown).find('script').each((index, script) => {

                // Replace the old itemtype select id by the new one
                script.text = script.text.replace(
                    /\$\(['"]#dropdown_[^)]+['"]\)/g,
                    `$("#${CSS.escape(itemtype_select.attr('id'))}")`
                );

                // Replace the old id by the new one
                script.text = script.text.replace(
                    /\$\(['"]#show_[^)]+['"]\)/g,
                    `$("#${CSS.escape(items_id_name)}${id}")`
                );

                script.text = script.text.replace(/rand:[0-9]+/g, `rand:'${id}'`);

                // Execute the script
                $.globalEval(script.text);
            });
        });
    }

    #handleAddButtonVisibility() {
        if (this.#reusable_strategies.size > 0) {
            // If there are reusable strategies, we don't limit the number of field configs
            this.#add_button.removeClass('d-none');
            return;
        }

        const count_options = this.#container.find('[data-glpi-itildestination-field-config]')
            .find('select[data-glpi-itildestination-strategy-select]').first().find('option').length;
        const count_field_configs = this.#container.find('[data-glpi-itildestination-field-config]').length;

        this.#add_button.toggleClass('d-none', count_field_configs >= count_options);
    }

    /**
     * Handle the change of a strategy
     * @param {Event} [event]
     */
    #handleStrategyChange(event = null) {
        const selected_strategies = [];
        this.#container.find('[data-glpi-itildestination-field-config]').each((index, field) => {
            const strategy = this.#getStrategySelect(field).find('option').filter(':selected').val();
            // Only add to selected_strategies if it's not reusable
            if (!this.isStrategyReusable(strategy)) {
                selected_strategies.push(strategy);
            }
        });

        this.#container.find('select[data-glpi-itildestination-strategy-select]').find('option').each((index, option) => {
            const optionValue = $(option).val();
            $(option).prop(
                'disabled',
                !option.selected && optionValue != 0 && selected_strategies.includes(optionValue)
            );
        });

        if (event) {
            const selected_value = $(event.target).val();

            // Compute display conditions
            $(event.target).closest('[data-glpi-itildestination-field-config]')
                .find(`[data-glpi-itildestination-field-config-display-condition]`)
                .toggleClass('d-none', true)
                .filter(`[data-glpi-itildestination-field-config-display-condition="${CSS.escape(selected_value)}"]`)
                .toggleClass('d-none', false);

            // Compute disabled state of the fields
            $(event.target).closest('[data-glpi-itildestination-field-config]')
                .find(`[data-glpi-itildestination-field-config-display-condition]`).each((index, field) => {
                    $(field).find(':input').prop('disabled', $(field).hasClass('d-none'));
                });
        }
    }

    /**
     * Get the strategy select element for a given field
     * @param {jQuery<HTMLElement>} field
     * @returns {jQuery<HTMLElement>}
     */
    #getStrategySelect(field) {
        return $(field).find('select[data-glpi-itildestination-strategy-select]');
    }
}
