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

import { test as test_unauthenticated, expect } from "playwright/test";
import { test as test_authenticated } from '../../fixtures/authenticated';
import { LoginPage } from "../../pages/LoginPage";
import { GlpiPage } from "../../pages/GlpiPage";
import { SessionManager } from "../../utils/SessionManager";

// This rule does not work correctly when using alias for the `test()` method.
/* eslint-disable playwright/no-standalone-expect */

test_unauthenticated.describe('tests without sessions', () => {
    // Reset storage state for this 'describe' block to avoid being authenticated
    test_unauthenticated.use({ storageState: { cookies: [], origins: [] } });

    test_unauthenticated('can login/logout with correct credentials', async ({ page }) => {
        const login_page = new LoginPage(page);

        // Login
        await login_page.goto();
        await expect(page).toHaveTitle('Authentication - GLPI');
        await login_page.login('glpi', 'glpi');
        await expect(page).toHaveTitle('Standard interface - GLPI');

        // Logout
        const glpi_page = new GlpiPage(page);
        await glpi_page.logout();
        await expect(page).toHaveTitle('Authentication - GLPI');
    });

    test_unauthenticated('redirect to a requested page after login', async ({ page }) => {
        // Should have a link to be redirected to the login page
        await page.goto('/front/preference.php');
        await page.getByRole('link', {'name': "Log in again"}).click();
        await expect(page).toHaveTitle('Authentication - GLPI');

        // login, should be redirected to requested page
        const login_page = new LoginPage(page);
        await login_page.login('glpi', 'glpi');
        await expect(page).toHaveURL('/front/preference.php?');

        // Note: not sure why GLPI add the extra "?" at the end, maybe a bug ?
    });
});

test_authenticated('can change profile', async ({ page, request }) => {
    // Make sure we are already super admin
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");

    // Go to any page and validate we are super admin
    const glpi_page = new GlpiPage(page);
    await page.goto('/front/preference.php');
    await expect(glpi_page.getMenuEntry('Administration')).toBeVisible();

    // Change profile
    await glpi_page.changeProfile('Self-Service');
    await expect(glpi_page.getMenuEntry('Administration')).not.toBeAttached();
});
