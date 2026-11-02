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

/* global TiptapCore, TiptapPMState, TiptapPMView */

import { createHeadingAnchor, getHeadingIds } from '/js/modules/Knowbase/HeadingAnchors.js';

const { Extension } = TiptapCore;
const { Plugin, PluginKey } = TiptapPMState;
const { Decoration, DecorationSet } = TiptapPMView;

const heading_anchors_key = new PluginKey('headingAnchors');

/**
 * @param {object} doc - ProseMirror document.
 * @returns {Array<{pos: number, node: object, id: string|null}>}
 */
function collectHeadings(doc) {
    const headings = [];
    doc.descendants((node, pos) => {
        if (node.type.name === 'heading') {
            headings.push({ pos, node });
        }
        // No heading inside a text block: skip its inline content.
        return !node.isTextblock;
    });

    const ids = getHeadingIds(headings.map(({ node }) => node.textContent));
    return headings.map((heading, index) => ({ ...heading, id: ids[index] }));
}

/**
 * Gives each heading an id, plus a copy-link icon when read-only, using
 * decorations so the saved article HTML stays untouched.
 */
export const HeadingAnchors = Extension.create({
    name: 'headingAnchors',

    addProseMirrorPlugins() {
        const editor = this.editor;
        // Called on each view update, cursor moves included: rebuild only on change.
        let cache = { headings: null, show_links: null, set: null };

        return [
            new Plugin({
                key: heading_anchors_key,
                state: {
                    init: (_, state) => collectHeadings(state.doc),
                    apply: (tr, headings) => (tr.docChanged ? collectHeadings(tr.doc) : headings),
                },
                props: {
                    decorations(state) {
                        const headings = heading_anchors_key.getState(state);
                        // Read on each view update, so it follows setEditable().
                        const show_links = !editor.options.editable;
                        if (cache.headings === headings && cache.show_links === show_links) {
                            return cache.set;
                        }
                        const decorations = [];
                        for (const { pos, node, id } of headings) {
                            if (id === null) {
                                continue;
                            }
                            decorations.push(Decoration.node(pos, pos + node.nodeSize, { id }));
                            if (show_links) {
                                decorations.push(Decoration.widget(
                                    pos + node.nodeSize - 1,
                                    () => createHeadingAnchor(id),
                                    { side: 1, ignoreSelection: true, key: `heading-anchor-${id}` },
                                ));
                            }
                        }
                        cache = {
                            headings,
                            show_links,
                            set: DecorationSet.create(state.doc, decorations),
                        };
                        return cache.set;
                    },
                },
            }),
        ];
    },
});
