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

/* global glpi_toast_error, glpi_toast_info, glpi_confirm_danger, getAjaxCsrfToken */

import { post } from "/js/modules/Ajax.js";
import { GlpiKnowbaseArticleSidePanelController } from "./ArticleSidePanelController.js";
import { KnowbaseEditor } from "../KnowbaseEditor.js";

export class GlpiKnowbaseArticleController
{
    /**
     * @type {HTMLElement}
     */
    #container;

    /**
     * @type {GlpiKnowbaseArticleSidePanelController}
     */
    #side_panel;

    /**
     * @type {KnowbaseEditor|null}
     */
    #editor = null;

    /**
     * @type {string}
     */
    #original_content = '';

    /**
     * @type {number|null}
     */
    #item_id = null;

    /**
     * @param {HTMLElement} container
     * @param {HTMLElement} side_panel_container
     */
    constructor(container, side_panel_container)
    {
        this.#container = container;
        this.#side_panel = new GlpiKnowbaseArticleSidePanelController(
            side_panel_container,
        );
        this.#item_id = parseInt(container.dataset.glpiKbItemId, 10) || null;

        this.#initEventListeners();
        this.#initEditor();

        // Enable dots menu once listeners are ready
        const dots = this.#container.querySelector('[data-glpi-kb-dots]');
        if (dots) {
            dots.classList.remove('pointer-events-none');
        }
    }

    #initEventListeners()
    {
        const actions = this.#container.querySelectorAll("[data-glpi-kb-action]");
        for (const action of actions) {
            action.addEventListener("click", (e) => {
                try {
                    this.#executeAction(e);
                } catch (e) {
                    console.error(e);
                    glpi_toast_error(__("An unexpected error occurred."));
                }
            });
        }
    }

    /**
     * @param {Event} event
     */
    #executeAction(event)
    {
        const element = event.currentTarget;
        const target = event.target;

        const type = element.dataset.glpiKbAction;
        const params = this.#extractParamsFromDataset(element.dataset);

        switch (type) {
            case 'LOAD_SIDE_PANEL':
                this.#side_panel.load(params.id, params.key);
                break;
            case 'TOGGLE_VALUE': {
                event.stopPropagation();
                const toggle = element.querySelector('input[type="checkbox"]');
                if (toggle) {
                    const clicked_on_toggle = target === toggle;
                    if (!clicked_on_toggle) {
                        toggle.checked = !toggle.checked;
                    }
                    this.#toggleValue(params.id, params.field, toggle);
                }
                break;
            }
            case 'DELETE_ARTICLE':
                this.#deleteItem(params.id);
                break;
        }
    }

    /** @param {DOMStringMap} dataset */
    #extractParamsFromDataset(dataset)
    {
        const params = {};
        const prefix = 'glpiKbActionParam';

        for (const [key, value] of Object.entries(dataset)) {
            if (key.startsWith(prefix)) {
                const param_name = key.slice(prefix.length).toLowerCase();
                params[param_name] = value;
            }
        }

        return params;
    }

    /**
     * @param {number} id
     * @param {string} field
     * @param {HTMLInputElement} toggle
     */
    async #toggleValue(id, field, toggle)
    {
        const value = toggle.checked;
        try {
            await post(`Knowbase/${id}/ToggleField`, {
                field: field,
                value: value,
            });
        } catch (e) {
            toggle.checked = !value;
            throw e;
        }
    }

    /**
     * @param {number} id
     */
    async #deleteItem(id)
    {
        const confirmed = await glpi_confirm_danger({
            title: __('Delete article'),
            message: __('Are you sure you want to delete this article?'),
            confirm_label: __('Delete'),
        });
        if (!confirmed) {
            return;
        }

        const response = await post(`Knowbase/KnowbaseItem/${id}/Delete`, {});
        const body = await response.json();
        window.location.href = body.redirect;
    }

    /**
     * Initialize the Tiptap editor if user can edit
     */
    async #initEditor()
    {
        const can_edit = this.#container.dataset.glpiKbCanEdit === 'true';
        if (!can_edit) {
            return;
        }

        const editor_element = this.#container.querySelector('#kb-tiptap-editor');
        const edit_button = this.#container.querySelector('[data-action="toggle-edit"]');
        const save_button = this.#container.querySelector('[data-action="save"]');
        const cancel_button = this.#container.querySelector('[data-action="cancel"]');

        if (!editor_element || !edit_button) {
            return;
        }

        // Store original content for cancel functionality
        this.#original_content = editor_element.innerHTML;

        // Initialize editor in readonly mode
        this.#editor = new KnowbaseEditor(editor_element, {
            content: this.#original_content,
            readonly: true,
            placeholder: __("Start writing..."),
            onUpdate: () => {
                // Content updated
            }
        });

        // Toggle edit mode
        edit_button.addEventListener('click', () => {
            this.#editor.setEditable(true);
            this.#editor.focus();
            edit_button.classList.add('d-none');
            save_button.classList.remove('d-none');
            cancel_button.classList.remove('d-none');
        });

        // Cancel editing
        cancel_button.addEventListener('click', () => {
            this.#editor.setContent(this.#original_content);
            this.#editor.setEditable(false);
            edit_button.classList.remove('d-none');
            save_button.classList.add('d-none');
            cancel_button.classList.add('d-none');
        });

        // Save content
        save_button.addEventListener('click', async () => {
            await this.#saveContent(edit_button, save_button, cancel_button);
        });
    }

    /**
     * Save the editor content
     * @param {HTMLElement} edit_button
     * @param {HTMLElement} save_button
     * @param {HTMLElement} cancel_button
     */
    async #saveContent(edit_button, save_button, cancel_button)
    {
        if (this.#item_id === null) {
            glpi_toast_error(__("Cannot save: article ID is missing"));
            return;
        }

        const original_button_html = save_button.innerHTML;
        save_button.disabled = true;
        save_button.innerHTML = `<i class="ti ti-loader me-1"></i>${__("Saving...")}`;

        try {
            const response = await fetch(
                `${CFG_GLPI.root_doc}/Knowbase/KnowbaseItem/${this.#item_id}/Answer`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                    },
                    body: JSON.stringify({
                        answer: this.#editor.getHTML(),
                    }),
                }
            );

            if (!response.ok) {
                throw new Error(__("Failed to save"));
            }

            // Update original content for future cancel operations
            this.#original_content = this.#editor.getHTML();
            this.#editor.setEditable(false);
            edit_button.classList.remove('d-none');
            save_button.classList.add('d-none');
            cancel_button.classList.add('d-none');

            // Show success notification
            glpi_toast_info(__("Article saved successfully"));
        } catch (error) {
            glpi_toast_error(error.message || __("An error occurred while saving"));
        } finally {
            save_button.disabled = false;
            save_button.innerHTML = original_button_html;
        }
    }
}
