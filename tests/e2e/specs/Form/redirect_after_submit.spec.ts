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
import { APIRequestContext } from 'playwright/test';
import { test, expect } from '../../fixtures/glpi_fixture';
import { Api } from '../../utils/Api';
import { CsrfExtractor } from '../../utils/CsrfExtractor';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId, getWorkerUserId } from '../../utils/WorkerEntities';
import { FormPreviewPage } from '../../pages/FormRenderPage';
import { PreferencePage } from '../../pages/PreferencePage';
import { TicketPage } from '../../pages/TicketPage';

async function createFormWithQuestion(api: Api): Promise<number>
{
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        'name': `Test form ${randomUUID()}`,
        'is_active': true,
        'entities_id': getWorkerEntityId(),
    });
    const sections = await api.getSubItems(
        'Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section'
    );
    await api.createItem('Glpi\\Form\\Question', {
        'name': 'Question 1',
        'type': 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
        'vertical_rank': 0,
        'forms_sections_id': sections[0].id,
    });

    return form_id;
}

/**
 * Update the "Go to created item after creation" preference of the worker
 * user. The preference page is used (instead of the API) so the value cached
 * in the current session is updated too.
 *
 * When the value matches the global default, GLPI stores `NULL` for the user
 * preference, so restoring the initial value also restores the initial state.
 *
 * The preference form is not an AJAX endpoint, thus GLPI destroys the CSRF
 * token once validated: the shared token of the `CsrfFetcher` service can't be
 * used, a new one is read from the preference page instead.
 */
async function setBackcreatedPreference(
    request: APIRequestContext,
    value: boolean
): Promise<void> {
    const page_response = await request.get('/front/preference.php');
    const token = new CsrfExtractor().extractToken(await page_response.text());

    const response = await request.post('/front/preference.php', {
        form: {
            '_glpi_csrf_token': token,
            'id': getWorkerUserId(),
            'backcreated': value ? 1 : 0,
            'update': 1,
        },
    });
    if (!response.ok()) {
        throw new Error(
            `Failed to update the "backcreated" preference: HTTP ${response.status()}`
        );
    }
}

test.describe('Redirection after form submission', () => {
    test('redirects to the created ticket when the preference is enabled', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form_id = await createFormWithQuestion(api);

        await profile.set(Profiles.SelfService);
        const form = new FormPreviewPage(page);
        await form.goto(form_id);
        await form.getTextbox('Question 1').fill('My answer');
        await form.doSubmitForm();

        await expect(page).toHaveURL(/\/front\/ticket\.form\.php\?id=\d+/);
        await expect(new TicketPage(page).getFormAnswer('My answer')).toBeVisible();
    });

    test('displays the success screen when the preference is disabled', async ({
        page,
        request,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form_id = await createFormWithQuestion(api);

        await profile.set(Profiles.SelfService);
        const preferences = new PreferencePage(page);
        await preferences.gotoPersonalizationTab();
        const initial_backcreated = await preferences.getBackcreatedValue();
        await setBackcreatedPreference(request, false);
        try {
            const form = new FormPreviewPage(page);
            await form.goto(form_id);
            await form.getTextbox('Question 1').fill('My answer');
            await form.doSubmitForm();

            await expect(form.getAlert('Item successfully created')).toBeVisible();
            await expect(form.success_message).toBeVisible();
            await expect(page).toHaveURL(new RegExp(`/Form/Render/${form_id}`));
        } finally {
            await setBackcreatedPreference(request, initial_backcreated);
        }
    });
});
