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

import { post } from '/js/modules/Ajax.js';

const DEBOUNCE_MS = 400;

/**
 * "Insert/Edit HTML Block" dialog. Previews the server-sanitized HTML, so it
 * matches what gets stored. Editor-agnostic: takes callbacks, not an editor.
 *
 * @param {object} options
 * @param {number} options.itemId - Saved KB article id.
 * @param {string} options.initialHtml - Existing source, or '' for a new block.
 * @param {(sanitizedHtml: string) => void} options.onSave
 * @param {() => void} [options.onClose]
 */
export function showHtmlBlockDialog({ itemId, initialHtml, onSave, onClose = () => {} }) {
    const uid = Math.random().toString(36).slice(2, 9);

    const overlay = document.createElement('div');
    overlay.className = 'image-dialog-overlay html-block-dialog-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'image-dialog html-block-dialog';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', `html-block-dialog-title-${uid}`);

    const header = document.createElement('div');
    header.className = 'image-dialog-header';
    const headerTitle = document.createElement('span');
    headerTitle.id = `html-block-dialog-title-${uid}`;
    headerTitle.textContent = __('Insert/Edit HTML Block');
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'image-dialog-close';
    closeBtn.setAttribute('aria-label', __('Close'));
    closeBtn.innerHTML = '<i class="ti ti-x" aria-hidden="true"></i>';
    header.appendChild(headerTitle);
    header.appendChild(closeBtn);
    dialog.appendChild(header);

    const body = document.createElement('div');
    body.className = 'image-dialog-body html-block-dialog-body';

    const sourceLabel = document.createElement('label');
    sourceLabel.htmlFor = `html-block-source-${uid}`;
    sourceLabel.textContent = __('HTML source');
    const sourceInput = document.createElement('textarea');
    sourceInput.id = `html-block-source-${uid}`;
    sourceInput.className = 'form-control html-block-source';
    sourceInput.value = initialHtml || '';
    sourceInput.setAttribute('autocomplete', 'off');
    sourceInput.setAttribute('spellcheck', 'false');

    const previewLabel = document.createElement('div');
    previewLabel.id = `html-block-preview-label-${uid}`;
    previewLabel.className = 'html-block-preview-label';
    previewLabel.textContent = __('Preview');
    const preview = document.createElement('div');
    preview.className = 'html-block-preview';
    preview.setAttribute('role', 'region');
    preview.setAttribute('aria-labelledby', previewLabel.id);

    const errorMsg = document.createElement('p');
    errorMsg.className = 'text-danger small mt-1 mb-0';
    errorMsg.style.display = 'none';

    body.appendChild(sourceLabel);
    body.appendChild(sourceInput);
    body.appendChild(previewLabel);
    body.appendChild(preview);
    body.appendChild(errorMsg);
    dialog.appendChild(body);

    const footer = document.createElement('div');
    footer.className = 'image-dialog-footer';
    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn btn-outline-secondary';
    cancelBtn.textContent = __('Cancel');
    const saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'btn btn-primary';
    saveBtn.textContent = __('Save');
    saveBtn.disabled = true;
    footer.appendChild(cancelBtn);
    footer.appendChild(saveBtn);
    dialog.appendChild(footer);

    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    sourceInput.focus();

    // Save uses this sanitized value, never the raw source.
    let lastSanitizedHtml = null;
    let debounceTimer = null;

    const showError = (message) => {
        errorMsg.textContent = message;
        errorMsg.setAttribute('role', 'alert');
        errorMsg.style.display = '';
    };
    const hideError = () => {
        errorMsg.removeAttribute('role');
        errorMsg.style.display = 'none';
    };

    const updatePreview = async () => {
        const raw = sourceInput.value;
        if (raw.trim() === '') {
            lastSanitizedHtml = null;
            saveBtn.disabled = true;
            preview.innerHTML = '';
            hideError();
            return;
        }
        try {
            const response = await post(`Knowbase/KnowbaseItem/${itemId}/SanitizeHtmlBlock`, { html: raw });
            const data = await response.json();
            // Source changed during the request: a newer one owns the state.
            if (sourceInput.value !== raw) {
                return;
            }
            // Unreachable today: `getSafeHtml()` escapes unknown tags rather
            // than emptying. Guards against a sanitizer change.
            if (!data.success || data.html.trim() === '') {
                lastSanitizedHtml = null;
                saveBtn.disabled = true;
                preview.innerHTML = '';
                showError(__('Nothing safe to insert from this HTML.'));
                return;
            }
            hideError();
            lastSanitizedHtml = data.html;
            saveBtn.disabled = false;
            preview.innerHTML = data.html;
        } catch {
            if (sourceInput.value !== raw) {
                return;
            }
            lastSanitizedHtml = null;
            saveBtn.disabled = true;
            showError(__('The preview could not be generated. Please try again.'));
        }
    };

    sourceInput.addEventListener('input', () => {
        // Previous preview is stale until the new request returns.
        lastSanitizedHtml = null;
        saveBtn.disabled = true;
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(updatePreview, DEBOUNCE_MS);
    });

    if (initialHtml) {
        updatePreview();
    }

    const close = () => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        document.removeEventListener('keydown', handleKeydown);
        overlay.remove();
        onClose();
    };

    const save = () => {
        if (!lastSanitizedHtml) {
            return;
        }
        onSave(lastSanitizedHtml);
        close();
    };

    const focusableEls = [closeBtn, sourceInput, cancelBtn, saveBtn];
    const handleKeydown = (e) => {
        if (e.key === 'Escape') {
            close();
            return;
        }
        if (e.key === 'Tab') {
            // A disabled Save cannot take focus.
            const enabledEls = focusableEls.filter((el) => !el.disabled);
            const first = enabledEls[0];
            const last = enabledEls[enabledEls.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    };
    document.addEventListener('keydown', handleKeydown);

    cancelBtn.addEventListener('click', close);
    closeBtn.addEventListener('click', close);
    saveBtn.addEventListener('click', save);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            close();
        }
    });
}
