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

const CONTEXT_LENGTH = 32;

/** Mirrors KnowbaseItem_Comment::MAX_ANCHOR_LENGTH. */
const MAX_ANCHOR_LENGTH = 1000;

/**
 * Count how many times `exact` occurs in `text` strictly before `before_index`.
 * @param {string} text
 * @param {string} exact
 * @param {number} before_index
 * @returns {number}
 */
function countOccurrencesBefore(text, exact, before_index) {
    if (exact.length === 0) {
        return 0;
    }
    let count = 0;
    let idx = text.indexOf(exact);
    while (idx !== -1 && idx < before_index) {
        count++;
        idx = text.indexOf(exact, idx + 1);
    }
    return count;
}

/**
 * Build a W3C TextQuoteSelector-style anchor for the given range of `text`.
 * @param {string} text - The full plain text the range was selected from.
 * @param {number} start
 * @param {number} end
 * @returns {{prefix: string, exact: string, suffix: string, occurrence: number}}
 */
export function extractAnchor(text, start, end) {
    const exact = text.slice(start, end);
    return {
        prefix: text.slice(Math.max(0, start - CONTEXT_LENGTH), start),
        exact,
        suffix: text.slice(end, Math.min(text.length, end + CONTEXT_LENGTH)),
        occurrence: countOccurrencesBefore(text, exact, start),
    };
}

/**
 * All indices at which `needle` occurs in `text`, left to right.
 * @param {string} text
 * @param {string} needle
 * @returns {number[]}
 */
function findAllOccurrences(text, needle) {
    const indices = [];
    let idx = text.indexOf(needle);
    while (idx !== -1) {
        indices.push(idx);
        idx = text.indexOf(needle, idx + 1);
    }
    return indices;
}

/**
 * Locate the quoted passage by bracketing it between `prefix` and `suffix`,
 * for when `exact` no longer matches verbatim. Pairs every occurrence of
 * each rather than requiring either alone to be unique; refuses when more
 * than one pairing is valid.
 * @param {string} text
 * @param {string} prefix
 * @param {string} suffix
 * @returns {[number, number]|null}
 */
function locateByBracketing(text, prefix, suffix) {
    const starts = prefix.length === 0
        ? [0]
        : findAllOccurrences(text, prefix).map((idx) => idx + prefix.length);
    const ends = suffix.length === 0
        ? [text.length]
        : findAllOccurrences(text, suffix);

    let candidate = null;
    for (const start of starts) {
        for (const end of ends) {
            if (end <= start || end - start > MAX_ANCHOR_LENGTH) {
                continue;
            }
            if (candidate !== null) {
                return null;
            }
            candidate = [start, end];
        }
    }

    return candidate;
}

/**
 * Locate an anchor's quoted text in `text`. Prefix/suffix context is used to
 * disambiguate repeated quotes, not to validate the quote itself: a single
 * occurrence of `exact` is always accepted, and an edited quote falls back to
 * bracketing between its prefix and suffix rather than being treated as
 * orphaned. Returns null only when nothing can be located.
 * @param {string} text
 * @param {{prefix: string, exact: string, suffix: string, occurrence: number}} anchor
 * @returns {[number, number]|null}
 */
export function locateAnchor(text, anchor) {
    const { exact, prefix, suffix, occurrence } = anchor;
    if (!exact) {
        return null;
    }

    const matches = [];
    let idx = text.indexOf(exact);
    while (idx !== -1) {
        matches.push(idx);
        idx = text.indexOf(exact, idx + 1);
    }

    if (matches.length === 1) {
        const start = matches[0];
        return [start, start + exact.length];
    }

    if (matches.length > 1) {
        const matchesContext = (start) => {
            const end = start + exact.length;
            const actual_prefix = text.slice(Math.max(0, start - prefix.length), start);
            const actual_suffix = text.slice(end, end + suffix.length);
            return actual_prefix === prefix && actual_suffix === suffix;
        };

        if (matches[occurrence] !== undefined && matchesContext(matches[occurrence])) {
            const start = matches[occurrence];
            return [start, start + exact.length];
        }

        const fallback = matches.find(matchesContext);
        if (fallback !== undefined) {
            return [fallback, fallback + exact.length];
        }
    }

    return locateByBracketing(text, prefix, suffix);
}

/**
 * Intersect [start, end) against a list of `{start, end}` segments, returning
 * the overlapping portion of each. Shared by the DOM-based (read mode) and
 * ProseMirror-based (edit mode) highlighters.
 * @param {Array<{start: number, end: number}>} segments
 * @param {number} start
 * @param {number} end
 * @returns {Array<{segment: object, start: number, end: number}>}
 */
export function overlaps(segments, start, end) {
    const result = [];
    for (const segment of segments) {
        const s = Math.max(start, segment.start);
        const e = Math.min(end, segment.end);
        if (s < e) {
            result.push({ segment, start: s, end: e });
        }
    }
    return result;
}
