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
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormPage } from "../../pages/FormPage";
import { FormPreviewPage } from "../../pages/FormRenderPage";

test('Can fill comment title and description and check persistence', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test comment - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    const comment = await form.addComment('Test comment title');
    const description = await form.getCommentDescription(comment);
    await description.fill('Test comment description');

    await form.doSaveFormEditorAndReload();

    const comment_after = form.getRegion('Comment details');
    await comment_after.getByRole('textbox', { name: 'Comment title' }).click();
    await expect(
        comment_after.getByRole('textbox', { name: 'Comment title' })
    ).toHaveValue('Test comment title');

    const description_after = await form.getCommentDescription(comment_after);
    await expect(description_after).toHaveText('Test comment description');
});

test('Can fill comment title and description and check persistence in preview', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test comment preview - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    const comment = await form.addComment('Test comment title');
    const description = await form.getCommentDescription(comment);
    await description.fill('Test comment description');

    await form.doSaveFormEditor();

    const preview = new FormPreviewPage(page);
    await preview.goto(form_id);

    await expect(page.getByRole('heading', { name: 'Test comment title' })).toBeVisible();
    await expect(page.getByText('Test comment description')).toBeVisible();
});
