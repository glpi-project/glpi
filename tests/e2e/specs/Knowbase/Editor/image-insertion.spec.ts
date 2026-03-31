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

import { expect, test } from "../../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../../pages/KnowbaseItemPage";
import { TEST_IMAGE_BASE64 } from "../../../utils/ImagePasteHelpers";
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

test.describe('Knowledge Base Editor - Image Insertion', () => {

    test.describe('Slash command /Image dialog', () => {

        test('Can insert image via URL using /Image dialog', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test image insertion via URL',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Image');

            const dialog = kb.imageDialog;
            await expect(dialog).toBeVisible();

            const dataUri = `data:image/png;base64,${TEST_IMAGE_BASE64}`;
            await dialog.getByLabel('Source').fill(dataUri);
            await dialog.getByRole('button', { name: 'Save' }).click();

            await expect(dialog).toBeHidden();
            // eslint-disable-next-line playwright/no-raw-locators
            await expect(kb.editor.contentContainer.locator(`img[src="${dataUri}"]`)).toBeVisible();
        });

        test('Dialog closes on Cancel', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test image dialog cancel',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Image');

            const dialog = kb.imageDialog;
            await expect(dialog).toBeVisible();

            await dialog.getByRole('button', { name: 'Cancel' }).click();
            await expect(dialog).toBeHidden();

            await expect(kb.editor.contentContainer.getByRole('img')).toHaveCount(0);
        });

        test('Dialog closes on Escape key', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test image dialog escape',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Image');

            const dialog = kb.imageDialog;
            await expect(dialog).toBeVisible();

            await page.keyboard.press('Escape');
            await expect(dialog).toBeHidden();
        });

        test('Dialog does not save with empty source', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Test image dialog empty source',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Image');

            const dialog = kb.imageDialog;
            await expect(dialog).toBeVisible();

            await dialog.getByRole('button', { name: 'Save' }).click();
            await expect(dialog).toBeVisible();
        });
    });

    test.describe('Image paste', () => {

        test('Pasted image is uploaded and inserted in editor', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const kb_id = await api.createItem('KnowbaseItem', {
                name: 'Test image paste upload',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(kb_id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            // Simulate a clipboard paste with a binary image file
            const uploadResponse = page.waitForResponse(
                response => response.url().includes('/UploadInlineImage') && response.request().method() === 'POST'
            );

            // eslint-disable-next-line playwright/no-raw-locators
            await kb.editor.contentContainer.locator('.ProseMirror').evaluate((element, base64) => {
                const binary = atob(base64);
                const bytes = new Uint8Array(binary.length);
                for (let i = 0; i < binary.length; i++) {
                    bytes[i] = binary.charCodeAt(i);
                }
                const blob = new Blob([bytes], { type: 'image/png' });
                const file = new File([blob], 'paste.png', { type: 'image/png' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                element.dispatchEvent(new ClipboardEvent('paste', {
                    bubbles: true,
                    cancelable: true,
                    clipboardData: dataTransfer,
                }));
            }, TEST_IMAGE_BASE64);

            const response = await uploadResponse;
            expect(response.status()).toBe(200);
            const data = await response.json();
            expect(data.success).toBe(true);

            await expect(kb.editor.contentContainer.getByRole('img')).toBeVisible();
        });
    });

    test.describe('Inline image does not appear in documents list', () => {

        test('Uploaded inline image is hidden from documents tab', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const kb_id = await api.createItem('KnowbaseItem', {
                name: 'Test inline image hidden from documents',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            const doc_id = await api.createItem('Document', {
                name: 'Inline test image',
                entities_id: getWorkerEntityId(),
            });
            // timeline_position: -1 = CommonITILObject::NO_TIMELINE (marks inline images)
            await api.createItem('Document_Item', {
                documents_id: doc_id,
                itemtype: 'KnowbaseItem',
                items_id: kb_id,
                timeline_position: -1,
            });

            await kb.goto(kb_id);

            await expect(page.getByTestId('documents-count')).not.toBeAttached();
        });

        test('Regular document still appears in documents tab', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const kb_id = await api.createItem('KnowbaseItem', {
                name: 'Test regular doc still visible',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            const doc_id = await api.createItem('Document', {
                name: 'Regular document',
                entities_id: getWorkerEntityId(),
            });
            await api.createItem('Document_Item', {
                documents_id: doc_id,
                itemtype: 'KnowbaseItem',
                items_id: kb_id,
            });

            await kb.goto(kb_id);

            await expect(page.getByTestId('documents-count')).toHaveText('1 document');
        });
    });
});
