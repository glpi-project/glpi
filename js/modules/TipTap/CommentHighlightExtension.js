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

import { extractAnchor, locateAnchor, overlaps } from '/js/modules/Knowbase/CommentAnchor.js';
import { buildPmTextIndex, pmPositionToOffset } from '/js/modules/TipTap/CommentPosition.js';

const { Extension } = TiptapCore;
const { Plugin, PluginKey } = TiptapPMState;
const { Decoration, DecorationSet } = TiptapPMView;

const comment_highlight_key = new PluginKey('commentHighlight');

/**
 * Attrs of one decoration; a passage's hovered state is carried by every fragment.
 * @param {string} comment_id
 * @param {boolean} is_hovered
 * @returns {object}
 */
function decorationAttrs(comment_id, is_hovered) {
    return {
        class: `kb-comment-highlight${is_hovered ? ' kb-comment-highlight--hovered' : ''}`,
        'data-comment-id': comment_id,
        role: 'button',
        tabindex: '0',
        'aria-label': __('View comment'),
    };
}

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
 * The anchors re-extracted from where the highlights currently sit, so saving can store
 * a quote that matches the saved content instead of the one selected long ago.
 * @param {object} state - ProseMirror editor state.
 * @returns {Array<{id: string, prefix: string, exact: string, suffix: string, occurrence: number}>}
 */
function getRefreshedCommentAnchors(state) {
    const plugin_state = comment_highlight_key.getState(state);
    if (!plugin_state) {
        return [];
    }

    // One anchor spans as many decorations as the text nodes it crosses.
    const ranges = new Map();
    for (const decoration of plugin_state.set.find()) {
        const id = decoration.spec.comment_id;
        const range = ranges.get(id);
        ranges.set(id, range === undefined
            ? { from: decoration.from, to: decoration.to }
            : { from: Math.min(range.from, decoration.from), to: Math.max(range.to, decoration.to) });
    }

    if (ranges.size === 0) {
        return [];
    }

    const { text, segments } = buildPmTextIndex(state.doc);
    const refreshed = [];
    for (const [id, range] of ranges) {
        const start = pmPositionToOffset(segments, range.from);
        const end = pmPositionToOffset(segments, range.to);
        refreshed.push({ id, ...extractAnchor(text, start, end) });
    }

    return refreshed;
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
        /** Locate one anchor by text search, as done on load and when recovering a lost range. */
        const locate = (index, anchor, hovered_id) => {
            const located = locateAnchor(index.text, anchor);
            if (!located) {
                return null;
            }
            const [start, end] = located;
            const id = String(anchor.id);
            const decorations = [];
            for (const { segment, start: seg_start, end: seg_end } of overlaps(index.segments, start, end)) {
                const from = segment.pos + (seg_start - segment.start);
                const to = segment.pos + (seg_end - segment.start);
                decorations.push(Decoration.inline(
                    from,
                    to,
                    decorationAttrs(id, id === hovered_id),
                    { comment_id: id },
                ));
            }
            return { decorations, resolved: { id, text: index.text.slice(start, end) } };
        };

        const build = (doc, hovered_id) => {
            const anchors = this.storage.anchors;
            if (!anchors || anchors.length === 0) {
                return { set: DecorationSet.empty, resolved: [], hovered_id };
            }

            const index = buildPmTextIndex(doc);
            const decorations = [];
            const resolved = [];

            for (const anchor of anchors) {
                const found = locate(index, anchor, hovered_id);
                if (found === null) {
                    continue;
                }
                decorations.push(...found.decorations);
                resolved.push(found.resolved);
            }

            return { set: DecorationSet.create(doc, decorations), resolved, hovered_id };
        };

        /**
         * Carry highlights across a document change by mapping their positions
         */
        const remap = (old, tr) => {
            const anchors = this.storage.anchors;
            if (!anchors || anchors.length === 0) {
                return { set: DecorationSet.empty, resolved: [], hovered_id: old.hovered_id };
            }

            const surviving = new Map();
            for (const deco of old.set.map(tr.mapping, tr.doc).find()) {
                const id = deco.spec.comment_id;
                if (!surviving.has(id)) {
                    surviving.set(id, []);
                }
                surviving.get(id).push(deco);
            }

            let index = null;
            const decorations = [];
            const resolved = [];

            for (const anchor of anchors) {
                const id = String(anchor.id);
                const group = (surviving.get(id) ?? []).sort((a, b) => a.from - b.from);
                const text = group.map((deco) => tr.doc.textBetween(deco.from, deco.to, '', '')).join('');
                if (text !== '') {
                    decorations.push(...group);
                    resolved.push({ id, text });
                    continue;
                }
                index ??= buildPmTextIndex(tr.doc);
                const found = locate(index, anchor, old.hovered_id);
                if (found !== null) {
                    decorations.push(...found.decorations);
                    resolved.push(found.resolved);
                }
            }

            return { set: DecorationSet.create(tr.doc, decorations), resolved, hovered_id: old.hovered_id };
        };

        /** Decoration attrs are immutable, so a hover change recreates them in place. */
        const rehover = (old, doc, hovered_id) => {
            const decorations = old.set.find().map((deco) => Decoration.inline(
                deco.from,
                deco.to,
                decorationAttrs(deco.spec.comment_id, deco.spec.comment_id === hovered_id),
                deco.spec,
            ));
            return { set: DecorationSet.create(doc, decorations), resolved: old.resolved, hovered_id };
        };

        return [
            new Plugin({
                key: comment_highlight_key,
                state: {
                    init: (_config, state) => build(state.doc, null),
                    apply: (tr, old) => {
                        if (tr.getMeta('commentHighlightRefresh')) {
                            return build(tr.doc, old.hovered_id);
                        }
                        // Distinguish a null hover (nothing hovered) from no meta at all.
                        const hovered_id = tr.getMeta('commentHighlightHover');
                        if (hovered_id !== undefined) {
                            // A chained command can carry both: map positions before retagging.
                            return rehover(tr.docChanged ? remap(old, tr) : old, tr.doc, hovered_id);
                        }
                        if (!tr.docChanged) {
                            return old;
                        }
                        return remap(old, tr);
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

            setCommentHighlightHover: (comment_id) => ({ tr, dispatch }) => {
                if (dispatch) {
                    dispatch(tr.setMeta('commentHighlightHover', comment_id));
                }
                return true;
            },
        };
    },
});

export { CommentHighlight, getRefreshedCommentAnchors, getResolvedCommentAnchors };
