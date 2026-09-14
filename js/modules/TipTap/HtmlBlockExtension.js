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

/* global TiptapCore, TiptapPMState */

import { showHtmlBlockDialog } from '/js/modules/TipTap/HtmlBlockDialog.js';

/**
 * KB "HTML Block" node holding HTML sanitized by `RichText::getSafeHtml()`.
 * Stored as the children of `div.kb-html-block`. The marker is a class because
 * the sanitizer strips unlisted `data-*` attributes.
 */
const { Node, createNodeFromContent } = TiptapCore;
const { Plugin } = TiptapPMState;

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

    addProseMirrorPlugins() {
        const { schema } = this.editor;
        const type = this.type;

        // `parseHTML` trusts the inner HTML, which is only safe for stored
        // content. Pasted or dropped blocks are unwrapped into regular nodes,
        // recursively since a block may contain another one.
        const unwrapBlocks = (fragment) => {
            const nodes = [];
            fragment.forEach((child) => {
                if (child.type === type) {
                    const doc = createNodeFromContent(child.attrs.html || '', schema, { slice: false });
                    unwrapBlocks(doc.content).forEach((node) => nodes.push(node));
                } else {
                    nodes.push(child.isLeaf ? child : child.copy(unwrapBlocks(child.content)));
                }
            });
            // ProseMirror's Fragment is not exposed (`TiptapCore.Fragment` is unrelated).
            return fragment.constructor.fromArray(nodes);
        };

        return [
            new Plugin({
                props: {
                    // Drags within this editor move content that is already trusted.
                    transformPasted: (slice, view) => (view.dragging
                        ? slice
                        : new slice.constructor(unwrapBlocks(slice.content), slice.openStart, slice.openEnd)),
                },
            }),
        ];
    },

    renderHTML({ node }) {
        // Returning a DOM node lets the children be arbitrary sanitized markup.
        // No ARIA on the stored form: the sanitizer strips `aria-*`, and the
        // node view carries it instead.
        const div = document.createElement('div');
        div.className = 'kb-html-block';
        div.innerHTML = node.attrs.html || '';
        return div;
    },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            // Kept current by `update()`, so Edit always shows the latest source.
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
                if (!editor.isEditable) {
                    return;
                }
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
