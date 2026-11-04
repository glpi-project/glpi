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

import { showVideoDialog } from '/js/modules/TipTap/VideoEmbedExtension.js';
import { createEditorDialog, createDialogField, createDialogError } from '/js/modules/TipTap/EditorDialog.js';
import { trackPosition } from '/js/modules/TipTap/TrackPosition.js';

/**
 * Slash commands extension for Tiptap editor
 * Provides a Notion-like command menu triggered by typing "/"
 */

const { Extension } = TiptapCore;

/**
 * Show the Insert/Edit Image dialog (Source, Alt, Width, Height)
 * @param {object} editor - TipTap editor instance
 * @param {object|null} existing_attrs - Existing image attributes for editing (null for new)
 * @param {number|null} pos - Position of the edited image (null to insert a new one)
 */
function showImageDialog(editor, existing_attrs = null, pos = null) {
    const uid = Math.random().toString(36).slice(2, 9);

    const source_group = createDialogField(__('Source'), 'text', `image-src-${uid}`, existing_attrs?.src || '');
    const src_input = source_group.querySelector('input');

    const alt_group = createDialogField(__('Alternative description'), 'text', `image-alt-${uid}`, existing_attrs?.alt || '');
    const alt_input = alt_group.querySelector('input');

    const width_group = createDialogField(__('Width'), 'number', `image-width-${uid}`, existing_attrs?.width || '');
    const height_group = createDialogField(__('Height'), 'number', `image-height-${uid}`, existing_attrs?.height || '');
    const width_input = width_group.querySelector('input');
    const height_input = height_group.querySelector('input');

    // Same minimum as a drag with the handles.
    const resize = editor.extensionManager.extensions.find((ext) => ext.name === 'image')?.options.resize;
    const min_width = resize?.minWidth ?? 1;
    const min_height = resize?.minHeight ?? 1;
    width_input.min = String(min_width);
    height_input.min = String(min_height);

    const lock_btn = document.createElement('button');
    lock_btn.type = 'button';
    lock_btn.className = 'image-size-lock is-locked';
    lock_btn.title = __('Constrain proportions');
    lock_btn.innerHTML = '<i class="ti ti-lock" aria-hidden="true"></i>';

    let ratio_locked = true;
    lock_btn.addEventListener('click', () => {
        ratio_locked = !ratio_locked;
        lock_btn.classList.toggle('is-locked', ratio_locked);
        lock_btn.innerHTML = ratio_locked
            ? '<i class="ti ti-lock" aria-hidden="true"></i>'
            : '<i class="ti ti-lock-open" aria-hidden="true"></i>';
    });

    const size_row = document.createElement('div');
    size_row.className = 'image-size-row';
    size_row.appendChild(width_group);
    size_row.appendChild(lock_btn);
    size_row.appendChild(height_group);

    // The stored size gives a ratio before the image loads, or if it never loads.
    let aspect_ratio = existing_attrs?.width && existing_attrs?.height
        ? existing_attrs.width / existing_attrs.height
        : null;
    const loadNaturalSize = () => {
        const img = new Image();
        const requested_src = src_input.value;
        img.onload = () => {
            // A load that ends after a source change must not give the old ratio.
            if (requested_src !== src_input.value) {
                return;
            }
            // An edited image without a size must stay responsive.
            if (pos === null && !width_input.value && !height_input.value) {
                width_input.value = img.naturalWidth;
                height_input.value = img.naturalHeight;
            }
            aspect_ratio = img.naturalWidth / img.naturalHeight;
        };
        img.src = requested_src;
    };
    src_input.addEventListener('change', loadNaturalSize);
    if (src_input.value) {
        loadNaturalSize();
    }

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

    const error = createDialogError(src_input, `image-error-${uid}`);
    const width_error = createDialogError(width_input, `image-width-error-${uid}`);
    const height_error = createDialogError(height_input, `image-height-error-${uid}`);

    // An upload that ends while the dialog is open can move or remove the edited image.
    const edited = pos === null ? null : trackPosition(editor, pos);

    const { body } = createEditorDialog({
        editor,
        title: __('Insert/Edit Image'),
        confirmLabel: __('Save'),
        onClose: () => edited?.stop(),
        onConfirm: ({ close }) => {
            const src = src_input.value.trim();
            if (!src) {
                error.show(__('An image source is required.'));
                src_input.focus();
                return;
            }

            const width = width_input.value ? parseInt(width_input.value, 10) : null;
            const height = height_input.value ? parseInt(height_input.value, 10) : null;
            if (width !== null && !(width >= min_width)) {
                width_error.show(__('The minimum value is %s.').replace('%s', min_width));
                width_input.focus();
                return;
            }
            if (height !== null && !(height >= min_height)) {
                height_error.show(__('The minimum value is %s.').replace('%s', min_height));
                height_input.focus();
                return;
            }

            // Explicit nulls so that a cleared field removes the attribute of an edited image.
            const attrs = {
                src,
                alt: alt_input.value.trim() || null,
                width,
                height,
            };

            if (edited === null) {
                editor.chain().focus().setImage(attrs).run();
            } else if (!edited.deleted && editor.state.doc.nodeAt(edited.pos)?.type.name === 'image') {
                editor.chain().focus().setNodeSelection(edited.pos).updateAttributes('image', attrs).run();
            }
            close();
        },
    });

    body.appendChild(source_group);
    body.appendChild(error.element);
    body.appendChild(alt_group);
    body.appendChild(size_row);
    body.appendChild(width_error.element);
    body.appendChild(height_error.element);

    (pos === null ? src_input : width_input).focus();
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
    {
        title: __('Video'),
        icon: 'ti ti-video',
        command: (editor, range) => {
            editor.chain().focus().deleteRange(range).run();
            showVideoDialog(editor);
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
                // Block the menu inside table cells to avoid nested tables/blocks.
                allow: ({ state, range }) => {
                    const $from = state.doc.resolve(range.from);
                    for (let depth = $from.depth; depth > 0; depth--) {
                        if ($from.node(depth).type.name === 'table') {
                            return false;
                        }
                    }
                    return true;
                },
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

                            const suggestionEl = document.querySelector('.suggestion');
                            if (suggestionEl) {
                                if (props.query === '') {
                                    suggestionEl.classList.add('is-empty');
                                } else {
                                    suggestionEl.classList.remove('is-empty');
                                }
                            }

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

                            const suggestionEl = document.querySelector('.suggestion');
                            if (suggestionEl) {
                                if (props.query === '') {
                                    suggestionEl.classList.add('is-empty');
                                } else {
                                    suggestionEl.classList.remove('is-empty');
                                }
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

export { SlashCommands, showImageDialog };
