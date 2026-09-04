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

/* global TiptapCore, TiptapStarterKit, TiptapImage, TiptapPlaceholder, TiptapBubbleMenu */
/* global TableKit, TiptapPMTables */
/* global TiptapFileHandler, glpi_toast_error */

import { SlashCommands } from '/js/modules/TipTap/SlashCommandsExtension.js';
import { Base64ImageHandler } from '/js/modules/TipTap/Base64ImageHandlerExtension.js';
import { VideoEmbed } from '/js/modules/TipTap/VideoEmbedExtension.js';
import { TableGrips } from '/js/modules/TipTap/TableGripsExtension.js';
import { post } from '/js/modules/Ajax.js';
import { FileUploader } from '/js/modules/FileUploader.js';
import { CommentHighlight, getRefreshedCommentAnchors, getResolvedCommentAnchors } from '/js/modules/TipTap/CommentHighlightExtension.js';
import { buildPmTextIndex, pmPositionToOffset } from '/js/modules/TipTap/CommentPosition.js';
import { extractAnchor } from '/js/modules/Knowbase/CommentAnchor.js';

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
     * @param {number|null} options.item_id - KB article ID (null for new articles)
     */
    constructor(element, options = {}) {
        this.#element = element;
        this.#options = {
            content: '',
            readonly: true,
            placeholder: __('Type / to insert...'),
            onUpdate: null,
            item_id: null,
            can_comment: false,
            comment_anchors: [],
            comment_anchor_max_length: Number.POSITIVE_INFINITY,
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

        // Tiptap appends its .ProseMirror element without clearing existing content.
        this.#element.innerHTML = '';

        // Not appended to DOM here: the BubbleMenu plugin manages insertion/removal.
        this.#bubbleMenuElement = this.#createBubbleMenu();

        // Get SlashCommands extension
        const slashCommandsExt = SlashCommands;

        const extensions = [
            TiptapStarterKit.configure({
                heading: {
                    levels: [1, 2, 3, 4, 5, 6],
                },
                link: {
                    openOnClick: false,
                    HTMLAttributes: {
                        rel: 'noopener noreferrer',
                    },
                },
            }),
            TiptapImage.configure({
                inline: false,
                allowBase64: true,
            }),
            TiptapPlaceholder.configure({
                placeholder: this.#options.placeholder,
                showOnlyCurrent: true,
            }),
            TiptapBubbleMenu.configure({
                element: this.#bubbleMenuElement,
                appendTo: () => this.#element.closest('.kb-article') ?? document.body,
                shouldShow: ({ editor, state }) => editor.isEditable
                    && !state.selection.empty
                    && !editor.isActive('image')
                    && !(state.selection instanceof TiptapPMTables.CellSelection),
                options: {
                    placement: 'top',
                    offset: 8,
                },
            }),
            TableKit.configure({
                table: {
                    resizable: true,
                }
            }),
            TableGrips,
            VideoEmbed,
            CommentHighlight.configure({ anchors: this.#options.comment_anchors }),
        ];

        // Add FileHandler for image drag & drop and paste (only for existing articles)
        if (this.#options.item_id > 0) {
            extensions.push(TiptapFileHandler.configure({
                allowedMimeTypes: ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/bmp'],
                onDrop: (editor, files, pos) => {
                    for (const file of files) {
                        this.#uploadAndInsertImage(editor, file, pos);
                    }
                },
                onPaste: (editor, files) => {
                    for (const file of files) {
                        this.#uploadAndInsertImage(editor, file);
                    }
                },
            }));

            // Intercept base64 images that bypass the FileHandler (e.g. pasted
            // HTML from external sources) and convert them to server uploads.
            extensions.push(Base64ImageHandler.configure({
                uploadHandler: async (dataUri) => {
                    try {
                        const file = this.#dataUriToFile(dataUri);
                        return await this.#uploadImageFile(file);
                    } catch (error) {
                        glpi_toast_error(__('Image upload failed'));
                        console.error('Base64 image upload error:', error);
                        return null;
                    }
                },
            }));
        }

        // Add SlashCommands extension if available
        if (slashCommandsExt) {
            extensions.push(slashCommandsExt);
        }

        this.#editor = new Editor({
            element: this.#element,
            extensions,
            content: this.#options.content,
            editable: this.#isEditable,
            editorProps: {
                // Chromium/Brave mis-place the caret in table cells on click;
                // force it back to the PM-computed pos (no-op on Firefox).
                handleClick: (view, pos) => {
                    const $pos = view.state.doc.resolve(pos);
                    for (let depth = $pos.depth; depth > 0; depth--) {
                        const name = $pos.node(depth).type.name;
                        if (name === 'tableCell' || name === 'tableHeader') {
                            this.#editor?.commands.setTextSelection(pos);
                            return true;
                        }
                    }
                    return false;
                },
                // Jump straight into the bubble menu on Tab instead of leaving
                // the keyboard user to tab through unrelated page controls first.
                handleKeyDown: (view, event) => {
                    if (event.key !== 'Tab' || event.shiftKey) {
                        return false;
                    }
                    // Don't hijack Tab's pre-existing meaning inside a list
                    // item (indent) or a table cell (next cell).
                    if (this.#editor.isActive('listItem') || this.#editor.isActive('tableCell') || this.#editor.isActive('tableHeader')) {
                        return false;
                    }
                    if (view.state.selection.empty || !this.#bubbleMenuElement) {
                        return false;
                    }
                    // isConnected also covers "not shown yet": Tiptap detaches
                    // the element (`element.remove()`) when hiding the menu.
                    const menuVisible = this.#bubbleMenuElement.isConnected
                        && this.#bubbleMenuElement.style.visibility !== 'hidden';
                    if (!menuVisible) {
                        return false;
                    }
                    const buttons = this.#getVisibleButtons(this.#bubbleMenuElement);
                    if (buttons.length === 0) {
                        return false;
                    }
                    event.preventDefault();
                    this.#focusBubbleButton(buttons[0]);
                    return true;
                },
            },
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

        // BubbleMenuView's constructor overwrites tabIndex to 0; re-assert -1
        // so the container itself isn't a second, dead tab stop.
        if (this.#bubbleMenuElement) {
            this.#bubbleMenuElement.tabIndex = -1;
        }

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
        menu.style.position = 'absolute';
        menu.style.width = 'max-content';
        menu.setAttribute('role', 'toolbar');
        menu.setAttribute('aria-label', __('Text formatting'));
        menu.setAttribute('aria-orientation', 'horizontal');
        menu.tabIndex = -1;
        menu.addEventListener('keydown', (e) => this.#handleBubbleMenuKeyDown(e));
        // Refocus only if `e.target` itself got hidden (e.g. "Remove link"),
        // not on unrelated blurs (Alt-Tab, window.prompt()) sharing relatedTarget===null.
        menu.addEventListener('focusout', (e) => {
            if (e.relatedTarget !== null) return;
            if (!menu.isConnected || menu.style.visibility === 'hidden') return;
            const visible = this.#getVisibleButtons(menu);
            if (e.target instanceof HTMLElement && visible.includes(e.target)) return;
            if (visible.length > 0) {
                this.#focusBubbleButton(visible[0]);
            }
        });

        const buttons = [
            { command: 'toggleBold', icon: 'ti ti-bold', title: __('Bold'), shortcutAria: 'Control+b', shortcutMac: '⌘B', shortcutOther: 'Ctrl+B' },
            { command: 'toggleItalic', icon: 'ti ti-italic', title: __('Italic'), shortcutAria: 'Control+i', shortcutMac: '⌘I', shortcutOther: 'Ctrl+I' },
            { command: 'toggleStrike', icon: 'ti ti-strikethrough', title: __('Strikethrough'), shortcutAria: 'Control+Shift+s', shortcutMac: '⌘⇧S', shortcutOther: 'Ctrl+Shift+S' },
            { command: 'toggleCode', icon: 'ti ti-code', title: __('Code'), shortcutAria: 'Control+e', shortcutMac: '⌘E', shortcutOther: 'Ctrl+E' },
            { type: 'divider' },
            { command: 'toggleHeading1', icon: 'ti ti-h-1', title: __('Heading 1'), special: 'heading', level: 1, shortcutAria: 'Control+Alt+1', shortcutMac: '⌘⌥1', shortcutOther: 'Ctrl+Alt+1' },
            { command: 'toggleHeading2', icon: 'ti ti-h-2', title: __('Heading 2'), special: 'heading', level: 2, shortcutAria: 'Control+Alt+2', shortcutMac: '⌘⌥2', shortcutOther: 'Ctrl+Alt+2' },
            { command: 'toggleHeading3', icon: 'ti ti-h-3', title: __('Heading 3'), special: 'heading', level: 3, shortcutAria: 'Control+Alt+3', shortcutMac: '⌘⌥3', shortcutOther: 'Ctrl+Alt+3' },
            { type: 'divider' },
            { command: 'toggleBulletList', icon: 'ti ti-list', title: __('Bullet List'), shortcutAria: 'Control+Shift+8', shortcutMac: '⌘⇧8', shortcutOther: 'Ctrl+Shift+8' },
            { command: 'toggleOrderedList', icon: 'ti ti-list-numbers', title: __('Numbered List'), shortcutAria: 'Control+Shift+7', shortcutMac: '⌘⇧7', shortcutOther: 'Ctrl+Shift+7' },
            { command: 'toggleBlockquote', icon: 'ti ti-blockquote', title: __('Quote'), shortcutAria: 'Control+Shift+b', shortcutMac: '⌘⇧B', shortcutOther: 'Ctrl+Shift+B' },
            { type: 'divider' },
            { command: 'setLink', icon: 'ti ti-link', title: _x('button', 'Link'), special: 'link' },
            { command: 'unsetLink', icon: 'ti ti-link-off', title: __('Remove link'), special: 'unlink' },
        ];

        if (this.#options.can_comment) {
            buttons.push(
                { type: 'divider' },
                { command: 'comment', icon: 'ti ti-message-circle-plus', title: _x('button', 'Comment'), special: 'comment' },
            );
        }

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
            // Without aria-hidden on the icon + an explicit aria-label, a
            // screen reader gets either no name or the glyph's raw code point.
            button.setAttribute('aria-label', btn.title);
            if (btn.shortcutAria) {
                const isMac = TiptapCore.isMacOS() || TiptapCore.isiOS();
                const shortcutLabel = isMac ? btn.shortcutMac : btn.shortcutOther;
                button.title = `${btn.title} (${shortcutLabel})`;
                // Tiptap's "Mod" shortcuts resolve to Cmd on macOS, not Ctrl.
                const ariaShortcut = isMac ? btn.shortcutAria.replace(/^Control\+/, 'Meta+') : btn.shortcutAria;
                button.setAttribute('aria-keyshortcuts', ariaShortcut);
            } else {
                button.title = btn.title;
            }
            button.tabIndex = -1;

            const icon = document.createElement('i');
            icon.className = btn.icon;
            icon.setAttribute('aria-hidden', 'true');
            button.appendChild(icon);

            button.addEventListener('mousedown', (e) => {
                // Keeps a mouse user's focus in the editor (doesn't affect keyboard activation).
                e.preventDefault();
            });
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.#executeBubbleCommand(btn.command, btn.special, btn.level);
                if (this.#getVisibleButtons(this.#bubbleMenuElement).includes(button)) {
                    this.#setRovingTabIndex(button);
                }
                // If this command hid `button` itself, the focusout listener above handles it.
            });

            menu.appendChild(button);
        });

        const initialButtons = this.#getVisibleButtons(menu);
        if (initialButtons.length > 0) {
            initialButtons[0].tabIndex = 0;
        }

        return menu;
    }

    /**
     * Buttons reachable via keyboard: not a divider, not hidden, not disabled.
     * @param {HTMLElement} container
     * @returns {HTMLButtonElement[]}
     */
    #getVisibleButtons(container) {
        return Array.from(container.querySelectorAll('.bubble-menu-btn'))
            .filter((btn) => btn.style.display !== 'none' && !btn.disabled);
    }

    /**
     * Roving tabindex: exactly one visible button is a tab stop at a time.
     * @param {HTMLButtonElement} activeButton
     */
    #setRovingTabIndex(activeButton) {
        if (!this.#bubbleMenuElement) return;
        this.#bubbleMenuElement.querySelectorAll('.bubble-menu-btn').forEach((btn) => {
            btn.tabIndex = btn === activeButton ? 0 : -1;
        });
    }

    /**
     * @param {HTMLButtonElement} button
     */
    #focusBubbleButton(button) {
        this.#setRovingTabIndex(button);
        button.focus();
    }

    /**
     * Arrow/Home/End navigation between the toolbar's visible buttons.
     * @param {KeyboardEvent} event
     */
    #handleBubbleMenuKeyDown(event) {
        if (!this.#bubbleMenuElement) return;

        const buttons = this.#getVisibleButtons(this.#bubbleMenuElement);
        const currentIndex = buttons.indexOf(document.activeElement);

        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            if (buttons.length === 0) return;
            if (currentIndex === -1) {
                this.#focusBubbleButton(buttons[0]);
                return;
            }
            const delta = event.key === 'ArrowRight' ? 1 : -1;
            const nextIndex = (currentIndex + delta + buttons.length) % buttons.length;
            this.#focusBubbleButton(buttons[nextIndex]);
        } else if (event.key === 'Home') {
            event.preventDefault();
            if (buttons.length > 0) this.#focusBubbleButton(buttons[0]);
        } else if (event.key === 'End') {
            event.preventDefault();
            if (buttons.length > 0) this.#focusBubbleButton(buttons[buttons.length - 1]);
        } else if (event.key === 'Escape') {
            // No stopPropagation(): let page-level Escape handlers still see this.
            event.preventDefault();
            this.#editor?.commands.focus();
        }
    }

    /**
     * Execute a bubble menu command
     * @param {string} command
     * @param {string|undefined} special
     * @param {number|undefined} level
     */
    #executeBubbleCommand(command, special, level) {
        if (!this.#editor) return;

        // No `.focus()` in these chains: keeping DOM focus wherever it
        // already is lets a keyboard user chain another toolbar action.
        if (special === 'link') {
            const previousUrl = this.#editor.getAttributes('link').href || '';
            const url = window.prompt(__('Enter URL'), previousUrl);
            if (url === null) return; // Cancelled
            if (url === '') {
                this.#editor.chain().unsetLink().run();
            } else {
                this.#editor.chain().setLink({ href: url }).run();
            }
        } else if (special === 'heading') {
            this.#editor.chain().toggleHeading({ level }).run();
        } else if (special === 'comment') {
            this.#dispatchCommentSelection();
        } else if (this.#editor.chain()[command]) {
            this.#editor.chain()[command]().run();
        }
    }

    /**
     * A passage longer than the server accepts can't be commented on.
     * @param {HTMLButtonElement} button
     */
    #updateCommentButtonAvailability(button) {
        const { from, to } = this.#editor.state.selection;
        const selected = this.#editor.state.doc.textBetween(from, to);

        button.disabled = selected.length > this.#options.comment_anchor_max_length;
    }

    /**
     * Extract an anchor for the current selection and dispatch it so
     * ArticleController.js can open the Comments panel with it pre-filled.
     */
    #dispatchCommentSelection() {
        if (!this.#editor) return;

        const { from, to } = this.#editor.state.selection;
        if (from === to) return;

        const { text, segments } = buildPmTextIndex(this.#editor.state.doc);
        const start = pmPositionToOffset(segments, from);
        const end = pmPositionToOffset(segments, to);
        const anchor = extractAnchor(text, start, end);

        if (anchor.exact.length > this.#options.comment_anchor_max_length) {
            return;
        }

        this.#element.dispatchEvent(new CustomEvent('glpi:kb:comment-selection', {
            bubbles: true,
            detail: { anchor },
        }));
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
            } else if (special === 'comment') {
                this.#updateCommentButtonAvailability(btn);
            }

            btn.classList.toggle('is-active', isActive);
        });

        const visible = this.#getVisibleButtons(this.#bubbleMenuElement);
        const hasRovingTarget = visible.some((btn) => btn.tabIndex === 0);
        if (!hasRovingTarget && visible.length > 0) {
            this.#setRovingTabIndex(visible[0]);
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
     * Recompute the highlighted comment ranges (e.g. after a new anchored
     * comment was added while editing).
     * @param {Array<{id: number|string, prefix: string, exact: string, suffix: string, occurrence: number}>} anchors
     */
    refreshCommentAnchors(anchors) {
        this.#editor?.commands.refreshCommentHighlights(anchors);
    }

    /**
     * Mark `comment_id`'s passage as hovered across all its fragments, or clear it with null.
     * @param {string|null} comment_id
     */
    setCommentHighlightHover(comment_id) {
        this.#editor?.commands.setCommentHighlightHover(comment_id);
    }

    /**
     * Anchors whose quoted text is still present in the document.
     * @returns {Array<{id: string, text: string}>}
     */
    getResolvedCommentAnchors() {
        return this.#editor ? getResolvedCommentAnchors(this.#editor.state) : [];
    }

    /**
     * Anchors re-extracted from where their highlight now sits, to be persisted on save.
     * @returns {Array<{id: string, prefix: string, exact: string, suffix: string, occurrence: number}>}
     */
    getRefreshedCommentAnchors() {
        return this.#editor ? getRefreshedCommentAnchors(this.#editor.state) : [];
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
     * Upload an image file to GLPI and create a Document linked to this KB item.
     * @param {File} file - Image file to upload
     * @returns {Promise<string>} The document.send.php URL for the uploaded image
     */
    async #uploadImageFile(file) {
        // Step 1: Upload to GLPI temp dir
        const file_info = await FileUploader.uploadFile(file);

        // Step 2: Create Document via controller
        const doc_response = await post(
            `Knowbase/${this.#options.item_id}/UploadInlineImage`,
            {
                filename: file_info.name,
                prefix: file_info.prefix || '',
            }
        );
        const doc_data = await doc_response.json();
        if (!doc_data.success) {
            throw new Error(doc_data.message || __('Document creation failed'));
        }

        return doc_data.url;
    }

    /**
     * Upload an image file and insert it into the editor
     * @param {object} editor - TipTap editor instance
     * @param {File} file - Image file
     * @param {number|undefined} pos - Insert position (undefined = current cursor)
     */
    async #uploadAndInsertImage(editor, file, pos) {
        try {
            const image_url = await this.#uploadImageFile(file);

            const attrs = { src: image_url };
            if (pos !== undefined) {
                editor.chain().focus().insertContentAt(pos, {
                    type: 'image',
                    attrs,
                }).run();
            } else {
                editor.chain().focus().setImage(attrs).run();
            }
        } catch (error) {
            glpi_toast_error(__('Image upload failed'));
            console.error('Image upload error:', error);
        }
    }

    /**
     * Convert a data: URI to a File object
     * @param {string} dataUri - The data: URI (e.g. "data:image/png;base64,...")
     * @returns {File}
     */
    #dataUriToFile(dataUri) {
        const commaIndex = dataUri.indexOf(',');
        if (commaIndex === -1) {
            throw new Error('Invalid data URI: missing comma separator');
        }
        const header = dataUri.slice(0, commaIndex);
        const base64Data = dataUri.slice(commaIndex + 1);
        const mimeMatch = header.match(/:(.*?);/);
        if (!mimeMatch) {
            throw new Error('Invalid data URI: could not extract MIME type');
        }
        const mime = mimeMatch[1];
        if (mime === 'image/svg+xml') {
            throw new Error('SVG images are not supported');
        }
        const binary = atob(base64Data);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        const ext = mime.split('/')[1].replace('+xml', '');
        return new File([bytes], `pasted-image.${ext}`, { type: mime });
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
