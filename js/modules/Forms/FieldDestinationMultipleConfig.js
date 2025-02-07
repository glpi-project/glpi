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

    constructor(container) {
        this.#container = container;
        this.#template = container.find('[data-glpi-itildestination-field-config-template]');
        this.#add_button = container.find('[data-glpi-itildestination-add-field-config]');

        // Register events
        this.#container.find('[data-glpi-itildestination-remove-field-config]')
            .on('click', (e) => this.#removeFieldConfig(e.target.closest('[data-glpi-itildestination-field-config]')));
        this.#add_button.on('click', () => this.#addFieldConfig());
        this.#container.find('[data-glpi-itildestination-field-config]')
            .each((index, field) => $(field).find('select').first().on('change', (e) => this.#handleStrategyChange(e)));

        // Trigger change event to initialize the display
        this.#container.find('[data-glpi-itildestination-field-config]').each((index, field) => {
            $(field).find('select').first().trigger('change');
        });

        this.#handleAddButtonVisibility();
    }

    /**
     * Add a new field config
     */
    #addFieldConfig() {
        const selected_strategies = [];
        this.#container.find('[data-glpi-itildestination-field-config]').each((index, field) => {
            selected_strategies.push($(field).find('select').first().find('option').filter(':selected').val());
        });

        const new_config_field = $(this.#template.html()).insertBefore(this.#add_button);

        new_config_field.find('[data-glpi-itildestination-remove-field-config]')
            .on('click', (e) => this.#removeFieldConfig(e.target.closest('[data-glpi-itildestination-field-config]')));

        new_config_field.find('select').find('option').each((index, option) => {
            if ($(option).val() != 0 && selected_strategies.includes($(option).val())) {
                $(option).prop('disabled', true);
            }
        });

        new_config_field.find('select').first().on('change', (e) => this.#handleStrategyChange(e));

        this.#initDropdowns(new_config_field);
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
    }

    #handleAddButtonVisibility() {
        const count_options = this.#container.find('[data-glpi-itildestination-field-config]')
            .find('select').first().find('option').length;
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
            selected_strategies.push($(field).find('select').first().find('option').filter(':selected').val());
        });

        this.#container.find('select').find('option').each((index, option) => {
            $(option).prop(
                'disabled',
                !option.selected && $(option).val() != 0 && selected_strategies.includes($(option).val())
            );
        });

        if (event) {
            const selected_value = $(event.target).val();

            // Compute display conditions
            $(event.target).closest('[data-glpi-itildestination-field-config]')
                .find(`[data-glpi-itildestination-field-config-display-condition]`)
                .toggleClass('d-none', true)
                .filter(`[data-glpi-itildestination-field-config-display-condition="${selected_value}"]`)
                .toggleClass('d-none', false);

            // Compute disabled state of the fields
            $(event.target).closest('[data-glpi-itildestination-field-config]')
                .find(`[data-glpi-itildestination-field-config-display-condition]`).each((index, field) => {
                    $(field).find(':input').prop('disabled', $(field).hasClass('d-none'));
                });
        }
    }
}
