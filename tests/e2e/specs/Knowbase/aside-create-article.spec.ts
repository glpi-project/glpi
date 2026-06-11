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

    // Seed an article so the aside renders the category in the tree.
    // We navigate to this article (in the worker entity) because the aside is
    // only rendered on a viewable article page; a hardcoded id is not reliable
    // across a fresh CI database.
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(seed_article_id);

    const add_link = kb.getAsideCategory(category_name).getByRole('link', {
        name: new RegExp(`Create an article in ${category_name}`, 'i'),
    });
    // Reveal the action button (hidden until the category row is hovered).
    await kb.getAsideCategoryToggle(category_name).hover();
    await expect(add_link).toBeVisible();
    await add_link.click();

    await expect(page).toHaveURL(new RegExp(`knowbaseitemcategories_id=${category_id}`));
    await expect(page).toHaveURL(/knowbaseitem\.form\.php/);

    const add_button = page.getByRole('button', { name: 'Add article' });
    await expect(add_button).toHaveAttribute('data-glpi-kb-prefilled-category-id', String(category_id));

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
    await kb.goto(seed_article_id);
    const category_node = kb.getAsideCategory(category_name);
    await expect(category_node.getByRole('link', { name: article_title })).toBeVisible();
});

test('clicking the aside add-article link on Uncategorized creates an article without a category', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const article_title = `E2E Uncategorized Article ${unique}`;

    // Seed an uncategorized article so the aside renders on a viewable page.
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(seed_article_id);

    const uncategorized = kb.getAsideCategory('Uncategorized');
    const add_link = uncategorized.getByRole('link', {
        name: /Create an article in Uncategorized/i,
    });
    // Reveal the action button (hidden until the category row is hovered).
    await kb.getAsideCategoryToggle('Uncategorized').hover();
    await expect(add_link).toBeVisible();
    await add_link.click();

    await expect(page).toHaveURL(/knowbaseitem\.form\.php/);
    await expect(page).not.toHaveURL(/knowbaseitemcategories_id=/);

    const add_button = page.getByRole('button', { name: 'Add article' });
    await expect(add_button).not.toHaveAttribute('data-glpi-kb-prefilled-category-id');

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

    await kb.goto(seed_article_id);
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
    const seed_article_id = await api.createItem('KnowbaseItem', {
        name: `Seed ${unique}`,
        answer: 'Seed content',
        entities_id: getWorkerEntityId(),
        _categories: [child_id],
    });

    await kb.goto(seed_article_id);

    const parent_add = kb.getAsideCategory(parent_name).getByRole('link', {
        name: new RegExp(`Create an article in ${parent_name}`, 'i'),
    });
    const child_add = kb.getAsideCategory(child_name).getByRole('link', {
        name: new RegExp(`Create an article in ${child_name}`, 'i'),
    });

    await kb.getAsideCategoryToggle(child_name).hover();

    await expect(child_add).toBeVisible();
    await expect(parent_add).toBeHidden();
});
