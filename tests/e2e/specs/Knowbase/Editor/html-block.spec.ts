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

import { Page } from "@playwright/test";
import { expect, test } from "../../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../../pages/KnowbaseItemPage";
import { Api } from "../../../utils/Api";
import { Profiles } from "../../../utils/Profiles";
import { ProfileSwitcher } from "../../../utils/ProfileSwitcher";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

/** Create an article, empty it in edit mode and open the HTML block dialog from the slash menu. */
async function openNewHtmlBlockDialog(page: Page, profile: ProfileSwitcher, api: Api, name: string) {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name,
        entities_id: getWorkerEntityId(),
        answer: '<p>Content</p>',
    });

    await kb.goto(id);
    await kb.editor.enterEditMode();
    await kb.editor.clearContent();

    await kb.slashMenu.open();
    await kb.slashMenu.selectByClick('HTML Block');

    return { kb, dialog: kb.htmlBlockDialog };
}

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
            // Read mode has no node view, so check the article text.
            await expect(kb.editor.contentContainer).toContainText('Custom markup');

            await kb.editor.enterEditMode();
            await expect(kb.htmlBlock).toBeVisible();
            await expect(kb.htmlBlock).toContainText('Custom markup');

            await kb.editor.save();
            await page.reload();

            // Proves the block survived storage and was parsed back into a node.
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

            // Same code path as a real Ctrl+V of external HTML.
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

            // Wait for the broken `src` to fail, so `onerror` would have fired.
            const image = kb.editor.contentContainer.getByRole('img', { name: 'Pasted image' });
            await expect.poll(() => image.evaluate((img: HTMLImageElement) => img.complete)).toBe(true);
            expect(await page.evaluate(() => (window as Window & { __kbXss?: boolean }).__kbXss)).toBeUndefined();
        });

        test('A block copied right after insertion survives being pasted back', async ({ page, profile, api }) => {
            const { kb, dialog } = await openNewHtmlBlockDialog(page, profile, api, 'HTML block copy round-trip');
            // `@` is numerically encoded by the sanitizer but not by the browser, so the node attribute and its clipboard form differ until the block has made a round trip through the DOM.
            await dialog.getByLabel('HTML source').fill('<p>Mail: bob@corp.tld</p>');
            await expect(dialog.getByRole('button', { name: 'Save' })).toBeEnabled();
            await dialog.getByRole('button', { name: 'Save' }).click();
            await expect(dialog).toBeHidden();
            await expect(kb.htmlBlock).toContainText('Mail: bob@corp.tld');

            // Select the freshly inserted node, then copy and paste it back over itself.
            await kb.htmlBlock.getByText('Mail: bob@corp.tld').click();
            await page.evaluate(() => {
                const data = new DataTransfer();
                const target = document.activeElement;
                target?.dispatchEvent(new ClipboardEvent('copy', { clipboardData: data, bubbles: true, cancelable: true }));
                target?.dispatchEvent(new ClipboardEvent('paste', { clipboardData: data, bubbles: true, cancelable: true }));
            });

            // Still a block, not unwrapped into a bare paragraph.
            await expect(kb.htmlBlock).toHaveCount(1);
            await expect(kb.htmlBlock).toContainText('Mail: bob@corp.tld');
        });
    });

    test.describe('Slash command /HTML Block dialog', () => {

        test('HTML Block is hidden from the menu while composing a brand-new article', async ({ page, profile }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            await page.goto('/front/knowbaseitem.form.php');

            // The add page has no edit toggle, helpers still find the editor.
            const menu = await kb.slashMenu.open();
            await expect(menu.getByRole('button', { name: 'HTML Block' })).toBeHidden();
            // Ensures the menu is populated.
            await expect(menu.getByRole('button', { name: 'Table' })).toBeVisible();

            await kb.slashMenu.close();
        });

        test('Insert an HTML block via the slash command', async ({ page, profile, api }) => {
            const { kb, dialog } = await openNewHtmlBlockDialog(page, profile, api, 'Insert HTML block via dialog');
            await expect(dialog).toBeVisible();

            await dialog.getByLabel('HTML source').fill('<p onclick="alert(1)">Hi</p><script>alert(1)</script>');

            await expect(dialog.getByRole('region', { name: 'Preview' })).toContainText('Hi');
            await expect(dialog.getByRole('button', { name: 'Save' })).toBeEnabled();

            await dialog.getByRole('button', { name: 'Save' }).click();
            await expect(dialog).toBeHidden();

            // `toHaveText`: the script body must not leak in as text either.
            await expect(kb.htmlBlock).toHaveText('Hi');

            await kb.editor.save();

            await page.reload();
            await kb.editor.enterEditMode();
            await expect(kb.htmlBlock).toHaveText('Hi');
        });

        test('Save is disabled whenever the source is empty', async ({ page, profile, api }) => {
            const { dialog } = await openNewHtmlBlockDialog(page, profile, api, 'HTML block save gating');
            const source = dialog.getByLabel('HTML source');
            const saveBtn = dialog.getByRole('button', { name: 'Save' });

            // No preview yet.
            await expect(saveBtn).toBeDisabled();

            await source.fill('<p>Something</p>');
            await expect(saveBtn).toBeEnabled();

            // Clearing the source must discard the previous preview.
            await source.fill('   ');
            await expect(saveBtn).toBeDisabled();

            await page.keyboard.press('Escape');
            await expect(dialog).toBeHidden();
        });

        test('Tab stays inside the dialog while Save is disabled', async ({ page, profile, api }) => {
            const { dialog } = await openNewHtmlBlockDialog(page, profile, api, 'HTML block focus trap');
            const cancelBtn = dialog.getByRole('button', { name: 'Cancel' });
            const closeBtn = dialog.getByRole('button', { name: 'Close' });
            await expect(dialog.getByRole('button', { name: 'Save' })).toBeDisabled();

            // Save is disabled, so Cancel is the last focusable button.
            await cancelBtn.focus();
            await page.keyboard.press('Tab');
            await expect(closeBtn).toBeFocused();

            await page.keyboard.press('Shift+Tab');
            await expect(cancelBtn).toBeFocused();
        });

        test('Source with nothing left after sanitizing cannot be saved', async ({ page, profile, api }) => {
            const { dialog } = await openNewHtmlBlockDialog(page, profile, api, 'HTML block fully stripped source');
            await dialog.getByLabel('HTML source').fill('<script>alert(1)</script>');

            // `<script>` is dropped with its contents, so nothing remains to insert.
            await expect(dialog.getByRole('alert')).toContainText('Nothing safe to insert');
            await expect(dialog.getByRole('region', { name: 'Preview' })).toBeEmpty();
            await expect(dialog.getByRole('button', { name: 'Save' })).toBeDisabled();
        });

        test('Source using tags outside the rich-text guess is rendered, not escaped', async ({ page, profile, api }) => {
            const { kb, dialog } = await openNewHtmlBlockDialog(page, profile, api, 'HTML block uncommon tags');
            // None of these tags is in `isRichTextHtmlContent()`'s list, but the sanitizer allows them all.
            await dialog.getByLabel('HTML source').fill('<figure><figcaption>Schema</figcaption></figure>');

            // `toHaveText` is exact: escaped source would read '<figure><figcaption>Schema</figcaption></figure>'.
            await expect(dialog.getByRole('region', { name: 'Preview' })).toHaveText('Schema');
            await dialog.getByRole('button', { name: 'Save' }).click();
            await expect(dialog).toBeHidden();

            await expect(kb.htmlBlock).toHaveText('Schema');

            await kb.editor.save();
            await page.reload();
            await kb.editor.enterEditMode();
            await expect(kb.htmlBlock).toHaveText('Schema');
        });

        test('Save stays disabled when the sanitize request fails', async ({ page, profile, api }) => {
            const { dialog } = await openNewHtmlBlockDialog(page, profile, api, 'HTML block preview failure');
            const source = dialog.getByLabel('HTML source');

            await page.route('**/Knowbase/KnowbaseItem/*/SanitizeHtmlBlock', (route) =>
                route.fulfill({ status: 500, body: '' })
            );

            await source.fill('<p>Hi</p>');
            await expect(dialog.getByRole('alert')).toBeVisible();
            await expect(dialog.getByRole('button', { name: 'Save' })).toBeDisabled();
            // The typed source is kept.
            await expect(source).toHaveValue('<p>Hi</p>');

            await page.keyboard.press('Escape');
            await expect(dialog).toBeHidden();
        });

        test('Save never stores a preview older than the current source', async ({ page, profile, api }) => {
            const { kb, dialog } = await openNewHtmlBlockDialog(page, profile, api, 'HTML block stale preview');
            const source = dialog.getByLabel('HTML source');
            const saveBtn = dialog.getByRole('button', { name: 'Save' });

            await source.fill('<p>Old</p>');
            await expect(saveBtn).toBeEnabled();

            // Hold sanitize responses so the new source stays unpreviewed.
            let release!: () => void;
            const held = new Promise<void>((resolve) => { release = resolve; });
            await page.route('**/Knowbase/KnowbaseItem/*/SanitizeHtmlBlock', async (route) => {
                await held;
                await route.continue();
            });

            await source.fill('<p>New</p>');
            // Otherwise a click would save "Old".
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

        test('The edit button is gone once back in read mode', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'HTML block read mode after save',
                entities_id: getWorkerEntityId(),
                answer: '<div class="kb-html-block"><p>Original</p></div>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.save();

            // The node view stays mounted after leaving edit mode.
            await kb.htmlBlock.hover();
            await expect(page.getByRole('button', { name: 'Edit HTML block' })).toBeHidden();
        });
    });
});
