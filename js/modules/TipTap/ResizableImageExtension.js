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
 * Image whose resizable node view also follows size changes that do not come
 * from a drag (image dialog, undo, redo): TipTap applies them only on creation.
 */
export const ResizableImage = TiptapImage.extend({
    addNodeView() {
        const createNodeView = this.parent?.();
        if (!createNodeView) {
            return null;
        }

        return (props) => {
            const node_view = createNodeView(props);
            const update = node_view.update.bind(node_view);

            node_view.update = (node, decorations, inner_decorations) => {
                if (!update(node, decorations, inner_decorations)) {
                    return false;
                }
                const { width, height } = node.attrs;
                node_view.element.style.width = width ? `${width}px` : '';
                node_view.element.style.height = height ? `${height}px` : '';
                return true;
            };

            return node_view;
        };
    },
});
