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

/* global setupAjaxDropdown, bootstrap, glpi_toast_error, glpi_toast_info, _ */

import { post } from '/js/modules/Ajax.js';

/**
 * Controller for the "Link a document" tab in the KB document modal.
 *
 * Initializes a Select2 dropdown for document search, manages a visual
 * list of selected documents (matching the upload tab style), and
 * handles AJAX submission to link them to the KB article.
 */
export class DocumentLinkController
{
    /** @type {HTMLElement} */
    #container;

    /** @type {HTMLElement|null} */
    #modal;

    /** @type {HTMLButtonElement} */
    #submitBtn;

    /** @type {HTMLElement} */
    #previewContainer;

    /** @type {HTMLElement} */
    #listContainer;

    /** @type {Map<number, {id: number, text: string}>} */
    #selectedDocuments = new Map();

    /** @type {number} */
    #itemId;

    /** @type {number[]} */
    #usedIds;

    /**
     * @param {HTMLElement} container - The #kb-modal-link-pane element
     * @param {HTMLElement|null} modal - The modal element (optional)
     */
    constructor(container, modal = null)
    {
        this.#container = container;
        this.#modal = modal;
        this.#submitBtn = container.querySelector('[data-glpi-kb-link-submit]');
        this.#previewContainer = container.querySelector('[data-glpi-kb-link-preview]');
        this.#listContainer = container.querySelector('[data-glpi-kb-link-list]');
        this.#itemId = parseInt(container.dataset.glpiKbLinkItemId, 10);
        this.#usedIds = JSON.parse(container.dataset.glpiKbLinkUsedIds || '[]');

        this.#initSelect2();
        this.#bindEvents();
    }

    #initSelect2()
    {
        const container = this.#container;

        setupAjaxDropdown({
            field_id: 'kb-link-document-select',
            url: container.dataset.glpiKbLinkAjaxUrl,
            params: {
                _idor_token: container.dataset.glpiKbLinkIdorToken,
                itemtype: 'Document',
                entity_restrict: -1,
                display_emptychoice: 1,
                used: this.#usedIds,
            },
            dropdown_max: 100,
            width: '100%',
            placeholder: __('Search for a document...'),
            allowclear: true,
            multiple: false,
            container_css_class: '',
            parent_id_field: '',
            on_change: '',
            ajax_limit_count: 0,
        });
    }

    #bindEvents()
    {
        // When a document is selected in the dropdown, add to list
        $('#kb-link-document-select').on('select2:select', (e) => {
            const data = e.params.data;
            if (data && data.id) {
                this.#addDocument(parseInt(data.id, 10), data.text);
            }
        });

        // Submit button
        if (this.#submitBtn) {
            this.#submitBtn.addEventListener('click', () => this.#onSubmit());
        }

        // Remove button delegation on list
        if (this.#listContainer) {
            this.#listContainer.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-glpi-kb-link-remove]');
                if (btn) {
                    const docId = parseInt(btn.dataset.glpiKbLinkRemove, 10);
                    this.#removeDocument(docId);
                }
            });
        }

        // Reset on modal close
        if (this.#modal) {
            this.#modal.addEventListener('hidden.bs.modal', () => {
                this.#reset();
            });
        }
    }

    /**
     * @param {number} id
     * @param {string} text
     */
    #addDocument(id, text)
    {
        if (this.#selectedDocuments.has(id)) {
            return;
        }

        this.#selectedDocuments.set(id, { id, text });

        // Add to the used list so it won't appear in dropdown again
        this.#usedIds.push(id);

        // Clear the Select2 selection
        $('#kb-link-document-select').val(null).trigger('change');

        this.#renderList();
        this.#updateSubmitButton();
    }

    /**
     * @param {number} id
     */
    #removeDocument(id)
    {
        this.#selectedDocuments.delete(id);

        // Remove from used list
        const idx = this.#usedIds.indexOf(id);
        if (idx !== -1) {
            this.#usedIds.splice(idx, 1);
        }

        this.#renderList();
        this.#updateSubmitButton();
    }

    #renderList()
    {
        if (!this.#previewContainer || !this.#listContainer) {
            return;
        }

        if (this.#selectedDocuments.size === 0) {
            this.#previewContainer.classList.add('d-none');
            this.#listContainer.innerHTML = '';
            return;
        }

        this.#previewContainer.classList.remove('d-none');
        this.#listContainer.innerHTML = Array.from(this.#selectedDocuments.values())
            .map((doc) => `
                <div class="file-uploader-item d-flex align-items-center p-2 border rounded mb-2" role="listitem">
                    <i class="ti ti-file-symlink me-2 text-muted"></i>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-medium text-truncate">${_.escape(doc.text)}</div>
                    </div>
                    <button type="button"
                            class="btn btn-sm btn-ghost-danger file-uploader-remove ms-2"
                            data-glpi-kb-link-remove="${_.escape(doc.id)}"
                            title="${__('Remove')}">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            `).join('');
    }

    #updateSubmitButton()
    {
        if (this.#submitBtn) {
            this.#submitBtn.disabled = this.#selectedDocuments.size === 0;
        }
    }

    async #onSubmit()
    {
        if (this.#selectedDocuments.size === 0) {
            return;
        }

        this.#setLoading(true);

        try {
            const documents_ids = Array.from(this.#selectedDocuments.keys());
            const response = await post(`Knowbase/${this.#itemId}/LinkDocuments`, {
                documents_ids,
            });
            const result = await response.json();

            this.#onSuccess(result.linked_count);
        } catch (error) {
            console.error('Document linking failed:', error);
        } finally {
            this.#setLoading(false);
        }
    }

    /**
     * @param {number} count
     */
    #onSuccess(count)
    {
        if (this.#modal) {
            const modalInstance = bootstrap.Modal.getInstance(this.#modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }

        glpi_toast_info(
            count === 1
                ? __('Document linked successfully')
                : __('%d documents linked successfully').replace('%d', count)
        );

        window.location.reload();
    }

    /**
     * @param {boolean} loading
     */
    #setLoading(loading)
    {
        if (!this.#submitBtn) {
            return;
        }

        if (loading) {
            this.#submitBtn.disabled = true;
            this.#submitBtn.dataset.originalHtml = this.#submitBtn.innerHTML;
            this.#submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${__('Linking...')}`;
        } else {
            this.#updateSubmitButton();
            if (this.#submitBtn.dataset.originalHtml) {
                this.#submitBtn.innerHTML = this.#submitBtn.dataset.originalHtml;
            }
        }
    }

    #reset()
    {
        this.#selectedDocuments.clear();
        // Restore usedIds to original (only initially linked documents)
        const originalUsed = JSON.parse(this.#container.dataset.glpiKbLinkUsedIds || '[]');
        this.#usedIds.length = 0;
        this.#usedIds.push(...originalUsed);

        $('#kb-link-document-select').val(null).trigger('change');
        this.#renderList();
        this.#updateSubmitButton();
    }
}
