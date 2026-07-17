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

/* global TiptapPMTables */

/**
 * Pure query helpers over the active table's map, built on prosemirror-tables
 * primitives. All functions assume the selection is inside a table; callers
 * guard with `isInTable(state)` first.
 */

const { selectedRect, CellSelection, isInTable } = TiptapPMTables;

/**
 * Position of the first-row cell of every column (index === column index).
 * @param {object} state - ProseMirror EditorState
 * @returns {number[]}
 */
function getColumnCells(state) {
    const rect = selectedRect(state);
    const cells = [];
    for (let col = 0; col < rect.map.width; col++) {
        cells.push(rect.tableStart + rect.map.map[col]);
    }
    return cells;
}

/**
 * Position of the first-column cell of every row (index === row index).
 * @param {object} state
 * @returns {number[]}
 */
function getRowCells(state) {
    const rect = selectedRect(state);
    const cells = [];
    for (let row = 0; row < rect.map.height; row++) {
        cells.push(rect.tableStart + rect.map.map[row * rect.map.width]);
    }
    return cells;
}

/**
 * @param {number} index
 * @returns {function(object): boolean} true when the column selection covers `index`
 */
function isColumnSelected(index) {
    return (state) => {
        const sel = state.selection;
        if (!(sel instanceof CellSelection) || !sel.isColSelection()) {
            return false;
        }
        const rect = selectedRect(state);
        return rect.left <= index && index < rect.right;
    };
}

/**
 * @param {number} index
 * @returns {function(object): boolean} true when the row selection covers `index`
 */
function isRowSelected(index) {
    return (state) => {
        const sel = state.selection;
        if (!(sel instanceof CellSelection) || !sel.isRowSelection()) {
            return false;
        }
        const rect = selectedRect(state);
        return rect.top <= index && index < rect.bottom;
    };
}

/**
 * @param {object} state
 * @returns {boolean} true when the selection spans the whole table
 */
function isTableSelected(state) {
    const sel = state.selection;
    return sel instanceof CellSelection && sel.isColSelection() && sel.isRowSelection();
}

export {
    getColumnCells,
    getRowCells,
    isColumnSelected,
    isRowSelected,
    isTableSelected,
    isInTable,
};
