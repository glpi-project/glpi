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

test('Can view and add comments', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item with a comment
    const id = await api.createItem('KnowbaseItem', {
        name: 'My kb entry',
        entities_id: getWorkerEntityId(),
        answer: "My answer",
    });
    await api.createItem('KnowbaseItem_Comment', {
        knowbaseitems_id: id,
        comment: "My first comment",
    });

    // Go to article
    await kb.goto(id);
    await expect(page.getByText('My answer')).toBeVisible();

    // Toggle comments panel
    await expect(kb.getHeading('Comments')).not.toBeAttached();
    await page.getByTitle('More actions').click();
    await kb.getButton('Comments').click();
    await expect(kb.getHeading('Comments')).toBeVisible();

    // Existing comment should be shown
    await expect(page.getByText('E2E API account · Just now')).toBeVisible();
    await expect(kb.getCommentByContent('My first comment')).toBeVisible();

    // Add a new comment
    await expect(kb.getButton("Add comment")).toBeHidden();
    await page.getByPlaceholder("Add a comment...").fill("My second comment");
    await expect(kb.getButton("Add comment")).toBeVisible();
    await kb.getButton("Add comment").click();

    // New comment should be added
    await expect(kb.getButton("Add comment")).toBeHidden();
    await expect(page.getByPlaceholder("Add a comment...")).toBeEmpty();
    await expect(page.getByText(/E2E worker account \d+\s+·\s+Now/)).toBeVisible();
    await expect(kb.getCommentByContent('My second comment')).toBeVisible();
});

test('Can edit a comment', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB item with a comment
    const id = await api.createItem('KnowbaseItem', {
        name: 'My kb entry for edit test',
        entities_id: getWorkerEntityId(),
        answer: "My answer",
    });
    await api.createItem('KnowbaseItem_Comment', {
        knowbaseitems_id: id,
        comment: "Original comment",
    });

    // Go to article and open comments panel
    await kb.goto(id);
    await page.getByTitle('More actions').click();
    await kb.getButton('Comments').click();
    await expect(kb.getHeading('Comments')).toBeVisible();

    // Original comment should be visible
    await expect(kb.getCommentByContent('Original comment')).toBeVisible();

    // Open the edit form via the dropdown menu
    const comment = page.getByTestId('comment').filter({ hasText: 'Original comment' });
    await comment.getByTitle('More actions').click();
    await comment.getByText('Edit comment').click();

    // Edit form should be visible with original content
    const textarea = comment.getByTestId('comment-edit-textarea');
    await expect(textarea).toBeVisible();
    await expect(textarea).toHaveValue('Original comment');

    // Modify the comment
    await textarea.fill('Updated comment');
    await comment.getByRole('button', { name: 'Save' }).click();

    // Updated comment should be visible
    await expect(kb.getCommentByContent('Updated comment')).toBeVisible();
    await expect(kb.getCommentByContent('Original comment')).not.toBeAttached();

    // Edit form should be hidden
    await expect(textarea).toBeHidden();
});
