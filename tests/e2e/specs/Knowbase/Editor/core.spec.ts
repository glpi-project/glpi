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
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

test.describe('Knowledge Base Editor - Core', () => {
    test('Can enter edit mode', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test enter edit mode',
            entities_id: getWorkerEntityId(),
            answer: '<p>Initial content</p>',
        });

        await kb.goto(id);
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('[data-glpi-kb-content]')).toContainText('Initial content');

        await kb.editor.enterEditMode();

        await expect(page.getByTestId('save-button')).toBeVisible();
        await expect(page.getByTestId('cancel-button')).toBeVisible();
        await expect(page.getByTestId('edit-button')).toBeHidden();
    });

    test('Can save content and persist after reload', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test save and persist',
            entities_id: getWorkerEntityId(),
            answer: '<p>Original</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.setContent('Updated content');
        await kb.editor.save();

        await kb.editor.assertContainsText('Updated content');

        await page.reload();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('[data-glpi-kb-content]')).toContainText('Updated content');
    });

    test('Cancel discards changes', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test cancel',
            entities_id: getWorkerEntityId(),
            answer: '<p>Original content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.setContent('This should be discarded');
        await kb.editor.cancel();

        await kb.editor.assertContainsText('Original content');
    });

    test('Can edit title inline', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Original Title',
            entities_id: getWorkerEntityId(),
            answer: '<p>Some content</p>',
        });

        await kb.goto(id);
        // eslint-disable-next-line playwright/no-raw-locators
        const subject = page.locator('[data-glpi-kb-subject]');
        await expect(subject).toHaveText('Original Title');

        await kb.editor.enterEditMode();

        // Title should be contenteditable
        await expect(subject).toHaveAttribute('contenteditable', 'true');

        // Clear and type new title
        await subject.click();
        await page.keyboard.press('Control+a');
        await page.keyboard.type('Updated Title');

        await kb.editor.save();

        await expect(subject).toHaveText('Updated Title');

        // Verify persistence after reload
        await page.reload();
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(page.locator('[data-glpi-kb-subject]')).toHaveText('Updated Title');
    });

    test('Enter in title moves focus to editor', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Focus Test Title',
            entities_id: getWorkerEntityId(),
            answer: '<p>Editor content</p>',
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();

        // eslint-disable-next-line playwright/no-raw-locators
        const subject = page.locator('[data-glpi-kb-subject]');
        await subject.click();
        await page.keyboard.press('Enter');

        // The ProseMirror editor should now be focused
        // eslint-disable-next-line playwright/no-raw-locators
        const editor = page.locator('[data-glpi-kb-content]').locator('.ProseMirror');
        await expect(editor).toBeFocused();
    });

    test('Cancel restores original title', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Title Before Cancel',
            entities_id: getWorkerEntityId(),
            answer: '<p>Content</p>',
        });

        await kb.goto(id);
        // eslint-disable-next-line playwright/no-raw-locators
        const subject = page.locator('[data-glpi-kb-subject]');
        await expect(subject).toHaveText('Title Before Cancel');

        await kb.editor.enterEditMode();

        // Modify the title
        await subject.click();
        await page.keyboard.press('Control+a');
        await page.keyboard.type('Modified Title');
        await expect(subject).toHaveText('Modified Title');

        // Cancel should restore
        await kb.editor.cancel();
        await expect(subject).toHaveText('Title Before Cancel');
        await expect(subject).toHaveAttribute('contenteditable', 'false');
    });
});
