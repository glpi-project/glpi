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
import { CsrfExtractor } from './CsrfExtractor';

const CONFIG_FORM_URL = '/front/config.form.php';

/**
 * Service used to update GLPI's general configuration ("Setup > General").
 *
 * Warning: this configuration is global to the whole application, it is not
 * isolated by the worker entity. Only isolated tests (`*.spec.isolated.ts`)
 * may use this service, see `playwright.isolated.config.ts`.
 */
export class GeneralConfig
{
    /**
     * Allow us to execute HTTP requests with the current worker cookies
     */
    private request: APIRequestContext;

    public constructor(request: APIRequestContext)
    {
        this.request = request;
    }

    /**
     * Update the given general configuration fields.
     *
     * The values are sent to the endpoint used by the configuration form, thus
     * GLPI executes the exact same post update actions. This matters for the
     * values that are cached in the session: `devices_in_menu` for example
     * needs the menu of the current session to be regenerated.
     */
    public async set(fields: Record<string, string | string[]>): Promise<void>
    {
        const body = new URLSearchParams();
        body.append('_glpi_csrf_token', await this.getCsrfToken());
        body.append('update', '1'); // Name and value of the "Save" button

        for (const [name, value] of Object.entries(fields)) {
            if (Array.isArray(value)) {
                // A multiple `<select>` sends nothing when nothing is selected,
                // thus `Dropdown::showFromArray()` renders an extra empty
                // hidden input with the same name. It must be sent too,
                // otherwise GLPI would keep the previous value instead of
                // clearing it.
                body.append(name, '');
                for (const item of value) {
                    body.append(`${name}[]`, item);
                }
            } else {
                body.append(name, value);
            }
        }

        const response = await this.request.post(CONFIG_FORM_URL, {
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            data: body.toString(),
        });

        if (!response.ok()) {
            throw new Error(
                `Failed to update the general configuration: HTTP ${response.status()}`
            );
        }
    }

    /**
     * The configuration form is not an AJAX endpoint, thus GLPI destroys the
     * token once it has been validated (see `CheckCsrfListener`: only the AJAX
     * branch uses `preserve_token`).
     *
     * The shared token of the `CsrfFetcher` service can't be used here: it
     * would be destroyed and the next request of this worker would then be
     * rejected. A new token is read from the form page instead, which is what a
     * browser does.
     */
    private async getCsrfToken(): Promise<string>
    {
        const response = await this.request.get(CONFIG_FORM_URL);
        if (!response.ok()) {
            throw new Error(
                `Failed to load the general configuration form: HTTP ${response.status()}`
            );
        }

        return new CsrfExtractor().extractToken(await response.text());
    }
}
