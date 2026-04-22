/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/* global bootstrap, glpi_toast_error, glpi_toast_info, tinymce */

import { post } from "/js/modules/Ajax.js";

const toggle_selector = "[data-glpi-service-catalog-toggle]";
const config_selector = "[data-glpi-service-catalog-config]";
const form_selector   = "[data-glpi-service-catalog-form]";
const submit_selector = "[data-glpi-service-catalog-submit]";
const kb_id_selector  = "[data-glpi-kb-id]";

export class GlpiKnowbaseServiceCatalogPanelController
{
    /**
     * @type {HTMLElement}
     */
    #container;

    constructor(container)
    {
        this.#container = container;
        this.#initEventListeners();

        // Sync custom widgets to the initial server-rendered disabled state.
        // TinyMCE may not be initialized yet; also hook into AddEditor so that
        // editors initialized after the constructor also get the correct mode.
        const config = container.querySelector(config_selector);
        if (config?.disabled) {
            this.#syncCustomWidgets(config, false);
            if (window.tinymce !== undefined) {
                window.tinymce.on('AddEditor', ({ editor }) => {
                    if (config.disabled && config.contains(editor.getElement())) {
                        editor.on('init', () => editor.mode.set('readonly'));
                    }
                });
            }
        }
    }

    #initEventListeners()
    {
        this.#container.addEventListener('change', (e) => {
            const toggle = e.target.closest(toggle_selector);
            if (toggle) {
                this.#updateConfigVisibility(toggle.checked);
            }
        });

        this.#container.addEventListener('submit', (e) => {
            const service_catalog_form = e.target.closest(form_selector);
            if (service_catalog_form) {
                e.preventDefault();
                this.#submitForm();
            }
        });
    }

    #syncCustomWidgets(config, enabled)
    {
        // Select2 replaces <select> with custom DOM and watches the `disabled`
        // content attribute via MutationObserver — fieldset[disabled] only
        // propagates the IDL property, not the attribute. Set/remove the
        // attribute explicitly so Select2 updates its aria-disabled state.
        config.querySelectorAll('select').forEach(select => {
            if (enabled) {
                select.removeAttribute('disabled');
            } else {
                select.setAttribute('disabled', '');
            }
        });

        // TinyMCE replaces <textarea> with an iframe editor; set mode explicitly.
        if (window.tinymce !== undefined) {
            const mode = enabled ? 'design' : 'readonly';
            window.tinymce.get().forEach(editor => {
                if (config.contains(editor.getElement())) {
                    editor.mode.set(mode);
                }
            });
        }
    }

    #updateConfigVisibility(enabled)
    {
        const config = this.#container.querySelector(config_selector);
        if (config) {
            config.disabled = !enabled;
            this.#syncCustomWidgets(config, enabled);
        }
    }

    async #submitForm()
    {
        const submit_btn = this.#container.querySelector(submit_selector);
        const kb_id = this.#container.querySelector(kb_id_selector).value;
        const form = this.#container.querySelector(form_selector);

        // Show loading state
        submit_btn.classList.add('pointer-events-none');
        submit_btn.querySelector('[data-glpi-loading]').classList.remove('d-none');
        submit_btn.querySelector('[data-glpi-icon]').classList.add('d-none');

        // Convert form data to object
        const form_data = new FormData(form);
        const data = {};
        form_data.forEach((value, key) => data[key] = value);

        try {
            await post(`Knowbase/${kb_id}/UpdateServiceCatalog`, data);
            glpi_toast_info(__("Service catalog settings saved."));
            bootstrap.Modal.getInstance(this.#container)?.hide();
        } finally {
            // Remove loading state
            submit_btn.classList.remove('pointer-events-none');
            submit_btn.querySelector('[data-glpi-loading]').classList.add('d-none');
            submit_btn.querySelector('[data-glpi-icon]').classList.remove('d-none');
        }
    }
}
