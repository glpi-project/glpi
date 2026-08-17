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

    test('A comment anchor survives edits to the surrounding words, keeping its quote unchanged', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test anchor survives context edits',
            entities_id: getWorkerEntityId(),
            answer: '<p>Some lead-in words. The quoted passage stays intact. Some trailing words.</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('The quoted passage stays intact.');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment on a passage kept as-is');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment on a passage kept as-is')).toBeVisible();

        await kb.editor.enterEditMode();
        await kb.editor.setContent(
            'Totally different lead-in now. The quoted passage stays intact. Totally different trailing now.'
        );
        await kb.editor.save();

        await page.reload();
        await kb.waitForArticleReady();
        await expect(kb.getCommentHighlightByText('The quoted passage stays intact.')).toBeVisible();

        await kb.doOpenCommentsPanel();
        await expect(kb.getComment('Comment on a passage kept as-is')).toBeVisible();
        await expect(kb.getCommentAnchorQuotes()).toHaveText('The quoted passage stays intact.');
    });

    test('A comment anchor follows its quoted passage when it is reworded', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test anchor follows reworded passage',
            entities_id: getWorkerEntityId(),
            answer: '<p>Lead-in context words. This passage needs a rewrite. Trailing context words.</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('This passage needs a rewrite.');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment on a passage being reworded');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment on a passage being reworded')).toBeVisible();

        await kb.editor.enterEditMode();
        await kb.editor.setContent(
            'Lead-in context words. This passage needs rewriting badly. Trailing context words.'
        );
        await kb.editor.save();

        await page.reload();
        await kb.waitForArticleReady();
        await expect(kb.getCommentHighlightByText('This passage needs rewriting badly.')).toBeVisible();

        await kb.doOpenCommentsPanel();
        await expect(kb.getComment('Comment on a passage being reworded')).toBeVisible();
        // The quote follows the rewritten text, not the stale stored `anchor_exact`.
        await expect(kb.getCommentAnchorQuotes()).toHaveText('This passage needs rewriting badly.');
    });

    test('A comment anchor survives an in-place edit even when its prefix repeats elsewhere', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test anchor with repeated prefix, unique suffix',
            entities_id: getWorkerEntityId(),
            answer: '<p>Shared lead-in phrase. The passage needs tweaking. '
                + 'Marker after passage. Shared lead-in phrase used again here.</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('The passage needs tweaking.');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment surviving a repeated prefix');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment surviving a repeated prefix')).toBeVisible();

        await kb.editor.enterEditMode();
        // Prefix repeats but suffix stays unique: must still resolve, not orphan.
        await kb.editor.setContent(
            'Shared lead-in phrase. The passage absolutely needs tweaking. '
            + 'Marker after passage. Shared lead-in phrase. Repeated once more.'
        );
        await kb.editor.save();

        await page.reload();
        await kb.waitForArticleReady();
        await expect(kb.getCommentHighlightByText('The passage absolutely needs tweaking.')).toBeVisible();

        await kb.doOpenCommentsPanel();
        await expect(kb.getComment('Comment surviving a repeated prefix')).toBeVisible();
        await expect(kb.getCommentAnchorQuotes()).toHaveText('The passage absolutely needs tweaking.');
    });

    test('A comment anchor resolves correctly when its quoted text also appears elsewhere in the article', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test anchor with a repeated quote',
            entities_id: getWorkerEntityId(),
            answer: '<p>Alpha section: a repeated phrase sits here. Beta section: a repeated phrase sits here.</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('a repeated phrase sits here.');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment on a phrase that repeats');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment on a phrase that repeats')).toBeVisible();

        await page.reload();
        await kb.waitForArticleReady();
        // Only the originally-quoted occurrence is highlighted, not both.
        await expect(kb.getCommentHighlightByText('a repeated phrase sits here.')).toHaveCount(1);

        await kb.doOpenCommentsPanel();
        await expect(kb.getComment('Comment on a phrase that repeats')).toBeVisible();
        await expect(kb.getCommentAnchorQuotes()).toHaveText('a repeated phrase sits here.');
    });

    test('A comment anchor stays orphaned when its context becomes ambiguous after a rewrite', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test anchor with ambiguous context after edit',
            entities_id: getWorkerEntityId(),
            answer: '<p>Unique lead-in context. The passage needs a rewrite. Unique trailing context.</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('The passage needs a rewrite.');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment orphaned by ambiguous context');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment orphaned by ambiguous context')).toBeVisible();

        await kb.editor.enterEditMode();
        // The quoted passage is gone, and its prefix now occurs twice: bracketing
        // can no longer tell which occurrence bounds the original quote.
        await kb.editor.setContent(
            'Unique lead-in context. This is totally different wording. '
            + 'Unique lead-in context. More filler content. Unique trailing context.'
        );
        await kb.editor.save();

        await kb.doOpenCommentsPanel();
        await expect(kb.getComment('Comment orphaned by ambiguous context')).toBeVisible();
        await expect(kb.getCommentHighlights()).toHaveCount(0);
        await expect(kb.getCommentAnchorQuotes()).toHaveCount(0);
    });

    test('A comment anchor stays orphaned when the rewritten passage exceeds the anchor limit', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test anchor with oversized rewritten passage',
            entities_id: getWorkerEntityId(),
            answer: '<p>Lead marker. The passage to change. Trail marker.</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('The passage to change.');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment orphaned by oversized rewrite');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment orphaned by oversized rewrite')).toBeVisible();

        await kb.editor.enterEditMode();
        // Prefix and suffix both stay unique, but the gap between them is over
        // KnowbaseItem_Comment::MAX_ANCHOR_LENGTH: bracketing must refuse it.
        await kb.editor.setContent(`Lead marker. ${'x'.repeat(1001)} Trail marker.`);
        await kb.editor.save();

        await kb.doOpenCommentsPanel();
        await expect(kb.getComment('Comment orphaned by oversized rewrite')).toBeVisible();
        await expect(kb.getCommentHighlights()).toHaveCount(0);
        await expect(kb.getCommentAnchorQuotes()).toHaveCount(0);
    });

    test('Comment button is disabled for a selection longer than the anchor limit', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        // Over KnowbaseItem_Comment::MAX_ANCHOR_LENGTH.
        const oversized = 'a'.repeat(1001);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test oversized selection',
            entities_id: getWorkerEntityId(),
            answer: `<p>${oversized}</p><p>Short passage</p>`,
        });

        await kb.goto(id);

        await kb.selectTextInReadMode(oversized);
        await kb.readModeCommentBubble.assertDisabled();

        await kb.selectTextInReadMode('Short passage');
        await kb.readModeCommentBubble.assertEnabled();
    });

    // Context shared by three paragraphs, each with a unique middle.
    const SHARED_HEAD = 'Sed ut perspiciatis unde omnis iste natus error sit accusantium laudantium totam rem aperiam. ';
    const SHARED_TAIL = ' Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit.';
    const QUOTED_MIDDLE = 'The second branch documents how to reset a federated account.';
    const EDITED_MIDDLE = 'The branch documents how to reset a federated account.';

    const repeatedContextArticle = () => [
        `<p>${SHARED_HEAD}The first branch documents how to reset a local account.${SHARED_TAIL}</p>`,
        `<p>${SHARED_HEAD}${QUOTED_MIDDLE}${SHARED_TAIL}</p>`,
        `<p>${SHARED_HEAD}The third branch documents how to reset a service account.${SHARED_TAIL}</p>`,
    ].join('');

    test('A highlight survives deleting a few words inside its quoted passage', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test in-place deletion inside a quote',
            entities_id: getWorkerEntityId(),
            answer: repeatedContextArticle(),
        });

        await kb.goto(id);
        await kb.selectTextInReadMode(QUOTED_MIDDLE);
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment kept through an in-place deletion');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment kept through an in-place deletion')).toBeVisible();

        await kb.editor.enterEditMode();
        await kb.selectTextInEditMode('second ');
        await kb.editor.pressKey('Backspace');

        // The highlight narrows to the surviving words instead of vanishing.
        await expect(kb.getCommentHighlightByText(EDITED_MIDDLE)).toBeVisible();
        await expect(kb.getCommentHighlights()).toHaveCount(1);
    });

    test('A highlight grows when words are typed inside its quoted passage', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test in-place insertion inside a quote',
            entities_id: getWorkerEntityId(),
            answer: repeatedContextArticle(),
        });

        await kb.goto(id);
        await kb.selectTextInReadMode(QUOTED_MIDDLE);
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment kept through an in-place insertion');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment kept through an in-place insertion')).toBeVisible();

        await kb.editor.enterEditMode();
        await kb.selectTextInEditMode('second');
        await kb.editor.typeText('newly rewritten second');

        await expect(
            kb.getCommentHighlightByText('The newly rewritten second branch documents how to reset a federated account.')
        ).toBeVisible();
    });

    test('An edited quote stays anchored through repeated saves', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test anchor refreshed on save',
            entities_id: getWorkerEntityId(),
            answer: repeatedContextArticle(),
        });

        await kb.goto(id);
        await kb.selectTextInReadMode(QUOTED_MIDDLE);
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment whose quote is refreshed on save');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment whose quote is refreshed on save')).toBeVisible();

        await kb.editor.enterEditMode();
        await kb.selectTextInEditMode('second ');
        await kb.editor.pressKey('Backspace');
        await kb.editor.save();

        // Reloading resolves against the stored anchor: it has to carry the edited quote.
        await page.reload();
        await kb.waitForArticleReady();
        await expect(kb.getCommentHighlightByText(EDITED_MIDDLE)).toBeVisible();
        await kb.doOpenCommentsPanel();
        await expect(kb.getCommentAnchorQuotes()).toHaveText(EDITED_MIDDLE);

        // Saving again must not report the comment as orphaned.
        await kb.editor.enterEditMode();
        await kb.editor.save();
        await page.reload();
        await kb.waitForArticleReady();
        await expect(kb.getCommentHighlightByText(EDITED_MIDDLE)).toBeVisible();
    });

    test('Undoing the removal of a quoted passage brings its highlight back', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test undo restores a highlight',
            entities_id: getWorkerEntityId(),
            answer: repeatedContextArticle(),
        });

        await kb.goto(id);
        await kb.selectTextInReadMode(QUOTED_MIDDLE);
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment restored by undo');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment restored by undo')).toBeVisible();

        await kb.editor.enterEditMode();
        await kb.selectTextInEditMode(QUOTED_MIDDLE);
        await kb.editor.pressKey('Backspace');
        await expect(kb.getCommentHighlights()).toHaveCount(0);

        await kb.editor.pressKey('Control+z');
        await expect(kb.getCommentHighlightByText(QUOTED_MIDDLE)).toBeVisible();
    });

    test('Any comment can be focused by clicking it, receding the others', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test comment focus',
            entities_id: getWorkerEntityId(),
            answer: '<p>A passage worth commenting on</p>',
        });

        await kb.goto(id);
        await kb.doOpenCommentsPanel();

        // Neither comment is anchored to a passage.
        await kb.getNewCommentTextarea().fill('First plain comment');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('First plain comment')).toBeVisible();
        await kb.getNewCommentTextarea().fill('Second plain comment');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Second plain comment')).toBeVisible();

        const first  = kb.getCommentThreadByContent('First plain comment');
        const second = kb.getCommentThreadByContent('Second plain comment');

        await expect(first).toHaveCSS('opacity', '1');
        await expect(second).toHaveCSS('opacity', '1');

        await kb.getComment('First plain comment').click();

        await expect(first).toHaveCSS('opacity', '1');
        await expect(second).toHaveCSS('opacity', '0.65');
    });

    test('Escape clears the comment focus', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test focus cleared by Escape',
            entities_id: getWorkerEntityId(),
            answer: '<p>Some article body</p>',
        });

        await kb.goto(id);
        await kb.doOpenCommentsPanel();

        await kb.getNewCommentTextarea().fill('Focused then released');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Focused then released')).toBeVisible();
        await kb.getNewCommentTextarea().fill('The other one');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('The other one')).toBeVisible();

        const other = kb.getCommentThreadByContent('The other one');

        await kb.getComment('Focused then released').click();
        await expect(other).toHaveCSS('opacity', '0.65');

        await page.keyboard.press('Escape');
        await expect(other).toHaveCSS('opacity', '1');
    });

    test('Clicking a highlight focuses its thread and keeps it focused', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test highlight focuses thread',
            entities_id: getWorkerEntityId(),
            answer: '<p>Text with a targeted part inside</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('targeted part');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Anchored comment');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Anchored comment')).toBeVisible();

        await kb.getNewCommentTextarea().fill('Unrelated comment');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Unrelated comment')).toBeVisible();

        await page.reload();
        await kb.waitForArticleReady();
        await kb.getCommentHighlightByText('targeted part').click();

        const anchored  = kb.getCommentThreadByContent('Anchored comment');
        const unrelated = kb.getCommentThreadByContent('Unrelated comment');

        // Persistent, unlike the former 2s flash.
        await expect(anchored).toHaveCSS('opacity', '1');
        await expect(unrelated).toHaveCSS('opacity', '0.65');
    });

    test('Focusing an anchored thread emphasises its passage in the article', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test reciprocal emphasis',
            entities_id: getWorkerEntityId(),
            answer: '<p>An emphasised passage lives here</p>',
        });

        await kb.goto(id);
        await kb.selectTextInReadMode('emphasised passage');
        await kb.readModeCommentBubble.click();
        await kb.getNewCommentTextarea().fill('Comment driving the emphasis');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment driving the emphasis')).toBeVisible();

        await page.reload();
        await kb.waitForArticleReady();
        await kb.doOpenCommentsPanel();

        expect(await kb.getCommentHighlightThickness('emphasised passage')).toBe('2px');

        await kb.getComment('Comment driving the emphasis').click();

        await expect
            .poll(() => kb.getCommentHighlightThickness('emphasised passage'))
            .toBe('3px');
    });

    test('Clicking the article body clears the comment focus', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);

        const id = await api.createItem('KnowbaseItem', {
            name: 'Test focus cleared from the article',
            entities_id: getWorkerEntityId(),
            answer: '<p>Plain body text with nothing anchored</p>',
        });

        await kb.goto(id);
        await kb.doOpenCommentsPanel();

        await kb.getNewCommentTextarea().fill('Comment to be released');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Comment to be released')).toBeVisible();
        await kb.getNewCommentTextarea().fill('Neighbour comment');
        await page.getByRole('button', { name: 'Add comment' }).click();
        await expect(kb.getComment('Neighbour comment')).toBeVisible();

        const neighbour = kb.getCommentThreadByContent('Neighbour comment');

        await kb.getComment('Comment to be released').click();
        await expect(neighbour).toHaveCSS('opacity', '0.65');

        await page.getByText('Plain body text with nothing anchored').click();
        await expect(neighbour).toHaveCSS('opacity', '1');
    });
});
