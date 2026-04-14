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
import { GlpiPage } from '../../pages/GlpiPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerLogin } from '../../utils/WorkerEntities';

test.describe('Session', () => {
    test('can login and logout', async ({ anonymousPage }) => {
        await anonymousPage.setExtraHTTPHeaders({ 'Accept-Language': 'en-GB,en;q=0.9' });

        const login_page = new LoginPage(anonymousPage);
        await login_page.goto();
        await expect(anonymousPage).toHaveTitle('Authentication - GLPI');

        const worker_login = getWorkerLogin();
        await login_page.doLogin(worker_login, worker_login, true);
        await expect(anonymousPage).toHaveURL(/(\/front\/central\.php|\/Helpdesk)/);

        const cookies = await anonymousPage.context().cookies();
        const glpi_cookies = cookies.filter(c => c.name.startsWith('glpi_'));
        expect(glpi_cookies.length).toBeGreaterThanOrEqual(2);
        expect(glpi_cookies.filter(c => c.name.endsWith('_rememberme'))).toHaveLength(1);

        await anonymousPage.goto('/front/computer.form.php');
        await login_page.doLogout();

        await anonymousPage.goto('/front/computer.form.php');
        await expect(anonymousPage.getByText('Your session has expired. Please log in again.')).toBeVisible();
    });

    test('redirects to requested page after login', async ({ anonymousPage }) => {
        await anonymousPage.goto('/front/ticket.form.php');
        await anonymousPage.getByRole('link', { name: 'Log in again' }).click();

        const login_page = new LoginPage(anonymousPage);
        const worker_login = getWorkerLogin();
        await login_page.doLogin(worker_login, worker_login);

        await expect(anonymousPage).toHaveURL(/\/front\/ticket\.form\.php/);
    });

    test('redirects to requested page after login with 2FA enabled', async ({ anonymousPage, api }) => {
        const username = `e2e_tests_2fa${Date.now()}`;
        await api.createItem('User', {
            name: username,
            login: username,
            password: 'glpi',
            password2: 'glpi',
            _profiles_id: Profiles.SuperAdmin,
        });

        const login_page = new LoginPage(anonymousPage);
        await login_page.goto();
        await login_page.doLogin(username, 'glpi');

        await anonymousPage.goto('/front/preference.php?forcetab=Preference$1');
        const secret = await anonymousPage.getByRole('textbox', { name: '2FA secret' }).inputValue();
        await login_page.doFillTotpCode(authenticator.generate(secret));
        await expect(anonymousPage.getByRole('button', { name: 'Disable 2FA' })).toBeVisible();

        await login_page.doLogout();

        await anonymousPage.goto('/front/ticket.form.php');
        await anonymousPage.getByRole('link', { name: 'Log in again' }).click();

        await login_page.doLogin(username, 'glpi');
        await login_page.doFillTotpCode(authenticator.generate(secret));

        await expect(anonymousPage).toHaveURL(/\/MFA\/ShowBackupCodes/);
        await anonymousPage.getByRole('button', { name: 'Continue' }).click();

        await expect(anonymousPage).toHaveURL(/\/front\/ticket\.form\.php/);
    });

    test('can change profile', async ({ page, profile }) => {
        const glpi_page = new GlpiPage(page);
        await profile.set(Profiles.SuperAdmin);
        await profile.invalidateCachedProfile(); // This test will do some manual profiles changes
        await page.goto('/front/computer.form.php');

        await expect(glpi_page.user_menu).toContainText('Super-Admin');
        await expect(page.getByRole('listitem', { name: 'Administration' })).toBeVisible();

        await glpi_page.doChangeProfile('Self-Service');

        await expect(glpi_page.user_menu).toContainText('Self-Service');
        await expect(page.getByRole('listitem', { name: 'Administration' })).not.toBeAttached();
    });

    test('can setup 2FA during login', async ({ anonymousPage, api }) => {
        const username = `e2e_tests_2fa${Date.now()}`;
        const user_id = await api.createItem('User', {
            name: username,
            login: username,
            password: 'glpi',
            password2: 'glpi',
            _profiles_id: Profiles.SuperAdmin,
        });
        const group_id = await api.createItem('Group', {
            name: `e2e_tests_group_2fa${Date.now()}`,
            entities_id: 0,
            '2fa_enforced': 1,
        });
        await api.createItem('Group_User', {
            groups_id: group_id,
            users_id: user_id,
        });

        await anonymousPage.setExtraHTTPHeaders({ 'Accept-Language': 'en-GB,en;q=0.9' });
        const login_page = new LoginPage(anonymousPage);
        await login_page.goto();
        await expect(anonymousPage).toHaveTitle('Authentication - GLPI');

        await login_page.doLogin(username, 'glpi');
        await expect(anonymousPage).toHaveURL(/\/MFA\/Setup/);

        const secret = await anonymousPage.getByRole('textbox', { name: '2FA secret' }).inputValue();
        await login_page.doFillTotpCode(authenticator.generate(secret));

        await expect(anonymousPage).toHaveURL(/\/MFA\/ShowBackupCodes/);
        await expect(anonymousPage.getByText(/Backup codes \(This is the only time these will be shown\)/i)).toBeVisible();
        await expect(anonymousPage.getByTestId('backup-code')).toHaveCount(5);
        await anonymousPage.getByRole('button', { name: 'Continue' }).click();

        await expect(anonymousPage).toHaveURL(/\/front\/central\.php/);

        const glpi_page = new GlpiPage(anonymousPage);
        await glpi_page.doLogout();

        await login_page.goto();
        await login_page.doLogin(username, 'glpi');
        await login_page.doFillTotpCode(authenticator.generate(secret));

        await expect(anonymousPage).toHaveURL(/\/front\/central\.php/);
    });
});
