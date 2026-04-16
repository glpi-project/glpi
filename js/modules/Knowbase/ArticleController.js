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

/* global glpi_ajax_dialog, glpi_alert, glpi_confirm_danger, glpi_toast_error, glpi_toast_info, bootstrap */

import { get, post } from "/js/modules/Ajax.js";
import { DocumentLinkController } from "/js/modules/Knowbase/DocumentLinkController.js";
import { LinkItemFormController } from "/js/modules/Knowbase/LinkItemFormController.js";
import { GlpiKnowbaseArticleSidePanelController } from "/js/modules/Knowbase/ArticleSidePanelController.js";

const EditorActionType = Object.freeze({
    LOAD_SIDE_PANEL: 'LOAD_SIDE_PANEL',
    TOGGLE_VALUE:    'TOGGLE_VALUE',
    TOGGLE_FAVORITE: 'TOGGLE_FAVORITE',
    DELETE_ARTICLE:  'DELETE_ARTICLE',
    OPEN_MODAL:      'OPEN_MODAL',
});

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

    /** @type {boolean} */
    #is_editing = false;

    /** @type {string|null} */
    #translation_language = null;

    /** @type {string} */
    #default_language = '';

    /** @type {string[]} */
    #existing_translations = [];

    /** @type {string} */
    #base_content = '';

    /** @type {string} */
    #base_title = '';

    /** @type {DocumentLinkController|null} */
    #document_link_controller = null;

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
     * @type {string|null} Original content HTML (saved before diff mode)
     */
    #originalContent = null;

    /**
     * @type {boolean}
     */
    #isDiffMode = false;

    /**
     * @param {HTMLElement} container
     * @param {HTMLElement} side_panel_container
     * @param {HTMLElement} offcanvas_container
     * @param {string} mode
     */
    constructor(container, side_panel_container, offcanvas_container, mode)
    {
        this.#container = container;
        if (mode === "edit") {
            this.#side_panel = new GlpiKnowbaseArticleSidePanelController(
                side_panel_container,
                offcanvas_container,
                this.#container.querySelector('[data-glpi-knowbase-article-content]'),
            );
        }
        this.#item_id = parseInt(container.dataset.glpiKbItemId, 10) || null;
        this.#initEventListeners();
        this.#initEditor();
        this.#initDiffListeners();
        this.#initIllustrationPicker();
        this.#initRecursiveToggle();

        if (mode === "edit") {
            this.#default_language = container.dataset.glpiKbDefaultLanguage;
            this.#existing_translations = JSON.parse(
                container.dataset.glpiKbExistingTranslations
            );
            this.#initTranslationMode();
            this.#initVisibilityDates();
        }

        if (mode === 'add') {
            this.#enableEditMode();
            const add_button = this.#container.parentElement?.querySelector('[data-glpi-kb-add-article]');
            if (add_button) {
                add_button.addEventListener('click', () => this.#addArticle());
            }
        }

        // Enable interactions once all listeners are registered
        this.#container.classList.remove('pe-none');
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

        // Lazy-init DocumentLinkController when the link tab is shown
        const link_tab = document.getElementById('kb-modal-link-tab');
        if (link_tab) {
            link_tab.addEventListener('shown.bs.tab', () => {
                if (!this.#document_link_controller) {
                    const link_pane = document.getElementById('kb-modal-link-pane');
                    const modal = document.getElementById('kb-add-document-modal');
                    if (link_pane) {
                        this.#document_link_controller = new DocumentLinkController(link_pane, modal);
                    }
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

        // Delegated listener for item unlink buttons
        this.#container.addEventListener("click", (e) => {
            const button = e.target.closest("[data-glpi-kb-unlink-item]");
            if (button) {
                e.stopPropagation();
                e.preventDefault();
                this.#unlinkItem(button);
            }
        });

        // Handle documents uploaded without page reload
        this.#container.addEventListener('documents:uploaded', (e) => {
            this.#onDocumentsUploaded(e.detail.documents ?? []);
        });

        // Handle items linked without page reload
        this.#container.addEventListener('item:linked', (e) => {
            this.#onItemLinked(e.detail.item ?? null);
        });
    }

    #initDiffListeners()
    {
        this.#container.addEventListener('glpi:kb:compare', (e) => {
            this.#showDiff(e.detail);
        });

        this.#container.addEventListener('glpi:kb:compare-off', () => {
            this.#hideDiff();
        });
    }

    /**
     * @param {{content_diff: string}} detail
     */
    #showDiff({content_diff})
    {
        const contentEl = this.#container.querySelector('[data-glpi-kb-content]');

        if (!contentEl) {
            return;
        }

        // Save original content on first activation
        if (this.#originalContent === null) {
            this.#originalContent = contentEl.innerHTML;
        }

        // Replace with diff
        contentEl.innerHTML = content_diff;

        // Add diff-mode class for styling
        const article = this.#container.querySelector('.kb-article');
        if (article) {
            article.classList.add('kb-article--diff-mode');
        }

        this.#isDiffMode = true;
    }

    #hideDiff()
    {
        if (!this.#isDiffMode) {
            return;
        }

        const contentEl = this.#container.querySelector('[data-glpi-kb-content]');

        if (contentEl && this.#originalContent !== null) {
            contentEl.innerHTML = this.#originalContent;
        }

        const article = this.#container.querySelector('.kb-article');
        if (article) {
            article.classList.remove('kb-article--diff-mode');
        }

        this.#originalContent = null;
        this.#isDiffMode = false;
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
            case EditorActionType.LOAD_SIDE_PANEL:
                this.#side_panel.load(params.id, params.key);
                break;
            case EditorActionType.TOGGLE_VALUE: {
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
            case EditorActionType.TOGGLE_FAVORITE: {
                event.stopPropagation();
                const toggle = element.querySelector('input[type="checkbox"]');
                if (toggle) {
                    const clicked_on_toggle = target === toggle;
                    if (!clicked_on_toggle) {
                        toggle.checked = !toggle.checked;
                    }
                    this.#toggleFavorite(params.id, toggle);
                }
                break;
            }
            case EditorActionType.DELETE_ARTICLE:
                this.#deleteItem(params.id);
                break;
            case EditorActionType.OPEN_MODAL:
                this.#openModal(params.id, params.key, params.title);
                break;
            case 'SCHEDULE_VISIBILITY': {
                // Show indicator
                const indicator   = this.#getScheduledArticleIndicator();
                const toggle_link = this.#getScheduleDropdownToggle();
                indicator.classList.remove('d-none');

                // Open the dropdown.
                // Defer show() so that Bootstrap's clearMenus handler (fired
                // as the triggering click propagates to document) does not
                // immediately close the dropdown we are about to open.
                requestAnimationFrame(() => {
                    bootstrap.Dropdown.getOrCreateInstance(toggle_link).show();
                });
                break;
            }
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
     * @param {HTMLInputElement} toggle
     */
    async #toggleFavorite(id, toggle)
    {
        const value = toggle.checked;
        try {
            await post(`Knowbase/${id}/ToggleFavorite`, { value: value });
        } catch (e) {
            toggle.checked = !value;
            throw e;
        }
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
     * @param {number} id
     * @param {string} key
     * @param {string} title
     */
    #openModal(id, key, title)
    {
        glpi_ajax_dialog({
            url: `${CFG_GLPI.root_doc}/Knowbase/${id}/${key}`,
            title: title || '',
            dialogclass: 'modal-lg',
            show: key === 'LinkItemModal' ? (e) => {
                new LinkItemFormController(e.target.closest('.modal'));
            } : () => {},
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
     * Unlink an item from the KB article
     * @param {HTMLElement} button
     */
    async #unlinkItem(button)
    {
        const assoc_id = button.dataset.glpiKbUnlinkItem;

        const confirmed = await glpi_confirm_danger({
            title: __('Unlink item'),
            message: __('Are you sure you want to unlink this item from the article?'),
            confirm_label: __('Unlink'),
        });
        if (!confirmed) {
            return;
        }

        await post(`Knowbase/UnlinkItem/${assoc_id}`);

        // Remove badge from DOM
        const badge = this.#container.querySelector(
            `[data-glpi-item-assoc-id="${CSS.escape(assoc_id)}"]`
        );
        if (badge) {
            badge.remove();
        }

        this.#updateRelatedItemCount(-1);
        glpi_toast_info(__('Item unlinked successfully'));
    }

    /**
     * Update related item count in the metadata bar and tab badge
     * @param {number} delta
     */
    #updateRelatedItemCount(delta)
    {
        // Tab badge count
        const tab_badge = this.#container.querySelector(
            '#kb-items-tab-btn .badge'
        );
        if (tab_badge) {
            const current = parseInt(tab_badge.textContent, 10) || 0;
            const updated = Math.max(0, current + delta);
            tab_badge.textContent = updated;
        }

        // Metadata bar count
        const meta_link = this.#container.querySelector(
            '[data-kb-related-items-count]'
        );
        if (meta_link) {
            const current = parseInt(meta_link.textContent, 10) || 0;
            const updated = Math.max(0, current + delta);
            const meta_container = meta_link.closest('[data-kb-related-items-count-container]');
            if (updated === 0) {
                meta_container.classList.add('d-none');
            } else {
                meta_container.classList.remove('d-none');
                const label = _n('%s related item', '%s related items', updated).replace('%s', updated);
                meta_link.textContent = label;
            }
        }
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
            '[data-kb-documents-count]'
        );
        if (meta_link) {
            const current = parseInt(meta_link.textContent, 10) || 0;
            const updated = Math.max(0, current + delta);
            const meta_container = meta_link.closest('[data-kb-documents-count-container]');
            if (updated === 0) {
                meta_container.classList.add('d-none');
            } else {
                meta_container.classList.remove('d-none');
                const label = _n('%s document', '%s documents', updated).replace('%s', updated);
                meta_link.textContent = label;
            }
        }
    }

    /**
     * @param {Array<html: string}>} documents
     */
    #onDocumentsUploaded(documents)
    {
        if (documents.length === 0) {
            return;
        }

        // Insert new badges to show linked documents
        const badges_container = this.#container.querySelector('[data-glpi-kb-documents-list]');
        for (const doc of documents) {
            badges_container.insertAdjacentHTML('beforeend', doc.html);
        }
        badges_container.classList.remove('d-none');

        // Update counters
        this.#updateDocumentCount(documents.length);
    }

    /**
     * @param {{html: string}|null} item
     */
    #onItemLinked(item)
    {
        if (!item) {
            return;
        }

        // Insert new badge
        const badges_container = this.#container.querySelector('[data-glpi-kb-related-items-list]');
        badges_container.insertAdjacentHTML('beforeend', item.html);
        badges_container.classList.remove('d-none');

        // Update counters
        this.#updateRelatedItemCount(1);
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

        if (!editor_element) {
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
        if (edit_button) {
            edit_button.addEventListener('click', async () => {
                await this.#enableEditMode();
            });
        }

        // Cancel editing
        if (cancel_button) {
            cancel_button.addEventListener('click', () => {
                this.#editor.setContent(this.#original_content);
                this.#editor.setEditable(false);
                this.#is_editing = false;
                this.#disableTitleEditing(true);

                edit_button.classList.remove('d-none');
                save_button.classList.add('d-none');
                cancel_button.classList.add('d-none');
            });
        }

        // Save content
        if (save_button && edit_button && cancel_button) {
            save_button.addEventListener('click', async () => {
                await this.#saveContent(edit_button, save_button, cancel_button);
            });
        }

        // Enable edit button once editor is ready
        if (edit_button) {
            edit_button.classList.remove('pointer-events-none');
        }
    }

    #initIllustrationPicker()
    {
        if (this.#item_id === null) {
            return;
        }

        const illustration_input = this.#container.querySelector(
            '[data-glpi-kb-illustration-container] [data-glpi-icon-picker-value]'
        );
        if (!illustration_input) {
            return;
        }

        // Update hook for illustration input
        const original_descriptor = Object.getOwnPropertyDescriptor(
            HTMLInputElement.prototype, 'value'
        );
        const controller = this;
        Object.defineProperty(illustration_input, 'value', {
            get() {
                return original_descriptor.get.call(this);
            },
            set(new_value) {
                const old_value = original_descriptor.get.call(this);
                original_descriptor.set.call(this, new_value);
                if (new_value !== old_value) {
                    controller.#saveIllustration(new_value);
                }
            },
        });
    }

    async #saveIllustration(illustration)
    {
        if (this.#item_id === null) {
            return;
        }

        await post(`Knowbase/${this.#item_id}/UpdateIllustration`, {
            illustration: illustration,
        });
    }

    #initRecursiveToggle()
    {
        if (this.#item_id === null) {
            return;
        }

        const checkbox = document.querySelector('[data-glpi-child-entities-checkbox]');
        if (!checkbox || checkbox.disabled) {
            return;
        }

        checkbox.addEventListener('change', async () => {
            const value = checkbox.checked;
            try {
                await post(`Knowbase/${this.#item_id}/ToggleField`, {
                    field: 'is_recursive',
                    value: value,
                });
                glpi_toast_info(value ? __('Child entities enabled') : __('Child entities disabled'));
            } catch (e) {
                checkbox.checked = !value;
                throw e;
            }
        });
    }

    #initVisibilityDates()
    {
        // Gather target nodes
        const panel       = this.#container.querySelector('[data-glpi-kb-schedule-panel]');
        const begin_input = panel.querySelector('[data-glpi-kb-begin-date]');
        const end_input   = panel.querySelector('[data-glpi-kb-end-date]');
        const apply_btn   = panel.querySelector('[data-glpi-kb-schedule-apply]');
        const cancel_btn  = panel.querySelector('[data-glpi-kb-schedule-cancel]');
        const indicator   = this.#getScheduledArticleIndicator();
        const toggle_link = this.#getScheduleDropdownToggle();

        // Close the dropdown panel on cancel
        cancel_btn.addEventListener('click', () => {
            bootstrap.Dropdown.getOrCreateInstance(toggle_link).hide();
            // Re-hide the indicator if no dates are actually set as the item
            // won't be "Scheduled".
            if (!begin_input.value && !end_input.value && indicator) {
                indicator.classList.add('d-none');
            }
        });

        // Save value to backend on apply
        apply_btn.addEventListener('click', async () => {
            // Read form values
            const begin_date = begin_input.value || null;
            const end_date   = end_input.value || null;

            // Apply loading state feedback to the submit button
            const original_html = apply_btn.innerHTML;
            apply_btn.disabled = true;
            apply_btn.innerHTML = `<i class="ti ti-loader me-1"></i>${__('Saving...')}`;

            try {
                // Sent value to backend
                await post(`Knowbase/${this.#item_id}/UpdateVisibilityDates`, {
                    begin_date,
                    end_date,
                });

                // Close dropdown panel and remove the indicator if both dates
                // were set to null.
                indicator.classList.toggle('d-none', !begin_date && !end_date);
                bootstrap.Dropdown.getOrCreateInstance(toggle_link).hide();

                glpi_toast_info(__('Visibility dates updated'));
            } finally {
                // Restore submit button
                apply_btn.disabled = false;
                apply_btn.innerHTML = original_html;
            }
        });
    }

    /**
     * Enable edit mode, loading the editor lazily if needed
     */
    async #enableEditMode()
    {
        const editor_element = this.#container.querySelector('#kb-tiptap-editor');
        const edit_button = this.#container.querySelector('[data-action="toggle-edit"]');
        const save_button = this.#container.querySelector('[data-action="save"]');
        const cancel_button = this.#container.querySelector('[data-action="cancel"]');

        // Lazy load editor on first use
        if (this.#editor === null) {
            const { KnowbaseEditor } = await import('/js/modules/KnowbaseEditor.js');
            this.#editor = new KnowbaseEditor(editor_element, {
                content: this.#original_content,
                readonly: false,
                placeholder: __("Start writing..."),
                item_id: this.#item_id,
            });
        } else {
            this.#editor.setEditable(true);
        }

        this.#enableTitleEditing();
        this.#is_editing = true;

        this.#editor.focus();
        if (edit_button) {
            edit_button.classList.add('d-none');
        }
        if (save_button) {
            save_button.classList.remove('d-none');
        }
        if (cancel_button) {
            cancel_button.classList.remove('d-none');
        }
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
            this.#is_editing = false;

            this.#base_content = this.#original_content;
            this.#base_title = this.#original_title;

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

    async #addArticle()
    {
        const title = this.#title_element
            ? this.#title_element.textContent.trim()
            : '';
        const answer = this.#editor ? this.#editor.getHTML() : '';

        if (title.length === 0) {
            glpi_toast_error(__("Title cannot be empty"));
            if (this.#title_element) {
                this.#title_element.focus();
            }
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${CFG_GLPI.root_doc}/front/knowbaseitem.form.php`;
        form.style.display = 'none';

        const fields = {
            add: '1',
            name: title,
            answer: answer,
        };

        for (const [key, value] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }

    #initTranslationMode()
    {
        const toggle_link = this.#container.querySelector('[data-glpi-kb-toggle-translation-mode]');
        toggle_link.addEventListener('click', async (e) => {
            e.preventDefault();
            await this.#enterTranslationMode();
        });
        toggle_link.classList.remove("pointer-events-none");

        const close_btn = this.#container.querySelector('[data-glpi-kb-translation-close]');
        close_btn.addEventListener('click', () => {
            this.#exitTranslationMode();
        });

        const delete_btn = this.#container.querySelector('[data-glpi-kb-translation-delete]');
        delete_btn.addEventListener('click', async () => {
            await this.#deleteTranslation();
        });

        const translation_save_btn = this.#container.querySelector('[data-glpi-kb-translation-save]');
        translation_save_btn.addEventListener('click', async () => {
            await this.#saveTranslation(translation_save_btn);
        });

        const language_select = this.#container.querySelector('[data-glpi-kb-translation-language]');
        language_select.addEventListener('change', async () => {
            await this.#switchTranslationLanguage(language_select.value);
        });
    }

    async #enterTranslationMode()
    {
        if (this.#is_editing) {
            glpi_alert({
                title: __('Translation mode unavailable'),
                message: __('Please save or cancel your current changes before entering translation mode.'),
            });
            return;
        }

        const alert_el = this.#container.querySelector('[data-glpi-kb-translation-alert]');
        const language_select = this.#container.querySelector('[data-glpi-kb-translation-language]');

        this.#base_content = this.#original_content;
        this.#base_title = this.#original_title;

        const response = await get(`Knowbase/KnowbaseItem/${this.#item_id}/Languages`);
        const data = await response.json();
        this.#populateLanguageDropdown(language_select, data.languages);

        alert_el.classList.remove('d-none');
        alert_el.classList.add('d-flex');

        // Hide main editor actions during translation mode
        const editor_actions = this.#container.querySelector('.kb-editor-actions');
        if (editor_actions) {
            editor_actions.classList.remove('d-flex');
            editor_actions.classList.add('d-none');
        }

        const edit_button = this.#container.querySelector('[data-action="toggle-edit"]');
        const save_button = this.#container.querySelector('[data-action="save"]');
        const cancel_button = this.#container.querySelector('[data-action="cancel"]');

        const selected_language = language_select.value;
        this.#translation_language = selected_language;
        this.#updateDeleteButtonVisibility();
        await this.#loadTranslationContent(selected_language);

        const editor_element = this.#container.querySelector('#kb-tiptap-editor');
        if (editor_element && edit_button) {
            await this.#enableEditMode(editor_element, edit_button, save_button, cancel_button);
        }
    }

    /**
     * @param {HTMLSelectElement} select
     * @param {Array<{code: string, name: string, has_translation: boolean}>} languages
     */
    #populateLanguageDropdown(select, languages)
    {
        select.innerHTML = '';

        const default_lang = languages.find(l => l.code === this.#default_language);
        const with_translation = languages.filter(l => l.has_translation && l.code !== this.#default_language);
        const without_translation = languages.filter(l => !l.has_translation && l.code !== this.#default_language);

        const existing_group = document.createElement('optgroup');
        existing_group.label = __("Existing translations");

        if (default_lang) {
            const option = document.createElement('option');
            option.value = default_lang.code;
            option.textContent = `${default_lang.name} (${__('Default')})`;
            existing_group.appendChild(option);
        }
        for (const lang of with_translation) {
            const option = document.createElement('option');
            option.value = lang.code;
            option.textContent = lang.name;
            existing_group.appendChild(option);
        }
        select.appendChild(existing_group);

        if (without_translation.length > 0) {
            const group = document.createElement('optgroup');
            group.label = __("Add new translation");
            for (const lang of without_translation) {
                const option = document.createElement('option');
                option.value = lang.code;
                option.textContent = lang.name;
                group.appendChild(option);
            }
            select.appendChild(group);
        }

        if (with_translation.length > 0) {
            select.value = with_translation[0].code;
        } else {
            select.value = this.#default_language;
        }
    }

    async #switchTranslationLanguage(language)
    {
        this.#translation_language = language;
        this.#updateDeleteButtonVisibility();
        await this.#loadTranslationContent(language);
    }

    async #loadTranslationContent(language)
    {
        const editor_element = this.#container.querySelector('#kb-tiptap-editor');

        if (language === this.#default_language) {
            this.#original_content = this.#base_content;
            this.#original_title = this.#base_title;

            if (this.#editor) {
                this.#editor.setContent(this.#base_content);
            } else if (editor_element) {
                editor_element.innerHTML = this.#base_content;
            }
            if (this.#title_element) {
                this.#title_element.textContent = this.#base_title;
            }
            return;
        }

        const response = await get(
            `Knowbase/KnowbaseItem/${this.#item_id}/Translation/${language}`
        );
        const data = await response.json();

        if (!data.exists) {
            this.#original_content = this.#base_content;
            this.#original_title = this.#base_title;

            if (this.#editor) {
                this.#editor.setContent(this.#base_content);
            } else if (editor_element) {
                editor_element.innerHTML = this.#base_content;
            }
            if (this.#title_element) {
                this.#title_element.textContent = this.#base_title;
            }
            return;
        }

        const content = data.answer;
        const title = data.name;

        this.#original_content = content;
        this.#original_title = title;

        if (this.#editor) {
            this.#editor.setContent(content);
        } else if (editor_element) {
            editor_element.innerHTML = content;
        }
        if (this.#title_element) {
            this.#title_element.textContent = title || this.#title_element.dataset.placeholder || '';
        }
    }

    #exitTranslationMode()
    {
        this.#translation_language = null;

        const alert_el = this.#container.querySelector('[data-glpi-kb-translation-alert]');
        if (alert_el) {
            alert_el.classList.add('d-none');
            alert_el.classList.remove('d-flex');
        }

        const editor_element = this.#container.querySelector('#kb-tiptap-editor');
        const edit_button = this.#container.querySelector('[data-action="toggle-edit"]');
        const save_button = this.#container.querySelector('[data-action="save"]');
        const cancel_button = this.#container.querySelector('[data-action="cancel"]');

        this.#original_content = this.#base_content;
        this.#original_title = this.#base_title;

        if (this.#editor) {
            this.#editor.setContent(this.#base_content);
            this.#editor.setEditable(false);
        } else if (editor_element) {
            editor_element.innerHTML = this.#base_content;
        }
        if (this.#title_element) {
            this.#title_element.textContent = this.#base_title;
        }
        this.#disableTitleEditing();
        this.#is_editing = false;

        // Restore main editor actions visibility
        const editor_actions = this.#container.querySelector('.kb-editor-actions');
        if (editor_actions) {
            editor_actions.classList.remove('d-none');
            editor_actions.classList.add('d-flex');
        }
        if (edit_button) {
            edit_button.classList.remove('d-none');
        }
        if (save_button) {
            save_button.classList.add('d-none');
        }
        if (cancel_button) {
            cancel_button.classList.add('d-none');
        }
    }

    async #saveTranslation(save_btn)
    {
        if (this.#item_id === null) {
            glpi_toast_error(__("Cannot save: article ID is missing"));
            return;
        }

        const new_title = this.#title_element
            ? this.#title_element.textContent.trim()
            : null;
        if (this.#title_element && new_title.length === 0) {
            glpi_toast_error(__("Title cannot be empty"));
            this.#title_element.focus();
            return;
        }

        const original_button_html = save_btn.innerHTML;
        save_btn.disabled = true;
        save_btn.innerHTML = `<i class="ti ti-loader me-1"></i>${__("Saving...")}`;

        if (this.#translation_language !== this.#default_language) {
            const body = {
                language: this.#translation_language,
                answer: this.#editor.getHTML(),
            };
            if (new_title !== null) {
                body.name = new_title;
            }
            await post(
                `Knowbase/KnowbaseItem/${this.#item_id}/Translation`,
                body
            );

            if (!this.#existing_translations.includes(this.#translation_language)) {
                this.#existing_translations.push(this.#translation_language);
                this.#updateTranslationsCount();
                this.#moveOptionToExistingGroup(this.#translation_language);
            }
            this.#updateDeleteButtonVisibility();

            this.#original_content = this.#editor.getHTML();
            if (new_title !== null) {
                this.#original_title = new_title;
            }

            glpi_toast_info(__("Translation saved successfully"));
        } else {
            const body = {
                answer: this.#editor.getHTML(),
            };
            if (new_title !== null) {
                body.name = new_title;
            }

            await post(`Knowbase/KnowbaseItem/${this.#item_id}/Answer`, body);

            this.#original_content = this.#editor.getHTML();
            this.#base_content = this.#original_content;
            if (new_title !== null) {
                this.#original_title = new_title;
                this.#base_title = this.#original_title;
            }

            glpi_toast_info(__("Article saved successfully"));
        }

        save_btn.disabled = false;
        save_btn.innerHTML = original_button_html;
    }

    async #deleteTranslation()
    {
        if (!this.#translation_language || this.#translation_language === this.#default_language) {
            return;
        }

        const confirmed = await glpi_confirm_danger({
            title: __('Delete translation'),
            message: __('Are you sure you want to delete this translation?'),
            confirm_label: __('Delete'),
        });
        if (!confirmed) {
            return;
        }

        await post(
            `Knowbase/KnowbaseItem/${this.#item_id}/Translation/${this.#translation_language}/Delete`,
            {}
        );

        const idx = this.#existing_translations.indexOf(this.#translation_language);
        if (idx !== -1) {
            this.#existing_translations.splice(idx, 1);
        }
        this.#updateTranslationsCount();

        this.#moveOptionToNewGroup(this.#translation_language);
        await this.#loadTranslationContent(this.#translation_language);
        this.#updateDeleteButtonVisibility();

        glpi_toast_info(__("Translation deleted successfully"));
    }

    #updateTranslationsCount()
    {
        const count_el = this.#container.querySelector('[data-glpi-kb-toggle-translation-mode]');
        if (count_el) {
            const count = this.#existing_translations.length;
            count_el.textContent = `${count} ${_n('translation', 'translations', count)}`;
        }
    }

    #moveOptionToExistingGroup(language_code)
    {
        const select = this.#container.querySelector('[data-glpi-kb-translation-language]');
        if (!select) {
            return;
        }

        const option = select.querySelector(`option[value="${CSS.escape(language_code)}"]`);
        if (!option) {
            return;
        }

        let existing_group = select.querySelector(`optgroup[label="${CSS.escape(__('Existing translations'))}"]`);
        if (!existing_group) {
            existing_group = document.createElement('optgroup');
            existing_group.label = __('Existing translations');
            select.prepend(existing_group);
        }

        existing_group.appendChild(option);

        const new_group = select.querySelector(`optgroup[label="${CSS.escape(__('Add new translation'))}"]`);
        if (new_group && new_group.children.length === 0) {
            new_group.remove();
        }

        select.value = language_code;
    }

    #moveOptionToNewGroup(language_code)
    {
        const select = this.#container.querySelector('[data-glpi-kb-translation-language]');
        if (!select) {
            return;
        }

        const option = select.querySelector(`option[value="${CSS.escape(language_code)}"]`);
        if (!option) {
            return;
        }

        let new_group = select.querySelector(`optgroup[label="${CSS.escape(__('Add new translation'))}"]`);
        if (!new_group) {
            new_group = document.createElement('optgroup');
            new_group.label = __('Add new translation');
            select.appendChild(new_group);
        }

        const options = Array.from(new_group.querySelectorAll('option'));
        const insert_before = options.find(o => o.textContent.localeCompare(option.textContent) > 0);
        if (insert_before) {
            new_group.insertBefore(option, insert_before);
        } else {
            new_group.appendChild(option);
        }

        const existing_group = select.querySelector(`optgroup[label="${CSS.escape(__('Existing translations'))}"]`);
        if (existing_group && existing_group.children.length === 0) {
            existing_group.remove();
        }

        select.value = language_code;
    }

    #updateDeleteButtonVisibility()
    {
        const delete_btn = this.#container.querySelector('[data-glpi-kb-translation-delete]');
        if (!delete_btn) {
            return;
        }

        if (
            this.#translation_language
            && this.#translation_language !== this.#default_language
            && this.#existing_translations.includes(this.#translation_language)
        ) {
            delete_btn.classList.remove('d-none');
        } else {
            delete_btn.classList.add('d-none');
        }
    }

    #getScheduledArticleIndicator()
    {
        return this.#container.querySelector(
            '[data-glpi-kb-visibility-dates-indicator]'
        );
    }

    #getScheduleDropdownToggle()
    {
        return this.#container.querySelector(
            '[data-glpi-kb-toggle-visibility-dates]'
        );
    }
}
