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
import { LoginPage } from '../../pages/OAuthPage';
import { Constants } from '../../utils/Constants';

const OAUTH_CLIENT_ID     = 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789';
const OAUTH_CLIENT_SECRET = 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210';
const OAUTH_SCOPE         = 'api user';
const OAUTH_REDIRECT_URI  = '/api.php/oauth2/redirection';

async function assertAuthorizationConsent(login_page: LoginPage): Promise<void>
{
    await expect(login_page.oauth_authorization_heading).toBeVisible();
    await expect(login_page.page.getByText('Access to the API')).toBeVisible();
    await expect(login_page.page.getByText("Access to the user's information")).toBeVisible();
    await expect(login_page.oauth_deny_button).toBeVisible();
}

async function assertTokenExchange(oauth_page: LoginPage): Promise<void>
{
    const code = await oauth_page.doGetAuthorizationCode();
    const token = await oauth_page.doExchangeCodeForToken(
        code,
        OAUTH_CLIENT_ID,
        OAUTH_CLIENT_SECRET,
        OAUTH_REDIRECT_URI
    );
    expect(token.access_token).toBeTruthy();
    expect(token.refresh_token).toBeTruthy();
}

test('Should authorize without login session - no remember me', async ({ anonymousPage }) => {
    const worker_index = String(test.info().parallelIndex + 1).padStart(2, '0');
    const worker_login = `${Constants.E2E_WORKER_PREFIX}${worker_index}`;

    const oauth_page = new LoginPage(anonymousPage);
    await oauth_page.gotoOauthAuthorize(OAUTH_CLIENT_ID, OAUTH_SCOPE, OAUTH_REDIRECT_URI);

    await oauth_page.doLogin(worker_login, worker_login);

    await assertAuthorizationConsent(oauth_page);
    await assertTokenExchange(oauth_page);
});

test('Should authorize without login session - remember me', async ({ anonymousPage }) => {
    const worker_index = String(test.info().parallelIndex + 1).padStart(2, '0');
    const worker_login = `${Constants.E2E_WORKER_PREFIX}${worker_index}`;

    const oauth_page = new LoginPage(anonymousPage);
    await oauth_page.gotoOauthAuthorize(OAUTH_CLIENT_ID, OAUTH_SCOPE, OAUTH_REDIRECT_URI);

    await oauth_page.doLogin(worker_login, worker_login, true);

    await assertAuthorizationConsent(oauth_page);
    await assertTokenExchange(oauth_page);
});

test('Should authorize with existing login session', async ({ page }) => {
    const oauth_page = new LoginPage(page);
    await oauth_page.gotoOauthAuthorize(OAUTH_CLIENT_ID, OAUTH_SCOPE, OAUTH_REDIRECT_URI);

    // Already logged in — login form should not be shown
    await assertAuthorizationConsent(oauth_page);
    await assertTokenExchange(oauth_page);
});
