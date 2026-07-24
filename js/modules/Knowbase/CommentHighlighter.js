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

import { locateAnchor } from '/js/modules/Knowbase/CommentAnchor.js';
import { buildDomTextIndex, wrapOffsetsInMarks } from '/js/modules/Knowbase/DomTextIndex.js';

/**
 * Remove any highlight marks previously added by this module, merging their
 * text back into the surrounding text node.
 * @param {Element} container
 */
function clearHighlights(container) {
    container.querySelectorAll('mark.kb-comment-highlight').forEach((mark) => {
        const parent = mark.parentNode;
        while (mark.firstChild) {
            parent.insertBefore(mark.firstChild, mark);
        }
        parent.removeChild(mark);
        parent.normalize();
    });
}

/**
 * Highlight, inside `container`'s rendered (read-only) HTML, every anchored
 * comment's quoted text that can still be located. Anchors that can no longer
 * be found (their quote no longer exists in the current content) are silently
 * skipped — the comment itself is unaffected, it just has no highlight.
 * Safe to call repeatedly (e.g. after a new comment is added) — always clears
 * previous highlights first.
 * @param {Element} container
 * @param {Array<{id: number|string, prefix: string, exact: string, suffix: string, occurrence: number}>} anchors
 */
export function highlightComments(container, anchors) {
    clearHighlights(container);

    if (!anchors || anchors.length === 0) {
        return;
    }

    // Rebuilt fresh for every anchor: wrapOffsetsInMarks() mutates the DOM
    // (splits text nodes via Range.surroundContents), which would invalidate
    // a shared index's node references/offsets for any anchor processed
    // after the first.
    for (const anchor of anchors) {
        const index = buildDomTextIndex(container);
        const located = locateAnchor(index.text, anchor);
        if (!located) {
            continue;
        }
        const [start, end] = located;
        wrapOffsetsInMarks(index, start, end, anchor.id);
    }
}
