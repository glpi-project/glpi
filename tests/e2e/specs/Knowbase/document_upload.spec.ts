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

test('Can open document upload modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for document upload test',
        entities_id: getWorkerEntityId(),
        answer: "Article content",
    });

    await kb.goto(id);

    // Open the document upload modal
    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    // Verify both tabs are visible
    await expect(modal.getByRole('tab', { name: 'Upload a file' })).toBeVisible();
    await expect(modal.getByRole('tab', { name: 'Link a document' })).toBeVisible();

    // Verify the upload tab is active by default
    await expect(modal.getByRole('tab', { name: 'Upload a file' })).toHaveAttribute('aria-selected', 'true');
});

test('Can select files and upload via modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for document upload test',
        entities_id: getWorkerEntityId(),
        answer: "Article content",
    });

    await kb.goto(id);

    // Open the document upload modal
    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    // Select a file - verify it appears in preview
    await kb.doSelectFilesForKbUpload(['uploads/foo.png'], modal);

    // Verify file is shown in preview
    await expect(modal.getByRole('listitem')).toHaveCount(1);
    await expect(modal.getByRole('listitem')).toContainText('foo.png');

    // Verify upload button is enabled
    await expect(modal.getByRole('button', { name: 'Upload Documents' })).toBeEnabled();

    // Click upload and verify modal closes
    await modal.getByRole('button', { name: 'Upload Documents' }).click();
    await expect(modal).toBeHidden();

    // Wait for page reload
    await page.waitForLoadState('load');
});

test('Can select multiple files for upload', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for multiple document upload test',
        entities_id: getWorkerEntityId(),
        answer: "Article content",
    });

    await kb.goto(id);

    // Open the document upload modal
    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    // Select multiple files
    await kb.doSelectFilesForKbUpload(['uploads/foo.png', 'uploads/bar.png'], modal);

    // Verify both files are shown in preview
    await expect(modal.getByRole('listitem')).toHaveCount(2);

    // Upload and verify modal closes
    await modal.getByRole('button', { name: 'Upload Documents' }).click();
    await expect(modal).toBeHidden();

    // Wait for page reload
    await page.waitForLoadState('load');
});

test('Can remove a file from selection', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for file removal test',
        entities_id: getWorkerEntityId(),
        answer: "Article content",
    });

    await kb.goto(id);

    // Open the document upload modal
    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    // Select multiple files
    await kb.doSelectFilesForKbUpload(['uploads/foo.png', 'uploads/bar.png'], modal);

    // Verify files are in the preview
    const fileItems = modal.getByRole('listitem');
    await expect(fileItems).toHaveCount(2);

    // Remove the first file
    await fileItems.first().getByTitle('Remove').click();

    // Verify only one file remains
    await expect(fileItems).toHaveCount(1);
});

test('Can add description before upload', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for document description test',
        entities_id: getWorkerEntityId(),
        answer: "Article content",
    });

    await kb.goto(id);

    // Open the document upload modal
    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    // Select a file
    await kb.doSelectFilesForKbUpload(['uploads/foo.png'], modal);

    // Fill the description field
    const description = 'Test document description';
    await modal.getByLabel('Description').fill(description);

    // Verify description is filled
    await expect(modal.getByLabel('Description')).toHaveValue(description);

    // Upload the file
    await modal.getByRole('button', { name: 'Upload Documents' }).click();
    await expect(modal).toBeHidden();

    // Wait for page reload
    await page.waitForLoadState('load');
});

test('Upload button is disabled without files', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'KB entry for button state test',
        entities_id: getWorkerEntityId(),
        answer: "Article content",
    });

    await kb.goto(id);

    // Open the document upload modal
    await page.getByRole('button', { name: 'Add Document' }).click();
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();

    // Verify the upload button is disabled
    const uploadButton = modal.getByRole('button', { name: 'Upload Documents' });
    await expect(uploadButton).toBeDisabled();

    // Select a file
    await kb.doSelectFilesForKbUpload(['uploads/foo.png'], modal);

    // Verify the upload button is now enabled
    await expect(uploadButton).toBeEnabled();
});
