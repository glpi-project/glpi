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

/* global getAjaxCsrfToken, bootstrap, glpi_toast_error, glpi_toast_info */

import { FileUploader } from '../FileUploader.js';

/**
 * KB-specific document upload controller.
 *
 * Wraps the generic FileUploader and adds document creation logic,
 * modal lifecycle management, and page reload on success.
 */
export class DocumentUploadController
{
    /** @type {HTMLElement} */
    #container;

    /** @type {FileUploader} */
    #uploader;

    /** @type {HTMLElement|null} */
    #modal;

    /** @type {HTMLFormElement} */
    #form;

    /** @type {HTMLButtonElement} */
    #uploadBtn;

    /**
     * @param {HTMLElement} container - Form container (tab pane)
     * @param {HTMLElement|null} modal - Modal element (optional)
     */
    constructor(container, modal = null)
    {
        this.#container = container;
        this.#modal = modal;
        this.#form = container.querySelector('form');
        this.#uploadBtn = container.querySelector('[data-glpi-kb-upload-submit]');

        if (!this.#form) {
            console.error('DocumentUploadController: form element not found');
            return;
        }

        this.#uploader = new FileUploader(container);

        this.#bindEvents();
    }

    #bindEvents()
    {
        // Update submit button state when uploader state changes
        this.#container.addEventListener('file-uploader:change', () => {
            this.#updateUploadButton();
        });

        // Form submission
        this.#form.addEventListener('submit', (e) => this.#onSubmit(e));

        // Modal lifecycle
        if (this.#modal) {
            this.#modal.addEventListener('hide.bs.modal', () => {
                if (document.activeElement && this.#modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            this.#modal.addEventListener('hidden.bs.modal', () => {
                this.#uploader.reset();
                const descField = this.#form.querySelector('#kb-document-description');
                if (descField) {
                    descField.value = '';
                }
            });
        }
    }

    #updateUploadButton()
    {
        if (!this.#uploadBtn) {
            return;
        }

        this.#uploadBtn.disabled = this.#uploader.isUploading()
            || !this.#uploader.hasSuccessfulUploads();
    }

    /**
     * @param {Event} e
     */
    async #onSubmit(e)
    {
        e.preventDefault();

        const successEntries = this.#uploader.getSuccessfulEntries();
        if (successEntries.length === 0) {
            return;
        }

        this.#setLoading(true);

        try {
            await this.#createDocuments(successEntries);
            this.#onSuccess();
        } catch (error) {
            console.error('Document creation failed:', error);
            glpi_toast_error(__('Upload failed: %s').replace('%s', error.message));
        } finally {
            this.#setLoading(false);
        }
    }

    /**
     * @param {boolean} loading
     */
    #setLoading(loading)
    {
        if (!this.#uploadBtn) {
            return;
        }

        if (loading) {
            this.#uploadBtn.disabled = true;
            this.#uploadBtn.dataset.originalHtml = this.#uploadBtn.innerHTML;
            this.#uploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>${__('Uploading...')}`;
        } else {
            this.#updateUploadButton();
            if (this.#uploadBtn.dataset.originalHtml) {
                this.#uploadBtn.innerHTML = this.#uploadBtn.dataset.originalHtml;
            }
        }
    }

    /**
     * @param {{ file: File, status: string, result: Object|null }[]} successEntries
     */
    async #createDocuments(successEntries)
    {
        const description = this.#form.querySelector('#kb-document-description')?.value || '';
        const itemtype = this.#form.querySelector('[name="itemtype"]')?.value;
        const items_id = this.#form.querySelector('[name="items_id"]')?.value;

        for (const entry of successEntries) {
            const uploadedFile = entry.result;
            const formData = new FormData();
            const fullTempName = uploadedFile.name || '';
            const displayName = uploadedFile.display || '';
            const filePrefix = uploadedFile.prefix || '';
            const fileTag = uploadedFile.id || '';

            formData.append('_filename[0]', fullTempName);
            formData.append('_prefix_filename[0]', filePrefix);
            formData.append('_tag_filename[0]', fileTag);
            formData.append('name', displayName.replace(/\.[^.]+$/, ''));
            formData.append('comment', description);

            if (itemtype && items_id) {
                formData.append('itemtype', itemtype);
                formData.append('items_id', items_id);
            }

            formData.append('add', '1');

            const response = await fetch(
                `${CFG_GLPI.root_doc}/front/document.form.php`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                    },
                }
            );

            if (!response.ok) {
                throw new Error(`Failed to create document for ${displayName}`);
            }
        }
    }

    #onSuccess()
    {
        const count = this.#uploader.getSuccessfulEntries().length;

        if (this.#modal) {
            const modalInstance = bootstrap.Modal.getInstance(this.#modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }

        glpi_toast_info(
            count === 1
                ? __('Document uploaded successfully')
                : __('%d documents uploaded successfully').replace('%d', count)
        );

        this.#container.dispatchEvent(new CustomEvent('documents:uploaded', {
            bubbles: true,
            detail: { count: count }
        }));

        window.location.reload();
    }
}
