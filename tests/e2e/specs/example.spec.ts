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

import { test, expect } from '../fixtures/glpi_fixture';

test('has title', async ({ page }) => {
    await page.goto('https://playwright.dev/');

    // Expect a title "to contain" a substring.
    await expect(page).toHaveTitle(/Playwright/);
});

test('get started link', async ({ page }) => {
    await page.goto('https://playwright.dev/');

    // Click the get started link.
    await page.getByRole('link', { name: 'Get started' }).click();

    // Expects page to have a heading with the name of Installation.
    await expect(page.getByRole('heading', { name: 'Installation' })).toBeVisible();
});

test('anonymous GLPI page', async ({anonymousPage}) => {
    await anonymousPage.goto('');
    await expect(anonymousPage).toHaveTitle("Authentication - GLPI");
});

test('logged in GLPI page', async ({page}) => {
    await page.goto('');
    await expect(page).toHaveTitle("Standard interface - GLPI");
});

test('logged and anonymous page in the same test', async ({page, anonymousPage}) => {
    // This can be useful if you need to change some settings as an admin, then
    // validate with an anonymous user that they are applied
    await page.goto('');
    await expect(page).toHaveTitle("Standard interface - GLPI");

    await anonymousPage.goto('');
    await expect(anonymousPage).toHaveTitle("Authentication - GLPI");
});
