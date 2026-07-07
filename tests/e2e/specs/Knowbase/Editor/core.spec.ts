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
import { KnowbaseApi } from '../../../utils/KnowbaseApi';
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";
import { getUniqueName } from "../../../utils/Random";

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
        await kb.editor.assertContainsText('Initial content');

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
        await kb.editor.assertContainsText('Updated content');
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
        await expect(kb.subject).toHaveText('Original Title');

        await kb.editor.enterEditMode();

        // Title should be contenteditable
        await expect(kb.subject).toHaveAttribute('contenteditable', 'true');

        // Clear and type new title
        await kb.subject.click();
        await page.keyboard.press('Control+a');
        await page.keyboard.type('Updated Title');

        await kb.editor.save();

        await expect(kb.subject).toHaveText('Updated Title');

        // Verify persistence after reload
        await page.reload();
        await expect(kb.subject).toHaveText('Updated Title');
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

        await kb.subject.click();
        await page.keyboard.press('Enter');

        // The ProseMirror editor should now be focused
        await expect(kb.editor.getEditor()).toBeFocused();
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
        await expect(kb.subject).toHaveText('Title Before Cancel');

        await kb.editor.enterEditMode();

        // Modify the title
        await kb.subject.click();
        await page.keyboard.press('Control+a');
        await page.keyboard.type('Modified Title');
        await expect(kb.subject).toHaveText('Modified Title');

        // Cancel should restore
        await kb.editor.cancel();
        await expect(kb.subject).toHaveText('Title Before Cancel');
        await expect(kb.subject).toHaveAttribute('contenteditable', 'false');
    });

    test('Renaming an article updates its name in the aside tree', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const category_name = getUniqueName(`E2E Category`);
        const original_name = getUniqueName(`Original article`);
        const renamed_name  = getUniqueName(`Renamed article`);

        const category_id = await api.createItem('KnowbaseItemCategory', {
            name: category_name,
            entities_id: getWorkerEntityId(),
        });
        const article_id = await api.createItem('KnowbaseItem', {
            name: original_name,
            answer: '<p>Some content</p>',
            entities_id: getWorkerEntityId(),
            _categories: [category_id],
        });

        await kb.goto(article_id);

        // The aside tree shows the original name.
        await expect(kb.getAsideCategoryArticle(category_name, original_name)).toBeVisible();

        // Rename the article inline.
        await kb.editor.enterEditMode();
        await kb.subject.click();
        await page.keyboard.press('Control+a');
        await page.keyboard.type(renamed_name);
        await kb.editor.save();

        await expect(kb.subject).toHaveText(renamed_name);

        // The aside tree reflects the new name without a page reload.
        await expect(kb.getAsideCategoryArticle(category_name, renamed_name)).toBeVisible();
        await expect(kb.getAsideCategoryArticle(category_name, original_name)).toBeHidden();
    });

    test('Renaming a favorited article updates its name in the favorites section', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);
        const kb_api = new KnowbaseApi(api);

        const original_name = getUniqueName(`Original favorite`);
        const renamed_name  = getUniqueName(`Renamed favorite`);

        const article_id = await kb_api.createArticle({ name: original_name });
        await kb_api.addFavorite(article_id);

        await kb.goto(article_id);

        // The favorites section shows the original name.
        await expect(kb.getFavoriteArticle(original_name)).toBeVisible();

        // Rename the article inline.
        await kb.editor.enterEditMode();
        await kb.subject.click();
        await page.keyboard.press('Control+a');
        await page.keyboard.type(renamed_name);
        await kb.editor.save();

        // The favorites entry reflects the new name without a page reload.
        await expect(kb.getFavoriteArticle(renamed_name)).toBeVisible();
        await expect(kb.getFavoriteArticle(original_name)).toBeHidden();
    });
});
