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

import { renderMermaidSvg } from '/js/modules/Mermaid/MermaidRenderer.js';

/**
 * Mermaid diagram Tiptap node. It is stored as a standard code block
 * (`<pre><code class="language-mermaid">`), so the source stays readable
 * where the diagram is not drawn.
 */

const { Node } = TiptapCore;

const PREVIEW_DELAY = 400;

export const MERMAID_TEMPLATE = 'flowchart TD\n    A --> B\n    A --> C';

/**
 * @param {object} props - Tiptap node view props
 * @returns {object} ProseMirror node view
 */
function createMermaidNodeView({ node, editor, getPos }) {
    const uid = Math.random().toString(36).slice(2, 9);
    let current_node = node;
    let preview_timer = null;
    let last_source = null;
    let render_id = 0;

    const dom = document.createElement('div');
    dom.className = 'kb-mermaid-block';

    const tablist = document.createElement('div');
    tablist.className = 'kb-mermaid-tabs';
    tablist.contentEditable = 'false';
    tablist.setAttribute('role', 'tablist');
    tablist.setAttribute('aria-label', __('Diagram'));

    const preview = document.createElement('div');
    preview.className = 'kb-mermaid-preview';
    preview.contentEditable = 'false';

    const source = document.createElement('pre');
    source.className = 'kb-mermaid-source';
    const code = document.createElement('code');
    code.className = 'language-mermaid';
    source.appendChild(code);

    const tabs = {};
    for (const [name, label, panel] of [
        ['preview', __('Diagram'), preview],
        ['source', __('Source'), source],
    ]) {
        const tab = document.createElement('button');
        tab.type = 'button';
        tab.id = `kb-mermaid-${name}-tab-${uid}`;
        tab.className = 'kb-mermaid-tab';
        tab.textContent = label;
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-controls', `kb-mermaid-${name}-${uid}`);
        tab.addEventListener('click', () => showTab(name, true));
        tablist.appendChild(tab);
        tabs[name] = tab;

        panel.id = `kb-mermaid-${name}-${uid}`;
        panel.setAttribute('role', 'tabpanel');
        panel.setAttribute('aria-labelledby', tab.id);
    }

    tablist.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            showTab(dom.classList.contains('is-source') ? 'preview' : 'source');
            tablist.querySelector('[aria-selected="true"]').focus();
        }
    });

    // Without this, the browser puts the caret in the hidden source.
    preview.addEventListener('mousedown', (event) => {
        if (!editor.isEditable) {
            return;
        }
        event.preventDefault();
        editor.chain().focus().setNodeSelection(getPos()).run();
    });

    dom.append(tablist, preview, source);

    /**
     * @param {'preview'|'source'} name
     * @param {boolean} focus - Move the focus to the shown panel
     */
    function showTab(name, focus = false) {
        dom.classList.toggle('is-source', name === 'source');
        for (const [tab_name, tab] of Object.entries(tabs)) {
            tab.setAttribute('aria-selected', String(tab_name === name));
            tab.tabIndex = tab_name === name ? 0 : -1;
        }
        if (!focus) {
            return;
        }
        if (name === 'source') {
            editor.chain().focus().setTextSelection(getPos() + current_node.nodeSize - 1).run();
        } else {
            tabs.preview.focus();
        }
    }

    async function renderPreview() {
        const text = current_node.textContent;
        if (text === last_source) {
            return;
        }
        last_source = text;
        const current_render = ++render_id;

        let content;
        if (text.trim() === '') {
            content = document.createElement('p');
            content.className = 'kb-mermaid-message';
            content.textContent = __('Empty diagram');
        } else {
            try {
                const svg = await renderMermaidSvg(text);
                content = document.createElement('figure');
                content.className = 'kb-mermaid-diagram';
                content.setAttribute('aria-label', __('Diagram'));
                content.innerHTML = svg;
            } catch (error) {
                content = document.createElement('p');
                content.className = 'kb-mermaid-message is-error';
                content.textContent = error?.message ?? __('Invalid diagram');
            }
        }

        // A slower render of an older source must not replace a newer one.
        if (current_render === render_id) {
            preview.replaceChildren(content);
            dom.classList.toggle('is-invalid', content.tagName !== 'FIGURE');
        }
    }

    showTab(node.attrs.isNew ? 'source' : 'preview');
    renderPreview();

    // Open on the source once only, not each time the view is built again (undo, ...).
    if (node.attrs.isNew) {
        setTimeout(() => {
            const pos = getPos();
            if (editor.isDestroyed || typeof pos !== 'number') {
                return;
            }
            const { tr } = editor.state;
            editor.view.dispatch(
                tr.setNodeMarkup(pos, undefined, { ...current_node.attrs, isNew: false }).setMeta('addToHistory', false)
            );
        });
    }

    return {
        dom,
        contentDOM: code,
        update(updated_node) {
            if (updated_node.type !== current_node.type) {
                return false;
            }
            current_node = updated_node;
            clearTimeout(preview_timer);
            preview_timer = setTimeout(renderPreview, PREVIEW_DELAY);
            return true;
        },
        stopEvent: (event) => tablist.contains(event.target) || preview.contains(event.target),
        ignoreMutation: (mutation) => mutation.type !== 'selection' && !code.contains(mutation.target),
        destroy() {
            clearTimeout(preview_timer);
        },
    };
}

export const MermaidBlock = Node.create({
    name: 'mermaidBlock',
    group: 'block',
    content: 'text*',
    marks: '',
    code: true,
    defining: true,

    addAttributes() {
        return {
            // Set on insertion only, to open the new block on its source.
            isNew: {
                default: false,
                rendered: false,
                parseHTML: () => false,
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'pre',
                // Ahead of the generic code block, which also matches <pre>.
                priority: 60,
                preserveWhitespace: 'full',
                getAttrs: (dom) => (dom.querySelector(':scope > code.language-mermaid') ? null : false),
            },
            {
                // Diagram drawn by the read view, in content copied from it.
                tag: 'figure.kb-mermaid-diagram',
                ignore: true,
            },
        ];
    },

    renderHTML() {
        return ['pre', ['code', { class: 'language-mermaid' }, 0]];
    },

    addNodeView() {
        return createMermaidNodeView;
    },

    addCommands() {
        return {
            insertMermaidBlock: (source = MERMAID_TEMPLATE) => ({ commands }) => commands.insertContent({
                type: this.name,
                attrs: { isNew: true },
                content: [{ type: 'text', text: source }],
            }),
        };
    },

    addKeyboardShortcuts() {
        /**
         * Select the diagram next to the caret, in place of joining the current block
         * into its source, which is hidden while the diagram is shown.
         *
         * @param {-1|1} direction - Look before (-1) or after (1) the current block
         * @returns {boolean}
         */
        const selectAdjacentDiagram = (direction) => {
            const { empty, $anchor } = this.editor.state.selection;
            const at_edge = direction < 0 ? $anchor.parentOffset === 0 : $anchor.parentOffset === $anchor.parent.content.size;
            if (!empty || $anchor.depth === 0 || !at_edge) {
                return false;
            }

            const block_start = $anchor.before();
            const block_end = $anchor.after();
            const $edge = this.editor.state.doc.resolve(direction < 0 ? block_start : block_end);
            const diagram = direction < 0 ? $edge.nodeBefore : $edge.nodeAfter;
            if (diagram?.type.name !== this.name) {
                return false;
            }

            const diagram_pos = direction < 0 ? block_start - diagram.nodeSize : block_end;
            if ($anchor.parent.content.size > 0) {
                return this.editor.commands.setNodeSelection(diagram_pos);
            }
            // Remove an empty block, as next to any other block.
            return this.editor.chain()
                .deleteRange({ from: block_start, to: block_end })
                .setNodeSelection(direction < 0 ? diagram_pos : block_start)
                .run();
        };

        return {
            Backspace: () => {
                const { empty, $anchor } = this.editor.state.selection;
                // Turn an empty diagram back into a paragraph, as for code blocks.
                if (empty && $anchor.parent.type.name === this.name && $anchor.parent.textContent === '') {
                    return this.editor.commands.clearNodes();
                }
                // Do not join the source into the block above.
                if (empty && $anchor.parent.type.name === this.name && $anchor.parentOffset === 0) {
                    return true;
                }
                return selectAdjacentDiagram(-1);
            },
            Delete: () => selectAdjacentDiagram(1),
        };
    },
});
