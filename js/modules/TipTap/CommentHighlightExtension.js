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

import { locateAnchor, overlaps } from '/js/modules/Knowbase/CommentAnchor.js';
import { buildPmTextIndex } from '/js/modules/TipTap/CommentPosition.js';

const { Extension } = TiptapCore;
const { Plugin, PluginKey } = TiptapPMState;
const { Decoration, DecorationSet } = TiptapPMView;

const comment_highlight_key = new PluginKey('commentHighlight');

/**
 * Anchors whose quoted text is still present in the document.
 * @param {object} state - ProseMirror editor state.
 * @returns {Array<{id: string, text: string}>} The text actually located, which can
 * drift from the stored `exact` when resolved by bracketing.
 */
function getResolvedCommentAnchors(state) {
    return comment_highlight_key.getState(state)?.resolved ?? [];
}

/**
 * Highlights anchored comments' quoted text using decorations (not real marks),
 * so saved article HTML and revision history stay untouched by anchors.
 */
const CommentHighlight = Extension.create({
    name: 'commentHighlight',

    addOptions() {
        return {
            anchors: [],
        };
    },

    // this.options is recomputed on every access, so mutations don't persist;
    // this.storage is the stable reference shared by commands and plugins.
    addStorage() {
        return {
            anchors: this.options.anchors,
        };
    },

    addProseMirrorPlugins() {
        const build = (doc) => {
            const anchors = this.storage.anchors;
            if (!anchors || anchors.length === 0) {
                return { set: DecorationSet.empty, resolved: [] };
            }

            const { text, segments } = buildPmTextIndex(doc);
            const decorations = [];
            const resolved = [];

            for (const anchor of anchors) {
                const located = locateAnchor(text, anchor);
                if (!located) {
                    continue;
                }
                const [start, end] = located;
                resolved.push({ id: String(anchor.id), text: text.slice(start, end) });
                for (const { segment, start: seg_start, end: seg_end } of overlaps(segments, start, end)) {
                    const from = segment.pos + (seg_start - segment.start);
                    const to = segment.pos + (seg_end - segment.start);
                    decorations.push(Decoration.inline(from, to, {
                        class: 'kb-comment-highlight',
                        'data-comment-id': String(anchor.id),
                        role: 'button',
                        tabindex: '0',
                        'aria-label': __('View comment'),
                    }));
                }
            }

            return { set: DecorationSet.create(doc, decorations), resolved };
        };

        return [
            new Plugin({
                key: comment_highlight_key,
                state: {
                    init: (_config, state) => build(state.doc),
                    apply: (tr, old) => {
                        if (tr.docChanged || tr.getMeta('commentHighlightRefresh')) {
                            return build(tr.doc);
                        }
                        return old;
                    },
                },
                props: {
                    decorations(state) {
                        return this.getState(state).set;
                    },
                },
            }),
        ];
    },

    addCommands() {
        return {
            refreshCommentHighlights: (anchors) => ({ tr, dispatch }) => {
                if (anchors) {
                    this.storage.anchors = anchors;
                }
                if (dispatch) {
                    dispatch(tr.setMeta('commentHighlightRefresh', true));
                }
                return true;
            },
        };
    },
});

export { CommentHighlight, getResolvedCommentAnchors };
