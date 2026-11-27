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
 * Follow a document position through the transactions of `editor`,
 * for work that ends after the document may have changed (upload, dialog).
 *
 * @param {object} editor - TipTap editor instance
 * @param {number} pos
 * @param {number} assoc - Side the position sticks to when content is inserted at it: -1 before, 1 after.
 * @returns {{pos: number, deleted: boolean, stop: function(): void}}
 *          `deleted` is true once the content on the `assoc` side of the position was removed.
 */
export function trackPosition(editor, pos, assoc = 1) {
    let current = pos;
    let deleted = false;
    const map = ({ transaction, appendedTransactions = [] }) => {
        for (const tr of [transaction, ...appendedTransactions]) {
            const result = tr.mapping.mapResult(current, assoc);
            current = result.pos;
            deleted ||= assoc > 0 ? result.deletedAfter : result.deletedBefore;
        }
    };
    editor.on('transaction', map);

    return {
        get pos() {
            return current;
        },
        get deleted() {
            return deleted;
        },
        stop: () => editor.off('transaction', map),
    };
}
