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

// The editor UI only creates these levels; deeper ones stay out of the outline.
export const MAX_HEADING_LEVEL = 3;
export const HEADING_SELECTOR = 'h1, h2, h3';

/**
 * @param {string} text
 * @returns {string}
 */
function slugify(text) {
    return text
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^\p{L}\p{N}]+/gu, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * Ids for a list of heading texts, in document order. An empty text gets no
 * id; a repeated one gets a numbered suffix, so each id stays unique.
 * The `h-` prefix keeps them apart from the ids of the page itself.
 * @param {string[]} texts
 * @returns {Array<string|null>}
 */
export function getHeadingIds(texts) {
    const used = new Set();
    return texts.map((text) => {
        if (text.trim() === '') {
            return null;
        }
        const base = `h-${slugify(text) || 'section'}`;
        let id = base;
        for (let i = 1; used.has(id); i++) {
            id = `${base}-${i}`;
        }
        used.add(id);
        return id;
    });
}

/**
 * The copy-link anchor shown on a heading. Its `#` comes from CSS, so it adds
 * no text node to the plain-text index used to anchor comments.
 * @param {string} id
 * @returns {HTMLAnchorElement}
 */
export function createHeadingAnchor(id) {
    const anchor = document.createElement('a');
    setAnchorTarget(anchor, id);
    anchor.className = 'kb-heading-anchor';
    anchor.contentEditable = 'false';
    anchor.dataset.glpiKbHeadingAnchor = '';
    anchor.setAttribute('aria-label', __('Copy link to this section'));
    anchor.title = __('Copy link to this section');

    return anchor;
}

/**
 * @param {HTMLAnchorElement} anchor
 * @param {string} id
 */
function setAnchorTarget(anchor, id) {
    const url = new URL(window.location.href);
    url.hash = id;
    anchor.href = `#${id}`;
    // Copied by the `data-glpi-clipboard-text` handler of common.js.
    anchor.dataset.glpiClipboardText = url.toString();
}

/**
 * Give an id and a copy-link icon to each heading of a plain (non-editor)
 * DOM. Safe to call repeatedly.
 * @param {Element} root
 */
export function annotateHeadings(root) {
    const headings = [...root.querySelectorAll(HEADING_SELECTOR)];
    const ids = getHeadingIds(headings.map((heading) => heading.textContent));

    headings.forEach((heading, index) => {
        const id = ids[index];
        const anchor = heading.querySelector(':scope > [data-glpi-kb-heading-anchor]');

        if (id === null) {
            heading.removeAttribute('id');
            anchor?.remove();
            return;
        }
        heading.id = id;
        if (anchor === null) {
            heading.append(createHeadingAnchor(id));
        } else {
            setAnchorTarget(anchor, id);
        }
    });
}
