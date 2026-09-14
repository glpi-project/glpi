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

/* global TiptapCore */

import { showHtmlBlockDialog } from '/js/modules/TipTap/HtmlBlockDialog.js';

/**
 * KB "HTML Block" Tiptap node — holds a chunk of user-authored HTML that has
 * already been through `RichText::getSafeHtml()` (client-side, via the
 * sanitize-preview endpoint; authoritatively again on save). Stored as the
 * literal children of a `div.kb-html-block` wrapper, so it survives in the
 * article's `answer` field like any other rich content.
 *
 * Unlike VideoEmbed, there is no placeholder/allowlist-renderer pair here:
 * there is no fixed set of "known safe blocks" to allowlist against, so the
 * sanitizer itself is the one and only trust boundary.
 *
 * The marker is a plain CSS class, not a `data-*` attribute: GLPI's
 * sanitizer allows `class` on every element already but only allows an
 * explicit, hand-picked set of `data-*` attributes — reusing the class
 * avoids extending that allowlist for this feature.
 */
const { Node } = TiptapCore;

export const HtmlBlock = Node.create({
    name: 'kbHtmlBlock',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    addOptions() {
        return {
            itemId: null,
        };
    },

    addAttributes() {
        return {
            html: {
                default: '',
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'div.kb-html-block',
                getAttrs: (dom) => ({ html: dom.innerHTML }),
            },
        ];
    },

    renderHTML({ node }) {
        // A real DOM node is a valid ProseMirror DOMOutputSpec (not just the
        // array-tuple form), which is what lets the node's *children* be
        // arbitrary already-sanitized markup rather than something the
        // schema has to describe structurally.
        //
        // No role/aria here on purpose: this is the persisted form. `aria-*`
        // is stripped by `RichText::getSafeHtml()` anyway, and tagging the
        // reader-facing article with role="figure" would add noise to markup
        // that is meant to pass through transparently. The editor-only node
        // view below carries the ARIA instead.
        const div = document.createElement('div');
        div.className = 'kb-html-block';
        div.innerHTML = node.attrs.html || '';
        return div;
    },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            // Tracks the node's current attrs across `update()` calls, so a
            // later "Edit" click always shows the latest source, not the
            // one this view was first created with.
            let currentNode = node;

            const wrapper = document.createElement('div');
            wrapper.className = 'kb-html-block';
            wrapper.contentEditable = 'false';
            wrapper.setAttribute('role', 'figure');
            wrapper.setAttribute('aria-label', __('HTML block'));

            const content = document.createElement('div');
            content.className = 'kb-html-block-content';
            content.innerHTML = currentNode.attrs.html || '';
            wrapper.appendChild(content);

            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'kb-html-block-edit';
            editBtn.setAttribute('aria-label', __('Edit HTML block'));
            editBtn.innerHTML = '<i class="ti ti-pencil" aria-hidden="true"></i>';
            editBtn.addEventListener('click', () => {
                showHtmlBlockDialog({
                    itemId: this.options.itemId,
                    initialHtml: currentNode.attrs.html,
                    onSave: (sanitizedHtml) => {
                        if (typeof getPos !== 'function') {
                            return;
                        }
                        editor.view.dispatch(
                            editor.state.tr.setNodeMarkup(getPos(), undefined, { html: sanitizedHtml })
                        );
                    },
                    onClose: () => editor.chain().focus().run(),
                });
            });
            wrapper.appendChild(editBtn);

            return {
                dom: wrapper,
                update: (updatedNode) => {
                    if (updatedNode.type.name !== 'kbHtmlBlock') {
                        return false;
                    }
                    currentNode = updatedNode;
                    content.innerHTML = updatedNode.attrs.html || '';
                    return true;
                },
            };
        };
    },
});
