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

test('Dragging an article onto a category title row reparents it and persists after reload', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = randomUUID().slice(0, 8);
    const article_name = `E2E Article ${token}`;
    const anchor_name  = `E2E Anchor ${token}`;

    const from_id = await api.createItem('KnowbaseItemCategory', {
        name: `E2E From ${token}`,
        entities_id: getWorkerEntityId(),
    });
    const to_id = await api.createItem('KnowbaseItemCategory', {
        name: `E2E To ${token}`,
        entities_id: getWorkerEntityId(),
    });

    const article_id = await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Body',
        entities_id: getWorkerEntityId(),
        _categories: [from_id],
    });
    const anchor_id = await api.createItem('KnowbaseItem', {
        name: anchor_name,
        answer: 'Anchor',
        entities_id: getWorkerEntityId(),
        _categories: [to_id],
    });

    await kb.gotoAndWait(anchor_id);

    await expect(kb.getAsideArticleInCategoryById(from_id, article_name)).toBeVisible();
    await expect(kb.getAsideArticleInCategoryById(to_id, article_name)).toBeHidden();

    await kb.doDragArticleToCategoryById(article_id, from_id, to_id);
    await kb.gotoAndWait(anchor_id);

    await expect(kb.getAsideArticleInCategoryById(to_id, article_name)).toBeVisible();
    await expect(kb.getAsideArticleInCategoryById(from_id, article_name)).toBeHidden();
});

test('Dragging a category onto another category nests it and persists after reload', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = randomUUID().slice(0, 8);
    const anchor_name = `E2E Anchor ${token}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: `E2E Parent ${token}`,
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItemCategory', {
        name: `E2E Child ${token}`,
        entities_id: getWorkerEntityId(),
    });
    const anchor_id = await api.createItem('KnowbaseItem', {
        name: anchor_name,
        answer: 'Anchor',
        entities_id: getWorkerEntityId(),
        _categories: [parent_id],
    });

    await kb.gotoAndWait(anchor_id);

    await expect(kb.getAsideCategoryById(parent_id)).toBeVisible();
    await expect(kb.getAsideCategoryById(child_id)).toBeVisible();
    await expect(kb.getAsideNestedCategoryById(parent_id, child_id)).toHaveCount(0);

    await kb.doDragCategoryToCategoryById(child_id, parent_id);
    await kb.gotoAndWait(anchor_id);

    await expect(kb.getAsideNestedCategoryById(parent_id, child_id)).toBeVisible();
});

test('Dragging a category onto one of its own descendants is rejected and leaves the tree unchanged', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token = randomUUID().slice(0, 8);
    const anchor_name = `E2E Anchor ${token}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: `E2E Parent ${token}`,
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItemCategory', {
        name: `E2E Child ${token}`,
        entities_id: getWorkerEntityId(),
        knowbaseitemcategories_id: parent_id,
    });
    const anchor_id = await api.createItem('KnowbaseItem', {
        name: anchor_name,
        answer: 'Anchor',
        entities_id: getWorkerEntityId(),
        _categories: [parent_id],
    });

    await kb.gotoAndWait(anchor_id);
    await expect(kb.getAsideNestedCategoryById(parent_id, child_id)).toBeVisible();

    await kb.doDragCategoryToCategoryById(parent_id, child_id);
    await kb.gotoAndWait(anchor_id);

    await expect(kb.getAsideNestedCategoryById(parent_id, child_id)).toBeVisible();
    await expect(kb.getAsideNestedCategoryById(child_id, parent_id)).toHaveCount(0);
});
