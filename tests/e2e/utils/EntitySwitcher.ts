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
import { getWorkerEntityId } from './WorkerEntities';

export class EntitySwitcher
{
    /**
     * Allow us to execute HTTP requests with the current worker cookies
     */
    private request: APIRequestContext;

    private crsf: CsrfFetcher;

    public constructor(
        request: APIRequestContext,
        crsf: CsrfFetcher,
    ) {
        this.request = request;
        this.crsf = crsf;
    }

    public async resetToDefaultWorkerEntity(): Promise<void>
    {
        await this.setById(getWorkerEntityId(), true);
    }

    public async switchToWithRecursion(entitiy_id: number): Promise<void>
    {
        await this.setById(entitiy_id, true);
    }

    public async switchToWithoutRecursion(entitiy_id: number): Promise<void>
    {
        await this.setById(entitiy_id, false);
    }

    private async setById(
        entity_id: number,
        is_recursive: boolean
    ): Promise<void> {
        // Send http request to change profile.
        await this.request.post(
            `/Session/ChangeEntity`,
            {
                form: {
                    id: entity_id,
                    is_recursive: is_recursive,
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Glpi-Csrf-Token': await this.crsf.get(),
                }
            }
        );
    }
}
