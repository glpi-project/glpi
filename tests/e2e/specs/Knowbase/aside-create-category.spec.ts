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

import { randomUUID } from 'crypto';
import { expect, test } from '../../fixtures/glpi_fixture';
import { KnowbaseItemPage } from '../../pages/KnowbaseItemPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('clicking the aside create-sub-category link creates a category under the parent', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Aside Parent Cat ${unique}`;
    const child_name = `E2E Aside Sub Cat ${unique}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: parent_name,
        entities_id: getWorkerEntityId(),
    });

    await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [parent_id],
    });

    await kb.goto(1);

    const create_link = kb.getAsideCategory(parent_name).getByRole('link', {
        name: new RegExp(`Create a sub-category in ${parent_name}`, 'i'),
    });
    await expect(create_link).toBeVisible();
    await create_link.click();

    const dialog = page.getByRole('dialog', { name: 'Create a category' });
    await expect(dialog).toBeVisible();

    await expect(dialog.getByText(parent_name)).toBeVisible();

    await dialog.getByLabel('Name', { exact: true }).fill(child_name);
    await dialog.getByRole('button', { name: 'Create' }).click();

    await expect(dialog).toBeHidden();
    await expect(kb.getAlert('Category created')).toBeVisible();

    await kb.goto(1);
    const parent_node = kb.getAsideCategory(parent_name);
    await expect(parent_node.getByRole('group', { name: child_name })).toBeVisible();
});

test('submitting the modal with an empty name shows an inline validation error', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Aside Val Cat ${unique}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: parent_name,
        entities_id: getWorkerEntityId(),
    });

    await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [parent_id],
    });

    await kb.goto(1);

    await kb.getAsideCategory(parent_name).getByRole('link', {
        name: new RegExp(`Create a sub-category in ${parent_name}`, 'i'),
    }).click();

    const dialog = page.getByRole('dialog', { name: 'Create a category' });
    await expect(dialog).toBeVisible();

    await dialog.getByRole('button', { name: 'Create' }).click();

    await expect(dialog.getByRole('alert').filter({ hasText: 'Title is mandatory' })).toBeVisible();
    await expect(dialog).toBeVisible();
    await expect(kb.getAlert('Category created')).toHaveCount(0);
});

test('clicking Cancel closes the modal without creating a category', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Aside Cancel Cat ${unique}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: parent_name,
        entities_id: getWorkerEntityId(),
    });

    await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [parent_id],
    });

    await kb.goto(1);

    await kb.getAsideCategory(parent_name).getByRole('link', {
        name: new RegExp(`Create a sub-category in ${parent_name}`, 'i'),
    }).click();

    const dialog = page.getByRole('dialog', { name: 'Create a category' });
    await expect(dialog).toBeVisible();

    await dialog.getByRole('button', { name: 'Cancel' }).click();

    await expect(dialog).toBeHidden();
    await expect(kb.getAlert('Category created')).toHaveCount(0);
});

test('the Uncategorized row has no create-sub-category link', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    await kb.goto(1);

    const uncategorized = kb.getAsideCategory('Uncategorized');
    await expect(uncategorized).toBeVisible();
    await expect(uncategorized.getByRole('link', {
        name: /Create a sub-category in Uncategorized/i,
    })).toHaveCount(0);
});

test('hovering a sub-category does not reveal the parent category create-sub-category link', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Hover Cat Parent ${unique}`;
    const child_name = `E2E Hover Cat Child ${unique}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: parent_name,
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItemCategory', {
        name: child_name,
        knowbaseitemcategories_id: parent_id,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [child_id],
    });

    await kb.goto(1);

    const parent_create = kb.getAsideCategory(parent_name).getByRole('link', {
        name: new RegExp(`Create a sub-category in ${parent_name}`, 'i'),
    });
    const child_create = kb.getAsideCategory(child_name).getByRole('link', {
        name: new RegExp(`Create a sub-category in ${child_name}`, 'i'),
    });

    await kb.getAsideCategoryToggle(child_name).hover();

    await expect(child_create).toHaveCSS('opacity', '1');
    await expect(parent_create).toHaveCSS('opacity', '0');
});
