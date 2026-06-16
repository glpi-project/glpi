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

import {nextTick, onMounted, ref} from 'vue';

export function useEntitySelector(container_el)
{
    const loading = ref(false);
    const tree_data = ref([]);

    onMounted(() => {
        container_el.value.addEventListener('shown.bs.dropdown', () => {
            container_el.value.querySelector('input[name="entsearchtext"]').focus();
        });
        // Add key listeners for navigating the tree with the keyboard
        container_el.value.addEventListener('keyup', (event) => {
            const list_item = event.target.closest('li');
            if (!list_item) {
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                // Focus the next visible list item
                const next = list_item.nextElementSibling;
                if (next) {
                    next.focus();
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                // Focus the previous visible list item
                const prev = list_item.previousElementSibling;
                if (prev) {
                    prev.focus();
                }
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                if (list_item.dataset.hasChildren === 'true' && list_item.ariaExpanded === 'false') {
                    list_item.querySelector('.collapse-item').click();
                    // Need to wait for DOM changes since child items are not in the DOM until the parent is expanded
                    nextTick().then(() => {
                        const next = list_item.nextElementSibling;
                        if (next && parseInt(next.dataset.nodeLevel) > parseInt(list_item.dataset.nodeLevel)) {
                            next.focus();
                        }
                    });
                }
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                if (list_item.dataset.hasChildren === 'true' && list_item.ariaExpanded === 'true') {
                    list_item.querySelector('.collapse-item').click();
                } else {
                    // Focus the parent list item
                    const level = parseInt(list_item.dataset.nodeLevel);
                    if (level > 0) {
                        let prev = list_item.previousElementSibling;
                        while (prev && parseInt(prev.dataset.nodeLevel) >= level) {
                            prev = prev.previousElementSibling;
                        }
                        if (prev) {
                            prev.focus();
                        }
                    }
                }
            } else if (event.key === 'Enter') {
                const select_children = event.metaKey || event.ctrlKey; // Allow selecting an entity and all its children by holding Ctrl or Cmd
                event.preventDefault();
                event.stopPropagation();
                changeEntity(list_item.dataset.key, select_children);
            }
        });
    });

    function loadTreeData() {
        loading.value = true;
        return fetch(`${window.CFG_GLPI.root_doc}/ajax/entitytreesons.php`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        }).then(response => {
            response.json().then(data => {
                let universal_order_i = 0;
                function preprocess(data, level = 0, parents = []) {
                    data.forEach((item) => {
                        item.level = level;
                        // Save array of parent objects (will be references rather than copies)
                        item.parents = parents;
                        item.universal_order = universal_order_i++;
                        if (item.children.length) {
                            preprocess(item.children, level + 1, [...parents, item]);
                        }
                        if (item.children.length && item.expanded === undefined) {
                            item.expanded = false;
                        }
                    });
                }
                preprocess(data);
                tree_data.value = data;
                loading.value = false;
            });
        });
    }

    /**
     * Change entity to "Full structure" which means access to all of the user's entities.
     */
    function changeFullStructure() {
        return fetch(`${window.CFG_GLPI.root_doc}/Session/ChangeEntity`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                full_structure: 'true',
            }),
        });
    }

    function changeEntity(entity_id, is_recursive) {
        return fetch(`${window.CFG_GLPI.root_doc}/Session/ChangeEntity`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                id: entity_id,
                is_recursive: is_recursive,
            }),
        }).then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                window.glpi_toast_error(__('An error occurred while changing the entity. Please try again.'));
            }
        });
    }

    return {
        loadTreeData,
        loading,
        tree_data,
        changeFullStructure,
        changeEntity
    };
}
