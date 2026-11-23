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

import { expect } from '@playwright/test';
import { authenticator } from 'otplib';
import { test } from '../../fixtures/glpi_fixture';
import { LoginPage } from '../../pages/LoginPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerLogin } from '../../utils/WorkerEntities';

// Seeded OAuth client (see tests/e2e/specs/Security/oauth_authcode.spec.ts).
const OAUTH_CLIENT_ID    = 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789';
const OAUTH_SCOPE        = 'api user';
const OAUTH_REDIRECT_URI = '/api.php/oauth2/redirection';

// RAWeb criterion 16: the help link must be available from every page, including
// the anonymous pages that share the page_card_notlogged layout. The login and
// lost-password pages are trivial to reach; this spec covers the
// hard-to-reproduce ones (OAuth consent and the 2FA prompts) where a silent
// layout regression would otherwise go unnoticed.
test.describe('Anonymous help link', () => {
    test('is available on the OAuth authorization page', async ({ anonymousPage }) => {
        await anonymousPage.setExtraHTTPHeaders({ 'Accept-Language': 'en-GB,en;q=0.9' });

        const oauth_page = new LoginPage(anonymousPage);
        await oauth_page.gotoOauthAuthorize(OAUTH_CLIENT_ID, OAUTH_SCOPE, OAUTH_REDIRECT_URI);

        const worker_login = getWorkerLogin();
        await oauth_page.doLogin(worker_login, worker_login);

        await expect(oauth_page.oauth_authorization_heading).toBeVisible();
        await expect(anonymousPage.getByRole('link', { name: 'Help' })).toBeVisible();
    });

    test('is available on the 2FA setup and prompt pages', async ({ anonymousPage, api }) => {
        // Heavy fixture setup (user + enforced-2FA group) plus two full login flows.
        test.slow();
        await anonymousPage.setExtraHTTPHeaders({ 'Accept-Language': 'en-GB,en;q=0.9' });

        const username = `e2e_tests_help_2fa${Date.now()}`;
        const user_id = await api.createItem('User', {
            name: username,
            login: username,
            password: 'glpi',
            password2: 'glpi',
            _profiles_id: Profiles.SuperAdmin,
        });
        const group_id = await api.createItem('Group', {
            name: `e2e_tests_help_group_2fa${Date.now()}`,
            entities_id: 0,
            '2fa_enforced': 1,
        });
        await api.createItem('Group_User', {
            groups_id: group_id,
            users_id: user_id,
        });

        const login_page = new LoginPage(anonymousPage);
        await login_page.goto();
        await login_page.doLogin(username, 'glpi');

        // Enforced 2FA setup page.
        await expect(anonymousPage).toHaveURL(/\/MFA\/Setup/);
        await expect(anonymousPage.getByRole('link', { name: 'Help' })).toBeVisible();

        // Complete the setup so 2FA is required on the next login.
        const secret = await anonymousPage.getByRole('textbox', { name: '2FA secret' }).inputValue();
        await login_page.doFillTotpCode(authenticator.generate(secret));
        await expect(anonymousPage).toHaveURL(/\/MFA\/ShowBackupCodes/);
        await anonymousPage.getByRole('button', { name: 'Continue' }).click();

        await login_page.doLogout();

        // 2FA code prompt shown on the next login.
        await login_page.goto();
        await login_page.doLogin(username, 'glpi');
        await expect(anonymousPage).toHaveURL(/\/MFA\/Prompt/);
        await expect(anonymousPage.getByRole('link', { name: 'Help' })).toBeVisible();
    });
});
