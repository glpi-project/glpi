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

/* global glpi_ajax_dialog, glpi_confirm_danger, glpi_toast_error, glpi_toast_info, */

import { post } from "/js/modules/Ajax.js";
import { GlpiKnowbaseArticleSidePanelController } from "/js/modules/Knowbase/ArticleSidePanelController.js";

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

    /** @type {HTMLElement|null} */
    #title_element = null;

    /** @type {string} */
    #original_title = '';

    /**
     * @type {number|null}
     */
    #item_id = null;

    #handleTitleKeydown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.#editor.focus();
        }
    };

    #handleTitlePaste = (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData)
            .getData('text/plain')
            .replace(/[\r\n]+/g, ' ')
            .trim();
        document.execCommand('insertText', false, text);
    };

    /**
     * @param {HTMLElement} container
     * @param {HTMLElement} side_panel_container
     * @param {HTMLElement} offcanvas_container
     */
    constructor(container, side_panel_container, offcanvas_container)
    {
        this.#container = container;
        this.#side_panel = new GlpiKnowbaseArticleSidePanelController(
            side_panel_container,
            offcanvas_container,
            this.#container.querySelector('[data-glpi-knowbase-article-content]'),
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

        // Delegated listener for document unlink buttons
        this.#container.addEventListener("click", (e) => {
            const button = e.target.closest("[data-glpi-kb-unlink-document]");
            if (button) {
                e.stopPropagation();
                e.preventDefault();
                this.#unlinkDocument(button);
            }
        });
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
            case 'LOAD_MODAL':
                this.#loadModal(params.id, params.key, params.title);
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
     * Load content in a modal dialog
     * @param {string} id - KnowbaseItem ID
     * @param {string} key - Content key (e.g., 'permissions')
     * @param {string} title - Modal title
     */
    #loadModal(id, key, title)
    {
        glpi_ajax_dialog({
            url: `${CFG_GLPI.root_doc}/Knowbase/${id}/SidePanel/${key}`,
            title: title || '',
            dialogclass: 'modal-lg',
        });
    }

    /**
     * Unlink a document from the KB article
     * @param {HTMLElement} button
     */
    async #unlinkDocument(button)
    {
        const assoc_id = button.dataset.glpiKbUnlinkDocument;

        const confirmed = await glpi_confirm_danger({
            title: __('Unlink document'),
            message: __('Are you sure you want to unlink this document from the article?'),
            confirm_label: __('Unlink'),
        });
        if (!confirmed) {
            return;
        }

        await post(`Knowbase/UnlinkDocument/${assoc_id}`);

        // Remove badge from DOM
        const badge = this.#container.querySelector(
            `[data-glpi-document-assoc-id="${assoc_id}"]`
        );
        if (badge) {
            badge.remove();
        }

        this.#updateDocumentCount(-1);
        glpi_toast_info(__('Document unlinked successfully'));
    }

    /**
     * Update document count in the metadata bar and tab badge
     * @param {number} delta
     */
    #updateDocumentCount(delta)
    {
        // Tab badge count
        const tab_badge = this.#container.querySelector(
            '#kb-documents-tab-btn .badge'
        );
        if (tab_badge) {
            const current = parseInt(tab_badge.textContent, 10) || 0;
            const updated = Math.max(0, current + delta);
            tab_badge.textContent = updated;
        }

        // Metadata bar count
        const meta_link = this.#container.querySelector(
            '[data-testid="documents-count"]'
        );
        if (meta_link) {
            const current = parseInt(meta_link.textContent, 10) || 0;
            const updated = Math.max(0, current + delta);
            if (updated === 0) {
                // Hide the entire metadata entry when no documents remain
                meta_link.closest('.d-flex')?.remove();
            } else {
                const label = _n('%s document', '%s documents', updated).replace('%s', updated);
                meta_link.textContent = label;
            }
        }
    }

    /**
     * Initialize edit button listeners (editor is loaded lazily on first edit)
     */
    #initEditor()
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

        // Store title element for inline editing
        this.#title_element = this.#container.querySelector('[data-field="name"]');
        if (this.#title_element) {
            this.#original_title = this.#title_element.textContent.trim();
        }

        // Toggle edit mode (lazy load editor on first click)
        edit_button.addEventListener('click', async () => {
            await this.#enableEditMode(editor_element, edit_button, save_button, cancel_button);
        });

        // Cancel editing
        cancel_button.addEventListener('click', () => {
            this.#editor.setContent(this.#original_content);
            this.#editor.setEditable(false);

            this.#disableTitleEditing(true);

            edit_button.classList.remove('d-none');
            save_button.classList.add('d-none');
            cancel_button.classList.add('d-none');
        });

        // Save content
        save_button.addEventListener('click', async () => {
            await this.#saveContent(edit_button, save_button, cancel_button);
        });

        // Enable edit button once editor is ready
        edit_button.classList.remove('pointer-events-none');
    }

    /**
     * Enable edit mode, loading the editor lazily if needed
     * @param {HTMLElement} editor_element
     * @param {HTMLElement} edit_button
     * @param {HTMLElement} save_button
     * @param {HTMLElement} cancel_button
     */
    async #enableEditMode(editor_element, edit_button, save_button, cancel_button)
    {
        // Lazy load editor on first use
        if (this.#editor === null) {
            const { KnowbaseEditor } = await import('/js/modules/KnowbaseEditor.js');
            this.#editor = new KnowbaseEditor(editor_element, {
                content: this.#original_content,
                readonly: false,
                placeholder: __("Start writing..."),
            });
        } else {
            this.#editor.setEditable(true);
        }

        this.#enableTitleEditing();

        this.#editor.focus();
        edit_button.classList.add('d-none');
        save_button.classList.remove('d-none');
        cancel_button.classList.remove('d-none');
    }

    #enableTitleEditing()
    {
        if (this.#title_element) {
            this.#title_element.contentEditable = 'true';
            this.#title_element.classList.add('is-editing');
            this.#title_element.addEventListener('keydown', this.#handleTitleKeydown);
            this.#title_element.addEventListener('paste', this.#handleTitlePaste);
        }
    }

    /**
     * @param {boolean} restore - Whether to restore the original title text
     */
    #disableTitleEditing(restore = false)
    {
        if (this.#title_element) {
            if (restore) {
                this.#title_element.textContent = this.#original_title;
            }
            this.#title_element.contentEditable = 'false';
            this.#title_element.classList.remove('is-editing');
            this.#title_element.removeEventListener('keydown', this.#handleTitleKeydown);
            this.#title_element.removeEventListener('paste', this.#handleTitlePaste);
        }
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

        // Validate title is not empty
        const new_title = this.#title_element
            ? this.#title_element.textContent.trim()
            : null;
        if (this.#title_element && new_title.length === 0) {
            glpi_toast_error(__("Title cannot be empty"));
            this.#title_element.focus();
            return;
        }

        const original_button_html = save_button.innerHTML;
        save_button.disabled = true;
        save_button.innerHTML = `<i class="ti ti-loader me-1"></i>${__("Saving...")}`;

        try {
            const body = {
                answer: this.#editor.getHTML(),
            };
            if (new_title !== null) {
                body.name = new_title;
            }

            await post(`Knowbase/KnowbaseItem/${this.#item_id}/Answer`, body);

            // Update originals for future cancel operations
            this.#original_content = this.#editor.getHTML();
            if (new_title !== null) {
                this.#original_title = new_title;
            }
            this.#editor.setEditable(false);
            this.#disableTitleEditing();

            edit_button.classList.remove('d-none');
            save_button.classList.add('d-none');
            cancel_button.classList.add('d-none');

            // Show success notification
            glpi_toast_info(__("Article saved successfully"));
        } catch {
            // Error toast already shown by post()
        } finally {
            save_button.disabled = false;
            save_button.innerHTML = original_button_html;
        }
    }
}
