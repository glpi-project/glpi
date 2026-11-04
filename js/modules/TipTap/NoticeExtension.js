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
const { Plugin, PluginKey, TextSelection } = TiptapPMState;

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
 * Tells if the slice brings in a block that the notice content does not accept.
 * Nodes on the open edges of the slice already exist in the document, so they are skipped.
 */
function sliceHasForbiddenBlock(fragment, open_start, open_end, is_forbidden) {
    let found = false;
    fragment.forEach((child, _offset, index) => {
        if (found) {
            return;
        }
        const child_open_start = index === 0 ? open_start : 0;
        const child_open_end = index === fragment.childCount - 1 ? open_end : 0;
        if (child_open_start > 0 || child_open_end > 0) {
            found = sliceHasForbiddenBlock(
                child.content,
                Math.max(child_open_start - 1, 0),
                Math.max(child_open_end - 1, 0),
                is_forbidden
            );
            return;
        }
        found = is_forbidden(child.type);
        child.descendants((node) => {
            found ||= is_forbidden(node.type);
            return !found;
        });
    });
    return found;
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

    addProseMirrorPlugins() {
        const notice_type = this.type;
        // Without this guard, ProseMirror splits the notice around the block (`---`, paste, drop).
        const is_forbidden = (type) => type.isInGroup('block') && notice_type.contentMatch.matchType(type) === null;
        const is_in_notice = ($pos) => {
            for (let depth = $pos.depth; depth > 0; depth--) {
                if ($pos.node(depth).type === notice_type) {
                    return true;
                }
            }
            return false;
        };

        return [
            new Plugin({
                key: new PluginKey('noticeContentGuard'),
                filterTransaction: (tr, state) => {
                    // Undo and redo restore a state that was already valid.
                    if (!tr.docChanged || tr.getMeta('history$')) {
                        return true;
                    }
                    const rejected = tr.steps.some((step, index) => step.slice
                        && step.slice.size > 0
                        && is_in_notice(tr.docs[index].resolve(step.from))
                        && sliceHasForbiddenBlock(step.slice.content, step.slice.openStart, step.slice.openEnd, is_forbidden));
                    if (!rejected) {
                        return true;
                    }

                    // An input rule consumes the typed character: type it again, as plain text.
                    const input = state.plugins
                        .filter((plugin) => plugin.spec.isInputRules)
                        .map((plugin) => tr.getMeta(plugin))
                        .find((meta) => meta?.text !== undefined);
                    if (input) {
                        setTimeout(() => {
                            const { view } = this.editor;
                            view.dispatch(view.state.tr.insertText(input.text, input.from, input.to));
                        });
                    }
                    return false;
                },
            }),
        ];
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
