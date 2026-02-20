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

test('Can view permissions modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for permissions test',
        entities_id: entity_id,
        answer: "Test content",
    });
    await api.createItem('Entity_KnowbaseItem', {
        knowbaseitems_id: id,
        entities_id: entity_id,
        is_recursive: 0,
    });

    await kb.goto(id);
    await expect(page.getByText('Test content')).toBeVisible();

    await page.getByTitle('More actions').click();
    await kb.getButton('Targets').click();

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    const permission_row = modal.locator('[data-glpi-permission-id]');
    await expect(permission_row).toBeVisible();
    await expect(permission_row.locator('.badge', { hasText: 'Entity' })).toBeVisible();
});

test('Can delete a permission from the modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for permission delete test',
        entities_id: entity_id,
        answer: "Test content",
    });
    await api.createItem('Entity_KnowbaseItem', {
        knowbaseitems_id: id,
        entities_id: entity_id,
        is_recursive: 0,
    });

    await kb.goto(id);
    await page.getByTitle('More actions').click();
    await kb.getButton('Targets').click();

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    const entity_row = modal.locator('[data-glpi-permission-itemtype="Entity_KnowbaseItem"]');
    await expect(entity_row).toBeVisible();
    await expect(entity_row.locator('.badge', { hasText: 'Entity' })).toBeVisible();

    await entity_row.locator('[data-glpi-permission-delete]').click();

    await expect(entity_row).not.toBeAttached();
});
