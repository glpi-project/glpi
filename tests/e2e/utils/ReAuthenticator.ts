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

import { APIRequestContext } from 'playwright/test';

/**
 * Controls the reauth ("sudo" mode) validity of the current session through
 * test-only endpoints (see tests/src/Controller/Security/ReAuthTestController).
 *
 * Since the "no reauth granted on login" change, a freshly logged-in user is
 * not reauthenticated, so any page requiring reauth redirects to the prompt.
 */
export class ReAuthenticator
{
    /**
     * Allow us to execute HTTP requests with the current worker cookies.
     */
    private request: APIRequestContext;

    public constructor(request: APIRequestContext)
    {
        this.request = request;
    }

    /**
     * Mark the current session as reauthenticated.
     */
    public async grant(): Promise<void>
    {
        const response = await this.request.post('/test/reauth/grant', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok()) {
            throw new Error(`Failed to grant reauth: HTTP ${response.status()}`);
        }
    }

    /**
     * Drop the reauth validity of the current session, so the next action
     * requiring reauth redirects to the prompt.
     */
    public async revoke(): Promise<void>
    {
        const response = await this.request.post('/test/reauth/revoke', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok()) {
            throw new Error(`Failed to revoke reauth: HTTP ${response.status()}`);
        }
    }
}
