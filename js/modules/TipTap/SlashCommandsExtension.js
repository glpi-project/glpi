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
 * Show the Insert/Edit Image dialog (Source, Alt, Width, Height)
 * @param {object} editor - TipTap editor instance
 * @param {object|null} existing_attrs - Existing image attributes for editing (null for new)
 */
function showImageDialog(editor, existing_attrs = null) {
    const overlay = document.createElement('div');
    overlay.className = 'image-dialog-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'image-dialog';

    const title = document.createElement('div');
    title.className = 'image-dialog-header';
    title.innerHTML = `<span>${__('Insert/Edit Image')}</span><button type="button" class="image-dialog-close" aria-label="${__('Close')}"><i class="ti ti-x"></i></button>`;
    dialog.appendChild(title);

    const body = document.createElement('div');
    body.className = 'image-dialog-body';

    // Source field
    const source_group = createField(__('Source'), 'text', 'image-src', existing_attrs?.src || '');
    body.appendChild(source_group);

    // Alt field
    const alt_group = createField(__('Alternative description'), 'text', 'image-alt', existing_attrs?.alt || '');
    body.appendChild(alt_group);

    // Width / Height row
    const size_row = document.createElement('div');
    size_row.className = 'image-dialog-size-row';

    const width_group = createField(__('Width'), 'number', 'image-width', existing_attrs?.width || '');
    const height_group = createField(__('Height'), 'number', 'image-height', existing_attrs?.height || '');

    const lock_btn = document.createElement('button');
    lock_btn.type = 'button';
    lock_btn.className = 'image-dialog-lock is-locked';
    lock_btn.title = __('Constrain proportions');
    lock_btn.innerHTML = '<i class="ti ti-lock"></i>';
    let ratio_locked = true;
    lock_btn.addEventListener('click', () => {
        ratio_locked = !ratio_locked;
        lock_btn.classList.toggle('is-locked', ratio_locked);
        lock_btn.innerHTML = ratio_locked ? '<i class="ti ti-lock"></i>' : '<i class="ti ti-lock-open"></i>';
    });

    size_row.appendChild(width_group);
    size_row.appendChild(lock_btn);
    size_row.appendChild(height_group);
    body.appendChild(size_row);

    // Ratio tracking
    let aspect_ratio = null;
    const width_input = width_group.querySelector('input');
    const height_input = height_group.querySelector('input');

    const src_input = source_group.querySelector('input');
    src_input.addEventListener('change', () => {
        // Try to load image to get natural dimensions
        const img = new Image();
        img.onload = () => {
            if (!width_input.value && !height_input.value) {
                width_input.value = img.naturalWidth;
                height_input.value = img.naturalHeight;
            }
            aspect_ratio = img.naturalWidth / img.naturalHeight;
        };
        img.src = src_input.value;
    });

    width_input.addEventListener('input', () => {
        if (ratio_locked && aspect_ratio && width_input.value) {
            height_input.value = Math.round(parseInt(width_input.value, 10) / aspect_ratio);
        }
    });
    height_input.addEventListener('input', () => {
        if (ratio_locked && aspect_ratio && height_input.value) {
            width_input.value = Math.round(parseInt(height_input.value, 10) * aspect_ratio);
        }
    });

    dialog.appendChild(body);

    // Footer
    const footer = document.createElement('div');
    footer.className = 'image-dialog-footer';

    const cancel_btn = document.createElement('button');
    cancel_btn.type = 'button';
    cancel_btn.className = 'btn btn-outline-secondary';
    cancel_btn.textContent = __('Cancel');

    const save_btn = document.createElement('button');
    save_btn.type = 'button';
    save_btn.className = 'btn btn-primary';
    save_btn.textContent = __('Save');

    footer.appendChild(cancel_btn);
    footer.appendChild(save_btn);
    dialog.appendChild(footer);

    overlay.appendChild(dialog);
    document.body.appendChild(overlay);

    // Focus the source input
    src_input.focus();

    const close = () => {
        overlay.remove();
        editor.commands.focus();
    };

    cancel_btn.addEventListener('click', close);
    title.querySelector('.image-dialog-close').addEventListener('click', close);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            close();
        }
    });

    save_btn.addEventListener('click', () => {
        const src = src_input.value.trim();
        if (!src) {
            src_input.focus();
            return;
        }

        const attrs = { src };
        const alt = source_group.parentElement.querySelector('#image-alt')?.value.trim();
        if (alt) {
            attrs.alt = alt;
        }
        const width = width_input.value;
        const height = height_input.value;
        if (width) {
            attrs.width = parseInt(width, 10);
        }
        if (height) {
            attrs.height = parseInt(height, 10);
        }

        editor.chain().focus().setImage(attrs).run();
        close();
    });
}

/**
 * Create a form field group
 * @param {string} label_text
 * @param {string} type
 * @param {string} id
 * @param {string} value
 * @returns {HTMLElement}
 */
function createField(label_text, type, id, value) {
    const group = document.createElement('div');
    group.className = 'image-dialog-field';

    const label = document.createElement('label');
    label.htmlFor = id;
    label.textContent = label_text;
    group.appendChild(label);

    const input = document.createElement('input');
    input.type = type;
    input.id = id;
    input.className = 'form-control';
    input.value = value;
    if (type === 'number') {
        input.min = '0';
    }
    group.appendChild(input);

    return group;
}

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
            editor.chain().focus().deleteRange(range).run();
            showImageDialog(editor);
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
