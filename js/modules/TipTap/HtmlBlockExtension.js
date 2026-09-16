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
 * KB "HTML Block" node holding HTML sanitized by `RichText::getSafeHtml()`,
 * stored as the children of `div.kb-html-block`. The marker is a class because
 * the sanitizer strips unlisted `data-*` attributes.
 */
const { Node, createNodeFromContent } = TiptapCore;
const { Plugin } = TiptapPMState;

/**
 * HTML of the blocks copied or cut from a KB editor on this page. Pasting one back must not unwrap it.
 *
 * @type {Set<string>}
 */
const copiedHtml = new Set();

const COPIED_HTML_LIMIT = 20;

const inertDocument = document.implementation.createHTMLDocument('');

/**
 * HTML as the browser serializes it. `attrs.html` holds the sanitizer output until the block round-trips through the DOM, and the two forms differ
 * (`&#64;` vs `@`, `<img />` vs `<img>`). The document is inert, so parsing pasted markup loads nothing and fires no `onerror`.
 *
 * @param {string} html
 * @returns {string}
 */
const normalizeHtml = (html) => {
    const div = inertDocument.createElement('div');
    div.innerHTML = html || '';
    return div.innerHTML;
};

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

        // `parseHTML` trusts the inner HTML, which holds for stored content but not for pasted or dropped blocks. Unwrap those into regular nodes,
        // recursively since a block may contain another one.
        const unwrapBlocks = (fragment) => {
            const nodes = [];
            fragment.forEach((child) => {
                if (child.type === type && !copiedHtml.has(normalizeHtml(child.attrs.html))) {
                    const doc = createNodeFromContent(child.attrs.html || '', schema, { slice: false });
                    unwrapBlocks(doc.content).forEach((node) => nodes.push(node));
                } else {
                    nodes.push(child.isLeaf ? child : child.copy(unwrapBlocks(child.content)));
                }
            });
            // ProseMirror's Fragment is not exported by Tiptap.
            return fragment.constructor.fromArray(nodes);
        };

        // Remember the blocks leaving this editor through the clipboard, so pasting them back keeps them whole. An outside page can only hit this
        // set with markup that already passed through the editor, so it stays a trust check and not just an origin guess.
        const rememberCopiedBlocks = (view) => {
            view.state.selection.content().content.descendants((node) => {
                if (node.type === type) {
                    copiedHtml.add(normalizeHtml(node.attrs.html));
                }
            });
            // A `Set` iterates in insertion order. ponytail: plain cap, make it an LRU if a real workflow ever overflows it.
            while (copiedHtml.size > COPIED_HTML_LIMIT) {
                copiedHtml.delete(copiedHtml.values().next().value);
            }
            return false;
        };

        return [
            new Plugin({
                props: {
                    handleDOMEvents: {
                        copy: rememberCopiedBlocks,
                        cut: rememberCopiedBlocks,
                    },
                    // Drags inside this editor move already trusted content.
                    transformPasted: (slice, view) => (view.dragging
                        ? slice
                        : new slice.constructor(unwrapBlocks(slice.content), slice.openStart, slice.openEnd)),
                },
            }),
        ];
    },

    addCommands() {
        return {
            /**
             * Open the HTML block dialog. Save updates the block at `getPos()` when given, else inserts a new block at the selection.
             * Unavailable without a saved article: the sanitize endpoint needs its id.
             */
            openHtmlBlockDialog: (getPos = null) => ({ editor, dispatch }) => {
                const { itemId } = this.options;
                if (!(itemId > 0) || !editor.isEditable) {
                    return false;
                }
                if (dispatch) {
                    showHtmlBlockDialog({
                        itemId,
                        initialHtml: getPos ? editor.state.doc.nodeAt(getPos()).attrs.html : '',
                        onSave: (sanitizedHtml) => {
                            if (!getPos) {
                                editor.chain().focus().insertContent({ type: this.name, attrs: { html: sanitizedHtml } }).run();
                                return;
                            }
                            // `getPos()` returns undefined once the node view is detached.
                            const pos = getPos();
                            if (pos !== undefined) {
                                editor.view.dispatch(editor.state.tr.setNodeMarkup(pos, undefined, { html: sanitizedHtml }));
                            }
                        },
                        onClose: () => editor.commands.focus(),
                    });
                }
                return true;
            },
        };
    },

    renderHTML({ node }) {
        // A DOM node lets the children be arbitrary sanitized markup. No ARIA here: the sanitizer strips `aria-*`, the node view carries it instead.
        // Inert document: `getHTML()` runs this on every transaction, and a live document would refetch every `<img>` in the block per keystroke.
        const div = inertDocument.createElement('div');
        div.className = 'kb-html-block';
        div.innerHTML = node.attrs.html || '';
        return div;
    },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'kb-html-block';
            wrapper.contentEditable = 'false';
            wrapper.setAttribute('role', 'figure');
            wrapper.setAttribute('aria-label', __('HTML block'));

            const content = document.createElement('div');
            content.className = 'kb-html-block-content';
            content.innerHTML = node.attrs.html || '';
            wrapper.appendChild(content);

            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'kb-html-block-edit';
            editBtn.setAttribute('aria-label', __('Edit HTML block'));
            editBtn.innerHTML = '<i class="ti ti-pencil" aria-hidden="true"></i>';
            editBtn.addEventListener('click', () => editor.commands.openHtmlBlockDialog(getPos));
            wrapper.appendChild(editBtn);

            return {
                dom: wrapper,
                update: (updatedNode) => {
                    if (updatedNode.type.name !== 'kbHtmlBlock') {
                        return false;
                    }
                    content.innerHTML = updatedNode.attrs.html || '';
                    return true;
                },
            };
        };
    },
});
