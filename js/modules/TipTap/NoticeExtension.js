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

/* global TiptapCore, TiptapPMState */

const { Node, mergeAttributes } = TiptapCore;
const { TextSelection } = TiptapPMState;

export const NOTICE_TYPES = {
    info: { label: __('Info notice'), icon: 'ti-info-circle-filled' },
    success: { label: __('Success notice'), icon: 'ti-circle-check-filled' },
    warning: { label: __('Warning notice'), icon: 'ti-alert-circle-filled' },
    tip: { label: __('Tip notice'), icon: 'ti-star-filled' },
};

const DEFAULT_TYPE = 'info';

function parseType(element) {
    const type = Object.keys(NOTICE_TYPES).find((key) => element.classList.contains(`kb-notice-${key}`));
    return type ?? DEFAULT_TYPE;
}

/**
 * @param {string} type
 * @returns {string} Classes of the icon, also used by the slash menu
 */
export function getNoticeIconClass(type) {
    return `ti ${NOTICE_TYPES[type].icon} kb-notice-icon kb-notice-icon-${type}`;
}

/**
 * Colored notice block, stored as `<div class="kb-notice kb-notice-{type}">`.
 * The icon and the label are added at render time (node view, RichText\NoticeRenderer).
 */
export const Notice = Node.create({
    name: 'notice',
    group: 'block',
    content: '(paragraph|heading|bulletList|orderedList|codeBlock|image)+',
    defining: true,

    addAttributes() {
        return {
            type: {
                default: DEFAULT_TYPE,
                parseHTML: parseType,
                // The class is rendered by renderHTML().
                renderHTML: () => ({}),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'div.kb-notice' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, { class: `kb-notice kb-notice-${node.attrs.type}` }), 0];
    },

    addNodeView() {
        return ({ node }) => {
            const type = node.attrs.type;

            const dom = document.createElement('div');
            dom.className = `kb-notice kb-notice-${type}`;
            dom.setAttribute('role', 'note');
            dom.setAttribute('aria-label', NOTICE_TYPES[type].label);

            const icon = document.createElement('i');
            icon.className = getNoticeIconClass(type);
            icon.setAttribute('aria-hidden', 'true');
            icon.contentEditable = 'false';
            dom.appendChild(icon);

            const content = document.createElement('div');
            content.className = 'kb-notice-content';
            dom.appendChild(content);

            return {
                dom,
                contentDOM: content,
                update: (updated) => updated.type.name === this.name && updated.attrs.type === type,
            };
        };
    },

    addCommands() {
        return {
            setNotice: (type) => ({ commands }) => {
                if (!Object.hasOwn(NOTICE_TYPES, type)) {
                    return false;
                }
                return commands.insertContent({
                    type: this.name,
                    attrs: { type },
                    content: [{ type: 'paragraph' }],
                });
            },
        };
    },

    addKeyboardShortcuts() {
        return {
            // Remove an empty notice, instead of joining it with the block above.
            Backspace: () => this.editor.commands.command(({ state, tr, dispatch }) => {
                const { $from, empty } = state.selection;
                if (!empty || $from.depth < 2) {
                    return false;
                }
                const notice = $from.node(-1);
                if (notice.type.name !== this.name || notice.childCount !== 1 || $from.parent.content.size !== 0) {
                    return false;
                }
                if (dispatch) {
                    const pos = $from.before(-1);
                    tr.replaceWith(pos, $from.after(-1), state.schema.nodes.paragraph.create());
                    tr.setSelection(TextSelection.create(tr.doc, pos + 1));
                }
                return true;
            }),
        };
    },
});
