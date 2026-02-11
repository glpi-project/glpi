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

import { expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

test('Can view revision history and restore a previous version', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item with multiple revisions
    const id = await api.createItem('KnowbaseItem', {
        name: 'Original Title',
        entities_id: getWorkerEntityId(),
        answer: 'Original content',
    });
    await api.updateItem('KnowbaseItem', id, { answer: 'Content V2' });
    await api.updateItem('KnowbaseItem', id, { answer: 'Updated content' });

    // Go to article
    await kb.goto(id);
    await expect(page.getByText('Updated content')).toBeVisible();

    // Toggle history panel
    await expect(kb.getHeading('History')).not.toBeAttached();
    await page.getByTitle('More actions').click();
    await expect(page.getByTestId('history-counter')).toHaveText("3");
    await kb.getButton('History').click();
    await expect(kb.getHeading('History')).toBeVisible();

    // Revisions should be displayed with correct badges
    const revisions = page.getByTestId('history-event');
    await expect(revisions).toHaveCount(3);
    await expect(revisions.nth(0).getByText('Current version')).toBeVisible();
    await expect(revisions.nth(1).getByText('Version 2')).toBeVisible();
    await expect(revisions.nth(2).getByText('Version 1')).toBeVisible();

    // Restore button should only be visible on non-current revisions
    await expect(revisions.nth(0).getByTitle('Restore this version')).not.toBeAttached();
    await expect(revisions.nth(1).getByTitle('Restore this version')).toBeVisible();
    await expect(revisions.nth(2).getByTitle('Restore this version')).toBeVisible();

    // Click restore on the initial version
    await revisions.nth(2).getByTitle('Restore this version').click();

    // Confirm in the Bootstrap modal
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();
    await expect(modal.getByText('Are you sure you want to restore this version?')).toBeVisible();
    await modal.getByRole('button', { name: 'Confirm' }).click();

    // Restored content should be visible after reload
    await expect(page.getByText('Original content')).toBeVisible();

    // Counter should be updated (new revision created from previous current version)
    await page.getByTitle('More actions').click();
    await expect(page.getByTestId('history-counter')).toHaveText("4");
});

test('Associated item changes appear in history', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item
    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'Article with linked items',
        entities_id: getWorkerEntityId(),
        answer: 'Some content',
    });

    // Create a computer and link it to the KB item
    const computer_id = await api.createItem('Computer', {
        name: 'Test PC',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem_Item', {
        knowbaseitems_id: kb_id,
        itemtype: 'Computer',
        items_id: computer_id,
    });

    // Go to article and open history panel
    await kb.goto(kb_id);
    await page.getByTitle('More actions').click();
    await kb.getButton('History').click();
    await expect(kb.getHeading('History')).toBeVisible();

    // Should show "Item linked" event with Computer type
    const events = page.getByTestId('history-event');
    await expect(events.filter({ hasText: 'Item linked' })).toBeVisible();
    await expect(events.filter({ hasText: 'Computer' })).toBeVisible();
});

test('Document attachment changes appear in history', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item
    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'Article with attachments',
        entities_id: getWorkerEntityId(),
        answer: 'Some content',
    });

    // Create a document and attach it to the KB item
    const doc_id = await api.createItem('Document', {
        name: 'test_attachment.pdf',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Document_Item', {
        documents_id: doc_id,
        itemtype: 'KnowbaseItem',
        items_id: kb_id,
    });

    // Go to article and open history panel
    await kb.goto(kb_id);
    await page.getByTitle('More actions').click();
    await kb.getButton('History').click();
    await expect(kb.getHeading('History')).toBeVisible();

    // Should show "File added" event
    const events = page.getByTestId('history-event');
    await expect(events.filter({ hasText: 'File added' })).toBeVisible();
    await expect(events.filter({ hasText: 'test_attachment.pdf' })).toBeVisible();
});

test('Can compare a revision with the current version', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Compare Title',
        entities_id: getWorkerEntityId(),
        answer: '<p>Original paragraph</p>',
    });
    await api.updateItem('KnowbaseItem', id, { answer: '<p>Updated paragraph</p>' });

    await kb.goto(id);
    await expect(page.getByText('Updated paragraph')).toBeVisible();

    // Open history panel
    await page.getByTitle('More actions').click();
    await kb.getButton('History').click();
    await expect(kb.getHeading('History')).toBeVisible();

    const revisions = page.getByTestId('revision');
    await expect(revisions).toHaveCount(2);

    const article = page.getByRole('article');
    const initialRevision = revisions.nth(1);

    // Click on the initial revision to activate comparison
    await initialRevision.click();

    // Article should enter diff mode
    await expect(article).toHaveClass(/kb-article--diff-mode/);

    // The revision item should be highlighted
    await expect(initialRevision).toHaveClass(/kb-revision--comparing/);

    // Diff markers should be visible (ins/del elements from htmldiff)
    await expect(article.getByRole('insertion').first()).toBeAttached();
    await expect(article.getByRole('deletion').first()).toBeAttached();

    // Click the same revision again to deactivate comparison
    await initialRevision.click();

    // Article should exit diff mode
    await expect(article).not.toHaveClass(/kb-article--diff-mode/);
    await expect(initialRevision).not.toHaveClass(/kb-revision--comparing/);

    // Original content should be restored
    await expect(page.getByText('Updated paragraph')).toBeVisible();
});

test('Switching between revisions does not corrupt the diff', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item with 3 revisions so we can switch between them
    const id = await api.createItem('KnowbaseItem', {
        name: 'Diff Title',
        entities_id: getWorkerEntityId(),
        answer: '<p>Content V1</p>',
    });
    await api.updateItem('KnowbaseItem', id, { answer: '<p>Content V2</p>' });
    await api.updateItem('KnowbaseItem', id, { answer: '<p>Content V3</p>' });

    await kb.goto(id);

    // Open history panel
    await page.getByTitle('More actions').click();
    await kb.getButton('History').click();
    await expect(kb.getHeading('History')).toBeVisible();

    const revisions = page.getByTestId('revision');
    await expect(revisions).toHaveCount(3);

    const article = page.getByRole('article');
    const content = page.getByTestId('content');

    // Click on Version 2 to activate comparison
    await revisions.nth(1).click();
    await expect(article).toHaveClass(/kb-article--diff-mode/);
    await expect(article.getByRole('deletion').first()).toBeAttached();

    // Count diff markers after first click
    const delCountAfterFirst = await content.getByRole('deletion').count();

    // Click on Initial version — diff should be recomputed, not accumulated
    await revisions.nth(2).click();
    await expect(article).toHaveClass(/kb-article--diff-mode/);
    await expect(revisions.nth(2)).toHaveClass(/kb-revision--comparing/);
    await expect(revisions.nth(1)).not.toHaveClass(/kb-revision--comparing/);

    // The number of <del> elements should not grow with each click
    const delCountAfterSecond = await content.getByRole('deletion').count();
    expect(delCountAfterSecond).toBeLessThanOrEqual(delCountAfterFirst + 5);

    // Click back to Version 2 — still no accumulation
    await revisions.nth(1).click();
    const delCountAfterThird = await content.getByRole('deletion').count();
    expect(delCountAfterThird).toBeLessThanOrEqual(delCountAfterFirst + 5);
});

test('Clicking current version deactivates comparison', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Reset Title',
        entities_id: getWorkerEntityId(),
        answer: '<p>Original content</p>',
    });
    await api.updateItem('KnowbaseItem', id, { answer: '<p>Updated content</p>' });

    await kb.goto(id);
    await expect(page.getByText('Updated content')).toBeVisible();

    // Open history panel
    await page.getByTitle('More actions').click();
    await kb.getButton('History').click();
    await expect(kb.getHeading('History')).toBeVisible();

    const revisions = page.getByTestId('revision');
    const article = page.getByRole('article');
    const currentVersion = revisions.nth(0);
    const initialRevision = revisions.nth(1);

    // Activate comparison on the initial revision
    await initialRevision.click();
    await expect(article).toHaveClass(/kb-article--diff-mode/);
    await expect(initialRevision).toHaveClass(/kb-revision--comparing/);

    // Click on "Current version" to deactivate comparison
    await currentVersion.click();

    // Article should exit diff mode and show original content
    await expect(article).not.toHaveClass(/kb-article--diff-mode/);
    await expect(initialRevision).not.toHaveClass(/kb-revision--comparing/);
    await expect(page.getByText('Updated content')).toBeVisible();
});
