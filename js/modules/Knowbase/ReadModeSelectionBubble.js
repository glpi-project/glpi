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

import { extractAnchor } from '/js/modules/Knowbase/CommentAnchor.js';
import { buildDomTextIndex, domRangeToOffsets } from '/js/modules/Knowbase/DomTextIndex.js';

/**
 * Minimal "Comment"-only bubble shown on text selection in a KB article that
 * isn't being edited. Reuses `.bubble-menu` styling for visual consistency.
 */
export class ReadModeSelectionBubble {
    /** @type {Element} */
    #container;

    /** @type {HTMLElement} */
    #bubble;

    /** @type {() => void} */
    #onSelectionChange = () => this.#update();

    /**
     * @param {Element} container - The KB article content container
     *  (`[data-glpi-kb-content]`).
     */
    constructor(container) {
        this.#container = container;
        this.#bubble = this.#createBubble();
        document.addEventListener('selectionchange', this.#onSelectionChange);
    }

    #createBubble() {
        const bubble = document.createElement('div');
        bubble.className = 'bubble-menu kb-read-mode-comment-bubble';
        bubble.style.position = 'absolute';
        bubble.style.width = 'max-content';
        bubble.style.display = 'none';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'bubble-menu-btn';

        const label = document.createElement('span');
        label.textContent = _x('button', 'Comment');
        button.appendChild(label);

        const icon = document.createElement('i');
        icon.className = 'ti ti-message-circle-plus';
        // Decorative: the label already names the button.
        icon.setAttribute('aria-hidden', 'true');
        button.appendChild(icon);

        button.addEventListener('mousedown', (e) => {
            // Prevent the browser from clearing the selection before click fires.
            e.preventDefault();
        });
        button.addEventListener('click', () => this.#dispatchCommentSelection());

        bubble.appendChild(button);
        return bubble;
    }

    /**
     * True if a live, editable Tiptap instance owns selection instead of this module.
     * @returns {boolean}
     */
    #isEditing() {
        return this.#container.querySelector('.ProseMirror[contenteditable="true"]') !== null;
    }

    #update() {
        if (this.#isEditing()) {
            this.#hide();
            return;
        }

        const selection = document.getSelection();
        if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
            this.#hide();
            return;
        }

        const range = selection.getRangeAt(0);
        if (!this.#container.contains(range.commonAncestorContainer)) {
            this.#hide();
            return;
        }

        this.#show(range);
    }

    #show(range) {
        if (!this.#bubble.isConnected) {
            document.body.appendChild(this.#bubble);
        }

        const rect = range.getBoundingClientRect();
        this.#bubble.style.display = '';

        // Centered on the selection's bounding box, clamped to the viewport.
        const max_left = document.documentElement.clientWidth - this.#bubble.offsetWidth;
        const left = Math.min(Math.max(0, rect.left + (rect.width - this.#bubble.offsetWidth) / 2), max_left);

        this.#bubble.style.top = `${window.scrollY + rect.top - this.#bubble.offsetHeight - 8}px`;
        this.#bubble.style.left = `${window.scrollX + left}px`;
    }

    #hide() {
        this.#bubble.style.display = 'none';
    }

    #dispatchCommentSelection() {
        const selection = document.getSelection();
        if (!selection || selection.isCollapsed || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);
        const index = buildDomTextIndex(this.#container);
        const offsets = domRangeToOffsets(index, range);
        if (!offsets) {
            return;
        }

        const [start, end] = offsets;
        const anchor = extractAnchor(index.text, start, end);

        this.#hide();
        this.#container.dispatchEvent(new CustomEvent('glpi:kb:comment-selection', {
            bubbles: true,
            detail: { anchor },
        }));
    }
}
