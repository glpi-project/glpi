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

    await expect(modal.getByRole('button', { name: 'Delete' })).toBeVisible();
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

    const deleteBtn = modal.getByRole('button', { name: 'Delete' });
    await expect(deleteBtn).toBeVisible();

    await deleteBtn.click();

    await expect(deleteBtn).not.toBeAttached();
});

test('Selecting a target type injects the matching visibility picker', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for target type injection test',
        entities_id: entity_id,
        answer: 'Test content',
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

    await expect(modal.getByRole('combobox')).toHaveCount(1);
    await expect(modal.getByRole('button', { name: 'Add' })).toBeHidden();

    const type_dropdown = modal.getByRole('combobox').first();
    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: 'User', exact: true }).click();

    await expect(modal.getByRole('combobox')).toHaveCount(2);
    await expect(modal.getByRole('button', { name: 'Add' })).toBeVisible();
});

test('Resetting the target type clears the visibility picker and hides Add', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for target type reset test',
        entities_id: entity_id,
        answer: 'Test content',
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

    const type_dropdown = modal.getByRole('combobox').first();
    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: 'User', exact: true }).click();

    await expect(modal.getByRole('combobox')).toHaveCount(2);
    await expect(modal.getByRole('button', { name: 'Add' })).toBeVisible();

    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: '-----' }).click();

    await expect(modal.getByRole('combobox')).toHaveCount(1);
    await expect(modal.getByRole('button', { name: 'Add' })).toBeHidden();
});

test('Selecting a Group value loads the entity sub-picker with Child entities label', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const group_name = `KB target group ${crypto.randomUUID()}`;
    await api.createItem('Group', {
        name: group_name,
        entities_id: entity_id,
        is_recursive: 1,
    });

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for group target type test',
        entities_id: entity_id,
        answer: 'Test content',
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

    await expect(modal.getByRole('combobox')).toHaveCount(1);

    const type_dropdown = modal.getByRole('combobox').first();
    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: 'Group', exact: true }).click();

    // Selecting "Group" only injects the group picker; the entity sub-picker is
    // loaded by a second AJAX call once an actual group value is picked.
    await expect(modal.getByRole('combobox')).toHaveCount(2);

    const group_dropdown = modal.getByRole('combobox').nth(1);
    await group_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: group_name }).click();

    await expect(modal.getByRole('combobox')).toHaveCount(4);
    await expect(modal.getByText('Child entities')).toBeVisible();
    await expect(modal.getByRole('button', { name: 'Add' })).toBeVisible();
});

test('Selecting Entity target type renders entity dropdown with Child entities label', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for entity target type test',
        entities_id: entity_id,
        answer: 'Test content',
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

    const type_dropdown = modal.getByRole('combobox').first();
    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: 'Entity', exact: true }).click();

    await expect(modal.getByRole('combobox')).toHaveCount(3);
    await expect(modal.getByText('Child entities')).toBeVisible();
    await expect(modal.getByRole('button', { name: 'Add' })).toBeVisible();
});

test('Adding a Group target persists it in the targets list', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const group_name = `KB target group ${crypto.randomUUID()}`;
    await api.createItem('Group', {
        name: group_name,
        entities_id: entity_id,
        is_recursive: 1,
    });

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for group target add test',
        entities_id: entity_id,
        answer: 'Test content',
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

    const type_dropdown = modal.getByRole('combobox').first();
    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: 'Group', exact: true }).click();

    // Group type renders the group picker first; the entity sub-picker arrives
    // only after an actual group value is chosen.
    await expect(modal.getByRole('combobox')).toHaveCount(2);

    const group_dropdown = modal.getByRole('combobox').nth(1);
    await group_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: group_name }).click();

    // Wait for the entity sub-picker AJAX to complete before submitting,
    // otherwise the form may post stale (unset) entity values.
    await expect(modal.getByRole('combobox')).toHaveCount(4);

    // The Add button is a real <input type="submit"> that POSTs to
    // /front/knowbaseitem.form.php, which reloads the page and closes the
    // modal. Reopen it to verify persistence.
    await modal.getByRole('button', { name: 'Add' }).click();

    await page.getByTitle('More actions').click();
    await kb.getButton('Targets').click();

    await expect(modal).toBeVisible();
    await expect(modal.getByText(group_name)).toBeVisible();
});
