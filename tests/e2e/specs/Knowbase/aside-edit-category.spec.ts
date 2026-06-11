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

test('the category edit button reveals on hover and opens an inline edit panel', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Edit Cat ${unique}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name,
        entities_id: getWorkerEntityId(),
    });
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(seed_article_id);

    const category = kb.getAsideCategory(name);
    await kb.getAsideCategoryToggle(name).hover();

    const edit_button = kb.getEditCategoryButton(name);
    await expect(edit_button).toBeVisible();
    await edit_button.click();

    await expect(kb.getCategoryCommentInput(name)).toBeVisible();
    await expect(category.getByRole('button', { name: 'Select an illustration' })).toBeVisible();
    await expect(category.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
    await expect(category.getByRole('button', { name: 'Cancel', exact: true })).toBeVisible();
});

test('editing the category comment and saving persists it', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Edit Save Cat ${unique}`;
    const comment = `Helpful description ${unique}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name,
        entities_id: getWorkerEntityId(),
    });
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(seed_article_id);

    await kb.getAsideCategoryToggle(name).hover();
    await kb.getEditCategoryButton(name).click();

    await kb.getCategoryCommentInput(name).fill(comment);
    await kb.getAsideCategory(name).getByRole('button', { name: 'Save', exact: true }).click();

    await expect(kb.getCategoryCommentInput(name)).toBeHidden();

    await kb.goto(seed_article_id);
    await kb.getAsideCategoryToggle(name).hover();
    await kb.getEditCategoryButton(name).click();

    await expect(kb.getCategoryCommentInput(name)).toHaveValue(comment);
});

test('cancelling the category edit discards the changes', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Edit Cancel Cat ${unique}`;
    const original = `Original ${unique}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name,
        comment: original,
        entities_id: getWorkerEntityId(),
    });
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(seed_article_id);

    await kb.getAsideCategoryToggle(name).hover();
    await kb.getEditCategoryButton(name).click();

    await kb.getCategoryCommentInput(name).fill(`Changed ${unique}`);
    await kb.getAsideCategory(name).getByRole('button', { name: 'Cancel', exact: true }).click();

    await expect(kb.getCategoryCommentInput(name)).toBeHidden();

    await kb.goto(seed_article_id);
    await kb.getAsideCategoryToggle(name).hover();
    await kb.getEditCategoryButton(name).click();

    await expect(kb.getCategoryCommentInput(name)).toHaveValue(original);
});

test('the edit panel surfaces an error when saving fails', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Edit Fail Cat ${unique}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name,
        entities_id: getWorkerEntityId(),
    });
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(seed_article_id);

    await kb.getAsideCategoryToggle(name).hover();
    await kb.getEditCategoryButton(name).click();

    await page.route(
        `**/Knowbase/Aside/Category/${category_id}`,
        route => route.fulfill({ status: 500 }),
    );

    await kb.getCategoryCommentInput(name).fill('Will not save');
    await kb.getAsideCategory(name).getByRole('button', { name: 'Save', exact: true }).click();

    const category = kb.getAsideCategory(name);
    await expect(category.getByRole('alert')).toBeVisible();
    await expect(kb.getCategoryCommentInput(name)).toBeVisible();
});

test('clicking edit shows a toast when the edit panel cannot be opened', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Edit Open Fail Cat ${unique}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name,
        entities_id: getWorkerEntityId(),
    });
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(seed_article_id);

    await page.route(
        `**/Knowbase/Aside/Category/${category_id}/EditForm`,
        route => route.fulfill({ status: 403 }),
    );

    await kb.getAsideCategoryToggle(name).hover();
    await kb.getEditCategoryButton(name).click();

    await expect(
        page.getByRole('alert').filter({ hasText: 'You are not allowed to edit this category' }),
    ).toBeVisible();
    await expect(kb.getCategoryCommentInput(name)).toHaveCount(0);
});

test('the Uncategorized row has no edit button', async ({ page, profile, api }) => {
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
        name: /Edit Uncategorized/i,
    })).toHaveCount(0);
});

test('editing the illustration updates the tree node icon without a page reload', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const name = `E2E Edit Illus Cat ${unique}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name,
        entities_id: getWorkerEntityId(),
    });
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(seed_article_id);

    const url_before = page.url();

    await kb.getAsideCategoryToggle(name).hover();
    await kb.getEditCategoryButton(name).click();

    const category = kb.getAsideCategory(name);
    await category.getByRole('button', { name: 'Select an illustration' }).click();

    // Scope to the category edit form's picker: the article view already renders
    // its own illustration picker modal, so a page-wide lookup is ambiguous.
    const modal = category.getByTestId('illustration-picker-modal');
    await expect(modal).toBeVisible();
    await modal.getByRole('img', { name: 'Antivirus', exact: true }).click();
    await expect(modal).toBeHidden();

    await category.getByRole('button', { name: 'Save', exact: true }).click();

    // The edit panel closes and the node's own icon refreshes in place — without
    // a reload. Scope to the toggle button, which holds only the category's
    // illustration (child article icons live in the same group but outside it).
    await expect(kb.getCategoryCommentInput(name)).toBeHidden();
    const node_header = kb.getAsideCategoryToggle(name);
    await expect(node_header.getByRole('img', { name: 'Antivirus' })).toBeVisible();
    await expect(node_header.getByRole('img', { name: 'Knowledge base and FAQ' })).not.toBeAttached();
    expect(page.url()).toBe(url_before);
});
