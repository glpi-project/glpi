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

import type { Page } from '@playwright/test';
import { expect, test } from '../../../fixtures/glpi_fixture';
import { GlpiPage } from '../../../pages/GlpiPage';
import { Profiles } from '../../../utils/Profiles';

// Value set by the installer, see `install/empty_data.php`.
const DEFAULT_DEVICES_IN_MENU = ['Item_DeviceSimcard'];

const SIMCARD_ITEMS = 'Simcard items';
const CASE_ITEMS = 'Case items';

/**
 * Open the "Assets" menu of the sidebar and check which device entries it
 * contains.
 *
 * The device entries only exist under this menu, thus looking for them in the
 * whole sidebar is enough to identify them.
 */
async function expectDevicesInMenu(
    page: Page,
    expected: string[],
    not_expected: string[],
): Promise<void> {
    const glpi_page = new GlpiPage(page);
    const sidebar = page.getByTestId('sidebar');

    await glpi_page.sidebar_menu_toggles
        .filter({ visible: true })
        .filter({ hasText: 'Assets' })
        .click()
    ;

    for (const name of expected) {
        await expect(
            sidebar.getByRole('link', { name: name, exact: true })
        ).toBeVisible();
    }
    for (const name of not_expected) {
        await expect(
            sidebar.getByRole('link', { name: name, exact: true })
        ).toBeHidden();
    }
}

test.describe('Assets configuration', () => {
    // The general configuration is global to the whole application, thus it
    // must be restored whatever happened during the test.
    // Doing it here instead of at the end of the test makes sure it is also
    // done when the test fails in the middle, which would otherwise leave a
    // dirty value for the retries and for the other tests.
    test.afterEach(async ({ profile, general_config }) => {
        await profile.set(Profiles.SuperAdmin);
        await general_config.set({
            'devices_in_menu': DEFAULT_DEVICES_IN_MENU,
        });
    });

    test('Devices displayed in menu', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const goToAssetsConfiguration = async () => {
            await page.goto('/front/config.form.php');
            await glpi_page.doGoToTab('Assets');
            return page.getByRole('tabpanel');
        };

        let tab_panel = await goToAssetsConfiguration();
        let devices_dropdown = glpi_page.getDropdownByLabel(
            'Devices displayed in menu',
            tab_panel,
        );

        // The installer only puts the simcard items in the menu.
        await expect(devices_dropdown).toContainText(SIMCARD_ITEMS);
        await expect(devices_dropdown).not.toContainText(CASE_ITEMS);
        await expectDevicesInMenu(page, [SIMCARD_ITEMS], [CASE_ITEMS]);

        // Add a second device: the menu must show both of them.
        await glpi_page.doSetDropdownValue(devices_dropdown, CASE_ITEMS);
        await tab_panel.getByRole('button', { name: 'Save' }).click();
        await expectDevicesInMenu(page, [SIMCARD_ITEMS, CASE_ITEMS], []);

        // Remove the first device: the menu must only show the second one.
        tab_panel = await goToAssetsConfiguration();
        devices_dropdown = glpi_page.getDropdownByLabel(
            'Devices displayed in menu',
            tab_panel,
        );
        await glpi_page.doClearDropdownValue(devices_dropdown, SIMCARD_ITEMS);
        await tab_panel.getByRole('button', { name: 'Save' }).click();
        await expectDevicesInMenu(page, [CASE_ITEMS], [SIMCARD_ITEMS]);

        // Remove the last device: the menu must show no device at all.
        tab_panel = await goToAssetsConfiguration();
        devices_dropdown = glpi_page.getDropdownByLabel(
            'Devices displayed in menu',
            tab_panel,
        );
        await glpi_page.doClearDropdownValue(devices_dropdown, CASE_ITEMS);
        await tab_panel.getByRole('button', { name: 'Save' }).click();
        await expectDevicesInMenu(page, [], [SIMCARD_ITEMS, CASE_ITEMS]);
    });
});
