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

import { CsrfManager } from './CsrfManager';
import { APIRequestContext } from 'playwright/test';

export class SessionManager
{
    private request: APIRequestContext;
    private token: string|null = null;

    public constructor(request: APIRequestContext) {
        this.request = request;
    }

    public async changeProfile(profile: string)
    {
        const Csrf = new CsrfManager(this.request);

        if (this.token === null) {
            this.token = await Csrf.getToken();
        }

        // TODO: add some caching mechanism to avoid loading a profile that is
        // already being used.
        const profiles = new Map([
            ['Self-Service', 1],
            ['Observer',     2],
            ['Admin',        3],
            ['Super-Admin',  4],
            ['Hotliner',     5],
            ['Technician',   6],
            ['Supervisor',   7],
            ['Read-Only',    8],
        ]);
        const profile_id = profiles.get(profile);
        if (profile_id === undefined) {
            throw new Error(`Unknown profile: ${profile}`);
        }

        await this.request.post(
            `/Session/ChangeProfile`,
            {
                form: {
                    id: profile_id,
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': this.token,
                }
            }
        );
    }
}
