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
    TableMap,
    getColumnCellPositions,
    getRowCellPositions,
    cellSelectionInfo,
} from '/js/modules/TipTap/tableQueries.js';
import { openTableContextMenu } from '/js/modules/TipTap/TableContextMenu.js';

const { Extension } = TiptapCore;
const { Plugin, PluginKey } = TiptapPMState;
const { Decoration, DecorationSet } = TiptapPMView;
const {
    CellSelection,
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

const CONTROL_SELECTOR = '.table-col-add, .table-row-add, .table-grip-column, .table-grip-row, .table-grip';

function controlFromPoint(x, y) {
    for (const node of document.elementsFromPoint(x, y)) {
        const match = node instanceof HTMLElement ? node.closest(CONTROL_SELECTOR) : null;
        if (match) {
            return match;
        }
    }
    return null;
}

// event.target can be the cell behind the gutter widget; fall back to coordinates.
function controlFromEvent(event) {
    const { target } = event;
    const direct = target instanceof HTMLElement ? target.closest(CONTROL_SELECTOR) : null;
    return direct || controlFromPoint(event.clientX, event.clientY);
}

// Resolve the hovered control from table geometry (cell rects), which stays
// reliable during pointer events unlike hit-testing the gutter widgets.
function controlAtPoint(dom, x, y) {
    const GUTTER = 40;
    const EDGE = 18; // split between the "+" (outer) and grip-bar (inner) zones
    const NEAR = 14; // distance to a boundary that counts as an insert "+"
    for (const table of dom.querySelectorAll('table')) {
        const t = table.getBoundingClientRect();
        if (x < t.left - GUTTER || x > t.right || y < t.top - GUTTER || y > t.bottom) {
            continue;
        }
        const rows = [...table.rows];
        if (!rows.length) {
            continue;
        }
        const cells = [...rows[0].cells];
        const colX = cells.map((c) => c.getBoundingClientRect().left);
        colX.push(cells[cells.length - 1].getBoundingClientRect().right);
        const rowY = rows.map((r) => r.getBoundingClientRect().top);
        rowY.push(rows[rows.length - 1].getBoundingClientRect().bottom);

        const nearest = (vals, v) => {
            let idx = 0;
            let best = Infinity;
            vals.forEach((val, i) => {
                const d = Math.abs(v - val);
                if (d < best) { best = d; idx = i; }
            });
            return { idx, dist: best };
        };
        const span = (bounds, v) => {
            for (let i = 0; i < bounds.length - 1; i++) {
                if (v >= bounds[i] && v < bounds[i + 1]) { return i; }
            }
            return -1;
        };
        const pick = (sel) => table.querySelector(sel);

        const inTop = y < t.top;
        const inLeft = x < t.left;
        if (inTop && inLeft) {
            return pick('.table-grip');
        }
        if (inTop && x >= t.left && x <= t.right) {
            const b = nearest(colX, x);
            if (y < t.top - EDGE && b.dist < NEAR) {
                return pick(`.table-col-add[data-insert-col="${b.idx}"]`);
            }
            const col = span(colX, x);
            return col < 0 ? null : pick(`.table-grip-column[data-col="${col}"]`);
        }
        if (inLeft && y >= t.top && y <= t.bottom) {
            const b = nearest(rowY, y);
            if (x < t.left - EDGE && b.dist < NEAR) {
                return pick(`.table-row-add[data-insert-row="${b.idx}"]`);
            }
            const row = span(rowY, y);
            return row < 0 ? null : pick(`.table-grip-row[data-row="${row}"]`);
        }
    }
    return null;
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

        // `selInfo` (or null) drives the "selected" highlight for this table.
        const buildTable = (node, tablePos, selInfo, push) => {
            const map = TableMap.get(node);
            const tableStart = tablePos + 1;
            const selHere = selInfo && selInfo.tableStart === tableStart ? selInfo : null;
            const tableSel = !!(selHere && selHere.isCol && selHere.isRow);
            const widget = (pos, factory, key) =>
                push(Decoration.widget(pos, factory, { key }));

            // Column controls — hosted in the first-row cell of each column.
            getColumnCellPositions(map, tableStart).forEach((pos, index) => {
                const selected = tableSel
                    || !!(selHere && selHere.isCol && selHere.left <= index && index < selHere.right);
                widget(pos + 1, () => makeControl(
                    'table-grip-column', { table: tablePos, col: index },
                    __('Select column %s').replace('%s', String(index + 1)), selected,
                ), `grip-col-${tablePos}-${index}-${selected}`);
                // Leading `+` before the very first column.
                if (index === 0) {
                    widget(pos + 1, () => makeControl(
                        'table-col-add first', { table: tablePos, insertCol: 0 },
                        __('Insert column at position %s').replace('%s', '1'),
                    ), `col-add-${tablePos}-0`);
                }
                // `+` at this column's trailing boundary (inserts after it).
                widget(pos + 1, () => makeControl(
                    'table-col-add', { table: tablePos, insertCol: index + 1 },
                    __('Insert column at position %s').replace('%s', String(index + 2)),
                ), `col-add-${tablePos}-${index + 1}`);
            });

            // Row controls — hosted in the first-column cell of each row.
            // Row 0 additionally hosts the whole-table corner grip.
            getRowCellPositions(map, tableStart).forEach((pos, index) => {
                const selected = tableSel
                    || !!(selHere && selHere.isRow && selHere.top <= index && index < selHere.bottom);
                if (index === 0) {
                    widget(pos + 1, () => makeControl(
                        'table-grip', { table: tablePos }, __('Select table'), tableSel,
                    ), `grip-table-${tablePos}-${tableSel}`);
                    widget(pos + 1, () => makeControl(
                        'table-row-add first', { table: tablePos, insertRow: 0 },
                        __('Insert row at position %s').replace('%s', '1'),
                    ), `row-add-${tablePos}-0`);
                }
                widget(pos + 1, () => makeControl(
                    'table-grip-row', { table: tablePos, row: index },
                    __('Select row %s').replace('%s', String(index + 1)), selected,
                ), `grip-row-${tablePos}-${index}-${selected}`);
                // `+` at this row's trailing boundary (inserts below it).
                widget(pos + 1, () => makeControl(
                    'table-row-add', { table: tablePos, insertRow: index + 1 },
                    __('Insert row at position %s').replace('%s', String(index + 2)),
                ), `row-add-${tablePos}-${index + 1}`);
            });
        };

        const build = (state) => {
            if (!editor.isEditable) {
                return DecorationSet.empty;
            }
            const decorations = [];
            const push = (deco) => decorations.push(deco);
            const selInfo = cellSelectionInfo(state);
            state.doc.descendants((node, pos) => {
                if (node.type.name === 'table') {
                    buildTable(node, pos, selInfo, push);
                    return false; // no nested tables; don't descend into cells
                }
                return !node.isTextblock; // skip inline content, recurse into containers
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
                // Publish each table's pixel size as CSS vars so the "+" guide line can span it.
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

                    // CSS `:hover` never fires on the gutter widgets, so track it from geometry.
                    let hovered = null;
                    let frame = 0;
                    let lastX = 0;
                    let lastY = 0;
                    const setHovered = (control) => {
                        if (control === hovered) {
                            return;
                        }
                        hovered?.classList.remove('is-hovered');
                        control?.classList.add('is-hovered');
                        hovered = control;
                    };
                    const flush = () => {
                        frame = 0;
                        setHovered(controlAtPoint(editorView.dom, lastX, lastY));
                    };
                    const onMove = (event) => {
                        lastX = event.clientX;
                        lastY = event.clientY;
                        frame = frame || requestAnimationFrame(flush);
                    };
                    const onLeave = () => {
                        if (frame) {
                            cancelAnimationFrame(frame);
                            frame = 0;
                        }
                        setHovered(null);
                    };
                    editorView.dom.addEventListener('mousemove', onMove);
                    editorView.dom.addEventListener('mouseleave', onLeave);

                    return {
                        update: (view, prevState) => {
                            // Newly inserted tables need observing (observe() is idempotent).
                            if (!view.state.doc.eq(prevState.doc)) {
                                observeTables();
                            }
                        },
                        destroy: () => {
                            observer.disconnect();
                            if (frame) {
                                cancelAnimationFrame(frame);
                            }
                            editorView.dom.removeEventListener('mousemove', onMove);
                            editorView.dom.removeEventListener('mouseleave', onLeave);
                        },
                    };
                },
                props: {
                    decorations(state) {
                        return this.getState(state);
                    },
                    handleDOMEvents: {
                        mousedown: (view, event) => {
                            const control = controlAtPoint(view.dom, event.clientX, event.clientY)
                                || controlFromEvent(event);
                            if (!control) {
                                return false;
                            }
                            event.preventDefault();
                            event.stopImmediatePropagation();
                            const tablePos = Number(control.dataset.table);
                            const { classList } = control;
                            if (classList.contains('table-col-add')) {
                                editor.commands.insertColumnAt(tablePos, Number(control.dataset.insertCol));
                            } else if (classList.contains('table-row-add')) {
                                editor.commands.insertRowAt(tablePos, Number(control.dataset.insertRow));
                            } else if (classList.contains('table-grip-column')) {
                                const index = Number(control.dataset.col);
                                // Measure before selecting: selecting re-renders
                                // the grip decoration, detaching this element.
                                const anchorRect = control.getBoundingClientRect();
                                editor.commands.selectColumnAt(tablePos, index);
                                openTableContextMenu({ anchorRect, kind: 'column', tablePos, index, editor });
                            } else if (classList.contains('table-grip-row')) {
                                const index = Number(control.dataset.row);
                                const anchorRect = control.getBoundingClientRect();
                                editor.commands.selectRowAt(tablePos, index);
                                openTableContextMenu({ anchorRect, kind: 'row', tablePos, index, editor });
                            } else {
                                const anchorRect = control.getBoundingClientRect();
                                editor.commands.selectTableAt(tablePos);
                                openTableContextMenu({ anchorRect, kind: 'table', tablePos, index: 0, editor });
                            }
                            return true;
                        },
                    },
                },
            }),
        ];
    },

    addCommands() {
        // Run `fn` against the table at `tablePos` (its own map/start), so a
        // command acts on the grip's table regardless of the live selection.
        const withTable = (tablePos, fn) => ({ state, dispatch }) => {
            const table = state.doc.nodeAt(tablePos);
            if (!table || table.type.name !== 'table') {
                return false;
            }
            return fn({
                state,
                dispatch,
                table,
                map: TableMap.get(table),
                tableStart: tablePos + 1,
            });
        };

        return {
            selectColumnAt: (tablePos, index) => withTable(tablePos, ({ state, dispatch, map, tableStart }) => {
                if (index < 0 || index >= map.width) {
                    return false;
                }
                if (dispatch) {
                    const $cell = state.doc.resolve(tableStart + map.map[index]);
                    dispatch(state.tr.setSelection(CellSelection.colSelection($cell)));
                }
                return true;
            }),
            selectRowAt: (tablePos, index) => withTable(tablePos, ({ state, dispatch, map, tableStart }) => {
                if (index < 0 || index >= map.height) {
                    return false;
                }
                if (dispatch) {
                    const $cell = state.doc.resolve(tableStart + map.map[index * map.width]);
                    dispatch(state.tr.setSelection(CellSelection.rowSelection($cell)));
                }
                return true;
            }),
            selectTableAt: (tablePos) => withTable(tablePos, ({ state, dispatch, map, tableStart }) => {
                if (dispatch) {
                    const $first = state.doc.resolve(tableStart + map.map[0]);
                    const $last = state.doc.resolve(tableStart + map.map[map.map.length - 1]);
                    dispatch(state.tr.setSelection(new CellSelection($first, $last)));
                }
                return true;
            }),
            insertColumnAt: (tablePos, col) => withTable(tablePos, ({ state, dispatch, table, map, tableStart }) => {
                if (dispatch) {
                    const tr = state.tr;
                    addColumn(tr, { map, tableStart, table }, col);
                    dispatch(tr);
                }
                return true;
            }),
            deleteColumnAt: (tablePos, col) => withTable(tablePos, ({ state, dispatch, table, map, tableStart }) => {
                if (dispatch) {
                    const tr = state.tr;
                    // Removing the only column would leave an empty table: drop it.
                    if (map.width <= 1) {
                        tr.delete(tablePos, tablePos + table.nodeSize);
                    } else {
                        removeColumn(tr, { map, tableStart, table }, col);
                    }
                    dispatch(tr);
                }
                return true;
            }),
            insertRowAt: (tablePos, row) => withTable(tablePos, ({ state, dispatch, table, map, tableStart }) => {
                if (dispatch) {
                    const tr = state.tr;
                    addRow(tr, { map, tableStart, table }, row);
                    dispatch(tr);
                }
                return true;
            }),
            deleteRowAt: (tablePos, row) => withTable(tablePos, ({ state, dispatch, table, map, tableStart }) => {
                if (dispatch) {
                    const tr = state.tr;
                    // Removing the only row would leave an empty table: drop it.
                    if (map.height <= 1) {
                        tr.delete(tablePos, tablePos + table.nodeSize);
                    } else {
                        removeRow(tr, { map, tableStart, table }, row);
                    }
                    dispatch(tr);
                }
                return true;
            }),
            deleteTableAt: (tablePos) => withTable(tablePos, ({ state, dispatch, table }) => {
                if (dispatch) {
                    dispatch(state.tr.delete(tablePos, tablePos + table.nodeSize));
                }
                return true;
            }),
        };
    },
});

export { TableGrips };
