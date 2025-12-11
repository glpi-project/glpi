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

import { CsrfFetcher } from './CsrfFetcher';
import { APIRequestContext } from 'playwright/test';
import { WorkerSessionCache } from './WorkerSessionCache';
import { Profiles } from './Profiles';

export class ProfileSwitcher
{
    /**
     * Allow us to execute HTTP requests with the current worker cookies
     */
    private request: APIRequestContext;

    private cache: WorkerSessionCache;

    private crsf: CsrfFetcher;

    public constructor(
        request: APIRequestContext,
        crsf: CsrfFetcher,
        cache: WorkerSessionCache,
    ) {
        this.request = request;
        this.crsf = crsf;
        this.cache = cache;
    }

    /**
     * Use one of the default profiles found in the `Profiles` enum.
     */
    public async set(profile_name: Profiles)
    {
        const profile_id = profile_name.valueOf();
        return this.setById(profile_id);
    }

    /**
     * Use a specific profile. Only call this method instead of `set` if you are
     * trying to use a non default profile (for example a new profile that you
     * just created for a given test).
     */
    public async setById(profile_id: number)
    {
        // Profile is already loaded, do nothing.
        if (this.cache.getCurrentProfileId() === profile_id) {
            return;
        }

        // Send http request to change profile.
        await this.request.post(
            `/Session/ChangeProfile`,
            {
                form: {
                    id: profile_id,
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': await this.crsf.get(),
                }
            }
        );
        this.cache.setCurrentProfileId(profile_id);
    }
}
