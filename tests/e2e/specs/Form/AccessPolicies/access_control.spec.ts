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
import { randomUUID } from 'crypto';
import { expect, test } from '../../../fixtures/glpi_fixture';
import { FormPage } from '../../../pages/FormPage';
import { Api } from '../../../utils/Api';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

test.describe('Access Control', () => {
    const setupForm = async (form: FormPage, api: Api): Promise<void> => {
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            'name': `[Tests] Access Control ${randomUUID()}`,
            '_init_access_policies': false,
            'entities_id': getWorkerEntityId(),
        });

        const tab = 'Glpi\\Form\\AccessControl\\FormAccessControl$1';
        await form.page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=${tab}`
        );
    };

    test('warnings are displayed', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Quick tests to ensure that warnings are rendered correcly by twig.
        // We don't check their exact content as it is already validated by unit tests.
        const alerts = page.getByRole('alert');
        await expect(alerts.nth(0)).toContainText(
            "This form is not visible to anyone because it is not active."
        );
        await expect(alerts.nth(1)).toContainText(
            "This form will not be visible to any users as there are currently no active access policies."
        );
    });

    test('can configure the allow list policy', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Change values
        const policy = form.getRegion(
            'Allow specifics users, groups or profiles'
        );
        const active_checkbox = policy.getByRole('checkbox', {
            name: 'Active',
            exact: true,
        });
        await expect(active_checkbox).not.toBeChecked();
        await active_checkbox.click();

        await form.doSetDropdownValue(
            form.getDropdownByLabel('Allow specifics users, groups or profiles'),
            'All users'
        );
        await expect(
            page.getByRole('link', {
                name: /There are \d+ user\(s\) matching these criteria\./,
            })
        ).toBeAttached();

        // Save changes
        await page.getByRole('button', { name: 'Save changes' }).click();

        // Check values are kept after update
        await expect(
            form.getRegion('Allow specifics users, groups or profiles')
                .getByRole('checkbox', { name: 'Active', exact: true })
        ).toBeChecked();
        await expect(
            form.getDropdownByLabel('Allow specifics users, groups or profiles')
        ).toContainText('All users');
    });

    test('can configure the direct access policy', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        await page.context().grantPermissions([
            'clipboard-read',
            'clipboard-write',
        ]);
        const form = new FormPage(page);

        await setupForm(form, api);

        const policy = form.getRegion('Allow direct access');
        const active_checkbox = policy.getByRole('checkbox', {
            name: 'Active',
            exact: true,
        });
        // This checkbox label embeds an icon, its accessible name can not be
        // matched exactly.
        const unauthenticated_checkbox = policy.getByRole('checkbox', {
            name: 'Allow unauthenticated users ?',
        });

        await expect(active_checkbox).not.toBeChecked();
        await active_checkbox.click();
        await expect(unauthenticated_checkbox).not.toBeChecked();
        await unauthenticated_checkbox.click();

        // Save changes
        await page.getByRole('button', { name: 'Save changes' }).click();

        // Check values are kept after update
        const saved_policy = form.getRegion('Allow direct access');
        await expect(
            saved_policy.getByRole('checkbox', { name: 'Active', exact: true })
        ).toBeChecked();
        await expect(
            saved_policy.getByRole('checkbox', {
                name: 'Allow unauthenticated users ?',
            })
        ).toBeChecked();

        // Make sure link can be copied to clipboard
        await saved_policy.getByLabel("Click to copy to clipboard").click();
        const clipboard_content = await page.evaluate(
            () => navigator.clipboard.readText()
        );
        expect(clipboard_content).toContain('token=');
    });

    test('activate policy when any input is modified', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        const policy = form.getRegion('Allow direct access');
        const active_checkbox = policy.getByRole('checkbox', {
            name: 'Active',
            exact: true,
        });
        // This checkbox label embeds an icon, its accessible name can not be
        // matched exactly.
        const unauthenticated_checkbox = policy.getByRole('checkbox', {
            name: 'Allow unauthenticated users ?',
        });

        await expect(active_checkbox).not.toBeChecked();
        await expect(unauthenticated_checkbox).not.toBeChecked();
        await unauthenticated_checkbox.click();
        await expect(active_checkbox).toBeChecked();
    });
});
