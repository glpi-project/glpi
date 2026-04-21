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
import { getUniqueName } from "../../utils/Random";
import { getWorkerEntityId, getWorkerEntityName } from "../../utils/WorkerEntities";

test('Can delete a permission from the modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create an article with one entity visibility rule.
    const id = await api.knowbase.createArticle();
    await api.knowbase.addEntityVisibility(id, getWorkerEntityId(), true);

    // Act: open the modal and click the delete button.
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await expect(page.getByTestId('permission-entry')).toHaveCount(1);
    await kb.getVisibilityModal().getByTestId('delete-permission').click();

    // Assert: the entry should be removed from the DOM.
    await expect(kb.getVisibilityModal().getByTestId('delete-permission')).not.toBeAttached();
    await expect(page.getByTestId('permission-entry')).toHaveCount(0);
});

test('Can add user permission', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();

    // Act: open the permission modal and add a user permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "User",
    );
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("User"),
        "glpi",
    );
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: user should be added to the permission list
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText("glpi");
    await expect(entry.getByTestId("entry-context")).not.toBeAttached();
});

test('Can add entity permission', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();

    // Act: open the permission modal and add a user permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "Entity",
    );
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Child entities"),
        "No",
    );
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: entity should be added to the permission list
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText(
        `Root entity > E2E tests entity > ${getWorkerEntityName()}`
    );
    await expect(entry.getByTestId("entry-context")).not.toBeAttached();
});

test('Can add entity permission (recursive)', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();

    // Act: open the permission modal and add an entity permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "Entity",
    );
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: entity should be added to the permission list
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText(
        `Root entity > E2E tests entity > ${getWorkerEntityName()} (recursive)`
    );
    await expect(entry.getByTestId("entry-context")).not.toBeAttached();
});

test('Can add group permission', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();
    const group_name = getUniqueName("My group");
    await api.createItem('Group', {
        name: group_name,
        entities_id: getWorkerEntityId(),
    });

    // Act: open the permission modal and add a group permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "Group",
    );
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Group"),
        group_name,
    );
    await expect(kb.getDropdownByLabel("Entity")).toBeHidden();
    await expect(kb.getDropdownByLabel("Child entities")).toBeHidden();
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: group should be added to the permission list
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText(group_name);
    await expect(entry.getByTestId("entry-context")).not.toBeAttached();
});

test('Can add group permission with recursive context', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();
    const group_name = getUniqueName("My group");
    await api.createItem('Group', {
        name: group_name,
        entities_id: getWorkerEntityId(),
    });

    // Act: open the permission modal and add a group permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "Group",
    );
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Group"),
        group_name,
    );
    await page.getByRole('button', { name: "Show advanced options" }).click();
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Entity"),
        getWorkerEntityName(),
    );
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Child entities"),
        "Yes",
    );
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: group should be added to the permission list with context
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText(group_name);
    await expect(entry.getByTestId("entry-context")).toHaveText(
        `Root entity > E2E tests entity > ${getWorkerEntityName()} (recursive)`
    );
});

test('Can add group permission with context', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();
    const group_name = getUniqueName("My group");
    await api.createItem('Group', {
        name: group_name,
        entities_id: getWorkerEntityId(),
    });

    // Act: open the permission modal and add a group permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "Group",
    );
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Group"),
        group_name,
    );
    await page.getByRole('button', { name: "Show advanced options" }).click();
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Entity"),
        getWorkerEntityName(),
    );
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Child entities"),
        "No",
    );
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: group should be added to the permission list with context
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText(group_name);
    await expect(entry.getByTestId("entry-context")).toHaveText(
        `Root entity > E2E tests entity > ${getWorkerEntityName()}`
    );
});

test('Can add profile permission', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();

    // Act: open the permission modal and add a profile permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "Profile",
    );
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Profile"),
        "Admin",
    );
    await expect(kb.getDropdownByLabel("Entity")).toBeHidden();
    await expect(kb.getDropdownByLabel("Child entities")).toBeHidden();
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: profile should be added to the permission list
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText("Admin");
    await expect(entry.getByTestId("entry-context")).not.toBeAttached();
});

test('Can add profile permission with recursive context', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();

    // Act: open the permission modal and add a profile permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "Profile",
    );
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Profile"),
        "Observer",
    );
    await page.getByRole('button', { name: "Show advanced options" }).click();
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Entity"),
        getWorkerEntityName(),
    );
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Child entities"),
        "Yes",
    );
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: profile should be added to the permission list with context
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText("Observer");
    await expect(entry.getByTestId("entry-context")).toHaveText(
        `Root entity > E2E tests entity > ${getWorkerEntityName()} (recursive)`
    );
});

test('Can add profile permission with context', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Arrange: create a KB article without permissions
    const id = await api.knowbase.createArticle();

    // Act: open the permission modal and add a profile permission
    await kb.goto(id);
    await kb.doOpenVisibilityModal();
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Add access for"),
        "Profile",
    );
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Profile"),
        "Observer",
    );
    await page.getByRole('button', { name: "Show advanced options" }).click();
    await kb.doSearchAndClickDropdownValue(
        kb.getDropdownByLabel("Entity"),
        getWorkerEntityName(),
    );
    await kb.doSetDropdownValue(
        kb.getDropdownByLabel("Child entities"),
        "No",
    );
    await page.getByRole('dialog').getByRole('button', { name: "Add " }).click();

    // Assert: profile should be added to the permission list with context
    await kb.doOpenVisibilityModal();
    const entry = page.getByTestId('permission-entry');
    await expect(entry.getByTestId("entry-label")).toHaveText("Observer");
    await expect(entry.getByTestId("entry-context")).toHaveText(
        `Root entity > E2E tests entity > ${getWorkerEntityName()}`
    );
});
