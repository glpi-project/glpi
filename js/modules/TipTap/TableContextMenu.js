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
 * Grip-triggered table menu: a small dropdown anchored at the click offering the
 * delete action for the selected column/row/table (insertion stays on the hover
 * "+"). Uses Tabler dropdown classes for native GLPI styling.
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
 * @param {number} index
 * @param {object} editor - Tiptap editor
 * @returns {{label: string, run: function}}
 */
function buildDeleteItem(kind, index, editor) {
    if (kind === 'column') {
        return { label: __('Delete column'), run: () => editor.commands.deleteColumnAt(index) };
    }
    if (kind === 'row') {
        return { label: __('Delete row'), run: () => editor.commands.deleteRowAt(index) };
    }
    return { label: __('Delete table'), run: () => editor.commands.deleteTable() };
}

/**
 * Open the table menu at the pointer.
 * @param {object} opts
 * @param {number} opts.x - viewport clientX
 * @param {number} opts.y - viewport clientY
 * @param {'column'|'row'|'table'} opts.kind
 * @param {number} opts.index - column/row index (ignored for 'table')
 * @param {object} opts.editor - Tiptap editor
 */
function openTableContextMenu({ x, y, kind, index, editor }) {
    closeTableContextMenu();

    const item = buildDeleteItem(kind, index, editor);

    const menu = document.createElement('div');
    menu.className = 'dropdown-menu show kb-table-menu';
    menu.setAttribute('role', 'menu');
    menu.style.position = 'fixed';
    menu.style.zIndex = '1080';

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'dropdown-item text-danger';
    button.setAttribute('role', 'menuitem');
    const icon = document.createElement('i');
    icon.className = 'ti ti-trash me-2';
    button.appendChild(icon);
    button.appendChild(document.createTextNode(item.label));
    button.addEventListener('click', () => {
        item.run();
        closeTableContextMenu();
        editor.commands.focus();
    });
    menu.appendChild(button);

    document.body.appendChild(menu);

    // Position at the pointer, clamped to the viewport.
    menu.style.left = `${x}px`;
    menu.style.top = `${y}px`;
    const rect = menu.getBoundingClientRect();
    if (rect.right > window.innerWidth - 8) {
        menu.style.left = `${Math.max(8, window.innerWidth - rect.width - 8)}px`;
    }
    if (rect.bottom > window.innerHeight - 8) {
        menu.style.top = `${Math.max(8, y - rect.height)}px`;
    }

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
            menu.remove();
        },
    };
}

export { openTableContextMenu, closeTableContextMenu };
