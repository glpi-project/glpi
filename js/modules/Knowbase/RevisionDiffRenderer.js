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

let libLoaded = null;

async function ensureLib()
{
    if (libLoaded === null) {
        libLoaded = import('/lib/htmldiff.js');
    }
    await libLoaded;
}

/**
 * Normalize HTML through browser's DOMParser to ensure consistent
 * entity encoding and attribute formatting between both diff inputs.
 *
 * @param {string} html
 * @returns {string}
 */
function normalizeHtml(html)
{
    const doc = new DOMParser().parseFromString(html, 'text/html');
    return doc.body.innerHTML.replace(/&nbsp;/g, ' ');
}

/**
 * Compute an inline HTML diff between two HTML strings.
 *
 * @param {string} oldHtml
 * @param {string} newHtml
 * @returns {Promise<string>} HTML with <ins>/<del> tags
 */
export async function computeHtmlDiff(oldHtml, newHtml)
{
    await ensureLib();

    const diff = new window.HtmlDiff(normalizeHtml(oldHtml), normalizeHtml(newHtml));
    return diff.build();
}
