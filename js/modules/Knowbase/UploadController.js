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

/* global bootstrap, glpi_toast_error, glpi_toast_info */

import { post } from '/js/modules/Ajax.js';
import { FileUploader } from '/js/modules/FileUploader.js';

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

    /** @type {HTMLElement|null} */
    #errorContainer;

    /** @type {HTMLElement|null} */
    #dropzone;

    /** @type {HTMLInputElement|null} */
    #fileInput;

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
        this.#errorContainer = container.querySelector('[data-glpi-kb-upload-error]');
        this.#dropzone = container.querySelector('[data-glpi-file-uploader-dropzone]');
        this.#fileInput = container.querySelector('[data-glpi-file-uploader-input]');

        if (!this.#form) {
            throw new Error('DocumentUploadController: form element not found');
        }

        this.#uploader = new FileUploader(container);

        this.#bindEvents();
    }

    #bindEvents()
    {
        // Clear validation error as soon as the uploader state changes
        this.#container.addEventListener('file-uploader:change', () => {
            if (this.#uploader.hasSuccessfulUploads()) {
                this.#clearError();
            }
        });

        // Validate on the upload button click rather than the form 'submit'
        // event, so that Enter on the focused file input still opens the
        // native file picker via the browser's default activation behaviour
        // instead of being captured by a form-level handler.
        if (this.#uploadBtn) {
            this.#uploadBtn.addEventListener('click', (e) => this.#onSubmit(e));
        }

        // Safety net: the form has no `action` attribute, so block any native
        // submission triggered through alternate paths (e.g. implicit submit).
        this.#form.addEventListener('submit', (e) => e.preventDefault());

        // Keyboard accessibility: explicitly open the picker on Enter when
        // the visually-hidden file input has focus. The browser's default
        // activation can be unreliable on a `visually-hidden` input, and
        // letting the keydown bubble could otherwise trigger implicit form
        // submission instead of the file picker.
        if (this.#fileInput) {
            this.#fileInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.#fileInput.click();
                }
            });
        }

        // Modal lifecycle
        if (this.#modal) {
            this.#modal.addEventListener('hide.bs.modal', () => {
                if (document.activeElement && this.#modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            this.#modal.addEventListener('hidden.bs.modal', () => {
                this.#uploader.reset();
                this.#clearError();
                const descField = this.#form.querySelector('#kb-document-description');
                if (descField) {
                    descField.value = '';
                }
            });
        }
    }

    #showError(message)
    {
        if (this.#errorContainer) {
            this.#errorContainer.textContent = message;
        }

        // The label provides the visual error state (is-invalid border).
        if (this.#dropzone) {
            this.#dropzone.classList.add('is-invalid');
        }

        // The input is the actual focusable control: ARIA + focus go on it.
        if (this.#fileInput) {
            this.#fileInput.setAttribute('aria-invalid', 'true');
            if (this.#errorContainer?.id) {
                this.#fileInput.setAttribute('aria-describedby', this.#errorContainer.id);
            }
            this.#fileInput.focus();
        }
    }

    #clearError()
    {
        if (this.#errorContainer) {
            this.#errorContainer.textContent = '';
        }

        if (this.#dropzone) {
            this.#dropzone.classList.remove('is-invalid');
        }

        if (this.#fileInput) {
            this.#fileInput.removeAttribute('aria-invalid');
            this.#fileInput.removeAttribute('aria-describedby');
        }
    }

    /**
     * @param {Event} e
     */
    async #onSubmit(e)
    {
        e.preventDefault();

        if (this.#uploader.isUploading()) {
            this.#showError(__('Please wait until file uploads complete.'));
            return;
        }

        const successEntries = this.#uploader.getSuccessfulEntries();
        if (successEntries.length === 0) {
            this.#showError(__('Please add at least one valid file before uploading.'));
            return;
        }

        this.#clearError();
        this.#setLoading(true);

        try {
            const documents = await this.#createDocuments(successEntries);
            this.#onSuccess(documents);
        } catch (error) {
            glpi_toast_error(__('Upload failed'));
            throw error;
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
            this.#uploadBtn.disabled = false;
            if (this.#uploadBtn.dataset.originalHtml) {
                this.#uploadBtn.innerHTML = this.#uploadBtn.dataset.originalHtml;
            }
        }
    }

    /**
     * @param {{ file: File, status: string, result: Object|null }[]} successEntries
     * @returns {Promise<Array>}
     */
    async #createDocuments(successEntries)
    {
        const description = this.#form.querySelector('#kb-document-description')?.value || '';
        const items_id = this.#form.querySelector('[name="items_id"]')?.value;

        const files = successEntries.map((entry) => {
            const uploaded_file = entry.result;
            return {
                name:             (uploaded_file.display || '').replace(/\.[^.]+$/, ''),
                comment:          description,
                _filename:        uploaded_file.name    || '',
                _prefix_filename: uploaded_file.prefix  || '',
                _tag_filename:    uploaded_file.id      || '',
            };
        });

        const response = await post(`Knowbase/${items_id}/UploadDocuments`, { files });
        const body = await response.json();
        return body.documents ?? [];
    }

    /**
     * @param {Array} documents
     */
    #onSuccess(documents)
    {
        const count = documents.length;

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
            detail: { count, documents },
        }));
    }
}
