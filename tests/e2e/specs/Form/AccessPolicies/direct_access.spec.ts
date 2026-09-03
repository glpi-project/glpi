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
import { Locator, Page } from '@playwright/test';
import { expect, test } from '../../../fixtures/glpi_fixture';
import { FormPage } from '../../../pages/FormPage';
import { Api } from '../../../utils/Api';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

test.describe('Form access policy', () => {
    const form_name = 'Test form for the access policy form suite';

    const setupForm = async (form: FormPage, api: Api): Promise<number> => {
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            'name': form_name,
            'is_active': true,
            '_init_access_policies': false,
            'entities_id': getWorkerEntityId(),
        });

        const tab = 'Glpi\\Form\\AccessControl\\FormAccessControl$1';
        await form.page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=${tab}`
        );

        return form_id;
    };

    const doEnableDirectAccess = async (form: FormPage): Promise<void> => {
        const policy = form.getRegion('Allow direct access');
        const active_checkbox = policy.getByRole('checkbox', {
            name: 'Active',
            exact: true,
        });
        await expect(active_checkbox).not.toBeChecked();
        await active_checkbox.click();
    };

    // This checkbox label embeds an icon, its accessible name can not be
    // matched exactly.
    const getUnauthenticatedCheckbox = (page: Page): Locator => {
        return page.getByRole('checkbox', {
            name: 'Allow unauthenticated users ?',
        });
    };

    const getDirectAccessUrlInput = (form: FormPage): Locator => {
        return form.getTextbox('Direct access URL');
    };

    // This button embeds an icon, its accessible name can not be matched
    // exactly.
    const doSaveChanges = async (page: Page): Promise<void> => {
        await page.getByRole('button', { name: 'Save changes' }).click();
    };

    test('check if form direct access policy can be set', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Enable direct access policy
        await doEnableDirectAccess(form);

        // Check if "allow unauthenticated users" checkbox isn't checked
        await expect(getUnauthenticatedCheckbox(page)).not.toBeChecked();
        const direct_access_url_before_save =
            await getDirectAccessUrlInput(form).inputValue();

        // Save changes
        await doSaveChanges(page);

        // Retrieve the direct access URL
        await expect(getDirectAccessUrlInput(form)).toBeAttached();
        const direct_access_url =
            await getDirectAccessUrlInput(form).inputValue();

        // Make sure the url wasn't regenerated
        await expect(getDirectAccessUrlInput(form))
            .toHaveValue(direct_access_url_before_save)
        ;

        // Visit the direct access URL as non admin (to make sure the token is taken into account)
        await profile.set(Profiles.SelfService);
        await page.goto(direct_access_url);

        // Check if the form title is displayed
        await expect(form.getHeading('Form title')).toContainText(
            form_name
        );
    });

    test('check if form direct access policy can be set and direct access works with autenticated user', async ({
        page,
        profile,
        api,
        anonymousPage,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Enable direct access policy
        await doEnableDirectAccess(form);

        // Check if "allow unauthenticated users" checkbox isn't checked
        await expect(getUnauthenticatedCheckbox(page)).not.toBeChecked();

        // Save changes
        await doSaveChanges(page);

        // Retrieve the direct access URL
        await expect(getDirectAccessUrlInput(form)).toBeAttached();
        const direct_access_url =
            await getDirectAccessUrlInput(form).inputValue();

        // Visit the direct access URL without a session, the user should be
        // directed to the login page.
        await anonymousPage.goto(direct_access_url);
        await expect(
            anonymousPage.getByText(
                "Your session has expired. Please log in again."
            )
        ).toBeVisible();
    });

    test('check if form direct access policy can be set and direct access works with unauthenticated user', async ({
        page,
        profile,
        api,
        anonymousPage,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Enable direct access policy
        await doEnableDirectAccess(form);

        // Enable "allow unauthenticated users"
        await getUnauthenticatedCheckbox(page).check();

        // Save changes
        await doSaveChanges(page);

        // Retrieve the direct access URL
        await expect(getDirectAccessUrlInput(form)).toBeAttached();
        const direct_access_url =
            await getDirectAccessUrlInput(form).inputValue();

        // Visit the direct access URL without a session
        await anonymousPage.goto(direct_access_url);

        // Check if the form title is displayed
        const anonymous_form = new FormPage(anonymousPage);
        await expect(anonymous_form.getHeading('Form title')).toContainText(
            form_name
        );
    });

    test('check if form direct access policy can be set and direct access works with unauthenticated user and hide blacklisted questions', async ({
        page,
        profile,
        api,
        anonymousPage,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const form_id = await setupForm(form, api);

        // Enable direct access policy
        await doEnableDirectAccess(form);

        // Enable "allow unauthenticated users"
        await getUnauthenticatedCheckbox(page).check();

        // Save changes
        await doSaveChanges(page);

        // Retrieve the direct access URL
        await expect(getDirectAccessUrlInput(form)).toBeAttached();
        const direct_access_url =
            await getDirectAccessUrlInput(form).inputValue();

        // Add a question
        await form.goto(form_id);

        const actor_question = await form.addQuestion('Actor question title');
        await form.setQuestionType(actor_question, 'Actors');

        await form.addQuestion('Short answer question title');

        // Save form
        await form.doSaveFormEditor();

        // Visit the direct access URL without a session
        await anonymousPage.goto(direct_access_url);
        const anonymous_form = new FormPage(anonymousPage);

        // Check if the form title is displayed
        await expect(anonymous_form.getHeading('Form title')).toContainText(
            form_name
        );

        // Check if the actor question is hidden
        await expect(anonymous_form.getHeading('Actor question title'))
            .not.toBeAttached()
        ;

        // Check if the short answer question is displayed
        await expect(
            anonymous_form.getHeading('Short answer question title')
        ).toBeAttached();
    });

    test('check that form can be submitted with direct access', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const form_id = await setupForm(form, api);

        // Enable direct access policy
        await doEnableDirectAccess(form);
        const direct_access_url =
            await getDirectAccessUrlInput(form).inputValue();
        await doSaveChanges(page);

        // Add a simple question to the form
        await form.goto(form_id);
        await form.addQuestion('Question 1');
        await form.doSaveFormEditor();

        // Change profile and go to the form
        await profile.set(Profiles.SelfService);
        await page.goto(direct_access_url);
        await expect(form.getHeading('Form title')).toBeAttached();
        await form.getTextbox('Question 1').fill('My answer');
        await form.getButton('Submit').click();
        await expect(form.getAlert('Item successfully created'))
            .toBeVisible()
        ;
    });
});
