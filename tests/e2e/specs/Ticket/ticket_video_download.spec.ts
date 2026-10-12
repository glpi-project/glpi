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

import { readFileSync } from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { expect, test } from '../../fixtures/glpi_fixture';
import { TicketPage } from '../../pages/TicketPage';
import { Profiles } from '../../utils/Profiles';

const VIDEO = 'uploads/test.mov';

test('Can attach a video to a ticket and download it', async ({ profile, page }) => {
    await profile.set(Profiles.SuperAdmin);

    const ticket = new TicketPage(page);
    await ticket.gotoCreationPage();
    await ticket.getTextbox('Title').fill(`Video ticket ${randomUUID()}`);
    await ticket.getRichTextByLabel('Description').fill('See attached video');
    await ticket.doAddFileToUploadArea(VIDEO, page.getByRole('main'));
    await ticket.getButton('Add').click();
    await page.waitForURL(/ticket\.form\.php\?id=\d+/);

    // eslint-disable-next-line playwright/no-raw-locators -- PhotoSwipe has no accessible roles
    await page.locator('.sub-documents .pswp-trigger').click();
    // eslint-disable-next-line playwright/no-raw-locators -- PhotoSwipe has no accessible roles
    const download_button = page.locator('.pswp a[download]');
    // eslint-disable-next-line playwright/no-raw-locators -- PhotoSwipe has no accessible roles
    await expect(page.locator('.pswp video')).toBeVisible();

    const download_promise = page.waitForEvent('download');
    await download_button.click();
    const download = await download_promise;

    const expected = readFileSync(path.join(__dirname, `../../../fixtures/${VIDEO}`));
    expect(readFileSync(await download.path()).equals(expected)).toBe(true);
});
