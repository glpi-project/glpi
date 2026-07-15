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

import { expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

// Reading/writing the clipboard requires explicit permissions.
test.use({ permissions: ['clipboard-read', 'clipboard-write'] });

test('Publishing copies the full link and toasts', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB share autocopy - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: 'Content for autocopy',
    });

    await kb.goto(id);
    await kb.openSharePopover();
    await kb.publishSwitch().check();

    await expect(kb.shareLink()).toBeVisible();
    await expect(kb.getAlert('Public link copied to clipboard')).toBeVisible();

    const copied = await page.evaluate(() => navigator.clipboard.readText());
    expect(copied).toContain('/Share/');
    expect(copied).toMatch(/[0-9a-f]{64}$/);
});

test('Editing the slug updates the link', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB share slug edit - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: 'Content for slug edit',
    });

    await kb.goto(id);
    await kb.openSharePopover();
    await kb.publishSwitch().check();
    await expect(kb.shareLink()).toBeVisible();

    await kb.shareSlugInput().fill('reset-password');
    await kb.shareSlugInput().press('Enter');

    // After reload the slug persists as the input value.
    await expect(kb.shareSlugInput()).toHaveValue('reset-password');

    // The copied full URL contains the slug and the token.
    const copied = await kb.copiedShareUrl();
    expect(copied).toContain('/Share/reset-password-');
    expect(copied).toMatch(/[0-9a-f]{64}$/);
});

test('Invalid slug shows an error and is not saved', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: `KB share slug invalid - ${crypto.randomUUID()}`,
        entities_id: getWorkerEntityId(),
        answer: 'Content for slug invalid',
    });

    await kb.goto(id);
    await kb.openSharePopover();
    await kb.publishSwitch().check();
    await expect(kb.shareLink()).toBeVisible();

    await kb.shareSlugInput().fill('Invalid Slug?');
    await expect(kb.shareSlugError()).toBeVisible();
});
