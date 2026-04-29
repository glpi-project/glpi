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
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { pasteImageInRichText } from '../../utils/ImagePasteHelpers';

test('pasted image is uploaded as document, not stored as base64', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    const ticket_id = await api.createItem('Ticket', {
        name: `Ticket ${crypto.randomUUID()}`,
        content: 'Test ticket for image paste',
        entities_id: getWorkerEntityId(),
    });

    await page.goto(`/front/ticket.form.php?id=${ticket_id}`);

    await page.getByRole('button', { name: 'Answer' }).click();
    await expect(page.getByRole('application')).toBeVisible();

    const getRichText = async () => {
        // eslint-disable-next-line playwright/no-raw-locators
        return page.getByRole('application').locator('iframe').contentFrame().locator('body');
    };

    await pasteImageInRichText(page, getRichText, '_uploader_filename');
    await expect((await getRichText()).getByRole('img')).toBeVisible();

    // eslint-disable-next-line playwright/no-raw-locators
    const add_button = page.locator('#new-ITILFollowup-block button[name="add"]');
    await expect(add_button).toBeEnabled();
    await add_button.click();
    await page.waitForURL(/ticket\.form\.php/);

    const followups = await api.getSubItems('Ticket', ticket_id, 'ITILFollowup');
    const last_followup = followups[followups.length - 1];
    expect(last_followup.content).not.toContain('data:image');
    expect(last_followup.content).toContain('document.send.php');
});
