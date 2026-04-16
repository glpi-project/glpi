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

/* global _, glpi_toast_error, uniqid */

/**
 * Generic file uploader with drag & drop support.
 *
 * Handles file selection, validation, immediate upload to the server,
 * progress tracking and file list rendering. Does not handle document
 * creation or any domain-specific logic.
 *
 * Emits a `file-uploader:change` CustomEvent on the container whenever
 * the internal state changes (file added, removed, upload complete, error).
 *
 * Elements are discovered inside the container via data attributes:
 *   - data-glpi-file-uploader-dropzone
 *   - data-glpi-file-uploader-input
 *   - data-glpi-file-uploader-preview
 *   - data-glpi-file-uploader-list
 */
export class FileUploader
{
    /** @type {HTMLElement} */
    #container;

    /** @type {HTMLElement} */
    #dropZone;

    /** @type {HTMLInputElement} */
    #fileInput;

    /** @type {HTMLElement} */
    #previewContainer;

    /** @type {HTMLElement} */
    #listContainer;

    /** @type {{ file: File, status: string, result: Object|null, error: string|null, xhr: XMLHttpRequest|null }[]} */
    #fileEntries = [];

    /** @type {AbortController} */
    #abortController;

    /**
     * @param {HTMLElement} container - Root element containing the uploader markup
     * @param {Object} options
     * @param {number} options.maxFileSize - Maximum file size in MB (default: CFG_GLPI.document_max_size || 50)
     */
    constructor(container, options = {})
    {
        this.#container = container;
        this.#dropZone = container.querySelector('[data-glpi-file-uploader-dropzone]');
        this.#fileInput = container.querySelector('[data-glpi-file-uploader-input]');
        this.#previewContainer = container.querySelector('[data-glpi-file-uploader-preview]');
        this.#listContainer = container.querySelector('[data-glpi-file-uploader-list]');
        this.#abortController = new AbortController();

        this.maxFileSize = (options.maxFileSize ?? CFG_GLPI?.document_max_size ?? 50) * 1024 * 1024;

        if (!this.#dropZone || !this.#fileInput) {
            throw new Error('FileUploader: Required elements not found (dropzone or input)');
        }

        this.#bindEvents();
        this.#dropZone.classList.remove('pe-none');
    }

    /**
     * @returns {{ file: File, status: string, result: Object|null }[]}
     */
    getSuccessfulEntries()
    {
        return this.#fileEntries.filter(e => e.status === 'success');
    }

    /**
     * @returns {boolean}
     */
    isUploading()
    {
        return this.#fileEntries.some(e => e.status === 'uploading' || e.status === 'pending');
    }

    /**
     * @returns {boolean}
     */
    hasSuccessfulUploads()
    {
        return this.#fileEntries.some(e => e.status === 'success');
    }

    reset()
    {
        for (const entry of this.#fileEntries) {
            if (entry.xhr) {
                entry.xhr.abort();
            }
        }

        this.#fileEntries = [];
        this.#renderFileList();
        this.#emitChange();
    }

    destroy()
    {
        this.reset();
        this.#abortController.abort();
    }

    /**
     * Upload a single file to the GLPI temp directory without requiring
     * an instance or any DOM elements.
     *
     * @param {File} file - The file to upload
     * @returns {Promise<{name: string, prefix: string, display: string, id: string}>}
     * @throws {Error} If the upload fails or the server returns an error.
     */
    static async uploadFile(file)
    {
        const uniquePrefix = uniqid('', true);
        const uploadName = uniquePrefix + file.name;
        const renamedFile = new File([file], uploadName, { type: file.type });

        const formData = new FormData();
        formData.append('name', 'filename');
        formData.append('filename[]', renamedFile);

        const response = await fetch(
            `${CFG_GLPI.root_doc}/ajax/fileupload.php`,
            {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            }
        );

        if (!response.ok) {
            throw new Error(__('Upload failed'));
        }

        const result = await response.json();
        const fileData = result.filename?.[0];

        if (!fileData || fileData.error) {
            throw new Error(fileData?.error || __('Upload failed'));
        }

        return fileData;
    }

    #bindEvents()
    {
        const signal = this.#abortController.signal;

        // Drag & Drop
        this.#dropZone.addEventListener('dragenter', (e) => this.#onDragEnter(e), { signal });
        this.#dropZone.addEventListener('dragover', (e) => this.#onDragOver(e), { signal });
        this.#dropZone.addEventListener('dragleave', (e) => this.#onDragLeave(e), { signal });
        this.#dropZone.addEventListener('drop', (e) => this.#onDrop(e), { signal });

        // File input
        this.#fileInput.addEventListener('change', (e) => {
            this.#addFiles([...e.target.files]);
            e.target.value = '';
        }, { signal });

        // Delegate remove button clicks
        if (this.#previewContainer) {
            this.#previewContainer.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('.file-uploader-remove');
                if (removeBtn) {
                    const index = parseInt(removeBtn.dataset.index, 10);
                    this.#removeFile(index);
                }
            }, { signal });
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

        this.#renderFileList();
        this.#emitChange();

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
        if (file.size > this.maxFileSize) {
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

        if (entry?.xhr) {
            entry.xhr.abort();
        }

        this.#fileEntries.splice(index, 1);
        this.#renderFileList();
        this.#emitChange();
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
        const uniquePrefix = uniqid('', true);
        const uploadName = uniquePrefix + file.name;
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

            this.#emitChange();
        });

        xhr.addEventListener('error', () => {
            entry.xhr = null;
            entry.status = 'error';
            entry.error = __('Upload failed');
            this.#updateFileProgress(index, 0, 'error');
            this.#showFileError(index, entry.error);
            glpi_toast_error(`${file.name}: ${__('Upload failed')}`);
            this.#emitChange();
        });

        xhr.open('POST', `${CFG_GLPI.root_doc}/ajax/fileupload.php`);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    }

    #renderFileList()
    {
        if (!this.#previewContainer || !this.#listContainer) {
            return;
        }

        if (this.#fileEntries.length === 0) {
            this.#previewContainer.classList.add('d-none');
            this.#listContainer.innerHTML = '';
            return;
        }

        this.#previewContainer.classList.remove('d-none');
        this.#listContainer.innerHTML = this.#fileEntries.map((entry, index) => {
            const file = entry.file;
            const isError = entry.status === 'error';
            const isSuccess = entry.status === 'success';
            const errorClass = isError ? 'border-danger bg-danger bg-opacity-10' : '';
            const statusIcon = isSuccess
                ? '<i class="ti ti-check text-success ms-2"></i>'
                : '';

            return `
            <div class="file-uploader-item d-flex align-items-center p-2 border rounded mb-2 ${errorClass}" role="listitem" data-file-index="${_.escape(index)}">
                <i class="ti ${_.escape(this.#getFileIcon(file.name))} me-2 text-muted"></i>
                <div class="flex-grow-1 min-width-0">
                    <div class="fw-medium text-truncate">${_.escape(file.name)}</div>
                    <small class="text-muted">${_.escape(this.#formatFileSize(file.size))}</small>
                    ${isError ? `<div class="text-danger small mt-1">${_.escape(entry.error)}</div>` : ''}
                </div>
                ${statusIcon}
                <button type="button"
                        class="btn btn-sm btn-ghost-danger file-uploader-remove ms-2"
                        data-index="${_.escape(index)}"
                        title="${__('Remove')}">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `;
        }).join('');
    }

    /**
     * @param {number} index
     * @param {string} message
     */
    #showFileError(index, message)
    {
        if (!this.#previewContainer) {
            return;
        }

        const fileItem = this.#previewContainer.querySelector(`[data-file-index="${CSS.escape(index)}"]`);
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
        if (!this.#previewContainer) {
            return;
        }

        const fileItem = this.#previewContainer.querySelector(`[data-file-index="${CSS.escape(index)}"]`);
        if (!fileItem) {
            return;
        }

        let progressBar = fileItem.querySelector('.file-uploader-progress');

        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.className = 'file-uploader-progress mt-1';
            progressBar.innerHTML = '<div class="file-uploader-progress-bar"></div>';
            fileItem.querySelector('.flex-grow-1').appendChild(progressBar);
        }

        const bar = progressBar.querySelector('.file-uploader-progress-bar');
        bar.style.width = `${percent}%`;
        bar.classList.remove('bg-success', 'bg-danger');

        if (status === 'success') {
            bar.classList.add('bg-success');
        } else if (status === 'error') {
            bar.classList.add('bg-danger');
        }
    }

    #emitChange()
    {
        this.#container.dispatchEvent(new CustomEvent('file-uploader:change', {
            bubbles: true,
            detail: {
                hasSuccessful: this.hasSuccessfulUploads(),
                isUploading: this.isUploading(),
                count: this.#fileEntries.length,
            },
        }));
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
}
