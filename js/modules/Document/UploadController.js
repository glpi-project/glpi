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

/* global getAjaxCsrfToken, bootstrap, glpi_toast_error, glpi_toast_info, uniqid */

/**
 * Controller for document upload modal with drag & drop support
 */
export class GlpiDocumentUploadController
{
    /** @type {HTMLElement} */
    #container;

    /** @type {HTMLElement} */
    #dropZone;

    /** @type {HTMLInputElement} */
    #fileInput;

    /** @type {HTMLElement} */
    #previewContainer;

    /** @type {HTMLButtonElement} */
    #uploadBtn;

    /** @type {HTMLFormElement} */
    #form;

    /** @type {{ file: File, status: string, result: Object|null, error: string|null, xhr: XMLHttpRequest|null }[]} */
    #fileEntries = [];

    /** @type {HTMLElement|null} */
    #modal;

    /**
     * @param {HTMLElement} container - Form container (tab pane)
     * @param {HTMLElement|null} modal - Modal element (optional)
     */
    constructor(container, modal = null)
    {
        this.#container = container;
        this.#modal = modal;
        this.#dropZone = container.querySelector('.kb-dropzone');
        this.#fileInput = container.querySelector('#kb-document-input');
        this.#previewContainer = container.querySelector('#kb-file-preview');
        this.#uploadBtn = container.querySelector('#kb-upload-btn');
        this.#form = container.querySelector('#kb-document-upload-form');

        if (!this.#dropZone || !this.#fileInput || !this.#form) {
            console.error('GlpiDocumentUploadController: Required elements not found');
            return;
        }

        this.#bindEvents();
    }

    #bindEvents()
    {
        // Drag & Drop events
        this.#dropZone.addEventListener('dragenter', (e) => this.#onDragEnter(e));
        this.#dropZone.addEventListener('dragover', (e) => this.#onDragOver(e));
        this.#dropZone.addEventListener('dragleave', (e) => this.#onDragLeave(e));
        this.#dropZone.addEventListener('drop', (e) => this.#onDrop(e));

        // File input change (click to browse)
        this.#fileInput.addEventListener('change', (e) => {
            this.#addFiles([...e.target.files]);
            e.target.value = '';
        });

        // Form submission
        this.#form.addEventListener('submit', (e) => this.#onSubmit(e));

        // Delegate remove button clicks
        this.#previewContainer.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.kb-file-remove');
            if (removeBtn) {
                const index = parseInt(removeBtn.dataset.index, 10);
                this.#removeFile(index);
            }
        });

        // Modal events
        if (this.#modal) {
            // Move focus away before modal hides to prevent aria-hidden warning
            this.#modal.addEventListener('hide.bs.modal', () => {
                if (document.activeElement && this.#modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            // Reset state when modal is hidden
            this.#modal.addEventListener('hidden.bs.modal', () => this.#reset());
        }
    }

    #onDragEnter(e)
    {
        e.preventDefault();
        this.#dropZone.classList.add('dragging');
    }

    #onDragOver(e)
    {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }

    #onDragLeave(e)
    {
        if (!this.#dropZone.contains(e.relatedTarget)) {
            this.#dropZone.classList.remove('dragging');
        }
    }

    #onDrop(e)
    {
        e.preventDefault();
        this.#dropZone.classList.remove('dragging');
        this.#addFiles([...e.dataTransfer.files]);
    }

    /**
     * @param {File[]} newFiles
     */
    #addFiles(newFiles)
    {
        const validFiles = newFiles.filter(file => this.#validateFile(file));
        const startIndex = this.#fileEntries.length;

        for (const file of validFiles) {
            this.#fileEntries.push({
                file,
                status: 'pending',
                result: null,
                error: null,
                xhr: null,
            });
        }

        this.#updatePreview();
        this.#updateUploadButton();

        // Start uploading each new file immediately
        for (let i = startIndex; i < this.#fileEntries.length; i++) {
            this.#uploadSingleFile(i);
        }
    }

    /**
     * @param {File} file
     * @returns {boolean}
     */
    #validateFile(file)
    {
        const maxSize = (CFG_GLPI?.document_max_size || 50) * 1024 * 1024;

        if (file.size > maxSize) {
            glpi_toast_error(__('File %s exceeds maximum size').replace('%s', file.name));
            return false;
        }

        if (file.size === 0) {
            glpi_toast_error(__('File %s is empty').replace('%s', file.name));
            return false;
        }

        return true;
    }

    /**
     * @param {number} index
     */
    #removeFile(index)
    {
        const entry = this.#fileEntries[index];

        // Abort active upload if in progress
        if (entry?.xhr) {
            entry.xhr.abort();
        }

        this.#fileEntries.splice(index, 1);
        this.#updatePreview();
        this.#updateUploadButton();
    }

    #updatePreview()
    {
        const listContainer = this.#previewContainer.querySelector('.kb-file-list');

        if (this.#fileEntries.length === 0) {
            this.#previewContainer.classList.add('d-none');
            listContainer.innerHTML = '';
            return;
        }

        this.#previewContainer.classList.remove('d-none');
        listContainer.innerHTML = this.#fileEntries.map((entry, index) => {
            const file = entry.file;
            const isError = entry.status === 'error';
            const isSuccess = entry.status === 'success';
            const errorClass = isError ? 'border-danger bg-danger bg-opacity-10' : '';
            const statusIcon = isSuccess
                ? '<i class="ti ti-check text-success ms-2"></i>'
                : '';

            return `
            <div class="kb-file-item d-flex align-items-center p-2 border rounded mb-2 ${errorClass}" role="listitem" data-file-index="${index}">
                <i class="ti ${this.#getFileIcon(file.name)} me-2 text-muted"></i>
                <div class="flex-grow-1 min-width-0">
                    <div class="fw-medium text-truncate">${this.#escapeHtml(file.name)}</div>
                    <small class="text-muted">${this.#formatFileSize(file.size)}</small>
                    ${isError ? `<div class="text-danger small mt-1">${this.#escapeHtml(entry.error)}</div>` : ''}
                </div>
                ${statusIcon}
                <button type="button"
                        class="btn btn-sm btn-ghost-danger kb-file-remove ms-2"
                        data-index="${index}"
                        title="${__('Remove')}">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `;
        }).join('');
    }

    #updateUploadButton()
    {
        if (this.#fileEntries.length === 0) {
            this.#uploadBtn.disabled = true;
            return;
        }

        const hasUploading = this.#fileEntries.some(e => e.status === 'uploading' || e.status === 'pending');
        const hasSuccess = this.#fileEntries.some(e => e.status === 'success');

        this.#uploadBtn.disabled = hasUploading || !hasSuccess;
    }

    /**
     * @param {Event} e
     */
    async #onSubmit(e)
    {
        e.preventDefault();

        const successEntries = this.#fileEntries.filter(e => e.status === 'success');
        if (successEntries.length === 0) {
            return;
        }

        this.#setLoading(true);

        try {
            await this.#createDocuments(successEntries);
            this.#onSuccess();
        } catch (error) {
            console.error('Document creation failed:', error);
            this.#onError(error);
        } finally {
            this.#setLoading(false);
        }
    }

    /**
     * @param {boolean} loading
     */
    #setLoading(loading)
    {
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
     * @param {number} index
     */
    #uploadSingleFile(index)
    {
        const entry = this.#fileEntries[index];
        const file = entry.file;

        entry.status = 'uploading';

        const xhr = new XMLHttpRequest();
        entry.xhr = xhr;

        const formData = new FormData();
        // Generate unique prefix to avoid filename collisions (same as setupFileUpload in common.js)
        const uniquePrefix = uniqid('', true);
        const uploadName = uniquePrefix + file.name;

        // Create a new File object with the prefixed name
        const renamedFile = new File([file], uploadName, { type: file.type });

        formData.append('name', 'filename');
        formData.append('filename[]', renamedFile);

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                this.#updateFileProgress(index, percent);
            }
        });

        xhr.addEventListener('load', () => {
            entry.xhr = null;

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    const fileData = response.filename?.[0];

                    // Check for backend error FIRST (e.g. filetype not allowed)
                    if (fileData?.error) {
                        entry.status = 'error';
                        entry.error = fileData.error;
                        this.#updateFileProgress(index, 0, 'error');
                        this.#showFileError(index, fileData.error);
                        glpi_toast_error(`${file.name}: ${fileData.error}`);
                    } else if (fileData) {
                        entry.status = 'success';
                        entry.result = fileData;
                        this.#updateFileProgress(index, 100, 'success');
                    } else {
                        entry.status = 'error';
                        entry.error = __('Invalid server response');
                        this.#updateFileProgress(index, 0, 'error');
                        this.#showFileError(index, entry.error);
                    }
                } catch {
                    entry.status = 'error';
                    entry.error = __('Invalid server response');
                    this.#updateFileProgress(index, 0, 'error');
                    this.#showFileError(index, entry.error);
                }
            } else {
                entry.status = 'error';
                entry.error = __('Upload failed');
                this.#updateFileProgress(index, 0, 'error');
                this.#showFileError(index, entry.error);
                glpi_toast_error(`${file.name}: ${__('Upload failed')}`);
            }

            this.#updateUploadButton();
        });

        xhr.addEventListener('error', () => {
            entry.xhr = null;
            entry.status = 'error';
            entry.error = __('Upload failed');
            this.#updateFileProgress(index, 0, 'error');
            this.#showFileError(index, entry.error);
            glpi_toast_error(`${file.name}: ${__('Upload failed')}`);
            this.#updateUploadButton();
        });

        xhr.open('POST', `${CFG_GLPI.root_doc}/ajax/fileupload.php`);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-Glpi-Csrf-Token', getAjaxCsrfToken());
        xhr.send(formData);
    }

    /**
     * @param {number} index
     * @param {string} message
     */
    #showFileError(index, message)
    {
        const fileItem = this.#previewContainer.querySelector(`[data-file-index="${index}"]`);
        if (!fileItem || fileItem.querySelector('.text-danger')) {
            return;
        }

        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-danger small mt-1';
        errorDiv.textContent = message;
        fileItem.querySelector('.flex-grow-1').appendChild(errorDiv);

        fileItem.classList.add('border-danger', 'bg-danger', 'bg-opacity-10');
    }

    /**
     * @param {number} index
     * @param {number} percent
     * @param {string} status
     */
    #updateFileProgress(index, percent, status = 'uploading')
    {
        const fileItem = this.#previewContainer.querySelector(`[data-file-index="${index}"]`);
        if (!fileItem) {
            return;
        }

        let progressBar = fileItem.querySelector('.kb-upload-progress');

        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.className = 'kb-upload-progress mt-1';
            progressBar.innerHTML = '<div class="kb-upload-progress-bar"></div>';
            fileItem.querySelector('.flex-grow-1').appendChild(progressBar);
        }

        const bar = progressBar.querySelector('.kb-upload-progress-bar');
        bar.style.width = `${percent}%`;
        bar.classList.remove('bg-success', 'bg-danger');

        if (status === 'success') {
            bar.classList.add('bg-success');
        } else if (status === 'error') {
            bar.classList.add('bg-danger');
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
        const count = this.#fileEntries.filter(e => e.status === 'success').length;

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

    #onError(error)
    {
        glpi_toast_error(__('Upload failed: %s').replace('%s', error.message));
    }

    #reset()
    {
        // Abort any in-progress uploads
        for (const entry of this.#fileEntries) {
            if (entry.xhr) {
                entry.xhr.abort();
            }
        }

        this.#fileEntries = [];
        this.#updatePreview();
        this.#updateUploadButton();

        const descField = this.#form.querySelector('#kb-document-description');
        if (descField) {
            descField.value = '';
        }
    }

    /**
     * @param {string} filename
     * @returns {string}
     */
    #getFileIcon(filename)
    {
        const ext = filename.split('.').pop()?.toLowerCase() || '';
        const iconMap = {
            'pdf': 'ti-file-type-pdf',
            'doc': 'ti-file-type-doc',
            'docx': 'ti-file-type-docx',
            'xls': 'ti-file-type-xls',
            'xlsx': 'ti-file-type-xls',
            'ppt': 'ti-file-type-ppt',
            'pptx': 'ti-file-type-ppt',
            'zip': 'ti-file-zip',
            'rar': 'ti-file-zip',
            '7z': 'ti-file-zip',
            'tar': 'ti-file-zip',
            'gz': 'ti-file-zip',
            'jpg': 'ti-photo',
            'jpeg': 'ti-photo',
            'png': 'ti-photo',
            'gif': 'ti-photo',
            'svg': 'ti-photo',
            'webp': 'ti-photo',
            'txt': 'ti-file-text',
            'md': 'ti-markdown',
            'csv': 'ti-file-spreadsheet',
            'json': 'ti-file-code',
            'xml': 'ti-file-code',
            'html': 'ti-file-code',
            'css': 'ti-file-code',
            'js': 'ti-file-code',
        };

        return iconMap[ext] || 'ti-file';
    }

    /**
     * @param {number} bytes
     * @returns {string}
     */
    #formatFileSize(bytes)
    {
        if (bytes === 0) {
            return '0 B';
        }
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`;
    }

    /**
     * @param {string} text
     * @returns {string}
     */
    #escapeHtml(text)
    {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
