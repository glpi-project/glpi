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

test('Matching articles are shown and non-matching are hidden across categories', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Use distinct tokens so searching for one cannot match the other
    const token_a = randomUUID().slice(0, 8);
    const token_b = randomUUID().slice(0, 8);
    const category_a = `E2E Category A ${token_a}`;
    const category_b = `E2E Category B ${token_b}`;
    const article_a  = `E2E Article A ${token_a}`;
    const article_b  = `E2E Article B ${token_b}`;

    const category_a_id = await api.createItem('KnowbaseItemCategory', {
        name: category_a,
        entities_id: getWorkerEntityId(),
    });
    const category_b_id = await api.createItem('KnowbaseItemCategory', {
        name: category_b,
        entities_id: getWorkerEntityId(),
    });
    const article_a_id = await api.createItem('KnowbaseItem', {
        name: article_a,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_a_id],
    });
    await api.createItem('KnowbaseItem', {
        name: article_b,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_b_id],
    });

    await kb.goto(article_a_id);
    await kb.doSearchAside(token_a);

    // Category A and its article must be visible
    await expect(kb.getAsideCategory(category_a)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(category_a, article_a)).toBeVisible();

    // Category B and its article must be hidden
    await expect(kb.getAsideCategory(category_b)).toBeHidden();
    await expect(kb.getAsideCategoryArticle(category_b, article_b)).toBeHidden();
});


test('Categories with at least one matching article remain visible', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const matching_token     = randomUUID().slice(0, 8);
    const non_matching_token = randomUUID().slice(0, 8);
    const category_name      = `E2E Category ${matching_token}`;
    const matching_article   = `E2E Article ${matching_token}`;
    const other_article      = `E2E Article ${non_matching_token}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name: category_name,
        entities_id: getWorkerEntityId(),
    });
    const matching_id = await api.createItem('KnowbaseItem', {
        name: matching_article,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });
    await api.createItem('KnowbaseItem', {
        name: other_article,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(matching_id);
    await kb.doSearchAside(matching_token);

    // The category must stay visible because it has one matching article
    await expect(kb.getAsideCategory(category_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(category_name, matching_article)).toBeVisible();

    // The non-matching article in the same category must be hidden
    await expect(kb.getAsideCategoryArticle(category_name, other_article)).toBeHidden();
});

test('Clearing the search restores the full tree', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token           = randomUUID().slice(0, 8);
    const no_match_token  = randomUUID().slice(0, 8);
    const category_name   = `E2E Category ${token}`;
    const article_name    = `E2E Article ${token}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name: category_name,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(article_id);

    // Filter the tree so the article is hidden
    await kb.doSearchAside(no_match_token);
    await expect(kb.getAsideCategoryArticle(category_name, article_name)).toBeHidden();

    // Clear the search manually, the tree must be fully restored
    await kb.doClearAsideSearch();
    await expect(kb.getAsideCategoryArticle(category_name, article_name)).toBeVisible();
});

test('"No articles found" message is shown when nothing matches and hidden when results exist', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token          = randomUUID().slice(0, 8);
    const no_match_token = randomUUID().slice(0, 8);
    const category_name  = `E2E Category ${token}`;
    const article_name   = `E2E Article ${token}`;

    const category_id = await api.createItem('KnowbaseItemCategory', {
        name: category_name,
        entities_id: getWorkerEntityId(),
    });
    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await kb.goto(article_id);

    // Search for something that matches nothing, message must appear
    await kb.doSearchAside(no_match_token);
    await expect(kb.asideNoResultsMessage).toBeVisible();

    // Search for the actual article, message must disappear
    await kb.doSearchAside(token);
    await expect(kb.asideNoResultsMessage).toBeHidden();
});
