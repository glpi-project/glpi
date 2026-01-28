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

test('Can view revision history and restore a previous version', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item with multiple revisions
    const id = await api.createItem('KnowbaseItem', {
        name: 'Original Title',
        entities_id: getWorkerEntityId(),
        answer: 'Original content',
    });
    await api.updateItem('KnowbaseItem', id, { answer: 'Content V2' });
    await api.updateItem('KnowbaseItem', id, { answer: 'Updated content' });

    // Go to article
    await kb.goto(id);
    await expect(page.getByText('Updated content')).toBeVisible();

    // Toggle history panel
    await expect(kb.getHeading('History')).not.toBeAttached();
    await page.getByTitle('More actions').click();
    await kb.getButton('History').click();
    await expect(kb.getHeading('History')).toBeVisible();

    // Revisions should be displayed with correct badges
    const revisions = page.getByTestId('revision');
    await expect(revisions).toHaveCount(3);
    await expect(revisions.nth(0).getByText('Current version')).toBeVisible();
    await expect(revisions.nth(1).getByText('Version 2')).toBeVisible();
    await expect(revisions.nth(2).getByText('Initial version')).toBeVisible();

    // Restore button should only be visible on non-current revisions
    await expect(revisions.nth(0).getByTitle('Restore this version')).not.toBeAttached();
    await expect(revisions.nth(1).getByTitle('Restore this version')).toBeVisible();
    await expect(revisions.nth(2).getByTitle('Restore this version')).toBeVisible();

    // Click restore on the initial version
    await revisions.nth(2).getByTitle('Restore this version').click();

    // Confirm in the Bootstrap modal
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();
    await expect(modal.getByText('Are you sure you want to restore this version?')).toBeVisible();
    await modal.getByRole('button', { name: 'Confirm' }).click();

    // Restored content should be visible after reload
    await expect(page.getByText('Original content')).toBeVisible();
});
