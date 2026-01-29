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

/* global TiptapCore, TiptapStarterKit, TiptapLink, TiptapImage, TiptapPlaceholder, TiptapBubbleMenu */
/* global TiptapTable, TiptapTableRow, TiptapTableHeader, TiptapTableCell */

import { SlashCommands } from '/js/modules/TipTap/SlashCommandsExtension.js';

/**
 * Knowbase article editor based on Tiptap
 */
class KnowbaseEditor {
    /** @type {HTMLElement} */
    #element;

    /** @type {object} */
    #options;

    /** @type {object|null} */
    #editor = null;

    /** @type {boolean} */
    #isEditable = false;

    /** @type {HTMLElement|null} */
    #bubbleMenuElement = null;

    /**
     * @param {HTMLElement} element - The DOM element to attach the editor to
     * @param {object} options - Editor options
     * @param {string} options.content - Initial HTML content
     * @param {boolean} options.readonly - Start in readonly mode
     * @param {string} options.placeholder - Placeholder text
     * @param {function} options.onUpdate - Callback when content changes
     */
    constructor(element, options = {}) {
        this.#element = element;
        this.#options = {
            content: '',
            readonly: true,
            placeholder: __('Start writing...'),
            onUpdate: null,
            ...options
        };

        this.#init();
    }

    /**
     * Initialize the Tiptap editor
     */
    #init() {
        const { Editor } = TiptapCore;

        this.#isEditable = !this.#options.readonly;

        // Clear the container before initializing Tiptap
        // Tiptap appends its .ProseMirror element without clearing existing content
        // This ensures we get a clean in-place editing experience (Notion-like)
        this.#element.innerHTML = '';

        // Create bubble menu element for text formatting
        this.#bubbleMenuElement = this.#createBubbleMenu();
        document.body.appendChild(this.#bubbleMenuElement);

        // Get SlashCommands extension
        const slashCommandsExt = SlashCommands;

        const extensions = [
            TiptapStarterKit.configure({
                heading: {
                    levels: [1, 2, 3, 4, 5, 6],
                },
            }),
            TiptapLink.configure({
                openOnClick: false,
                HTMLAttributes: {
                    rel: 'noopener noreferrer',
                },
            }),
            TiptapImage.configure({
                inline: false,
                allowBase64: false,
            }),
            TiptapPlaceholder.configure({
                placeholder: this.#options.placeholder,
            }),
            TiptapBubbleMenu.configure({
                element: this.#bubbleMenuElement,
                placement: 'top',
                offset: 8,
            }),
            TiptapTable.configure({
                resizable: true,
            }),
            TiptapTableRow,
            TiptapTableHeader,
            TiptapTableCell,
        ];

        // Add SlashCommands extension if available
        if (slashCommandsExt) {
            extensions.push(slashCommandsExt);
        }

        this.#editor = new Editor({
            element: this.#element,
            extensions,
            content: this.#options.content,
            editable: this.#isEditable,
            onUpdate: ({ editor }) => {
                if (typeof this.#options.onUpdate === 'function') {
                    this.#options.onUpdate(editor.getHTML());
                }
                this.#updateBubbleMenuState();
            },
            onSelectionUpdate: () => {
                this.#updateBubbleMenuState();
            },
        });

        // Add class to wrapper for styling
        this.#element.classList.add('kb-editor-wrapper');
        if (this.#isEditable) {
            this.#element.classList.add('is-editing');
        }
    }

    /**
     * Create the bubble menu DOM element
     * @returns {HTMLElement}
     */
    #createBubbleMenu() {
        const menu = document.createElement('div');
        menu.classList.add('bubble-menu');

        const buttons = [
            { command: 'toggleBold', icon: 'ti ti-bold', title: __('Bold') },
            { command: 'toggleItalic', icon: 'ti ti-italic', title: __('Italic') },
            { command: 'toggleStrike', icon: 'ti ti-strikethrough', title: __('Strikethrough') },
            { command: 'toggleCode', icon: 'ti ti-code', title: __('Code') },
            { type: 'divider' },
            { command: 'toggleHeading1', icon: 'ti ti-h-1', title: __('Heading 1'), special: 'heading', level: 1 },
            { command: 'toggleHeading2', icon: 'ti ti-h-2', title: __('Heading 2'), special: 'heading', level: 2 },
            { command: 'toggleHeading3', icon: 'ti ti-h-3', title: __('Heading 3'), special: 'heading', level: 3 },
            { type: 'divider' },
            { command: 'toggleBulletList', icon: 'ti ti-list', title: __('Bullet List') },
            { command: 'toggleOrderedList', icon: 'ti ti-list-numbers', title: __('Numbered List') },
            { command: 'toggleBlockquote', icon: 'ti ti-blockquote', title: __('Quote') },
            { type: 'divider' },
            { command: 'setLink', icon: 'ti ti-link', title: _x('button', 'Link'), special: 'link' },
            { command: 'unsetLink', icon: 'ti ti-link-off', title: __('Remove link'), special: 'unlink' },
        ];

        buttons.forEach((btn) => {
            if (btn.type === 'divider') {
                const divider = document.createElement('span');
                divider.classList.add('bubble-menu-divider');
                menu.appendChild(divider);
                return;
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.classList.add('bubble-menu-btn');
            button.dataset.command = btn.command;
            if (btn.special) {
                button.dataset.special = btn.special;
            }
            if (btn.level) {
                button.dataset.level = btn.level;
            }
            button.title = btn.title;

            const icon = document.createElement('i');
            icon.className = btn.icon;
            button.appendChild(icon);

            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.#executeBubbleCommand(btn.command, btn.special, btn.level);
            });

            menu.appendChild(button);
        });

        return menu;
    }

    /**
     * Execute a bubble menu command
     * @param {string} command
     * @param {string|undefined} special
     * @param {number|undefined} level
     */
    #executeBubbleCommand(command, special, level) {
        if (!this.#editor) return;

        if (special === 'link') {
            const previousUrl = this.#editor.getAttributes('link').href || '';
            const url = window.prompt(__('Enter URL'), previousUrl);
            if (url === null) return; // Cancelled
            if (url === '') {
                this.#editor.chain().focus().unsetLink().run();
            } else {
                this.#editor.chain().focus().setLink({ href: url }).run();
            }
        } else if (special === 'heading') {
            this.#editor.chain().focus().toggleHeading({ level }).run();
        } else if (this.#editor.chain().focus()[command]) {
            this.#editor.chain().focus()[command]().run();
        }
    }

    /**
     * Update bubble menu button states (active/inactive)
     */
    #updateBubbleMenuState() {
        if (!this.#editor || !this.#bubbleMenuElement) return;

        const buttons = this.#bubbleMenuElement.querySelectorAll('.bubble-menu-btn');
        buttons.forEach((btn) => {
            const command = btn.dataset.command;
            const special = btn.dataset.special;
            const level = btn.dataset.level ? parseInt(btn.dataset.level, 10) : null;

            let isActive = false;
            if (special === 'link' || special === 'unlink') {
                isActive = this.#editor.isActive('link');
                // Hide unlink button if no link, show link button always
                if (special === 'unlink') {
                    btn.style.display = isActive ? '' : 'none';
                }
            } else if (special === 'heading' && level) {
                isActive = this.#editor.isActive('heading', { level });
            } else if (command === 'toggleBold') {
                isActive = this.#editor.isActive('bold');
            } else if (command === 'toggleItalic') {
                isActive = this.#editor.isActive('italic');
            } else if (command === 'toggleStrike') {
                isActive = this.#editor.isActive('strike');
            } else if (command === 'toggleCode') {
                isActive = this.#editor.isActive('code');
            } else if (command === 'toggleBulletList') {
                isActive = this.#editor.isActive('bulletList');
            } else if (command === 'toggleOrderedList') {
                isActive = this.#editor.isActive('orderedList');
            } else if (command === 'toggleBlockquote') {
                isActive = this.#editor.isActive('blockquote');
            }

            btn.classList.toggle('is-active', isActive);
        });
    }

    /**
     * Get editor content as HTML
     * @returns {string}
     */
    getHTML() {
        return this.#editor?.getHTML() || '';
    }

    /**
     * Get editor content as JSON
     * @returns {object}
     */
    getJSON() {
        return this.#editor?.getJSON() || {};
    }

    /**
     * Set editor content
     * @param {string} content - HTML content
     */
    setContent(content) {
        this.#editor?.commands.setContent(content);
    }

    /**
     * Set editor editable state
     * @param {boolean} editable
     */
    setEditable(editable) {
        this.#isEditable = editable;
        this.#editor?.setEditable(editable);

        if (editable) {
            this.#element.classList.add('is-editing');
        } else {
            this.#element.classList.remove('is-editing');
        }
    }

    /**
     * Check if editor is in editable mode
     * @returns {boolean}
     */
    isEditable() {
        return this.#isEditable;
    }

    /**
     * Focus the editor
     */
    focus() {
        this.#editor?.commands.focus();
    }

    /**
     * Get the underlying Tiptap editor instance
     * @returns {object|null}
     */
    getEditor() {
        return this.#editor;
    }

    /**
     * Destroy the editor instance
     */
    destroy() {
        if (this.#bubbleMenuElement) {
            this.#bubbleMenuElement.remove();
            this.#bubbleMenuElement = null;
        }
        this.#editor?.destroy();
        this.#editor = null;
    }
}

export { KnowbaseEditor };
