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

/**
 * Shared modal shell for the Tiptap editor's dialogs (link, video, image).
 *
 * Owns everything that is identical between them: overlay, header with a close
 * button, body, footer, and the accessibility contract ; role="dialog",
 * aria-modal, aria-labelledby, Escape to close, a Tab focus trap, click-outside
 * to dismiss, and focus restored to the editor on close.
 *
 * Callers own only their fields: append them to the returned `body`.
 */

/**
 * @typedef {object} EditorDialogHandle
 * @property {HTMLElement} dialog - The dialog element.
 * @property {HTMLElement} body   - Append fields here.
 * @property {function(): void} close - Dismiss and refocus the editor.
 */

/**
 * @param {object} config
 * @param {object} config.editor - Tiptap editor instance.
 * @param {string} config.title
 * @param {string} config.confirmLabel
 * @param {function({close: function(): void}): void} config.onConfirm
 * @returns {EditorDialogHandle}
 */
export function createEditorDialog({ editor, title, confirmLabel, onConfirm }) {
    const uid = Math.random().toString(36).slice(2, 9);

    const overlay = document.createElement('div');
    overlay.className = 'editor-dialog-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'editor-dialog';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', `editor-dialog-title-${uid}`);

    // Header
    const header = document.createElement('div');
    header.className = 'editor-dialog-header';

    const header_title = document.createElement('span');
    header_title.id = `editor-dialog-title-${uid}`;
    header_title.textContent = title;

    const close_btn = document.createElement('button');
    close_btn.type = 'button';
    close_btn.className = 'editor-dialog-close';
    close_btn.setAttribute('aria-label', __('Close'));
    const close_icon = document.createElement('i');
    close_icon.className = 'ti ti-x';
    close_icon.setAttribute('aria-hidden', 'true');
    close_btn.appendChild(close_icon);

    header.appendChild(header_title);
    header.appendChild(close_btn);
    dialog.appendChild(header);

    // Body ; filled by the caller
    const body = document.createElement('div');
    body.className = 'editor-dialog-body';
    dialog.appendChild(body);

    // Footer
    const footer = document.createElement('div');
    footer.className = 'editor-dialog-footer';

    const cancel_btn = document.createElement('button');
    cancel_btn.type = 'button';
    cancel_btn.className = 'btn btn-outline-secondary';
    cancel_btn.textContent = __('Cancel');

    const confirm_btn = document.createElement('button');
    confirm_btn.type = 'button';
    confirm_btn.className = 'btn btn-primary';
    confirm_btn.textContent = confirmLabel;

    footer.appendChild(cancel_btn);
    footer.appendChild(confirm_btn);
    dialog.appendChild(footer);

    overlay.appendChild(dialog);
    document.body.appendChild(overlay);

    const close = () => {
        clearTimeout(keydown_timer);
        document.removeEventListener('keydown', handleKeydown, true);
        overlay.remove();
        editor.commands.focus();
    };

    const confirm = () => onConfirm({ close });

    // Read the focusable list at keypress time, not now: callers append their fields to `body` after this function has returned.
    const getFocusable = () => [
        close_btn,
        ...body.querySelectorAll('input, select, textarea, button'),
        cancel_btn,
        confirm_btn,
    ];

    const handleKeydown = (e) => {
        // Mid-composition, Enter and Escape belong to the IME.
        if (e.isComposing) {
            return;
        }

        if (e.key === 'Escape') {
            // The page's own Escape handlers are on document too, and registered first.
            e.stopImmediatePropagation();
            close();
            return;
        }

        // Enter submits from a field, not from a focused button.
        const active = document.activeElement;
        if (
            e.key === 'Enter'
            && body.contains(active)
            && active.tagName === 'INPUT'
        ) {
            e.preventDefault();
            confirm();
            return;
        }

        if (e.key === 'Tab') {
            const focusable = getFocusable();
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (!dialog.contains(active)) {
                e.preventDefault();
                first.focus();
            } else if (e.shiftKey && active === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && active === last) {
                e.preventDefault();
                first.focus();
            }
        }
    };
    // Deferred: the Enter that opens the dialog would otherwise confirm it right away.
    const keydown_timer = setTimeout(() => document.addEventListener('keydown', handleKeydown, true), 0);

    cancel_btn.addEventListener('click', close);
    close_btn.addEventListener('click', close);
    confirm_btn.addEventListener('click', confirm);
    let pressed_overlay = false;
    overlay.addEventListener('mousedown', (e) => {
        pressed_overlay = e.target === overlay;
    });
    overlay.addEventListener('mouseup', (e) => {
        if (e.target === overlay && pressed_overlay) {
            close();
        }
    });

    return { dialog, body, close };
}

/**
 * A labelled text/number/url input, ready to append to a dialog body.
 *
 * @param {string} label_text
 * @param {string} type - Any HTML input type.
 * @param {string} id
 * @param {string|number} value
 * @returns {HTMLElement}
 */
export function createDialogField(label_text, type, id, value) {
    const group = document.createElement('div');
    group.className = 'editor-dialog-field';

    const label = document.createElement('label');
    label.htmlFor = id;
    label.textContent = label_text;
    group.appendChild(label);

    const input = document.createElement('input');
    input.type = type;
    input.id = id;
    input.className = 'form-control';
    input.value = value;
    if (type === 'number') {
        input.min = '0';
    }
    group.appendChild(input);

    return group;
}

/**
 * A labelled checkbox, using Tabler's form-check layout so no bespoke CSS is needed.
 *
 * @param {string} label_text
 * @param {string} id
 * @param {boolean} checked
 * @returns {HTMLElement}
 */
export function createDialogCheckbox(label_text, id, checked) {
    const group = document.createElement('div');
    group.className = 'form-check';

    const input = document.createElement('input');
    input.type = 'checkbox';
    input.id = id;
    input.className = 'form-check-input';
    input.checked = checked;
    group.appendChild(input);

    const label = document.createElement('label');
    label.htmlFor = id;
    label.className = 'form-check-label';
    label.textContent = label_text;
    group.appendChild(label);

    return group;
}
