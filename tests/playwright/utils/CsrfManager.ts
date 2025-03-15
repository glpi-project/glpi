/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import { APIRequestContext } from 'playwright/test';
import { JSDOM } from 'jsdom';

export class CsrfManager
{
    private request: APIRequestContext;

    public constructor(request: APIRequestContext) {
        this.request = request;
    }

    /**
     * @param url Url from which the CSRF token will be extracted.
     * Defaults to "/front/preference.php" which is a lightweight page that
     * is accessible to all logged in users.
     */
    public async getToken(url: string = "/front/preference.php")
    {
        // TODO: try to reuse a single csrf token per worker if it is possible.
        const response = await this.request.get(url);
        return this.extractToken(await response.text());
    }

    public extractToken(body: string)
    {
        const dom = new JSDOM(body);
        const input = dom.window.document.querySelector(
            'input[name="_glpi_csrf_token"]'
        ) as HTMLInputElement;
        return input.value;
    }
}
