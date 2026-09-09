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
import { expect, test } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';

test.describe("Debug Mode", () => {
    test.afterEach(async ({ debug }) => {
        await debug.disable();
    });

    test('No debug mode for non super-admin', async ({ page, profile }) => {
        await profile.set(Profiles.Admin);
        await page.goto('/front/computer.form.php');

        /* eslint-disable playwright/no-raw-locators */
        await expect(page.locator('#debug-toolbar-applet')).not.toBeAttached();
        await page.locator('header a.user-menu-dropdown-toggle').click();
        await expect(page.locator('.dropdown-item[title="Change mode"]'))
            .not.toBeAttached()
        ;
        /* eslint-enable playwright/no-raw-locators */
    });

    test('Debug mode for super-admin', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        await page.goto('/front/computer.form.php');

        /* eslint-disable playwright/no-raw-locators */
        await expect(page.locator('#debug-toolbar-applet')).not.toBeAttached();
        await page.locator('header a.user-menu-dropdown-toggle').click();
        // `.first()`: the user menu is rendered twice, only one being shown
        // depending on the screen size.
        const change_mode = page
            .locator('.dropdown-item[title="Change mode"]')
            .first()
        ;
        /* eslint-enable playwright/no-raw-locators */

        await expect(change_mode).toBeAttached();
        await expect(change_mode).toHaveAttribute(
            'href',
            /\/ajax\/switchdebug\.php/
        );
    });
});
