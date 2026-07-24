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

test('Can fold and unfold a category in the KB aside', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const category_name = `E2E Category ${unique}`;
    const article_name = `E2E Article ${unique}`;

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

    // Category and article are visible by default
    const category_toggle = kb.getAsideCategoryToggle(category_name);
    const article_link = kb.getAsideCategoryArticle(category_name, article_name);

    await expect(category_toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(article_link).toBeVisible();

    // Fold the category — article should be hidden
    await kb.doToggleAsideCategory(category_name);
    await expect(category_toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(article_link).toBeHidden();

    // Unfold the category — article should be visible again
    await kb.doToggleAsideCategory(category_name);
    await expect(category_toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(article_link).toBeVisible();
});
