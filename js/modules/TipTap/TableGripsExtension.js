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

/* global TiptapCore, TiptapPMState, TiptapPMView, TiptapPMTables */

import {
    getColumnCells,
    getRowCells,
    isColumnSelected,
    isRowSelected,
    isTableSelected,
} from '/js/modules/TipTap/tableQueries.js';
import { openTableContextMenu } from '/js/modules/TipTap/TableContextMenu.js';

const { Extension } = TiptapCore;
const { Plugin, PluginKey } = TiptapPMState;
const { Decoration, DecorationSet } = TiptapPMView;
const {
    CellSelection,
    selectedRect,
    isInTable,
    addColumn,
    removeColumn,
    addRow,
    removeRow,
} = TiptapPMTables;

/**
 * Build an inert <a role="button"> control (grip or +/- button). The visible,
 * clickable shape is drawn by CSS ::after; this element is just the host and
 * the accessible name + data carrier.
 * @param {string} className
 * @param {object} dataset - dataset key/value pairs to set
 * @param {string} label - accessible name
 * @param {boolean} [selected=false] - adds the `selected` modifier class
 * @returns {HTMLAnchorElement}
 */
function makeControl(className, dataset, label, selected = false) {
    const el = document.createElement('a');
    el.setAttribute('role', 'button');
    el.className = selected ? `${className} selected` : className;
    el.setAttribute('aria-label', label);
    Object.entries(dataset).forEach(([k, v]) => { el.dataset[k] = String(v); });
    return el;
}

/**
 * Outline-style table grips: click a column/row/corner grip to select it,
 * hover it for a `+` (insert) and `−` (delete) button. Rendered as widget
 * decorations on the table currently holding the selection.
 */
const TableGrips = Extension.create({
    name: 'tableGrips',

    addProseMirrorPlugins() {
        const editor = this.editor;

        const build = (state) => {
            if (!editor.isEditable || !isInTable(state)) {
                return DecorationSet.empty;
            }
            const decorations = [];
            const widget = (pos, factory, key) =>
                decorations.push(Decoration.widget(pos, factory, { key }));

            // Column controls — hosted in the first-row cell of each column.
            const columns = getColumnCells(state);
            columns.forEach((pos, index) => {
                const selected = isColumnSelected(index)(state) || isTableSelected(state);
                widget(pos + 1, () => makeControl(
                    'table-grip-column', { col: index },
                    __('Select column %s').replace('%s', String(index + 1)), selected,
                ), `grip-col-${index}-${selected}`);
                // Leading `+` before the very first column.
                if (index === 0) {
                    widget(pos + 1, () => makeControl(
                        'table-col-add first', { insertCol: 0 }, __('Insert column'),
                    ), `col-add-0`);
                }
                // `+` at this column's trailing boundary (inserts after it).
                // Deletion is intentionally not surfaced yet (Outline-style, TBD).
                widget(pos + 1, () => makeControl(
                    'table-col-add', { insertCol: index + 1 }, __('Insert column'),
                ), `col-add-${index + 1}`);
            });

            // Row controls — hosted in the first-column cell of each row.
            // Row 0 additionally hosts the whole-table corner grip + its `−`.
            const rows = getRowCells(state);
            rows.forEach((pos, index) => {
                const selected = isRowSelected(index)(state) || isTableSelected(state);
                if (index === 0) {
                    const tableSel = isTableSelected(state);
                    widget(pos + 1, () => makeControl(
                        'table-grip', {}, __('Select table'), tableSel,
                    ), `grip-table-${tableSel}`);
                    widget(pos + 1, () => makeControl(
                        'table-row-add first', { insertRow: 0 }, __('Insert row'),
                    ), 'row-add-0');
                }
                widget(pos + 1, () => makeControl(
                    'table-grip-row', { row: index },
                    __('Select row %s').replace('%s', String(index + 1)), selected,
                ), `grip-row-${index}-${selected}`);
                // `+` at this row's trailing boundary (inserts below it).
                widget(pos + 1, () => makeControl(
                    'table-row-add', { insertRow: index + 1 }, __('Insert row'),
                ), `row-add-${index + 1}`);
            });

            return DecorationSet.create(state.doc, decorations);
        };

        return [
            new Plugin({
                key: new PluginKey('tableGrips'),
                state: {
                    init: (_config, state) => build(state),
                    apply: (tr, old, _oldState, newState) =>
                        (tr.docChanged || tr.selectionSet) ? build(newState) : old,
                },
                // Publish each table's pixel size as CSS vars so the insertion
                // guide line (a ::before on the "+") can span the whole table.
                // A ResizeObserver keeps them accurate after the initial layout
                // and on any later size change (content, column resize, window),
                // without measuring on every transaction.
                view: (editorView) => {
                    const publish = (table) => {
                        const h = `${table.offsetHeight}px`;
                        const w = `${table.offsetWidth}px`;
                        if (table.style.getPropertyValue('--kb-table-h') !== h) {
                            table.style.setProperty('--kb-table-h', h);
                        }
                        if (table.style.getPropertyValue('--kb-table-w') !== w) {
                            table.style.setProperty('--kb-table-w', w);
                        }
                    };
                    const observer = new ResizeObserver((entries) => {
                        entries.forEach((entry) => publish(entry.target));
                    });
                    const observeTables = () => {
                        editorView.dom.querySelectorAll('table').forEach((table) => observer.observe(table));
                    };
                    observeTables();
                    return {
                        update: (view, prevState) => {
                            // Newly inserted tables need observing (observe() is idempotent).
                            if (!view.state.doc.eq(prevState.doc)) {
                                observeTables();
                            }
                        },
                        destroy: () => observer.disconnect(),
                    };
                },
                props: {
                    decorations(state) {
                        return this.getState(state);
                    },
                    handleDOMEvents: {
                        mousedown: (view, event) => {
                            const target = event.target;
                            if (!(target instanceof HTMLElement)) {
                                return false;
                            }
                            // Order: buttons (more specific) before grips.
                            const colAdd = target.closest('.table-col-add');
                            if (colAdd) {
                                event.preventDefault(); event.stopImmediatePropagation();
                                editor.commands.insertColumnAt(Number(colAdd.dataset.insertCol));
                                return true;
                            }
                            const rowAdd = target.closest('.table-row-add');
                            if (rowAdd) {
                                event.preventDefault(); event.stopImmediatePropagation();
                                editor.commands.insertRowAt(Number(rowAdd.dataset.insertRow));
                                return true;
                            }
                            const colGrip = target.closest('.table-grip-column');
                            if (colGrip) {
                                event.preventDefault(); event.stopImmediatePropagation();
                                const index = Number(colGrip.dataset.col);
                                editor.commands.selectColumn(index);
                                openTableContextMenu({ x: event.clientX, y: event.clientY, kind: 'column', index, editor });
                                return true;
                            }
                            const rowGrip = target.closest('.table-grip-row');
                            if (rowGrip) {
                                event.preventDefault(); event.stopImmediatePropagation();
                                const index = Number(rowGrip.dataset.row);
                                editor.commands.selectRow(index);
                                openTableContextMenu({ x: event.clientX, y: event.clientY, kind: 'row', index, editor });
                                return true;
                            }
                            const tableGrip = target.closest('.table-grip');
                            if (tableGrip) {
                                event.preventDefault(); event.stopImmediatePropagation();
                                editor.commands.selectTable();
                                openTableContextMenu({ x: event.clientX, y: event.clientY, kind: 'table', index: 0, editor });
                                return true;
                            }
                            return false;
                        },
                    },
                },
            }),
        ];
    },

    addCommands() {
        return {
            selectColumn: (index) => ({ state, dispatch }) => {
                if (!isInTable(state)) {
                    return false;
                }
                const rect = selectedRect(state);
                const $cell = state.doc.resolve(rect.tableStart + rect.map.map[index]);
                if (dispatch) {
                    dispatch(state.tr.setSelection(CellSelection.colSelection($cell)));
                }
                return true;
            },
            selectRow: (index) => ({ state, dispatch }) => {
                if (!isInTable(state)) {
                    return false;
                }
                const rect = selectedRect(state);
                const $cell = state.doc.resolve(rect.tableStart + rect.map.map[index * rect.map.width]);
                if (dispatch) {
                    dispatch(state.tr.setSelection(CellSelection.rowSelection($cell)));
                }
                return true;
            },
            selectTable: () => ({ state, dispatch }) => {
                if (!isInTable(state)) {
                    return false;
                }
                const rect = selectedRect(state);
                const map = rect.map.map;
                const $first = state.doc.resolve(rect.tableStart + map[0]);
                const $last = state.doc.resolve(rect.tableStart + map[map.length - 1]);
                if (dispatch) {
                    dispatch(state.tr.setSelection(new CellSelection($first, $last)));
                }
                return true;
            },
            insertColumnAt: (col) => ({ state, dispatch }) => {
                if (!isInTable(state)) {
                    return false;
                }
                if (dispatch) {
                    const tr = state.tr;
                    addColumn(tr, selectedRect(state), col);
                    dispatch(tr);
                }
                return true;
            },
            deleteColumnAt: (col) => ({ state, dispatch }) => {
                if (!isInTable(state)) {
                    return false;
                }
                if (dispatch) {
                    const tr = state.tr;
                    removeColumn(tr, selectedRect(state), col);
                    dispatch(tr);
                }
                return true;
            },
            insertRowAt: (row) => ({ state, dispatch }) => {
                if (!isInTable(state)) {
                    return false;
                }
                if (dispatch) {
                    const tr = state.tr;
                    addRow(tr, selectedRect(state), row);
                    dispatch(tr);
                }
                return true;
            },
            deleteRowAt: (row) => ({ state, dispatch }) => {
                if (!isInTable(state)) {
                    return false;
                }
                if (dispatch) {
                    const tr = state.tr;
                    removeRow(tr, selectedRect(state), row);
                    dispatch(tr);
                }
                return true;
            },
        };
    },
});

export { TableGrips };
