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

import { expect, test } from '../../../fixtures/glpi_fixture';
import { Profiles } from '../../../utils/Profiles';

// Values of the `maintenance_mode` configuration.
const MAINTENANCE_ENABLED = '1';
const MAINTENANCE_DISABLED = '0'; // Value set by the installer.

const MAINTENANCE_MESSAGE = 'Temporarily down for maintenance';

test.describe('Maintenance mode', () => {
    // The maintenance mode is global to the whole application, thus it must be
    // disabled again whatever happened during the test.
    test.afterEach(async ({ request, profile, general_config }) => {
        // When the maintenance mode is active, GLPI answers the maintenance
        // page to every request of a session that has no backdoor flag. The
        // requests below would then do nothing at all.
        // This query parameter puts the flag back into the session, see
        // `CheckMaintenanceListener`.
        await request.get('/?skipMaintenance=1');

        await profile.set(Profiles.SuperAdmin);
        await general_config.set({
            'maintenance_mode': MAINTENANCE_DISABLED,
        });
    });

    test('GLPI is not accessible during maintenance', async ({
        page,
        anonymousPage,
        profile,
        general_config,
    }) => {
        await profile.set(Profiles.SuperAdmin);

        // A user that has no session gets the login page.
        await anonymousPage.goto('/');
        await expect(
            anonymousPage.getByText(MAINTENANCE_MESSAGE)
        ).toBeHidden();

        await general_config.set({
            'maintenance_mode': MAINTENANCE_ENABLED,
        });

        // The same user now gets the maintenance page.
        await anonymousPage.goto('/');
        await expect(
            anonymousPage.getByText(MAINTENANCE_MESSAGE)
        ).toBeVisible();

        // The session that enabled the maintenance mode keeps its access,
        // because `Config::prepareInputForUpdate()` gives it the backdoor flag.
        // This is also what allows the `afterEach` hook above to disable the
        // maintenance mode again, thus it must not break.
        await page.goto('/front/config.form.php');
        await expect(page.getByText(MAINTENANCE_MESSAGE)).toBeHidden();
    });
});
