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

import { post } from "/js/modules/Ajax.js";

/**
 * Mirror of the PHP Glpi\Knowbase\EditorActionType enum. Shared between the
 * article editor kebab menu and the knowledge base aside tree.
 */
export const EditorActionType = Object.freeze({
    LOAD_SIDE_PANEL: 'LOAD_SIDE_PANEL',
    TOGGLE_VALUE:    'TOGGLE_VALUE',
    TOGGLE_FAVORITE: 'TOGGLE_FAVORITE',
    DELETE_ARTICLE:  'DELETE_ARTICLE',
    OPEN_MODAL:      'OPEN_MODAL',
});

/**
 * Extract `data-glpi-kb-action-param-*` values from a dataset into a plain
 * object keyed by the (lower-cased) param name.
 *
 * @param {DOMStringMap} dataset
 * @returns {Object<string, string>}
 */
export function extractParamsFromDataset(dataset)
{
    const params = {};
    const prefix = 'glpiKbActionParam';

    for (const [key, value] of Object.entries(dataset)) {
        if (key.startsWith(prefix)) {
            const param_name = key.slice(prefix.length).toLowerCase();
            params[param_name] = value;
        }
    }

    return params;
}

/**
 * Add or remove an article from the current user's favorites.
 *
 * @param {number} id
 * @param {boolean} value
 * @returns {Promise<Response>}
 */
export function toggleFavorite(id, value)
{
    return post(`Knowbase/${id}/ToggleFavorite`, { value: value });
}

/**
 * Toggle a boolean field (e.g. `is_faq`) on an article.
 *
 * @param {number} id
 * @param {string} field
 * @param {boolean} value
 * @returns {Promise<Response>}
 */
export function toggleField(id, field, value)
{
    return post(`Knowbase/${id}/ToggleField`, { field: field, value: value });
}

/**
 * Delete an article.
 *
 * @param {number} id
 * @returns {Promise<Response>}
 */
export function deleteArticle(id)
{
    return post(`Knowbase/KnowbaseItem/${id}/Delete`, {});
}
