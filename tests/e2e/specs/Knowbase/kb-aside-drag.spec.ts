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

test('Can drag an article under another one', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const host_name = `E2E Host ${unique}`;
    const moved_name = `E2E Moved ${unique}`;

    await api.createItem('KnowbaseItem', {
        name: host_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const moved_id = await api.createItem('KnowbaseItem', {
        name: moved_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(moved_id);

    // The move is posted fire-and-forget: reloading before it lands would
    // abort the request.
    await Promise.all([
        page.waitForResponse('**/Knowbase/Aside/Article/*/Move'),
        kb.doDragArticleOnto(moved_name, host_name),
    ]);

    // The move is persisted: it survives a reload.
    await kb.goto(moved_id);
    await expect(
        kb.getAsideCategoryArticle(host_name, moved_name)
    ).toBeVisible();
});

test('Can drag an article back to the root level', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const child_name = `E2E Child ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });

    await kb.goto(child_id);
    await expect(kb.getAsideCategoryArticle(parent_name, child_name)).toBeVisible();

    // The move is posted fire-and-forget: reloading before it lands would
    // abort the request.
    await Promise.all([
        page.waitForResponse('**/Knowbase/Aside/Article/*/Move'),
        kb.doDragArticleToRoot(child_name),
    ]);

    await kb.goto(child_id);
    await expect(kb.getAsideCategoryArticle(parent_name, child_name)).toHaveCount(0);
    await expect(
        kb.getAsideArticlesList().getByRole('link', { name: child_name, exact: true })
    ).toBeVisible();
});

test('Cannot drop an article onto its own descendant', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Ancestor ${unique}`;
    const child_name = `E2E Descendant ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });

    await kb.goto(child_id);

    // Dropping the ancestor onto its descendant must be refused outright.
    await kb.doDragArticleOnto(parent_name, child_name);

    await kb.goto(child_id);
    await expect(kb.getAsideCategoryArticle(parent_name, child_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(child_name, parent_name)).toHaveCount(0);
});

test('Moving one occurrence leaves the other parents untouched', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_a_name = `E2E ParentA ${unique}`;
    const parent_b_name = `E2E ParentB ${unique}`;
    const host_name = `E2E Host ${unique}`;
    const shared_name = `E2E Shared ${unique}`;

    const parent_a_id = await api.createItem('KnowbaseItem', {
        name: parent_a_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const parent_b_id = await api.createItem('KnowbaseItem', {
        name: parent_b_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: host_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const shared_id = await api.createItem('KnowbaseItem', {
        name: shared_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_a_id, parent_b_id],
    });

    await kb.goto(shared_id);

    // The shared article is rendered under each of its parents.
    await expect(kb.getAsideCategoryArticle(parent_a_name, shared_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(parent_b_name, shared_name)).toBeVisible();

    // Drag the occurrence sitting under parent A onto the host article. The
    // move is posted fire-and-forget: reloading before it lands would abort
    // the request.
    await Promise.all([
        page.waitForResponse('**/Knowbase/Aside/Article/*/Move'),
        kb.dragOccurrenceOnto(parent_a_name, shared_name, host_name),
    ]);

    await kb.goto(shared_id);
    await expect(kb.getAsideCategoryArticle(parent_a_name, shared_name)).toHaveCount(0);
    await expect(kb.getAsideCategoryArticle(parent_b_name, shared_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(host_name, shared_name)).toBeVisible();
});

test('Cannot drop an article onto another occurrence of its descendant', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const ancestor_name = `E2E Ancestor ${unique}`;
    const other_name = `E2E Other ${unique}`;
    const descendant_name = `E2E Descendant ${unique}`;

    const ancestor_id = await api.createItem('KnowbaseItem', {
        name: ancestor_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const other_id = await api.createItem('KnowbaseItem', {
        name: other_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    // The descendant hangs under both the ancestor and an unrelated article,
    // so it is rendered twice.
    await api.createItem('KnowbaseItem', {
        name: descendant_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [ancestor_id, other_id],
    });

    await kb.goto(ancestor_id);

    // Drop the ancestor onto the descendant's occurrence sitting under the
    // unrelated parent. DOM containment would say that occurrence is outside
    // the dragged subtree and allow it; the id-based check must still see
    // that this descendant is really inside the ancestor's own subtree and
    // refuse it, since accepting it would create a cycle.
    await kb.dragOntoOccurrence(ancestor_name, other_name, descendant_name);

    await kb.goto(ancestor_id);

    // No cycle: the ancestor must not appear nested under the descendant.
    await expect(
        kb.getAsideCategoryArticle(descendant_name, ancestor_name)
    ).toHaveCount(0);
    // The pre-existing links are untouched.
    await expect(kb.getAsideCategoryArticle(ancestor_name, descendant_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(other_name, descendant_name)).toBeVisible();
});

test('A rejected move is reverted in the tree', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const host_name = `E2E Host ${unique}`;
    const child_name = `E2E Child ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: host_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });

    await kb.goto(child_id);

    // Force the endpoint to fail so the optimistic move must be undone.
    await page.route('**/Knowbase/Aside/Article/*/Move', (route) => route.fulfill({
        status: 403,
    }));

    await kb.doDragArticleOnto(child_name, host_name);

    // The article snaps back without any reload.
    await expect(kb.getAsideCategoryArticle(parent_name, child_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(host_name, child_name)).toHaveCount(0);
});

test('Can drop on a root article top edge to promote a child to root', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const child_name = `E2E Child ${unique}`;
    const other_name = `E2E Other ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });
    await api.createItem('KnowbaseItem', {
        name: other_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(child_id);
    await expect(kb.getAsideCategoryArticle(parent_name, child_name)).toBeVisible();

    // The move is posted fire-and-forget: reloading before it lands would
    // abort the request.
    await Promise.all([
        page.waitForResponse('**/Knowbase/Aside/Article/*/Move'),
        kb.doDropOnBand(child_name, other_name, 'top'),
    ]);

    await kb.goto(child_id);
    await expect(kb.getAsideCategoryArticle(parent_name, child_name)).toHaveCount(0);
    await expect(
        kb.getAsideArticlesList().getByRole('link', { name: child_name, exact: true })
    ).toBeVisible();
});

test('Can drop on a child bottom edge to become its sibling', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const child_name = `E2E Child ${unique}`;
    const moved_name = `E2E Moved ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });
    const moved_id = await api.createItem('KnowbaseItem', {
        name: moved_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(moved_id);

    await Promise.all([
        page.waitForResponse('**/Knowbase/Aside/Article/*/Move'),
        kb.doDropOnBand(moved_name, child_name, 'bottom'),
    ]);

    await kb.goto(moved_id);
    // Sibling of the child, so a child of the parent, not a grandchild.
    await expect(kb.getAsideCategoryArticle(parent_name, moved_name)).toBeVisible();
    await expect(kb.getAsideCategoryArticle(child_name, moved_name)).toHaveCount(0);
});

test('Dropping back onto the current parent issues no move request', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const child_name = `E2E Child ${unique}`;
    const host_name = `E2E Host ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    const child_id = await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });
    await api.createItem('KnowbaseItem', {
        name: host_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(child_id);

    let moves = 0;
    page.on('request', (request) => {
        if (request.url().includes('/Move')) {
            moves++;
        }
    });

    // Refused: the child already hangs from this parent.
    await kb.doDropOnBand(child_name, parent_name, 'middle');

    // Sentinel: a valid move whose response proves an earlier request, had one
    // been issued, would already have been sent.
    await Promise.all([
        page.waitForResponse('**/Knowbase/Aside/Article/*/Move'),
        kb.doDropOnBand(child_name, host_name, 'middle'),
    ]);

    expect(moves).toBe(1);
});

test('Dropping a parent onto its own child issues no move request', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const unique = randomUUID().slice(0, 8);
    const parent_name = `E2E Parent ${unique}`;
    const child_name = `E2E Child ${unique}`;
    const host_name = `E2E Host ${unique}`;

    const parent_id = await api.createItem('KnowbaseItem', {
        name: parent_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: child_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
        _parents: [parent_id],
    });
    await api.createItem('KnowbaseItem', {
        name: host_name,
        answer: 'Test content',
        entities_id: getWorkerEntityId(),
    });

    await kb.goto(parent_id);

    let moves = 0;
    page.on('request', (request) => {
        if (request.url().includes('/Move')) {
            moves++;
        }
    });

    // Refused: the target sits inside the dragged subtree.
    await kb.doDropOnBand(parent_name, child_name, 'middle');

    // Sentinel, as above.
    await Promise.all([
        page.waitForResponse('**/Knowbase/Aside/Article/*/Move'),
        kb.doDropOnBand(parent_name, host_name, 'middle'),
    ]);

    expect(moves).toBe(1);
});
