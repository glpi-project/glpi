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

import { randomUUID } from "crypto";
import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId, getWorkerUserId } from '../../utils/WorkerEntities';
import { FormPreviewPage } from "../../pages/FormRenderPage";
import { GlpiPage } from "../../pages/GlpiPage";

async function createFormAndRenderIt(
    api: any,
    page: any,
    uuid: string,
): Promise<number> {
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `My test form - ${uuid}`,
        is_active: true,
        entities_id: getWorkerEntityId(),
    });

    const sections = await api.getSubItems(
        'Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section'
    );
    await api.createItem('Glpi\\Form\\Question', {
        forms_sections_id: sections[0].id,
        name: 'Name',
        type: 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
        vertical_rank: 0,
    });

    const preview = new FormPreviewPage(page);
    await preview.goto(form_id);

    return form_id;
}

async function initDelegationWithAPI(
    api: any,
    uuid: string,
): Promise<void> {
    const worker_user_id = getWorkerUserId();
    const entity_id = getWorkerEntityId();

    const group_id = await api.createItem('Group', {
        name: `Test group - ${uuid}`,
        entities_id: entity_id,
    });

    const user_id = await api.createItem('User', {
        name: `Test user - ${uuid}`,
    });

    await api.createItem('Profile_User', {
        users_id: user_id,
        profiles_id: Profiles.SelfService,
        entities_id: entity_id,
        is_recursive: 1,
    });

    await api.createItem('Group_User', {
        groups_id: group_id,
        users_id: worker_user_id,
        is_userdelegate: 1,
    });

    await api.createItem('Group_User', {
        groups_id: group_id,
        users_id: user_id,
    });
}

test("Can't view delegation section when no delegation rights", async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const uuid = randomUUID();
    const glpi = new GlpiPage(page);

    // Clean up any existing delegation rights from previous test runs
    const worker_user_id = getWorkerUserId();
    const group_users = await api.getSubItems('User', worker_user_id, 'Group_User');
    const delegate_entries = group_users.filter(
        (gu: { is_userdelegate: number }) => gu.is_userdelegate
    );
    await Promise.all(
        delegate_entries.map(
            (gu: { id: number }) => api.purgeItem('Group_User', gu.id)
        )
    );

    await createFormAndRenderIt(api, page, uuid);

    await expect(glpi.getDropdownByLabel('Select the user to delegate')).toBeHidden();
    await expect(glpi.getDropdownByLabel('Do you want to be notified of future events of this ticket')).toBeHidden();
});

test('Can delegate', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const uuid = randomUUID();
    const glpi = new GlpiPage(page);

    await initDelegationWithAPI(api, uuid);
    await createFormAndRenderIt(api, page, uuid);

    const delegate_dropdown = glpi.getDropdownByLabel('Select the user to delegate');
    await expect(delegate_dropdown).toContainText('Myself');

    await expect(glpi.getDropdownByLabel('Do you want to be notified of future events of this ticket')).toBeHidden();

    await glpi.doSetDropdownValue(delegate_dropdown, `Test user - ${uuid}`, false);

    const notify_dropdown = glpi.getDropdownByLabel('Do you want to be notified of future events of this ticket');
    await expect(notify_dropdown).toContainText('He wants');

    await page.getByRole('button', { name: 'Address to send the notification' }).click();
    await page.getByRole('textbox', { name: 'Address to send the notification' }).fill('test@test.fr');

    await page.getByRole('textbox', { name: 'Name' }).fill('Test');

    await page.getByRole('button', { name: 'Submit' }).click();

    await page.getByRole('link', { name: `My test form - ${uuid}` }).click();

    const actors = glpi.getRegion('Actors');
    const user_tag = actors.getByRole('listitem', { name: `Test user - ${uuid}` });
    await expect(user_tag).toBeVisible({ timeout: 15000 });

    await user_tag.hover();
    await actors.getByRole('button', { name: 'Email followup' }).click();
    await expect(actors.getByRole('checkbox', { name: 'Email followup' })).toBeChecked();
    await expect(actors.getByRole('textbox', { name: 'Email address' })).toHaveValue('test@test.fr');
});

test('Can delegate in self-service', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const uuid = randomUUID();
    const glpi = new GlpiPage(page);

    await initDelegationWithAPI(api, uuid);
    await createFormAndRenderIt(api, page, uuid);

    await profile.set(Profiles.SelfService);
    await page.reload();

    const delegate_dropdown = glpi.getDropdownByLabel('Select the user to delegate');
    await expect(delegate_dropdown).toContainText('Myself');

    await expect(glpi.getDropdownByLabel('Do you want to be notified of future events of this ticket')).toBeHidden();

    await glpi.doSetDropdownValue(delegate_dropdown, `Test user - ${uuid}`, false);

    const notify_dropdown = glpi.getDropdownByLabel('Do you want to be notified of future events of this ticket');
    await expect(notify_dropdown).toContainText('He wants');

    await page.getByRole('button', { name: 'Address to send the notification' }).click();
    await page.getByRole('textbox', { name: 'Address to send the notification' }).fill('test@test.fr');

    await page.getByRole('textbox', { name: 'Name' }).fill('Test');

    await page.getByRole('button', { name: 'Submit' }).click();

    await profile.set(Profiles.SuperAdmin);

    await page.getByRole('link', { name: `My test form - ${uuid}` }).click();

    const actors = glpi.getRegion('Actors');
    const user_tag = actors.getByRole('listitem', { name: `Test user - ${uuid}` });
    await expect(user_tag).toBeVisible({ timeout: 15000 });

    await user_tag.hover();
    await actors.getByRole('button', { name: 'Email followup' }).click();
    await expect(actors.getByRole('checkbox', { name: 'Email followup' })).toBeChecked();
    await expect(actors.getByRole('textbox', { name: 'Email address' })).toHaveValue('test@test.fr');
});
