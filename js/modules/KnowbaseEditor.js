/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/* global TiptapCore, TiptapStarterKit, TiptapLink, TiptapImage, TiptapPlaceholder */
/* global TiptapTable, TiptapTableRow, TiptapTableHeader, TiptapTableCell */
/* global SlashCommands */

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
        const StarterKit = TiptapStarterKit.default || TiptapStarterKit.StarterKit || TiptapStarterKit;
        const Link = TiptapLink.default || TiptapLink.Link || TiptapLink;
        const Image = TiptapImage.default || TiptapImage.Image || TiptapImage;
        const Placeholder = TiptapPlaceholder.default || TiptapPlaceholder.Placeholder || TiptapPlaceholder;
        const Table = TiptapTable.default || TiptapTable.Table || TiptapTable;
        const TableRow = TiptapTableRow.default || TiptapTableRow.TableRow || TiptapTableRow;
        const TableHeader = TiptapTableHeader.default || TiptapTableHeader.TableHeader || TiptapTableHeader;
        const TableCell = TiptapTableCell.default || TiptapTableCell.TableCell || TiptapTableCell;

        this.#isEditable = !this.#options.readonly;

        // Clear the container before initializing Tiptap
        // Tiptap appends its .ProseMirror element without clearing existing content
        // This ensures we get a clean in-place editing experience (Notion-like)
        this.#element.innerHTML = '';

        // Get SlashCommands extension if available
        const slashCommandsExt = typeof window.SlashCommands !== 'undefined' ? window.SlashCommands : null;

        const extensions = [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3, 4, 5, 6],
                },
            }),
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    rel: 'noopener noreferrer',
                },
            }),
            Image.configure({
                inline: false,
                allowBase64: false,
            }),
            Placeholder.configure({
                placeholder: this.#options.placeholder,
            }),
            Table.configure({
                resizable: true,
            }),
            TableRow,
            TableHeader,
            TableCell,
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
            },
        });

        // Add class to wrapper for styling
        this.#element.classList.add('kb-editor-wrapper');
        if (this.#isEditable) {
            this.#element.classList.add('is-editing');
        }
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
        this.#editor?.destroy();
        this.#editor = null;
    }
}

// Expose to global scope
window.KnowbaseEditor = KnowbaseEditor;
