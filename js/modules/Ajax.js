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

/* global getAjaxCsrfToken, glpi_toast_error */

/**
 * Perform a POST request to a GLPI endpoint.
 *
 * @param {string} url - The relative URL path (without root_doc prefix).
 * @param {Object} values - The data to send as JSON in the request body.
 * @returns {Promise<Response>} The fetch Response object.
 * @throws {Error} If the request fails or returns a non-ok status.
 */
export async function post(url, values)
{
    try {
        const response = await fetch(
            `${CFG_GLPI.root_doc}/${url}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                },
                body: JSON.stringify(values),
            }
        );

        if (!response.ok) {
            throw new Error("POST request failed");
        }

        return response;
    } catch (e) {
        glpi_toast_error(__("An unexpected error occurred."));
        console.error(e);
        throw e;
    }
}
