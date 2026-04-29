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

test('Can delete an article', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'My kb entry for delete test',
        entities_id: getWorkerEntityId(),
        answer: "My answer to delete",
    });

    await kb.goto(id);
    await page.getByTitle('More actions').click();

    // Delete article
    const delete_button = kb.getButton('Delete article');
    await expect(delete_button).toBeVisible();
    await delete_button.click();

    // Confirm deletion in dialog
    const confirm_button = page.getByRole('button', { name: 'Delete' });
    await expect(confirm_button).toBeVisible();
    await confirm_button.click();

    // Should be redirected to knowbase list
    await expect(page).toHaveURL(/\/front\/knowbaseitem\.php/);
    await expect(page.getByText('Item successfully deleted.')).toBeVisible();

    // Article should no longer exist
    await kb.goto(id);
    await expect(page.getByText('The requested item has not been found')).toBeVisible();
});
