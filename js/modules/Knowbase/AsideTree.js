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

/**
 * Reading an occurrence's place in the aside tree.
 *
 * An article may have several parents and is rendered under each of them, so
 * an occurrence has no identity beyond its DOM element: its parent is read
 * from the ancestry, never from an attribute the server could drift from.
 */

/**
 * The `<li>` of an occurrence's parent, null when it sits at the root.
 *
 * @param {HTMLElement} row
 * @returns {HTMLElement|null}
 */
export function parentRowOf(row)
{
    // From `parentElement`: `closest()` includes its own start element.
    return row.parentElement?.closest('li[data-glpi-kb-article-id]') ?? null;
}

/**
 * Article id of an occurrence's parent, 0 at the root, matching the server.
 *
 * @param {HTMLElement} row
 * @returns {number}
 */
export function parentIdOf(row)
{
    return Number(parentRowOf(row)?.dataset.glpiKbArticleId ?? 0);
}
