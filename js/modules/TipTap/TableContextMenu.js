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
 * Grip-triggered delete control: a round red "−" button for the selected
 * column/row/table, anchored to the clicked grip's rect.
 */

let activeMenu = null;

function closeTableContextMenu() {
    if (activeMenu) {
        activeMenu.cleanup();
        activeMenu = null;
    }
}

/**
 * @param {'column'|'row'|'table'} kind
 * @param {number} tablePos - document position of the target table node
 * @param {number} index
 * @param {object} editor - Tiptap editor
 * @returns {{label: string, run: function}}
 */
function buildDeleteItem(kind, tablePos, index, editor) {
    if (kind === 'column') {
        return { label: __('Delete column'), run: () => editor.commands.deleteColumnAt(tablePos, index) };
    }
    if (kind === 'row') {
        return { label: __('Delete row'), run: () => editor.commands.deleteRowAt(tablePos, index) };
    }
    return { label: __('Delete table'), run: () => editor.commands.deleteTableAt(tablePos) };
}

/**
 * Position the button relative to the clicked grip so it always lands in the
 * same spot for a given column/row (columns → centred below the grip bar; rows
 * → left of the grip, centred on the row). Clamped to the viewport.
 * @param {HTMLElement} menu
 * @param {DOMRect} anchor - the grip host's bounding rect (measured pre-select)
 * @param {'column'|'row'|'table'} kind
 */
function positionTableMenu(menu, anchor, kind) {
    const gap = 6;
    const mw = menu.offsetWidth;
    const mh = menu.offsetHeight;
    let left;
    let top;
    if (kind === 'row') {
        // Left of the grip, in the gutter, centred on the row.
        left = anchor.left - mw - gap;
        top = anchor.top + (anchor.height / 2) - (mh / 2);
    } else if (kind === 'table') {
        // Up-left of the corner grip, clear of the row/column controls.
        left = anchor.left - mw - 16;
        top = anchor.top - mh - 16;
    } else {
        // column: above the grip, in the top gutter, centred on the column.
        left = anchor.left + (anchor.width / 2) - (mw / 2);
        top = anchor.top - mh - gap;
    }
    left = Math.min(Math.max(8, left), window.innerWidth - mw - 8);
    top = Math.min(Math.max(8, top), window.innerHeight - mh - 8);
    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
}

/**
 * Open the delete control anchored to the clicked grip.
 * @param {object} opts
 * @param {DOMRect} opts.anchorRect - the clicked grip host's bounding rect
 * @param {'column'|'row'|'table'} opts.kind
 * @param {number} opts.tablePos - document position of the target table node
 * @param {number} opts.index - column/row index (ignored for 'table')
 * @param {object} opts.editor - Tiptap editor
 */
function openTableContextMenu({ anchorRect, kind, tablePos, index, editor }) {
    closeTableContextMenu();

    const item = buildDeleteItem(kind, tablePos, index, editor);

    const menu = document.createElement('div');
    menu.className = 'kb-table-menu';
    menu.style.position = 'fixed';
    menu.style.zIndex = '1080';

    // Round red "−" button (mirror of the blue "+" insert control). The visible
    // shape is drawn in CSS; the action label lives on aria-label + title.
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'kb-table-delete';
    button.setAttribute('aria-label', item.label);
    button.setAttribute('title', item.label);
    button.addEventListener('click', () => {
        item.run();
        closeTableContextMenu();
        editor.commands.focus();
    });
    menu.appendChild(button);

    document.body.appendChild(menu);

    // Hide the insert "+" controls while deleting so they don't crowd the "−".
    const pmDom = editor.view?.dom;
    pmDom?.classList.add('kb-table-menu-open');

    positionTableMenu(menu, anchorRect, kind);

    button.focus();

    const onPointerDown = (event) => {
        if (!menu.contains(event.target)) {
            closeTableContextMenu();
        }
    };
    const onKeyDown = (event) => {
        if (event.key === 'Escape') {
            closeTableContextMenu();
            editor.commands.focus();
        }
    };
    const onScroll = () => closeTableContextMenu();

    // Defer so the opening mousedown doesn't immediately dismiss the menu.
    setTimeout(() => {
        document.addEventListener('mousedown', onPointerDown, true);
        document.addEventListener('keydown', onKeyDown, true);
        window.addEventListener('scroll', onScroll, true);
    }, 0);

    activeMenu = {
        cleanup: () => {
            document.removeEventListener('mousedown', onPointerDown, true);
            document.removeEventListener('keydown', onKeyDown, true);
            window.removeEventListener('scroll', onScroll, true);
            pmDom?.classList.remove('kb-table-menu-open');
            menu.remove();
        },
    };
}

export { openTableContextMenu, closeTableContextMenu };
