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

import { overlaps } from '/js/modules/Knowbase/CommentAnchor.js';

/**
 * Build a plain-text index of `root`'s text nodes (concatenated, no separator
 * at block boundaries — matches CommentPosition.js's ProseMirror-side index).
 * @param {Element} root
 * @returns {{text: string, segments: Array<{node: Text, start: number, end: number}>}}
 */
export function buildDomTextIndex(root) {
    const segments = [];
    let text = '';
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let node = walker.nextNode();
    while (node) {
        const start = text.length;
        text += node.nodeValue;
        segments.push({ node, start, end: text.length });
        node = walker.nextNode();
    }
    return { text, segments };
}

/**
 * Convert a DOM Range into plain-text offsets. Only handles start/end
 * containers that are indexed text nodes; returns null otherwise.
 * @param {{segments: Array<{node: Text, start: number, end: number}>}} index
 * @param {Range} range
 * @returns {[number, number]|null}
 */
export function domRangeToOffsets(index, range) {
    const start_segment = index.segments.find((segment) => segment.node === range.startContainer);
    const end_segment = index.segments.find((segment) => segment.node === range.endContainer);
    if (!start_segment || !end_segment) {
        return null;
    }
    return [
        start_segment.start + range.startOffset,
        end_segment.start + range.endOffset,
    ];
}

/**
 * Wrap [start, end) in `<mark class="kb-comment-highlight">` elements, one per
 * underlying text node so a selection crossing block boundaries doesn't throw.
 * @param {{segments: Array<{node: Text, start: number, end: number}>}} index
 * @param {number} start
 * @param {number} end
 * @param {number|string} comment_id
 */
export function wrapOffsetsInMarks(index, start, end, comment_id) {
    for (const { segment, start: seg_start, end: seg_end } of overlaps(index.segments, start, end)) {
        const range = document.createRange();
        range.setStart(segment.node, seg_start - segment.start);
        range.setEnd(segment.node, seg_end - segment.start);

        const mark = document.createElement('mark');
        mark.className = 'kb-comment-highlight';
        mark.dataset.commentId = String(comment_id);
        mark.setAttribute('role', 'button');
        mark.setAttribute('tabindex', '0');
        mark.setAttribute('aria-label', __('View comment'));
        range.surroundContents(mark);
    }
}
