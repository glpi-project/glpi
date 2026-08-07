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

import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';

test.beforeEach(async ({ page }) => {
    // prevent race conditions with ajax callbacks and speed up execution
    await page.route('/ajax/debug.php**', route => route.fulfill({ status: 404, body: '' }));
});

test.afterEach(async ({ debug }) => {
    // Always reset debug mode to avoid polluting subsequent tests
    await debug.disable();
});

test('Displays a bad request error', async ({ page, profile, debug }) => {
    await profile.set(Profiles.SuperAdmin);

    const urls = [
        '/front/impactcsv.php', // streamed response
        '/Form/Render/0',       // modern controller
    ];

    const expected_code    = 400;
    const expected_message = 'Invalid request parameters.';

    // Check with debug mode (stack trace should be displayed)
    await debug.enable();
    for (const url of urls) {
        const response = await page.goto(url);
        expect(response?.status()).toBe(expected_code);
        await expect(page.getByRole('alert')).toContainText(expected_message);
        await expect(page.getByTestId('stack-trace')).toBeAttached();
    }

    // Check without debug mode (stack trace should NOT be displayed)
    await debug.disable();
    for (const url of urls) {
        const response = await page.goto(url);
        expect(response?.status()).toBe(expected_code);
        await expect(page.getByRole('alert')).toContainText(expected_message);
        await expect(page.getByTestId('stack-trace')).not.toBeAttached();
    }
});

test('Displays an access denied error', async ({ page, profile }) => {
    await profile.set(Profiles.SelfService);

    const urls = [
        '/front/central.php',  // streamed response
        '/front/computer.php', // generic controller
        '/Form/Import',        // modern controller
    ];

    const expected_code    = 403;
    const expected_message = "You don't have permission to perform this action.";

    // Cannot test the debug mode with Self-Service profile

    // Check without debug mode (stack trace should NOT be displayed)
    for (const url of urls) {
        const response = await page.goto(url);
        expect(response?.status()).toBe(expected_code);
        await expect(page.getByRole('alert')).toContainText(expected_message);
        await expect(page.getByTestId('stack-trace')).not.toBeAttached();
    }
});

test('Displays a not found error', async ({ page, profile, debug }) => {
    await profile.set(Profiles.SuperAdmin);

    const urls = [
        '/front/logviewer.php?filepath=invalid', // streamed response
        '/front/computer.form.php?id=999999',    // generic response
        '/Form/Render/999999',                   // modern controller
    ];

    const expected_code    = 404;
    const expected_message = 'The requested item has not been found.';

    // Check with debug mode (stack trace should be displayed)
    await debug.enable();
    for (const url of urls) {
        const response = await page.goto(url);
        expect(response?.status()).toBe(expected_code);
        await expect(page.getByRole('alert')).toContainText(expected_message);
        await expect(page.getByTestId('stack-trace')).toBeAttached();
    }

    // Check without debug mode (stack trace should NOT be displayed)
    await debug.disable();
    for (const url of urls) {
        const response = await page.goto(url);
        expect(response?.status()).toBe(expected_code);
        await expect(page.getByRole('alert')).toContainText(expected_message);
        await expect(page.getByTestId('stack-trace')).not.toBeAttached();
    }
});
