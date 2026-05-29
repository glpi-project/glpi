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

test('Article linked to several categories appears under each one', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const category_a   = `E2E Multi Cat A ${token}`;
    const category_b   = `E2E Multi Cat B ${token}`;
    const article_name = `E2E Multi Article ${token}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const category_b_id = await api.createItem('KnowbaseItemCategory', {
        name: category_b,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id, category_b_id],
    });

    await kb.goto(article_id);

    await expect(kb.getAsideCategoryArticle(category_a, article_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(category_b, article_name)).toBeVisible();
});

test('Hovering one listing of a multi-category article highlights its other listings', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const category_a   = `E2E Hl Cat A ${token}`;
    const category_b   = `E2E Hl Cat B ${token}`;
    const article_name = `E2E Hl Article ${token}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const category_b_id = await api.createItem('KnowbaseItemCategory', {
        name: category_b,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id, category_b_id],
    });

    await kb.goto(article_id);

    // The listing under category B is the cross-tree copy we expect to light up.
    const row_under_b = kb.getAsideCategory(category_b).getByRole('listitem');
    await expect(row_under_b).not.toHaveClass(/kb-article--sibling/);

    await kb.getAsideCategoryArticle(category_a, article_name).hover();
    await expect(row_under_b).toHaveClass(/kb-article--sibling/);

    // Moving away clears the highlight everywhere.
    await page.mouse.move(0, 0);
    await expect(row_under_b).not.toHaveClass(/kb-article--sibling/);
});

test('A copy folded inside a collapsed category lights up that category header', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const category_a   = `E2E Fold Cat A ${token}`;
    const category_b   = `E2E Fold Cat B ${token}`;
    const article_name = `E2E Fold Article ${token}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const category_b_id = await api.createItem('KnowbaseItemCategory', {
        name: category_b,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id, category_b_id],
    });

    await kb.goto(article_id);

    // Fold category B so its copy of the article is hidden behind the header.
    await kb.doToggleAsideCategory(category_b);

    // Park the pointer so the collapse reflow can't fire a stray hover.
    await page.mouse.move(0, 0);

    const category_b_node = kb.getAsideCategory(category_b);
    await expect(category_b_node).not.toHaveClass(/kb-category--sibling/);

    // Hovering the still-visible copy under A lights up B's collapsed header
    // instead of its now-hidden row.
    await kb.getAsideCategoryArticle(category_a, article_name).hover();
    await expect(category_b_node).toHaveClass(/kb-category--sibling/);

    // Moving away clears the highlight.
    await page.mouse.move(0, 0);
    await expect(category_b_node).not.toHaveClass(/kb-category--sibling/);
});

test('Single-category article is not flagged as multi-category', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const category_a   = `E2E Single Cat ${token}`;
    const article_name = `E2E Single Article ${token}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id],
    });

    await kb.goto(article_id);

    await expect(kb.getAsideCategoryArticle(category_a, article_name)).toBeVisible();
    // A single-category article carries no cross-listing marker, so it never highlights.
    await expect(kb.getAsideCategory(category_a).getByRole('listitem'))
        .not.toHaveAttribute('data-glpi-kb-article-multi');
});
