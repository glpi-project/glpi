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

test('clicking the aside add-article link creates a new article linked to the category', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const category_name = `E2E Aside Cat ${unique}`;
    const article_title = `E2E Aside Article ${unique}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name: category_name,
        entities_id: getWorkerEntityId(),
    });

    // Seed an article so the aside renders the category in the tree
    await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(1);

    const add_link = kb.getAsideCategory(category_name).getByRole('link', {
        name: new RegExp(`Create an article in ${category_name}`, 'i'),
    });
    await expect(add_link).toBeVisible();
    await add_link.click();

    await expect(page).toHaveURL(new RegExp(`knowbaseitemcategories_id=${category_id}`));
    await expect(page).toHaveURL(/knowbaseitem\.form\.php/);

    // The article's category meta-line should reflect the prefilled category
    await expect(
        page.getByRole('group', { name: 'Article category' }).getByRole('link')
    ).toHaveText(category_name);
    const add_button = page.getByRole('button', { name: 'Add article' });

    // Fill in the title
    const title = page.getByTestId('subject');
    await title.click();
    await title.fill('');
    await page.keyboard.type(article_title);

    // Fill in the content
    // eslint-disable-next-line playwright/no-raw-locators -- Tiptap editor has no semantic label
    const editor = page.locator('#kb-tiptap-editor .ProseMirror');
    await editor.click();
    await page.keyboard.type('Body created from aside add-link.');

    await add_button.click();

    // After save we land on the new article view
    await expect(page.getByTestId('subject')).toHaveText(article_title);

    // Verify the article is now displayed under the chosen category in the aside
    await kb.goto(1);
    const category_node = kb.getAsideCategory(category_name);
    await expect(category_node.getByRole('link', { name: article_title })).toBeVisible();
});

test('clicking the aside add-article link on Uncategorized creates an article without a category', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const article_title = `E2E Uncategorized Article ${unique}`;

    await kb.goto(1);

    const uncategorized = kb.getAsideCategory('Uncategorized');
    const add_link = uncategorized.getByRole('link', {
        name: /Create an article in Uncategorized/i,
    });
    await expect(add_link).toBeVisible();
    await add_link.click();

    await expect(page).toHaveURL(/knowbaseitem\.form\.php/);
    await expect(page).not.toHaveURL(/knowbaseitemcategories_id=/);

    // The category meta-line should show "Uncategorized" when no category is prefilled
    await expect(
        page.getByRole('group', { name: 'Article category' }).getByRole('link')
    ).toHaveText('Uncategorized');
    const add_button = page.getByRole('button', { name: 'Add article' });

    const title = page.getByTestId('subject');
    await title.click();
    await title.fill('');
    await page.keyboard.type(article_title);

    // eslint-disable-next-line playwright/no-raw-locators -- Tiptap editor has no semantic label
    const editor = page.locator('#kb-tiptap-editor .ProseMirror');
    await editor.click();
    await page.keyboard.type('Body created from uncategorized add-link.');

    await add_button.click();

    await expect(page.getByTestId('subject')).toHaveText(article_title);

    await kb.goto(1);
    const uncategorized_after = kb.getAsideCategory('Uncategorized');
    await expect(uncategorized_after.getByRole('link', { name: article_title })).toBeVisible();
});

test('hovering a sub-category does not reveal the parent category add-article link', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Hover Parent ${unique}`;
    const child_name = `E2E Hover Child ${unique}`;

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

    const parent_add = kb.getAsideCategory(parent_name).getByRole('link', {
        name: new RegExp(`Create an article in ${parent_name}`, 'i'),
    });
    const child_add = kb.getAsideCategory(child_name).getByRole('link', {
        name: new RegExp(`Create an article in ${child_name}`, 'i'),
    });

    await kb.getAsideCategoryToggle(child_name).hover();

    await expect(child_add).toHaveCSS('opacity', '1');
    await expect(parent_add).toHaveCSS('opacity', '0');
});

test('add mode shows prefilled category in meta line', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const category_name = `E2E Meta Cat ${unique}`;
    const category_id = await api.createItem('KnowbaseItemCategory', {
        name: category_name,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(1);

    const add_link = kb.getAsideCategory(category_name).getByRole('link', {
        name: new RegExp(`Create an article in ${category_name}`, 'i'),
    });
    await add_link.click();

    // The category meta link should show the prefilled category name
    const category_display = page.getByRole('group', { name: 'Article category' }).getByRole('link');
    await expect(category_display).toHaveText(category_name);
});

test('add mode allows changing the staged category via the bar', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const cat_a = `E2E Bar A ${unique}`;
    const cat_b = `E2E Bar B ${unique}`;
    const article_title = `E2E Bar Article ${unique}`;

    const cat_a_id = await api.createItem('KnowbaseItemCategory', {
        name: cat_a,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItemCategory', {
        name: cat_b,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [cat_a_id],
    });

    await kb.goto(1);

    // Start from "Add" on category A
    const add_link = kb.getAsideCategory(cat_a).getByRole('link', {
        name: new RegExp(`Create an article in ${cat_a}`, 'i'),
    });
    await add_link.click();

    const category_group = page.getByRole('group', { name: 'Article category' });
    await category_group.getByRole('link').click();

    const category_editor = page.getByRole('group', { name: 'Category editor' });
    const select = category_editor.getByLabel('Category');
    await expect(select).toBeVisible();
    await select.selectOption({ label: cat_b });

    await category_editor.getByRole('button', { name: 'Save categories' }).click();

    // Display now reflects cat_b
    await expect(category_group.getByRole('link')).toHaveText(cat_b);

    // Finish article creation
    const title = page.getByTestId('subject');
    await title.click();
    await title.fill('');
    await page.keyboard.type(article_title);
    // eslint-disable-next-line playwright/no-raw-locators -- Tiptap editor has no semantic label
    const editor = page.locator('#kb-tiptap-editor .ProseMirror');
    await editor.click();
    await page.keyboard.type('Body created with category swapped via bar.');

    await page.getByRole('button', { name: 'Add article' }).click();
    await expect(page.getByTestId('subject')).toHaveText(article_title);

    // Article should now live under cat_b (not cat_a)
    await kb.goto(1);
    await expect(
        kb.getAsideCategory(cat_b).getByRole('link', { name: article_title })
    ).toBeVisible();
});

test('edit mode updates categories via the bar (AJAX)', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const cat_x = `E2E Edit X ${unique}`;
    const cat_y = `E2E Edit Y ${unique}`;
    const article_title = `E2E Edit Article ${unique}`;

    const cat_x_id = await api.createItem('KnowbaseItemCategory', {
        name: cat_x,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItemCategory', {
        name: cat_y,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_title,
        answer: 'Edit body',
        entities_id: getWorkerEntityId(),
        _categories: [cat_x_id],
    });

    await kb.goto(article_id);

    const category_group = page.getByRole('group', { name: 'Article category' });
    const category_display = category_group.getByRole('link');
    await expect(category_display).toHaveText(cat_x);

    await category_display.click();

    const category_editor = page.getByRole('group', { name: 'Category editor' });
    const select = category_editor.getByLabel('Category');
    await expect(select).toBeVisible();
    // Select both cat_x and cat_y (multi mode in edit)
    await select.selectOption([{ label: cat_x }, { label: cat_y }]);

    const save_response = page.waitForResponse(
        response => response.url().includes('/UpdateCategories') && response.status() === 200
    );
    await category_editor.getByRole('button', { name: 'Save categories' }).click();
    await save_response;

    // Display now reflects both, comma-separated
    await expect(category_display).toContainText(cat_x);
    await expect(category_display).toContainText(cat_y);

    // Reload to confirm persistence
    await page.reload();
    await expect(category_display).toContainText(cat_x);
    await expect(category_display).toContainText(cat_y);
});
