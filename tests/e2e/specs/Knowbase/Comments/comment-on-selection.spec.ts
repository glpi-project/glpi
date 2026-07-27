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

test.describe('Knowledge Base - Comment on a text selection', () => {

    test('Can comment on a selection in read mode without entering edit mode', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test read-mode comment',
            entities_id: getWorkerEntityId(),
            answer: '<p>Selectable passage of text</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('Selectable passage of text');
        await kb.readModeCommentBubble.click();

        await expect(kb.getPendingAnchorQuote()).toHaveText('Selectable passage of text');
        await kb.getNewCommentTextarea().fill('A comment on the selection');
        await page.getByRole('button', { name: 'Add comment' }).click();

        await expect(kb.getComment('A comment on the selection')).toBeVisible();
        // Article was never put into edit mode.
        await expect(page.getByTestId('edit-button')).toBeVisible();
        await expect(page.getByTestId('save-button')).toBeHidden();
    });

    test('Bubble is hidden without a selection and reappears when text is selected', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test read-mode bubble visibility',
            entities_id: getWorkerEntityId(),
            answer: '<p>Some plain text</p>',
        });

        await kb.goto(id);
        await kb.readModeCommentBubble.assertHidden();

        await kb.selectTextInReadMode('plain text');
        await kb.readModeCommentBubble.assertVisible();
    });

    test('Clicking a highlighted comment opens the panel and scrolls to it', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test click-to-open highlight',
            entities_id: getWorkerEntityId(),
            answer: '<p>Text with a highlighted part inside</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('highlighted part');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment for click-to-open test');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment for click-to-open test')).toBeVisible();

        // Reload so we exercise the highlight rendered from server data, not
        // just the live DOM left over from submitting.
        await page.reload();
        await kb.waitForArticleReady();

        const highlight = kb.getCommentHighlightByText('highlighted part');
        await expect(highlight).toBeVisible();
        await highlight.click();

        await expect(kb.getComment('Comment for click-to-open test')).toBeVisible();
        // A still-resolvable anchor must keep citing its passage.
        await expect(kb.getCommentAnchorQuotes()).toHaveCount(1);
    });

    test('A highlight created in read mode is still shown in edit mode', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test cross-mode highlight',
            entities_id: getWorkerEntityId(),
            answer: '<p>An anchored passage near the start</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('anchored passage');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment kept across modes');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment kept across modes')).toBeVisible();

        await page.reload();
        await kb.waitForArticleReady();
        await expect(kb.getCommentHighlightByText('anchored passage')).toBeVisible();

        // ProseMirror text has none of the template's indentation whitespace.
        await kb.editor.enterEditMode();
        await expect(kb.getCommentHighlightByText('anchored passage')).toBeVisible();
    });

    test('Commenting works on a selection spanning a whole paragraph', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test paragraph-wide selection',
            entities_id: getWorkerEntityId(),
            answer: '<p>A whole paragraph selected at once</p>',
        });

        await kb.goto(id);
        // Range boundaries land on the <p>, not on a text node.
        await kb.selectWholeParagraphInReadMode('whole paragraph');
        await kb.readModeCommentBubble.click();

        await expect(kb.getPendingAnchorQuote()).toHaveText('A whole paragraph selected at once');
    });

    test('A comment survives editing away its quoted text, without a highlight nor a quote', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test orphaned anchor',
            entities_id: getWorkerEntityId(),
            answer: '<p>This part will be removed later</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('will be removed');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment that will be orphaned');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment that will be orphaned')).toBeVisible();

        await kb.editor.enterEditMode();
        await kb.editor.setContent('This part is completely different now');
        await kb.editor.save();

        await kb.doOpenCommentsPanel();
        await expect(kb.getComment('Comment that will be orphaned')).toBeVisible();
        await expect(kb.getCommentHighlights()).toHaveCount(0);
        await expect(kb.getCommentAnchorQuotes()).toHaveCount(0);

        // The anchor is still in DB: the quote must stay hidden once re-sent by the server.
        await page.reload();
        await kb.waitForArticleReady();
        await kb.doOpenCommentsPanel();
        await expect(kb.getComment('Comment that will be orphaned')).toBeVisible();
        await expect(kb.getCommentAnchorQuotes()).toHaveCount(0);
    });
});
