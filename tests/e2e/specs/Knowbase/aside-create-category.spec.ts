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

test('creating a sub-category inline adds it under the parent without a dialog', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Aside Parent Cat ${unique}`;
    const child_name = `E2E Aside Sub Cat ${unique}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: parent_name,
        entities_id: getWorkerEntityId(),
    });

    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [parent_id],
    });

    await kb.goto(seed_article_id);

    await kb.getAsideCategoryToggle(parent_name).hover();
    await kb.getCreateSubCategoryButton(parent_name).click();

    await expect(page.getByRole('dialog')).toHaveCount(0);

    const input = kb.getCategoryNameInput();
    await expect(input).toBeFocused();
    await input.fill(child_name);
    await input.press('Enter');

    await expect(
        kb.getAsideCategory(parent_name).getByRole('group', { name: child_name }),
    ).toBeVisible();
});

test('creating a root category inline adds it at the tree root without a dialog', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Aside Root Cat ${unique}`;

    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(seed_article_id);

    await kb.getCreateRootCategoryButton().click();

    await expect(page.getByRole('dialog')).toHaveCount(0);

    const input = kb.getCategoryNameInput();
    await expect(input).toBeFocused();
    await input.fill(name);
    await input.press('Enter');

    await expect(kb.getAsideCategory(name)).toBeVisible();
});

test('pressing Enter with an empty name shows an inline validation error', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);

    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(seed_article_id);

    await kb.getCreateRootCategoryButton().click();

    const input = kb.getCategoryNameInput();
    await input.press('Enter');

    await expect(
        page.getByRole('main')
            .getByRole('complementary')
            .getByRole('alert')
            .filter({ hasText: 'Title is mandatory' }),
    ).toBeVisible();
    await expect(input).toBeVisible();
});

test('pressing Escape cancels the inline creation', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Aside Escape Cat ${unique}`;

    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(seed_article_id);

    await kb.getCreateRootCategoryButton().click();

    const input = kb.getCategoryNameInput();
    await input.fill(name);
    await input.press('Escape');

    await expect(kb.getCategoryNameInput()).toHaveCount(0);
    await expect(kb.getAsideCategory(name)).toHaveCount(0);
});

test('clicking away cancels the inline creation', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Aside Blur Cat ${unique}`;

    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(seed_article_id);

    await kb.getCreateRootCategoryButton().click();

    const input = kb.getCategoryNameInput();
    await input.fill(name);
    await kb.asideSearchInput.click();

    await expect(kb.getCategoryNameInput()).toHaveCount(0);
    await expect(kb.getAsideCategory(name)).toHaveCount(0);
});

test('the Uncategorized row has no create-sub-category button', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);

    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(seed_article_id);

    const uncategorized = kb.getAsideCategory('Uncategorized');
    await expect(uncategorized).toBeVisible();
    await expect(uncategorized.getByRole('button', {
        name: /Create a sub-category in Uncategorized/i,
    })).toHaveCount(0);
});

test('hovering a sub-category does not reveal the parent category create-sub-category button', async ({ page, profile, api }) => {
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
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [child_id],
    });

    await kb.goto(seed_article_id);

    const parent_create = kb.getCreateSubCategoryButton(parent_name);
    const child_create = kb.getCreateSubCategoryButton(child_name);

    await kb.getAsideCategoryToggle(child_name).hover();

    await expect(child_create).toBeVisible();
    await expect(parent_create).toBeHidden();
});
