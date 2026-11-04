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

/* global TiptapImage */

/**
 * Resizable image that also follows non-drag size changes (dialog, undo, redo).
 * `onLiveResize` is called during a drag, at most once per frame.
 */
export const ResizableImage = TiptapImage.extend({
    addOptions() {
        return {
            ...this.parent?.(),
            onLiveResize: null,
        };
    },

    addNodeView() {
        const createNodeView = this.parent?.();
        if (!createNodeView) {
            return null;
        }

        const on_live_resize = this.options.onLiveResize;

        return (props) => {
            const node_view = createNodeView(props);

            const apply_node_size = () => {
                const { width, height } = node_view.node.attrs;
                node_view.element.style.width = width ? `${width}px` : '';
                node_view.element.style.height = height ? `${height}px` : '';
            };

            const update = node_view.update.bind(node_view);
            node_view.update = (node, decorations, inner_decorations) => {
                if (!update(node, decorations, inner_decorations)) {
                    return false;
                }
                // Mid-drag, the node still has the old size: keep the dragged one.
                if (node_view.dom.dataset.resizeState !== 'true') {
                    apply_node_size();
                }
                return true;
            };

            // TipTap ends a drag on mouseup only, so a touch drag would never commit.
            // A mouseup event, and not TipTap's private handler, keeps this working across TipTap updates.
            const end_drag = () => document.dispatchEvent(new MouseEvent('mouseup'));
            let drag_touch_id = null;
            const end_touch_drag = (event) => {
                // Another finger lifted: the drag goes on.
                if (![...event.changedTouches].some((touch) => touch.identifier === drag_touch_id)) {
                    return;
                }
                drag_touch_id = null;
                document.removeEventListener('touchend', end_touch_drag);
                document.removeEventListener('touchcancel', end_touch_drag);

                if (event.type === 'touchend') {
                    end_drag();
                    return;
                }
                // A cancelled drag ends without commit and goes back to the stored size.
                const on_commit = node_view.onCommit;
                node_view.onCommit = () => {};
                end_drag();
                node_view.onCommit = on_commit;
                apply_node_size();
                if (typeof on_live_resize === 'function') {
                    on_live_resize();
                }
            };
            // Capture phase: the handle stops the propagation of its touchstart.
            node_view.dom.addEventListener('touchstart', (event) => {
                if (drag_touch_id !== null || !event.target.closest?.('[data-resize-handle]')) {
                    return;
                }
                drag_touch_id = event.changedTouches[0].identifier;
                document.addEventListener('touchend', end_touch_drag);
                document.addEventListener('touchcancel', end_touch_drag);
            }, { capture: true });

            if (typeof on_live_resize === 'function') {
                const on_resize = node_view.onResize;
                let frame = null;
                node_view.onResize = (width, height) => {
                    on_resize?.(width, height);
                    // Pointer moves come faster than frames: one call per frame is enough.
                    frame ??= requestAnimationFrame(() => {
                        frame = null;
                        on_live_resize();
                    });
                };
                const destroy = node_view.destroy.bind(node_view);
                node_view.destroy = () => {
                    cancelAnimationFrame(frame);
                    destroy();
                };
            }

            return node_view;
        };
    },
});
