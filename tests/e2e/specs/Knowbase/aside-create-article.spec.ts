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

test('the aside "+" opens an inline input; an empty submit creates nothing', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const category_name = `E2E Aside Cat ${unique}`;

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

    // The "+" click is now intercepted by AsideController (a dynamically
    // imported module), instead of being a plain <a href> navigation. Wait
    // for the controller to finish initializing before clicking it, using
    // the same readiness signal doSearchAside() relies on, otherwise the
    // click can race the module load and fall through to the browser's
    // default navigation.
    await expect(kb.asideSearchInput).not.toHaveClass(/pe-none/);

    const add_link = kb.getAsideCategory(category_name).getByRole('link', {
        name: new RegExp(`Create an article in ${category_name}`, 'i'),
    });
    await kb.getAsideCategoryToggle(category_name).hover();
    await expect(add_link).toBeVisible();
    await add_link.click();

    // No navigation: the "+" now opens an inline input instead of a full page.
    await expect(page).not.toHaveURL(/knowbaseitemcategories_id=/);
    const inline_input = kb.getAsideCategoryCreateInput(category_name);
    await expect(inline_input).toBeFocused();

    // Escape cancels: the input disappears, nothing is created.
    await inline_input.press('Escape');
    await expect(inline_input).toBeHidden();

    // Re-open and blur while empty: same result.
    await add_link.click();
    await expect(kb.getAsideCategoryCreateInput(category_name)).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(kb.getAsideCategoryCreateInput(category_name)).toBeHidden();
});

test('using the aside inline "+" on Uncategorized creates an article without a category', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const article_title = `E2E Uncategorized Article ${unique}`;

    await kb.goto(1);

    // The "+" click is intercepted by AsideController (a dynamically
    // imported module), instead of being a plain <a href> navigation. Wait
    // for the controller to finish initializing before clicking it, using
    // the same readiness signal doSearchAside() relies on, otherwise the
    // click can race the module load and fall through to the browser's
    // default navigation.
    await expect(kb.asideSearchInput).not.toHaveClass(/pe-none/);

    const uncategorized = kb.getAsideCategory('Uncategorized');
    const add_link = uncategorized.getByRole('link', {
        name: /Create an article in Uncategorized/i,
    });
    await kb.getAsideCategoryToggle('Uncategorized').hover();
    await expect(add_link).toBeVisible();
    await add_link.click();

    // No navigation: the "+" now opens an inline input instead of a full page.
    await expect(page).not.toHaveURL(/knowbaseitemcategories_id=/);
    const inline_input = kb.getAsideCategoryCreateInput('Uncategorized');
    await expect(inline_input).toBeFocused();
    await inline_input.fill(article_title);
    await inline_input.press('Enter');

    await expect(page.getByTestId('subject')).toHaveText(article_title);

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

    // visibility:hidden removes the parent link from the a11y tree, so the
    // role-based locator resolves to nothing — assert visibility, not CSS.
    await expect(child_add).toBeVisible();
    await expect(parent_add).toBeHidden();
});

test('typing a title and pressing Enter creates the article and navigates to it', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const category_name = `E2E Inline Create Cat ${unique}`;
    const article_title = `E2E Inline Create Article ${unique}`;

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

    // The "+" click is intercepted by AsideController (a dynamically imported
    // module) instead of being a plain <a href> navigation. Wait for the
    // controller to finish initializing before clicking it, using the same
    // readiness signal doSearchAside() relies on, otherwise the click can
    // race the module load and fall through to the browser's default
    // navigation.
    await expect(kb.asideSearchInput).not.toHaveClass(/pe-none/);

    const add_link = kb.getAsideCategory(category_name).getByRole('link', {
        name: new RegExp(`Create an article in ${category_name}`, 'i'),
    });
    await kb.getAsideCategoryToggle(category_name).hover();
    await add_link.click();

    const inline_input = kb.getAsideCategoryCreateInput(category_name);
    await expect(inline_input).toBeFocused();

    await inline_input.fill(article_title);
    await inline_input.press('Enter');

    // Lands on the new article after a full page load.
    await expect(page).toHaveURL(/knowbaseitem\.form\.php\?id=\d+/);
    await expect(page.getByTestId('subject')).toHaveText(article_title);

    // The new article is visible under the category in the aside, and marked current.
    const category_node = kb.getAsideCategory(category_name);
    const article_row = category_node.getByRole('listitem').filter({ hasText: article_title });
    await expect(article_row).toBeVisible();
    await expect(article_row).toHaveAttribute('aria-current', 'page');
});
