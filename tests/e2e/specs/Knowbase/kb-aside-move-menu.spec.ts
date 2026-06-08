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

test('Moving an article via the kebab menu reparents it and persists after reload', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const article_name = `E2E Article ${token}`;
    const anchor_name  = `E2E Anchor ${token}`;
    const from_name    = `E2E From ${token}`;
    const to_name      = `E2E To ${token}`;

    const from_id = await api.createItem('KnowbaseItemCategory', {
        name: from_name,
        entities_id: getWorkerEntityId(),
    });
    const to_id = await api.createItem('KnowbaseItemCategory', {
        name: to_name,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
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

    // Focus the article link so the row's :focus-within reveals the kebab, then click it.
    await kb.getAsideArticleInCategoryById(from_id, article_name).focus();
    await page.getByRole('button', { name: `Move ${article_name}` }).click();

    // Pick the target category in the modal and submit.
    const dialog = page.getByRole('dialog');
    await dialog.getByRole('radio', { name: to_name }).check();
    await dialog.getByRole('button', { name: 'Move', exact: true }).click();

    await kb.gotoAndWait(anchor_id);

    await expect(kb.getAsideArticleInCategoryById(to_id, article_name)).toBeVisible();
    await expect(kb.getAsideArticleInCategoryById(from_id, article_name)).toBeHidden();
});

test('Moving an article to (Uncategorized) makes it a root article', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token        = randomUUID().slice(0, 8);
    const article_name = `E2E Article ${token}`;
    const anchor_name  = `E2E Anchor ${token}`;

    const from_id = await api.createItem('KnowbaseItemCategory', {
        name: `E2E From ${token}`,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: article_name,
        answer: 'Body',
        entities_id: getWorkerEntityId(),
        _categories: [from_id],
    });
    // Anchor with no category so the page has an "Uncategorized" bucket already
    // populated besides the moved article.
    const anchor_id = await api.createItem('KnowbaseItem', {
        name: anchor_name,
        answer: 'Anchor',
        entities_id: getWorkerEntityId(),
    });

    await kb.gotoAndWait(anchor_id);

    await expect(kb.getAsideArticleInCategoryById(from_id, article_name)).toBeVisible();

    await kb.getAsideArticleInCategoryById(from_id, article_name).focus();
    await page.getByRole('button', { name: `Move ${article_name}` }).click();

    const dialog = page.getByRole('dialog');
    await dialog.getByRole('radio', { name: 'Uncategorized' }).check();
    await dialog.getByRole('button', { name: 'Move', exact: true }).click();

    await kb.gotoAndWait(anchor_id);

    await expect(kb.getAsideArticleInCategoryById(0, article_name)).toBeVisible();
    await expect(kb.getAsideArticleInCategoryById(from_id, article_name)).toBeHidden();
});

test('In the move picker, a category cannot be moved into one of its descendants', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token       = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${token}`;
    const child_name  = `E2E Child ${token}`;
    const anchor_name = `E2E Anchor ${token}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: parent_name,
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItemCategory', {
        name: child_name,
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

    // Focus the toggle button (inside the category title row) so the row's
    // :focus-within reveals the kebab.
    await kb
        .getAsideCategoryById(parent_id)
        .getByRole('button', { name: parent_name, exact: true })
        .focus();
    await page.getByRole('button', { name: `Move ${parent_name}` }).click();

    const dialog = page.getByRole('dialog');

    // The descendant category must be present but disabled (kept visible so the
    // tree structure stays readable, but not selectable).
    await expect(dialog.getByRole('radio', { name: child_name })).toBeDisabled();

    // The category itself is also disabled (it's the current selection).
    await expect(dialog.getByRole('radio', { name: parent_name })).toBeDisabled();
});

test('Moving a nested category to (Top level) makes it a root category', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const token       = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${token}`;
    const child_name  = `E2E Child ${token}`;
    const anchor_name = `E2E Anchor ${token}`;

    const parent_id = await api.createItem('KnowbaseItemCategory', {
        name: parent_name,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItemCategory', {
        name: child_name,
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

    // The child category is nested inside the parent's group.
    await expect(
        page.getByRole('group', { name: parent_name })
            .getByRole('group', { name: child_name }),
    ).toBeVisible();

    // Focus the child category's toggle so the row's :focus-within reveals the kebab.
    await page
        .getByRole('group', { name: child_name })
        .getByRole('button', { name: child_name, exact: true })
        .focus();
    await page.getByRole('button', { name: `Move ${child_name}` }).click();

    const dialog = page.getByRole('dialog');
    await dialog.getByRole('radio', { name: 'Top level' }).check();
    await dialog.getByRole('button', { name: 'Move', exact: true }).click();

    await kb.gotoAndWait(anchor_id);

    // The child is still in the tree, but no longer nested under the parent.
    await expect(page.getByRole('group', { name: child_name })).toBeVisible();
    await expect(
        page.getByRole('group', { name: parent_name })
            .getByRole('group', { name: child_name }),
    ).toHaveCount(0);
});
