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

/* global glpi_toast_error, glpi_toast_info, getAjaxCsrfToken */

import { GlpiKnowbaseArticleSidePanelController } from "./ArticleSidePanelController.js";

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
     * @type {object|null}
     */
    #editor = null;

    /**
     * @type {string}
     */
    #originalContent = '';

    /**
     * @type {number|null}
     */
    #itemId = null;

    constructor(container, side_panel_container)
    {
        this.#container = container;
        this.#side_panel = new GlpiKnowbaseArticleSidePanelController(
            side_panel_container,
        );
        this.#itemId = parseInt(container.dataset.glpiKbItemId, 10) || null;

        this.#initEventListeners();
        this.#initEditor();
    }

    #initEventListeners()
    {
        const actions = this.#container.querySelectorAll("[data-glpi-kb-action]");
        for (const action of actions) {
            action.addEventListener("click", (e) => {
                try {
                    this.#executeAction(e.currentTarget);
                } catch (e) {
                    console.error(e);
                    glpi_toast_error(__("An unexpected error occurred."));
                }
            });
        }
    }

    /** @param {HTMLElement} element */
    #executeAction(element)
    {
        const type = element.dataset.glpiKbAction;
        const params = this.#extractParamsFromDataset(element.dataset);

        switch (type) {
            case 'LOAD_SIDE_PANEL':
                this.#side_panel.load(params.id, params.key);
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
                const paramName = key.slice(prefix.length).toLowerCase();
                params[paramName] = value;
            }
        }

        return params;
    }

    /**
     * Initialize the Tiptap editor if user can edit
     */
    async #initEditor()
    {
        const canEdit = this.#container.dataset.glpiKbCanEdit === 'true';
        if (!canEdit) {
            return;
        }

        const editorElement = this.#container.querySelector('#kb-tiptap-editor');
        const editButton = this.#container.querySelector('[data-action="toggle-edit"]');
        const saveButton = this.#container.querySelector('[data-action="save"]');
        const cancelButton = this.#container.querySelector('[data-action="cancel"]');

        if (!editorElement || !editButton) {
            return;
        }

        // Dynamic import of KnowbaseEditor module
        let KnowbaseEditor;
        try {
            const module = await import("../KnowbaseEditor.js");
            KnowbaseEditor = module.KnowbaseEditor;
        } catch (error) {
            console.error('[KnowbaseEditor] Failed to load module:', error);
            return;
        }

        // Store original content for cancel functionality
        this.#originalContent = editorElement.innerHTML;

        // Initialize editor in readonly mode
        this.#editor = new KnowbaseEditor(editorElement, {
            content: this.#originalContent,
            readonly: true,
            placeholder: __("Start writing..."),
            onUpdate: () => {
                // Content updated
            }
        });

        // Toggle edit mode
        editButton.addEventListener('click', () => {
            this.#editor.setEditable(true);
            this.#editor.focus();
            editButton.classList.add('d-none');
            saveButton.classList.remove('d-none');
            cancelButton.classList.remove('d-none');
        });

        // Cancel editing
        cancelButton.addEventListener('click', () => {
            this.#editor.setContent(this.#originalContent);
            this.#editor.setEditable(false);
            editButton.classList.remove('d-none');
            saveButton.classList.add('d-none');
            cancelButton.classList.add('d-none');
        });

        // Save content
        saveButton.addEventListener('click', async () => {
            await this.#saveContent(editButton, saveButton, cancelButton);
        });
    }

    /**
     * Save the editor content
     * @param {HTMLElement} editButton
     * @param {HTMLElement} saveButton
     * @param {HTMLElement} cancelButton
     */
    async #saveContent(editButton, saveButton, cancelButton)
    {
        const originalButtonHtml = saveButton.innerHTML;
        saveButton.disabled = true;
        saveButton.innerHTML = `<i class="ti ti-loader me-1"></i>${__("Saving...")}`;

        try {
            const response = await fetch(
                `${CFG_GLPI.root_doc}/Knowbase/KnowbaseItem/${this.#itemId}/Answer`,
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

            const result = await response.json();

            if (result.success) {
                // Update original content for future cancel operations
                this.#originalContent = this.#editor.getHTML();
                this.#editor.setEditable(false);
                editButton.classList.remove('d-none');
                saveButton.classList.add('d-none');
                cancelButton.classList.add('d-none');

                // Show success notification
                if (typeof glpi_toast_info === 'function') {
                    glpi_toast_info(__("Article saved successfully"));
                }
            } else {
                throw new Error(result.message || __("Failed to save"));
            }
        } catch (error) {
            if (typeof glpi_toast_error === 'function') {
                glpi_toast_error(error.message || __("An error occurred while saving"));
            }
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = originalButtonHtml;
        }
    }
}
