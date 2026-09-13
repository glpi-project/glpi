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
import { GlpiPage } from '../../../pages/GlpiPage';
import { Profiles } from '../../../utils/Profiles';

test.describe('Display preference list', () => {
    test('Filter the displaypreference list', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        await page.goto('/front/config.form.php');
        await glpi_page.doGoToTab('Search result display');

        // eslint-disable-next-line playwright/no-raw-locators
        const search_input = page.locator('#search-itemtype');

        // eslint-disable-next-line playwright/no-raw-locators
        const user_row = page.locator('[data-itemtype="User"]');
        // eslint-disable-next-line playwright/no-raw-locators
        const computer_row = page.locator('[data-itemtype="Computer"]');

        await search_input.fill('user');

        await expect(user_row).toBeVisible();
        await expect(computer_row).toBeHidden();

        await search_input.fill('computer');

        await expect(user_row).toBeHidden();
        await expect(computer_row).toBeVisible();
    });
});
