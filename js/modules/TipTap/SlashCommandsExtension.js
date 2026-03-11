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

/* global TiptapCore, TiptapSuggestion, FloatingUI */

/**
 * Slash commands extension for Tiptap editor
 * Provides a Notion-like command menu triggered by typing "/"
 */

const { Extension } = TiptapCore;

/**
 * Available slash commands
 * Each command receives (editor, range) and must delete the range in the same chain
 * Uses insertContent to INSERT new blocks rather than transforming existing content
 */
const SLASH_COMMANDS = [
    {
        title: __('Heading 1'),
        icon: 'ti ti-h-1',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertContent({ type: 'heading', attrs: { level: 1 } }).run(),
    },
    {
        title: __('Heading 2'),
        icon: 'ti ti-h-2',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertContent({ type: 'heading', attrs: { level: 2 } }).run(),
    },
    {
        title: __('Heading 3'),
        icon: 'ti ti-h-3',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertContent({ type: 'heading', attrs: { level: 3 } }).run(),
    },
    {
        title: __('Bullet List'),
        icon: 'ti ti-list',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertContent({ type: 'bulletList', content: [{ type: 'listItem', content: [{ type: 'paragraph' }] }] }).run(),
    },
    {
        title: __('Numbered List'),
        icon: 'ti ti-list-numbers',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertContent({ type: 'orderedList', content: [{ type: 'listItem', content: [{ type: 'paragraph' }] }] }).run(),
    },
    {
        title: __('Quote'),
        icon: 'ti ti-blockquote',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertContent({ type: 'blockquote', content: [{ type: 'paragraph' }] }).run(),
    },
    {
        title: __('Code Block'),
        icon: 'ti ti-code',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertContent({ type: 'codeBlock' }).run(),
    },
    {
        title: __('Table'),
        icon: 'ti ti-table',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    },
    {
        title: __('Divider'),
        icon: 'ti ti-minus',
        command: (editor, range) => editor.chain().focus().deleteRange(range).insertContent({ type: 'horizontalRule' }).run(),
    },
    {
        title: __('Image'),
        icon: 'ti ti-photo',
        command: (editor, range) => {
            // TODO: Integrate with GLPI document picker
            const url = window.prompt(__('Enter image URL'));
            if (url) {
                editor.chain().focus().deleteRange(range).insertContent({ type: 'image', attrs: { src: url } }).run();
            }
        },
    },
];

/**
 * Create the slash menu DOM element
 * @param {Array} items - Command items to display
 * @param {number} selectedIndex - Currently selected index
 * @param {function} onSelect - Callback when an item is selected
 * @returns {HTMLElement}
 */
function createSlashMenu(items, selectedIndex, onSelect) {
    const menu = document.createElement('div');
    menu.classList.add('slash-menu');

    if (items.length === 0) {
        const empty = document.createElement('div');
        empty.classList.add('slash-menu-empty');
        empty.textContent = __('No results');
        menu.appendChild(empty);
        return menu;
    }

    items.forEach((item, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.classList.add('slash-menu-item');
        if (index === selectedIndex) {
            button.classList.add('is-selected');
        }

        const icon = document.createElement('i');
        icon.className = item.icon;
        button.appendChild(icon);

        const text = document.createElement('span');
        text.textContent = item.title;
        button.appendChild(text);

        button.addEventListener('click', () => onSelect(index));
        menu.appendChild(button);
    });

    return menu;
}

/**
 * Slash commands Tiptap extension
 */
const SlashCommands = Extension.create({
    name: 'slashCommands',

    addOptions() {
        return {
            suggestion: {
                char: '/',
                allowSpaces: false,
                startOfLine: false,
                items: ({ query }) => {
                    const lowerQuery = query.toLowerCase();
                    return SLASH_COMMANDS.filter((item) =>
                        item.title.toLowerCase().includes(lowerQuery)
                    );
                },
                render: () => {
                    const { computePosition, autoUpdate, offset, flip, shift } = FloatingUI;
                    let floatingElement = null;
                    let menuElement = null;
                    let cleanupAutoUpdate = null;
                    let selectedIndex = 0;
                    let items = [];
                    let commandFn = null;

                    const updateMenu = () => {
                        if (!menuElement) return;

                        menuElement.querySelectorAll('.slash-menu-item').forEach((el, index) => {
                            el.classList.toggle('is-selected', index === selectedIndex);
                        });

                        // Scroll selected item into view
                        const selectedEl = menuElement.querySelector('.is-selected');
                        if (selectedEl) {
                            selectedEl.scrollIntoView({ block: 'nearest' });
                        }
                    };

                    const selectItem = (index) => {
                        const item = items[index];
                        if (item) {
                            commandFn(item);
                        }
                    };

                    const updatePosition = (props) => {
                        if (!floatingElement) return;

                        // Create a virtual element from clientRect for Floating UI
                        const virtualElement = {
                            getBoundingClientRect: props.clientRect,
                        };

                        computePosition(virtualElement, floatingElement, {
                            placement: 'bottom-start',
                            middleware: [offset(6), flip(), shift({ padding: 8 })],
                        }).then(({ x, y }) => {
                            Object.assign(floatingElement.style, {
                                left: `${x}px`,
                                top: `${y}px`,
                            });
                        });
                    };

                    return {
                        onStart: (props) => {
                            items = props.items;
                            selectedIndex = 0;
                            commandFn = props.command;

                            menuElement = createSlashMenu(items, selectedIndex, selectItem);

                            // Create floating container
                            floatingElement = document.createElement('div');
                            floatingElement.style.cssText = 'position: absolute; z-index: 1050;';
                            floatingElement.appendChild(menuElement);
                            document.body.appendChild(floatingElement);

                            // Initial position
                            updatePosition(props);

                            // Auto-update position on scroll/resize
                            const virtualElement = {
                                getBoundingClientRect: props.clientRect,
                            };
                            cleanupAutoUpdate = autoUpdate(virtualElement, floatingElement, () => {
                                updatePosition(props);
                            });
                        },

                        onUpdate: (props) => {
                            items = props.items;
                            selectedIndex = 0;
                            commandFn = props.command;

                            // Recreate menu with new items
                            menuElement = createSlashMenu(items, selectedIndex, selectItem);

                            if (floatingElement) {
                                floatingElement.innerHTML = '';
                                floatingElement.appendChild(menuElement);
                                updatePosition(props);

                                // Update auto-update with new clientRect
                                if (cleanupAutoUpdate) {
                                    cleanupAutoUpdate();
                                }
                                const virtualElement = {
                                    getBoundingClientRect: props.clientRect,
                                };
                                cleanupAutoUpdate = autoUpdate(virtualElement, floatingElement, () => {
                                    updatePosition(props);
                                });
                            }
                        },

                        onKeyDown: (props) => {
                            const { event } = props;

                            if (event.key === 'ArrowUp') {
                                event.preventDefault();
                                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                                updateMenu();
                                return true;
                            }

                            if (event.key === 'ArrowDown') {
                                event.preventDefault();
                                selectedIndex = (selectedIndex + 1) % items.length;
                                updateMenu();
                                return true;
                            }

                            if (event.key === 'Enter') {
                                event.preventDefault();
                                selectItem(selectedIndex);
                                return true;
                            }

                            if (event.key === 'Escape') {
                                // Cleanup is handled by onExit
                                return true;
                            }

                            return false;
                        },

                        onExit: () => {
                            if (cleanupAutoUpdate) {
                                cleanupAutoUpdate();
                                cleanupAutoUpdate = null;
                            }
                            if (floatingElement) {
                                floatingElement.remove();
                                floatingElement = null;
                            }
                            menuElement = null;
                        },
                    };
                },
                command: ({ editor, range, props }) => {
                    // Execute the command with range - deletion happens in the same chain
                    props.command(editor, range);
                },
            },
        };
    },

    addProseMirrorPlugins() {
        return [
            TiptapSuggestion({
                editor: this.editor,
                ...this.options.suggestion,
            }),
        ];
    },
});

export { SlashCommands };
