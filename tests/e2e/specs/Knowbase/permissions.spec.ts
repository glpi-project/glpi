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
import { getWorkerEntityId, getWorkerUserId } from "../../utils/WorkerEntities";

// glpi superadmin (ID 2) is never a worker account (workers start at ID 8)
const GLPI_ADMIN_USER_ID = 2;

test('Can view permissions modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for permissions test',
        entities_id: entity_id,
        users_id: getWorkerUserId(),
        answer: "Test content",
    });
    await api.createItem('Entity_KnowbaseItem', {
        knowbaseitems_id: id,
        entities_id: entity_id,
        is_recursive: 0,
    });

    await kb.goto(id);
    await expect(page.getByText('Test content')).toBeVisible();

    const modal = await kb.doOpenPermissionsModal();

    await expect(modal.getByRole('button', { name: 'Delete' })).toBeVisible();
});

test('Can delete a permission from the modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for permission delete test',
        entities_id: entity_id,
        users_id: getWorkerUserId(),
        answer: "Test content",
    });
    await api.createItem('Entity_KnowbaseItem', {
        knowbaseitems_id: id,
        entities_id: entity_id,
        is_recursive: 0,
    });

    await kb.goto(id);
    const modal = await kb.doOpenPermissionsModal();

    const deleteBtn = modal.getByRole('button', { name: 'Delete' });
    await expect(deleteBtn).toBeVisible();

    await deleteBtn.click();

    await expect(deleteBtn).not.toBeAttached();
});

test('Displays all permission types with correct badges', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for display test',
        entities_id: entity_id,
        users_id: getWorkerUserId(),
        answer: "Test content",
    });

    // Create one permission of each type
    await api.createItem('Entity_KnowbaseItem', {
        knowbaseitems_id: id,
        entities_id: entity_id,
        is_recursive: 1,
    });
    const group_name = `Test group ${crypto.randomUUID()}`;
    const group_id = await api.createItem('Group', {
        name: group_name,
        entities_id: entity_id,
    });
    await api.createItem('Group_KnowbaseItem', {
        knowbaseitems_id: id,
        groups_id: group_id,
        entities_id: entity_id,
        is_recursive: 0,
    });
    await api.createItem('KnowbaseItem_Profile', {
        knowbaseitems_id: id,
        profiles_id: Profiles.Technician,
        entities_id: entity_id,
        is_recursive: 1,
    });
    await api.createItem('KnowbaseItem_User', {
        knowbaseitems_id: id,
        users_id: GLPI_ADMIN_USER_ID,
    });

    await kb.goto(id);
    const modal = await kb.doOpenPermissionsModal();

    const list = modal.getByRole('list');

    // Entity row — PermissionsRenderer::buildEntries() renders the entity breadcrumb
    // as entity_name context on Group/Profile rows too, so we exclude them explicitly.
    const entity_row = list.getByRole('listitem')
        .filter({ hasText: 'E2E worker entity' })
        .filter({ hasNotText: group_name })
        .filter({ hasNotText: 'Technician' });
    await expect(entity_row).toBeVisible();
    await expect(entity_row.getByText('Can view')).toBeVisible();
    await expect(entity_row.getByLabel('Recursive')).toBeVisible();

    // Group permission - not recursive
    const group_row = list.getByRole('listitem').filter({ hasText: group_name });
    await expect(group_row).toBeVisible();
    await expect(group_row.getByText('Can view')).toBeVisible();
    await expect(group_row.getByLabel('Recursive')).not.toBeAttached();

    // Profile permission - recursive
    const profile_row = list.getByRole('listitem').filter({ hasText: 'Technician' });
    await expect(profile_row).toBeVisible();
    await expect(profile_row.getByText('Can view')).toBeVisible();
    await expect(profile_row.getByLabel('Recursive')).toBeVisible();

    // User permission (rendered with user picture, not typed icon)
    const user_row = list.getByRole('listitem').filter({ hasText: 'glpi' });
    await expect(user_row).toBeVisible();
    await expect(user_row.getByText('Can view')).toBeVisible();

    // Owner is displayed in the footer
    // eslint-disable-next-line playwright/no-raw-locators -- Non-interactive footer section, no ARIA landmark applies
    const owner_section = modal.locator('.kb-permission-owner');
    await expect(owner_section).toBeVisible();
    await expect(owner_section.getByText('Owner')).toBeVisible();
});

test('Can add an Entity permission via the modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for add entity test',
        entities_id: entity_id,
        users_id: getWorkerUserId(),
        answer: "Test content",
    });

    await kb.goto(id);
    const modal = await kb.doOpenPermissionsModal();

    // Select "Entity" from the type dropdown
    const type_dropdown = kb.getDropdownByLabel('Add a target', modal);
    await kb.doSetDropdownValue(type_dropdown, 'Entity');

    // Wait for the AJAX-loaded entity dropdown to appear (pre-filled with active entity).
    // The Target region may also render an "is_recursive" Yes/No combobox (see ajax/visibility.php),
    // so we scope to first() — the target dropdown is always rendered before is_recursive.
    const target_combobox = modal.getByLabel('Target').getByRole('combobox').first();
    await expect(target_combobox).toBeVisible();

    // Submit the form — entity dropdown is already pre-filled with the active entity
    const load_promise = page.waitForEvent('load');
    await modal.getByRole('button', { name: 'Add' }).click();
    await load_promise;

    // Reopen modal to verify
    await kb.goto(id);
    const reopened_modal = await kb.doOpenPermissionsModal();

    // Verify the new entity permission appears in the list
    const entity_row = reopened_modal.getByRole('list').getByRole('listitem').filter({ hasText: 'E2E worker entity' });
    await expect(entity_row).toBeVisible();
    await expect(entity_row.getByText('Can view')).toBeVisible();
});

test('Can add a User permission via the modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for add user test',
        entities_id: entity_id,
        users_id: getWorkerUserId(),
        answer: "Test content",
    });

    await kb.goto(id);
    const modal = await kb.doOpenPermissionsModal();

    // Select "User" from the type dropdown
    const type_dropdown = kb.getDropdownByLabel('Add a target', modal);
    await kb.doSetDropdownValue(type_dropdown, 'User');

    // Wait for the AJAX-loaded user dropdown to appear.
    // The Target region only renders one combobox for User (no is_recursive), but we keep
    // first() for consistency with the Entity case where two comboboxes are rendered.
    const user_combobox = modal.getByLabel('Target').getByRole('combobox').first();
    await expect(user_combobox).toBeVisible();

    // Select "glpi" user from the user dropdown (name may include extra info)
    await kb.doSearchAndClickDropdownValue(user_combobox, 'glpi', false);

    // Submit
    const load_promise = page.waitForEvent('load');
    await modal.getByRole('button', { name: 'Add' }).click();
    await load_promise;

    // Reopen modal to verify
    await kb.goto(id);
    const reopened_modal = await kb.doOpenPermissionsModal();

    const user_row = reopened_modal.getByRole('list').getByRole('listitem').filter({ hasText: 'glpi' });
    await expect(user_row).toBeVisible();
    await expect(user_row.getByText('Can view')).toBeVisible();
});

test('Can delete permissions for all target types', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for delete all types test',
        entities_id: entity_id,
        users_id: getWorkerUserId(),
        answer: "Test content",
    });

    const group_name = `Delete test group ${crypto.randomUUID()}`;
    const group_id = await api.createItem('Group', {
        name: group_name,
        entities_id: entity_id,
    });
    await api.createItem('Group_KnowbaseItem', {
        knowbaseitems_id: id,
        groups_id: group_id,
        entities_id: entity_id,
        is_recursive: 0,
    });
    await api.createItem('KnowbaseItem_Profile', {
        knowbaseitems_id: id,
        profiles_id: Profiles.Technician,
        entities_id: entity_id,
        is_recursive: 0,
    });

    await kb.goto(id);
    const modal = await kb.doOpenPermissionsModal();

    const list = modal.getByRole('list');

    // Delete the group permission
    const group_row = list.getByRole('listitem').filter({ hasText: group_name });
    await group_row.getByRole('button', { name: 'Delete' }).click();
    await expect(group_row).not.toBeAttached();

    // Delete the profile permission (wait for row to be stable after previous deletion)
    const profile_row = list.getByRole('listitem').filter({ hasText: 'Technician' });
    await expect(profile_row).toBeVisible();
    await profile_row.getByRole('button', { name: 'Delete' }).click();
    await expect(profile_row).not.toBeAttached();

    // Verify the list is now empty
    await expect(list.getByRole('listitem')).toHaveCount(0);
});

test('Non-owner sees danger confirmation when deleting permission', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();

    // Create a KB item owned by a different user than the current worker
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for non-owner delete test',
        entities_id: entity_id,
        answer: "Test content",
        users_id: GLPI_ADMIN_USER_ID,
    });
    await api.createItem('Entity_KnowbaseItem', {
        knowbaseitems_id: id,
        entities_id: entity_id,
        is_recursive: 0,
    });

    await kb.goto(id);
    const modal = await kb.doOpenPermissionsModal();

    const deleteBtn = modal.getByRole('button', { name: 'Delete' });
    await deleteBtn.click();

    // Confirmation dialog should appear
    const confirm_dialog = page.getByRole('dialog').filter({ hasText: 'Delete target' });
    await expect(confirm_dialog).toBeVisible();
    await expect(confirm_dialog.getByText('Caution!')).toBeVisible();

    // Confirm the deletion
    await confirm_dialog.getByRole('button', { name: 'Delete' }).click();

    // The permission row should be removed
    await expect(modal.getByRole('list').getByRole('listitem')).toHaveCount(0);
});

test('User without UPDATE right cannot access permissions modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for readonly test',
        entities_id: entity_id,
        users_id: getWorkerUserId(),
        answer: "Test content",
    });

    // Switch to a profile with READ but no UPDATE on knowbase
    await profile.set(Profiles.Observer);

    await kb.goto(id);

    // Wait for article to render
    await expect(page.getByText('Test content')).toBeVisible();

    // "Targets" is gated behind UPDATE right in getEditorActions()
    // so the action should not exist in the DOM at all for Observer profile
    await expect(kb.getButton('Targets')).not.toBeAttached();
});

test('Owner is displayed in footer and not duplicated in list', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const entity_id = getWorkerEntityId();
    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for owner footer test',
        entities_id: entity_id,
        users_id: getWorkerUserId(),
        answer: "Test content",
    });

    await kb.goto(id);
    const modal = await kb.doOpenPermissionsModal();

    // Owner is shown in footer
    // eslint-disable-next-line playwright/no-raw-locators -- Non-interactive footer section, no ARIA landmark applies
    const owner_section = modal.locator('.kb-permission-owner');
    await expect(owner_section).toBeVisible();
    await expect(owner_section.getByText('Owner')).toBeVisible();

    // No permissions in the list (owner is excluded, no other targets)
    const list = modal.getByRole('list');
    await expect(list.getByRole('listitem')).toHaveCount(0);
});
