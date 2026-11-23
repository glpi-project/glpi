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
import { ReAuthPromptPage } from '../../pages/ReAuthPromptPage';
import { Config } from '../../utils/Config';
import { getWorkerLogin } from '../../utils/WorkerEntities';

// Config requires reauth (Config::itemTypeRequiresReauthentication() === true),
// and the e2e worker account is Super-Admin, so reaching this page with an
// expired reauth session redirects to the prompt.
const protected_url = '/front/config.form.php';

// The e2e worker account is a local (DB_GLPI) account whose password equals its
// login, so the password reauth strategy is the one offered on the prompt and
// getWorkerLogin() is the valid re-authentication secret.

test.describe('Reauth (sudo mode)', () => {
    // The worker only logs in once and the `ensureReauthenticated` fixture grants
    // reauth before each test; revoke it here so the protected page redirects to
    // the prompt, which is exactly what these tests exercise.
    test.beforeEach(async ({ reauth }) => {
        await reauth.revoke();
    });

    test('redirects to the reauth prompt on a protected page', async ({ page }) => {
        await page.goto(protected_url);

        const prompt = new ReAuthPromptPage(page);
        await expect(page).toHaveURL(/\/ReAuth\/Prompt/);
        await expect(prompt.heading).toBeVisible();
    });

    test('grants access when the correct password is provided', async ({ page }) => {
        await page.goto(protected_url);

        const prompt = new ReAuthPromptPage(page);
        await prompt.doVerify(getWorkerLogin());

        // On success the original GET request is replayed, landing back on the
        // protected page and leaving the prompt behind.
        await expect(page).toHaveURL(/config\.form\.php/);
        await expect(prompt.heading).toBeHidden();
    });

    test('rejects an incorrect password and stays on the prompt', async ({ page }) => {
        await page.goto(protected_url);

        const prompt = new ReAuthPromptPage(page);
        await prompt.doVerify('wrong-password');

        await expect(prompt.failure_alert).toBeVisible();
        await expect(prompt.password_field).toBeVisible();
    });

    test('cancel returns to the origin page', async ({ page }) => {
        // Reach the protected page with a referer so the prompt's Cancel target
        // (the origin URL) is a known page rather than the default root.
        const origin_url = `${Config.getBaseUrl()}/front/central.php`;
        await page.goto(protected_url, { referer: origin_url });

        const prompt = new ReAuthPromptPage(page);
        await expect(prompt.heading).toBeVisible();

        await prompt.doCancel();

        await expect(page).toHaveURL(/central\.php/);
    });
});
