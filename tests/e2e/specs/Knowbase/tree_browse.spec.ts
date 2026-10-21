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
import { test, expect } from '../../fixtures/glpi_fixture';
import { KnowbaseTreeBrowsePage } from '../../pages/KnowbaseTreeBrowsePage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('tree browse selects category from URL parameter', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    const unique = randomUUID().slice(0, 8);
    const category_name = `E2E Cat ${unique}`;
    const category_id = await api.createItem('KnowbaseItemCategory', {
        name: category_name,
        entities_id: getWorkerEntityId(),
    });

    await api.createItem('KnowbaseItem', {
        name: `E2E KB Article ${unique}`,
        answer: 'Test answer content',
        is_faq: 0,
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });

    await page.goto(
        `/front/knowbaseitem.php?knowbaseitemcategories_id=${category_id}&browse=1&forcetab=Knowbase$2`
    );

    // eslint-disable-next-line playwright/no-raw-locators
    const active_node = page.locator('#tree_category .fancytree-active .fancytree-title');
    await expect(active_node).toContainText(category_name);
});

test('tree browse defaults to "Without Category" when no URL parameter', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);

    await page.goto('/front/knowbaseitem.php?browse=1&forcetab=Knowbase$2');

    // eslint-disable-next-line playwright/no-raw-locators
    const active_node = page.locator('#tree_category .fancytree-active .fancytree-title');
    await expect(active_node).toContainText('Without Category');
});

test('tree browse does not duplicate search criteria', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    const unique = randomUUID().slice(0, 8);
    const category_name = `E2E Cat ${unique}`;
    const category_id = await api.createItem('KnowbaseItemCategory', {
        name: category_name,
        entities_id: getWorkerEntityId(),
    });
    const categorized_article = `E2E KB Categorized ${unique}`;
    await api.createItem('KnowbaseItem', {
        name: categorized_article,
        answer: 'Test answer content',
        is_faq: 0,
        entities_id: getWorkerEntityId(),
        _categories: [category_id],
    });
    const uncategorized_article = `E2E KB Uncategorized ${unique}`;
    await api.createItem('KnowbaseItem', {
        name: uncategorized_article,
        answer: 'Test answer content',
        is_faq: 0,
        entities_id: getWorkerEntityId(),
    });

    const tree_browse_page = new KnowbaseTreeBrowsePage(page);
    await tree_browse_page.goto();
    await expect(tree_browse_page.search_criteria_fields).toHaveCount(1);

    // Executing a search must only submit the displayed criteria, not the virtual category criterion
    await tree_browse_page.doSubmitSearchFilters();

    const submitted_params = [...new URL(page.url()).searchParams.keys()];
    expect(submitted_params.filter((key) => /^criteria\[\d+\]\[field\]$/.test(key))).toEqual(['criteria[0][field]']);
    expect(submitted_params.filter((key) => key.endsWith('[virtual]'))).toEqual([]);
    await expect(tree_browse_page.search_criteria_fields).toHaveCount(1);

    // Category filter must still be applied after the search has been submitted
    await tree_browse_page.doSelectCategory(category_name);
    await expect(tree_browse_page.getArticleLink(categorized_article)).toBeVisible();
    await expect(tree_browse_page.getArticleLink(uncategorized_article)).toBeHidden();
    await expect(tree_browse_page.search_criteria_fields).toHaveCount(1);
});
