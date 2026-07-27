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
 * Indentation between block tags: renders as nothing and absent from the
 * ProseMirror index, unlike an inline space (`<b>a</b> <i>b</i>`).
 * @param {Text} node
 * @returns {boolean}
 */
function isFormattingWhitespace(node) {
    if (node.nodeValue.trim() !== '') {
        return false;
    }
    const parent = node.parentElement;
    if (parent === null) {
        return false;
    }
    return [...parent.children].some(
        (child) => !/^(inline|contents)/.test(getComputedStyle(child).display)
    );
}

/**
 * Plain-text index of `root`'s rendered text nodes, concatenated with no
 * separator — matches CommentPosition.js's ProseMirror-side index.
 * @param {Element} root
 * @returns {{text: string, segments: Array<{node: Text, start: number, end: number}>}}
 */
export function buildDomTextIndex(root) {
    const segments = [];
    let text = '';
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let node = walker.nextNode();
    while (node) {
        if (!isFormattingWhitespace(node)) {
            const start = text.length;
            text += node.nodeValue;
            segments.push({ node, start, end: text.length });
        }
        node = walker.nextNode();
    }
    return { text, segments };
}

/**
 * Map a Range boundary to a plain-text offset, snapping inwards when it isn't
 * an indexed text node (triple-click, drag past a block end).
 * @param {{segments: Array<{node: Text, start: number, end: number}>}} index
 * @param {Node} container
 * @param {number} offset
 * @param {boolean} is_end
 * @returns {number|null}
 */
function boundaryToOffset(index, container, offset, is_end) {
    const segment = index.segments.find((candidate) => candidate.node === container);
    if (segment) {
        return segment.start + offset;
    }

    const boundary = document.createRange();
    boundary.setStart(container, offset);
    boundary.collapse(true);

    // Segments are in document order: last match for an end, first for a start.
    let snapped = null;
    for (const candidate of index.segments) {
        if (is_end) {
            if (boundary.comparePoint(candidate.node, candidate.node.length) <= 0) {
                snapped = candidate.end;
            }
        } else if (snapped === null && boundary.comparePoint(candidate.node, 0) >= 0) {
            snapped = candidate.start;
        }
    }
    return snapped;
}

/**
 * Convert a DOM Range into plain-text offsets, or null if it covers no text.
 * @param {{segments: Array<{node: Text, start: number, end: number}>}} index
 * @param {Range} range
 * @returns {[number, number]|null}
 */
export function domRangeToOffsets(index, range) {
    const start = boundaryToOffset(index, range.startContainer, range.startOffset, false);
    const end = boundaryToOffset(index, range.endContainer, range.endOffset, true);
    if (start === null || end === null || start >= end) {
        return null;
    }
    return [start, end];
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
