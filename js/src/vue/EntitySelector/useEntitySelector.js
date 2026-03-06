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

import { ref } from 'vue';

export function useEntitySelector(props)
{
    const loading = ref(false);
    const tree_data = ref([]);

    function loadTreeData()
    {
        loading.value = true;
        return fetch(`${window.CFG_GLPI.root_doc}/ajax/entitytreesons.php`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': window.getAjaxCsrfToken(),
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
    function changeFullStructure()
    {
        return fetch(`${window.CFG_GLPI.root_doc}/Session/ChangeEntity`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': props.csrf_token,
            },
            body: new URLSearchParams({
                full_structure: 'true',
                _glpi_csrf_token: props.csrf_token,
            }),
        });
    }

    function changeEntity(entity_id, is_recursive)
    {
        return fetch(`${window.CFG_GLPI.root_doc}/Session/ChangeEntity`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': props.csrf_token,
            },
            body: new URLSearchParams({
                id: entity_id,
                is_recursive: is_recursive,
                _glpi_csrf_token: props.csrf_token,
            }),
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
