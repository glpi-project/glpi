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

import {
    createEditorDialog,
    createDialogField,
    createDialogCheckbox,
} from '/js/modules/TipTap/EditorDialog.js';

/**
 * Insert/Edit link dialog for the Knowledge Base editor.
 *
 * Replaces the former window.prompt() and adds the one attribute an author
 * actually needs control over: whether the link opens in a new tab.
 *
 * rel is left to the extension config (noopener noreferrer, applied to every
 * link). nofollow is deliberately not offered: it is a crawler hint, and the
 * KB (public FAQ included) is not built for search-engine indexing.
 *
 * @param {object} editor - Tiptap editor instance.
 */
export function showLinkDialog(editor) {
    const uid = Math.random().toString(36).slice(2, 9);
    const attrs = editor.getAttributes('link');

    const url_group = createDialogField(__('URL'), 'url', `link-url-${uid}`, attrs.href || '');
    const url_input = url_group.querySelector('input');
    url_input.placeholder = 'https://example.com';
    url_input.setAttribute('autocomplete', 'off');

    const new_tab_group = createDialogCheckbox(
        __('Open in new tab'),
        `link-new-tab-${uid}`,
        attrs.target === '_blank'
    );
    const new_tab_input = new_tab_group.querySelector('input');

    const error_msg = document.createElement('p');
    error_msg.className = 'text-danger small mt-1 mb-0';
    error_msg.style.display = 'none';

    const showError = () => {
        if (error_msg.style.display !== 'none') {
            return;
        }
        error_msg.textContent = __('This URL is not accepted. Check the scheme (http, https, mailto, tel, ftp).');
        error_msg.setAttribute('role', 'alert');
        error_msg.style.display = '';
    };

    url_input.addEventListener('input', () => {
        error_msg.removeAttribute('role');
        error_msg.style.display = 'none';
    });

    const { body } = createEditorDialog({
        editor,
        title: __('Insert/Edit link'),
        confirmLabel: __('Save'),
        onConfirm: ({ close }) => {
            const href = url_input.value.trim();

            if (href === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
            } else {
                if (!editor.can().setLink({ href })) {
                    showError();
                    url_input.focus();
                    return;
                }
                editor.chain().focus().extendMarkRange('link').setLink({
                    href,
                    target: new_tab_input.checked ? '_blank' : null,
                }).run();
            }

            close();
        },
    });

    body.appendChild(url_group);
    body.appendChild(error_msg);
    body.appendChild(new_tab_group);

    url_input.focus();
}
