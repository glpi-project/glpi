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

import type { Locator, Page } from '@playwright/test';
import { expect, test } from '../../fixtures/glpi_fixture';
import { GlpiPage } from '../../pages/GlpiPage';
import { Api } from '../../utils/Api';
import { Profiles } from '../../utils/Profiles';

// Name of the LDAP server added by the installer in the `e2e_testing` env.
const LDAP_SERVER_NAME = '_e2e_ldap';

// LDAP user that is imported, then synchronized.
const LDAP_USER = 'walid';

// LDAP user that is never imported.
const OTHER_LDAP_USER = 'xavier';

const SIMPLE_MODE_FIELDS = [
    'Login',
    'Email',
    'Surname',
    'First name',
    'Phone',
    'Title',
    'Category',
];
const EXPERT_MODE_FIELDS = ['BaseDN', 'Search filter for users'];

async function getLdapServerId(api: Api): Promise<number>
{
    const servers = await api.getItemsByName('AuthLDAP', LDAP_SERVER_NAME);
    return servers[0].id;
}

async function purgeLdapUser(api: Api): Promise<void>
{
    const users = await api.getItemsByName('User', LDAP_USER);
    for (const user of users) {
        await api.purgeItem('User', user.id, true);
    }
}

function getResultRows(page: Page): Locator
{
    // Only the result rows have this checkbox, the header row has a
    // "Check all" checkbox instead.
    return page.getByRole('row').filter({
        has: page.getByRole('checkbox', { name: 'Select item' }),
    });
}

async function doSearch(
    glpi_page: GlpiPage,
    expected_url: RegExp,
): Promise<void> {
    await glpi_page.getButton('Search').click();

    // Wait for the results of this search, the previous results are still
    // displayed until the page is reloaded.
    await expect(glpi_page.page).toHaveURL(expected_url);
}

async function goToLdapUsersPage(
    glpi_page: GlpiPage,
    link: string,
): Promise<void> {
    const page = glpi_page.page;
    await page.goto('/front/user.php');

    // Not exact, the name of these links also contains their icon.
    await page.getByRole('link', { name: 'LDAP directory link' }).click();
    await page.getByRole('link', { name: link }).click();
}

async function expectSimpleMode(page: Page): Promise<void>
{
    await expect(page.getByLabel('Simple mode')).toBeAttached();
    for (const field of SIMPLE_MODE_FIELDS) {
        await expect(page.getByLabel(field, { exact: true })).toBeVisible();
    }
    for (const field of EXPERT_MODE_FIELDS) {
        await expect(page.getByLabel(field, { exact: true })).toBeHidden();
    }
}

async function doSwitchToExpertMode(page: Page): Promise<void>
{
    await page.getByLabel('Simple mode').click();

    await expect(page.getByLabel('Simple mode')).not.toBeAttached();
    await expect(page.getByLabel('Expert mode')).toBeAttached();
    for (const field of SIMPLE_MODE_FIELDS) {
        await expect(page.getByLabel(field, { exact: true })).toBeHidden();
    }
    for (const field of EXPERT_MODE_FIELDS) {
        await expect(page.getByLabel(field, { exact: true })).toBeVisible();
    }
}

async function doSearchByLogin(glpi_page: GlpiPage): Promise<void>
{
    await glpi_page.page.getByLabel('Login', { exact: true }).fill(LDAP_USER);
    await doSearch(glpi_page, new RegExp(LDAP_USER));
}

async function doSearchByLdapFilter(glpi_page: GlpiPage): Promise<void>
{
    await glpi_page.page.getByLabel('Search filter for users', { exact: true })
        .fill(`(& (uid=*${OTHER_LDAP_USER}*) (objectclass=inetOrgPerson))`)
    ;
    await doSearch(glpi_page, new RegExp(OTHER_LDAP_USER));
}

async function doMassiveActionOnFirstRow(
    glpi_page: GlpiPage,
    action: string,
): Promise<void> {
    const page = glpi_page.page;
    await getResultRows(page).first().getByRole('checkbox').check();

    // Not exact, the name of these buttons also contains their icon.
    await page.getByRole('button', { name: 'Actions' }).click();
    await glpi_page.doSetDropdownValue(
        glpi_page.getDropdownByLabel('Action'),
        action
    );
    await page.getByRole('button', { name: 'Post' }).click();
}

test.describe('LDAP integration', () => {
    // The LDAP server only exists on the CI.
    // See: `.github/actions/docker-compose-services.yml`.
    // eslint-disable-next-line playwright/no-skipped-test
    test.skip(!process.env.CI, 'The LDAP server of the CI is required');

    // The synchronization tests need the user imported by the import tests.
    test.describe.configure({ mode: 'serial' });

    test.beforeAll(async ({ workerSessionCache }) => {
        // Remove the user imported by a previous run.
        await purgeLdapUser(new Api(workerSessionCache));
    });

    test.afterAll(async ({ workerSessionCache }) => {
        await purgeLdapUser(new Api(workerSessionCache));
    });

    test.beforeEach(async ({ api, profile }) => {
        await api.updateItem('AuthLDAP', await getLdapServerId(api), {
            'is_active': 1,
            'is_default': 1,
        });

        // The LDAP menu links are only shown when a LDAP server is active, but
        // the menu is cached in the session. Changing the profile clears this
        // cache.
        await profile.invalidateCachedProfile();
        await profile.set(Profiles.SuperAdmin);
    });

    test.afterEach(async ({ api }) => {
        // Values set by the installer.
        await api.updateItem('AuthLDAP', await getLdapServerId(api), {
            'is_active': 0,
            'is_default': 1,
        });
    });

    test('Import users', async ({ page }) => {
        const glpi_page = new GlpiPage(page);
        const rows = getResultRows(page);

        await goToLdapUsersPage(glpi_page, 'Import new users');
        await expectSimpleMode(page);

        // Search without criteria.
        await doSearch(glpi_page, /search=/);
        await expect.poll(() => rows.count()).toBeGreaterThanOrEqual(5);

        // Import a specific user.
        await doSearchByLogin(glpi_page);
        await expect(rows).toHaveCount(1);
        await expect(rows).toContainText(LDAP_USER);
        await doMassiveActionOnFirstRow(glpi_page, 'Import');
        await expect(glpi_page.getAlert('Item successfully added')).toBeVisible();

        await doSwitchToExpertMode(page);
        await doSearchByLdapFilter(glpi_page);
        await expect(rows).toHaveCount(1);
        await expect(rows).toContainText(OTHER_LDAP_USER);
    });

    test('Import UI with no default server', async ({ page, api }) => {
        const glpi_page = new GlpiPage(page);

        await api.updateItem('AuthLDAP', await getLdapServerId(api), {
            'is_default': 0,
        });

        await page.goto('/front/ldap.import.php?mode=0&action=show');
        await doSearch(glpi_page, /search=/);
        await expect.poll(
            () => getResultRows(page).count()
        ).toBeGreaterThanOrEqual(5);
    });

    test('Sync users', async ({ page }) => {
        const glpi_page = new GlpiPage(page);
        const rows = getResultRows(page);

        await goToLdapUsersPage(glpi_page, 'Synchronizing already imported users');
        await expectSimpleMode(page);

        // Synchronize the user imported by the "Import users" test.
        await doSearchByLogin(glpi_page);
        await expect(rows).toHaveCount(1);
        await expect(rows).toContainText(LDAP_USER);
        await doMassiveActionOnFirstRow(glpi_page, 'Synchronize');
        await expect(glpi_page.getAlert('Item successfully updated')).toBeVisible();

        // This user was never imported.
        await doSwitchToExpertMode(page);
        await doSearchByLdapFilter(glpi_page);
        await expect(rows).toHaveCount(0);
    });

    test('Sync UI with no default server', async ({ page, api }) => {
        const glpi_page = new GlpiPage(page);
        const rows = getResultRows(page);

        await api.updateItem('AuthLDAP', await getLdapServerId(api), {
            'is_default': 0,
        });

        await page.goto('/front/ldap.import.php?mode=1&action=show');
        await doSearch(glpi_page, /search=/);
        await expect(rows).toHaveCount(1);
        await expect(rows).toContainText(LDAP_USER);
    });

    test('LDAP server passes all the connection tests', async ({ page, api }) => {
        const glpi_page = new GlpiPage(page);

        await page.goto(
            `/front/authldap.form.php?id=${await getLdapServerId(api)}`
        );
        await glpi_page.doGoToTab('Test');

        const tab_panel = page.getByRole('tabpanel');
        await expect(tab_panel.getByRole('listitem')).toHaveCount(5);

        // Each step of the test must succeed.
        const messages = tab_panel.getByTestId('ldap-test-message');
        await expect(messages).toHaveCount(5);
        for (const message of await messages.all()) {
            await expect(message).toHaveClass(/text-success/);
        }
    });
});
