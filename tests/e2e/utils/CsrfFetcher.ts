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
import { CsrfExtractor } from './CsrfExtractor';
import { WorkerSessionCache } from './WorkerSessionCache';

export class CsrfFetcher
{
    /**
     * Allow us to execute HTTP requests with the current worker cookies
     */
    private request: APIRequestContext;

    private cache: WorkerSessionCache;

    public constructor(request: APIRequestContext, cache: WorkerSessionCache)
    {
        this.request = request;
        this.cache = cache;
    }

    public async get(): Promise<string>
    {
        if (this.cache.getCsrfToken() !== null) {
            return this.cache.getCsrfToken();
        }

        const response = await this.request.get("/front/preference.php");
        const extractor = new CsrfExtractor();
        const token = extractor.extractToken(await response.text());
        this.cache.setCsrfToken(token);

        return token;
    }
}
