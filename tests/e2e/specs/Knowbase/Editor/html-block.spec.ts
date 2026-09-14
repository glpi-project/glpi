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

test.describe('Knowledge Base Editor - HTML Block', () => {

    test.describe('Node round-trip', () => {

        test('A stored HTML block renders in read mode and survives edit + save unchanged', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Stored HTML block round-trip',
                entities_id: getWorkerEntityId(),
                answer: '<p>Before</p><div class="kb-html-block"><p>Custom <strong>markup</strong></p></div><p>After</p>',
            });

            await kb.goto(id);
            // Read mode is plain stored HTML with no node-view chrome, so the
            // assertion is on the rendered article, not on the wrapper.
            await expect(kb.editor.contentContainer).toContainText('Custom markup');

            await kb.editor.enterEditMode();
            await expect(kb.htmlBlock).toBeVisible();
            await expect(kb.htmlBlock).toContainText('Custom markup');

            await kb.editor.save();
            await page.reload();

            // Re-entering edit mode is what actually proves the round-trip: the
            // wrapper had to survive storage *and* be re-parsed into the node.
            await kb.editor.enterEditMode();
            await expect(kb.htmlBlock).toBeVisible();
            await expect(kb.htmlBlock).toContainText('Custom markup');
            await expect(kb.editor.contentContainer).toContainText('Before');
            await expect(kb.editor.contentContainer).toContainText('After');
        });
    });

    test.describe('Pasted HTML', () => {

        test('A pasted HTML block is re-parsed as plain content, never rendered raw', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Pasted HTML block',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            // ProseMirror reads `clipboardData` the same way for a real Ctrl+V
            // of attacker-controlled HTML from another site.
            await page.evaluate(() => {
                const data = new DataTransfer();
                data.setData(
                    'text/html',
                    '<div class="kb-html-block"><p>Pasted</p><img src="x" alt="Pasted image" onerror="window.__kbXss = true"></div>'
                );
                document.activeElement?.dispatchEvent(
                    new ClipboardEvent('paste', { clipboardData: data, bubbles: true, cancelable: true })
                );
            });

            await expect(kb.editor.contentContainer).toContainText('Pasted');
            await expect(kb.htmlBlock).toHaveCount(0);

            // Wait for the broken `src` to error, so `onerror` would have run by now.
            const image = kb.editor.contentContainer.getByRole('img', { name: 'Pasted image' });
            await expect.poll(() => image.evaluate((img: HTMLImageElement) => img.complete)).toBe(true);
            expect(await page.evaluate(() => (window as Window & { __kbXss?: boolean }).__kbXss)).toBeUndefined();
        });
    });

    test.describe('Slash command /HTML Block dialog', () => {

        test('HTML Block is hidden from the menu while composing a brand-new article', async ({ page, profile }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            await page.goto('/front/knowbaseitem.form.php');

            // No read/edit toggle exists on the add-article page, so
            // enterEditMode() is skipped — but `#kb-tiptap-editor` and
            // `[data-glpi-kb-content]` are the same element
            // (templates/pages/tools/kb/article.html.twig:297-299), so
            // TipTapEditorHelper.getEditor()'s fallback resolves here and
            // SlashMenuHelper works unchanged. No raw locator needed.
            const menu = await kb.slashMenu.open();
            await expect(menu.getByRole('button', { name: 'HTML Block' })).toBeHidden();
            // Sanity check: the menu really is populated, so the assertion
            // above is about this one entry and not an empty menu.
            await expect(menu.getByRole('button', { name: 'Table' })).toBeVisible();

            await kb.slashMenu.close();
        });

        test('Insert an HTML block via the slash command', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Insert HTML block via dialog',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('HTML Block');

            const dialog = kb.htmlBlockDialog;
            await expect(dialog).toBeVisible();

            await dialog.getByLabel('HTML source').fill('<p onclick="alert(1)">Hi</p><script>alert(1)</script>');

            await expect(dialog.getByRole('region', { name: 'Preview' })).toContainText('Hi');
            await expect(dialog.getByRole('button', { name: 'Save' })).toBeEnabled();

            await dialog.getByRole('button', { name: 'Save' }).click();
            await expect(dialog).toBeHidden();

            // `toHaveText` (not `toContainText`): the `<script>` was dropped
            // outright by the sanitizer, so its "alert(1)" body must not have
            // leaked into the block as text either.
            await expect(kb.htmlBlock).toHaveText('Hi');

            await kb.editor.save();

            await page.reload();
            await kb.editor.enterEditMode();
            await expect(kb.htmlBlock).toHaveText('Hi');
        });

        test('Save is disabled whenever the source is empty', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'HTML block save gating',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('HTML Block');

            const dialog = kb.htmlBlockDialog;
            const source = dialog.getByLabel('HTML source');
            const saveBtn = dialog.getByRole('button', { name: 'Save' });

            // Nothing typed yet: no round trip has happened, so Save is inert.
            await expect(saveBtn).toBeDisabled();

            await source.fill('<p>Something</p>');
            await expect(saveBtn).toBeEnabled();

            // Emptying the source must revoke the previously sanitized value
            // rather than leave the stale one behind.
            await source.fill('   ');
            await expect(saveBtn).toBeDisabled();

            await page.keyboard.press('Escape');
            await expect(dialog).toBeHidden();
        });

        test('Source with no rich-text tag is escaped to visible text, not rejected', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'HTML block plain-text fallback',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('HTML Block');

            const dialog = kb.htmlBlockDialog;
            await dialog.getByLabel('HTML source').fill('<script>alert(1)</script>');

            // `RichText::getSafeHtml()` only takes its sanitizing path when
            // `isRichTextHtmlContent()` finds one of its allowlisted tags
            // (RichText.php:186) — `script` is not among them, so this input
            // takes the plain-text branch instead and comes back HTML-escaped
            // and wrapped in a <p>, never empty.
            //
            // Asserting the angle brackets are present *as text* is what proves
            // it was escaped rather than parsed: real markup would contribute no
            // such characters to textContent.
            await expect(dialog.getByRole('region', { name: 'Preview' }))
                .toContainText('<script>alert(1)</script>');
            await expect(dialog.getByRole('button', { name: 'Save' })).toBeEnabled();

            await dialog.getByRole('button', { name: 'Save' }).click();
            await expect(kb.htmlBlock).toContainText('<script>alert(1)</script>');
        });

        test('Save stays disabled when the sanitize request fails', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'HTML block preview failure',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('HTML Block');

            const dialog = kb.htmlBlockDialog;
            const source = dialog.getByLabel('HTML source');

            // Same pattern as kb-aside-search.spec.ts:215-224.
            await page.route('**/Knowbase/KnowbaseItem/*/SanitizeHtmlBlock', (route) =>
                route.fulfill({ status: 500, body: '' })
            );

            await source.fill('<p>Hi</p>');
            await expect(dialog.getByRole('alert')).toBeVisible();
            await expect(dialog.getByRole('button', { name: 'Save' })).toBeDisabled();
            // No data loss: whatever was typed is still there to retry with.
            await expect(source).toHaveValue('<p>Hi</p>');

            await page.keyboard.press('Escape');
            await expect(dialog).toBeHidden();
        });

        test('Save never stores a preview older than the current source', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'HTML block stale preview',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('HTML Block');

            const dialog = kb.htmlBlockDialog;
            const source = dialog.getByLabel('HTML source');
            const saveBtn = dialog.getByRole('button', { name: 'Save' });

            await source.fill('<p>Old</p>');
            await expect(saveBtn).toBeEnabled();

            // Hold every further sanitize response, so the edit below stays
            // un-previewed for as long as the assertion needs.
            let release!: () => void;
            const held = new Promise<void>((resolve) => { release = resolve; });
            await page.route('**/Knowbase/KnowbaseItem/*/SanitizeHtmlBlock', async (route) => {
                await held;
                await route.continue();
            });

            await source.fill('<p>New</p>');
            // Still enabled here would mean a click saves "Old".
            await expect(saveBtn).toBeDisabled();

            release();
            await expect(dialog.getByRole('region', { name: 'Preview' })).toHaveText('New');
            await saveBtn.click();
            await expect(kb.htmlBlock).toHaveText('New');
        });
    });

    test.describe('Edit an inserted HTML block', () => {

        test('Reopening an inserted block shows its original source and updates in place', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Edit HTML block in place',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p><div class="kb-html-block"><p>Original</p></div>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();

            await kb.htmlBlock.hover();
            await page.getByRole('button', { name: 'Edit HTML block' }).click();

            const dialog = kb.htmlBlockDialog;
            await expect(dialog).toBeVisible();
            await expect(dialog.getByLabel('HTML source')).toHaveValue('<p>Original</p>');

            await dialog.getByLabel('HTML source').fill('<p>Updated</p>');
            await expect(dialog.getByRole('region', { name: 'Preview' })).toContainText('Updated');
            await dialog.getByRole('button', { name: 'Save' }).click();
            await expect(dialog).toBeHidden();

            await expect(kb.htmlBlock).toContainText('Updated');
            await expect(kb.htmlBlock).not.toContainText('Original');

            await kb.editor.save();
            await page.reload();
            await kb.editor.enterEditMode();
            await expect(kb.htmlBlock).toContainText('Updated');
        });
    });
});
