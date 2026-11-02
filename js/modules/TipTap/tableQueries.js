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
 * Pure query helpers over a table's TableMap. Everything is keyed by an explicit
 * table position (not the live selection) so grips render and act on any table
 * in the document, independent of where the caret currently is.
 */

const { TableMap, CellSelection, selectedRect } = TiptapPMTables;

/**
 * Document position of the first-row cell of every column (index === column).
 * @param {object} map - TableMap
 * @param {number} tableStart - position of the table's first child
 * @returns {number[]}
 */
function getColumnCellPositions(map, tableStart) {
    const cells = [];
    for (let col = 0; col < map.width; col++) {
        cells.push(tableStart + map.map[col]);
    }
    return cells;
}

/**
 * Document position of the first-column cell of every row (index === row).
 * @param {object} map - TableMap
 * @param {number} tableStart
 * @returns {number[]}
 */
function getRowCellPositions(map, tableStart) {
    const cells = [];
    for (let row = 0; row < map.height; row++) {
        cells.push(tableStart + map.map[row * map.width]);
    }
    return cells;
}

/**
 * Highlight info for the current selection, or null when it is not a cell
 * selection. `tableStart` lets callers match it against a specific table.
 * @param {object} state - ProseMirror EditorState
 * @returns {?{tableStart:number,left:number,right:number,top:number,bottom:number,isCol:boolean,isRow:boolean}}
 */
function cellSelectionInfo(state) {
    const sel = state.selection;
    if (!(sel instanceof CellSelection)) {
        return null;
    }
    const rect = selectedRect(state);
    return {
        tableStart: rect.tableStart,
        left: rect.left,
        right: rect.right,
        top: rect.top,
        bottom: rect.bottom,
        isCol: sel.isColSelection(),
        isRow: sel.isRowSelection(),
    };
}

export {
    TableMap,
    getColumnCellPositions,
    getRowCellPositions,
    cellSelectionInfo,
};
