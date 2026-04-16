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
    const group_id = await api.createItem('Group', {
        name: `Test group ${crypto.randomUUID()}`,
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

    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const list = modal.locator('[data-glpi-permissions-list]');

    // Entity permission - recursive
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const entity_row = list.locator('[data-glpi-permission-itemtype="Entity_KnowbaseItem"]');
    await expect(entity_row).toBeVisible();
    await expect(entity_row.getByText('Can view')).toBeVisible();
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    await expect(entity_row.locator('[data-glpi-permission-recursive]')).toBeVisible();

    // Group permission - not recursive
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const group_row = list.locator('[data-glpi-permission-itemtype="Group_KnowbaseItem"]');
    await expect(group_row).toBeVisible();
    await expect(group_row.getByText('Can view')).toBeVisible();
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    await expect(group_row.locator('[data-glpi-permission-recursive]')).not.toBeAttached();

    // Profile permission - recursive
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const profile_row = list.locator('[data-glpi-permission-itemtype="KnowbaseItem_Profile"]');
    await expect(profile_row).toBeVisible();
    await expect(profile_row.getByText('Can view')).toBeVisible();
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    await expect(profile_row.locator('[data-glpi-permission-recursive]')).toBeVisible();

    // User permission (rendered with user picture, not typed icon)
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const user_row = list.locator('[data-glpi-permission-itemtype="KnowbaseItem_User"]');
    await expect(user_row).toBeVisible();
    await expect(user_row.getByText('Can view')).toBeVisible();

    // Owner is displayed in the footer
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const owner_section = modal.locator('[data-glpi-permission-owner]');
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

    // Wait for the AJAX-loaded entity dropdown to appear (pre-filled with active entity)
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const visibility_container = modal.locator('[data-glpi-permission-add-visibility]');
    await expect(visibility_container.getByRole('combobox').first()).toBeVisible();

    // Submit the form — entity dropdown is already pre-filled with the active entity
    const load_promise = page.waitForEvent('load');
    // eslint-disable-next-line playwright/no-raw-locators -- Must target native submit input by name for form submission
    await modal.locator('input[name="addvisibility"]').click();
    await load_promise;

    // Reopen modal to verify
    await kb.goto(id);
    const reopened_modal = await kb.doOpenPermissionsModal();

    // Verify the new entity permission appears in the list
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const entity_row = reopened_modal.locator('[data-glpi-permission-itemtype="Entity_KnowbaseItem"]');
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

    // Wait for the AJAX-loaded user dropdown to appear
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const visibility_container = modal.locator('[data-glpi-permission-add-visibility]');
    await expect(visibility_container.getByRole('combobox').first()).toBeVisible();

    // Select "glpi" user from the user dropdown (name may include extra info)
    const user_combobox = visibility_container.getByRole('combobox').first();
    await kb.doSearchAndClickDropdownValue(user_combobox, 'glpi', false);

    // Submit
    const load_promise = page.waitForEvent('load');
    // eslint-disable-next-line playwright/no-raw-locators -- Must target native submit input by name for form submission
    await modal.locator('input[name="addvisibility"]').click();
    await load_promise;

    // Reopen modal to verify
    await kb.goto(id);
    const reopened_modal = await kb.doOpenPermissionsModal();

    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const user_row = reopened_modal.locator('[data-glpi-permission-itemtype="KnowbaseItem_User"]');
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

    const group_id = await api.createItem('Group', {
        name: `Delete test group ${crypto.randomUUID()}`,
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

    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const list = modal.locator('[data-glpi-permissions-list]');

    // Delete the group permission
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const group_row = list.locator('[data-glpi-permission-itemtype="Group_KnowbaseItem"]');
    await group_row.getByRole('button', { name: 'Delete' }).click();
    await expect(group_row).not.toBeAttached();

    // Delete the profile permission (wait for row to be stable after previous deletion)
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const profile_row = list.locator('[data-glpi-permission-itemtype="KnowbaseItem_Profile"]');
    await expect(profile_row).toBeVisible();
    await profile_row.getByRole('button', { name: 'Delete' }).click();
    await expect(profile_row).not.toBeAttached();

    // Verify the list is now empty
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    await expect(list.locator('[data-glpi-permission-id]')).toHaveCount(0);
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
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    await expect(modal.locator('[data-glpi-permission-itemtype="Entity_KnowbaseItem"]')).not.toBeAttached();
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
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const owner_section = modal.locator('[data-glpi-permission-owner]');
    await expect(owner_section).toBeVisible();
    await expect(owner_section.getByText('Owner')).toBeVisible();

    // No permissions in the list (owner is excluded, no other targets)
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    const list = modal.locator('[data-glpi-permissions-list]');
    // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute rendered by permissions.html.twig
    await expect(list.locator('[data-glpi-permission-id]')).toHaveCount(0);
});
