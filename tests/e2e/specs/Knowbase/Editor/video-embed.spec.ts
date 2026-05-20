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

test.describe('Knowledge Base Editor - Video Embed', () => {

    test.describe('Slash command /Video dialog', () => {

        test('Insert YouTube video via the /Video dialog', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Insert YouTube via dialog',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Video');

            const dialog = kb.videoDialog;
            await expect(dialog).toBeVisible();

            await dialog.getByLabel('Video URL').fill('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
            await dialog.getByRole('button', { name: 'Insert' }).click();
            await expect(dialog).toBeHidden();

            // Placeholder is created in the editor with the parsed attrs
            const placeholder = kb.videoEmbedPlaceholders;
            await expect(placeholder).toHaveCount(1);
            await expect(placeholder).toHaveAttribute('data-video-provider', 'youtube');
            await expect(placeholder).toHaveAttribute('data-video-id', 'dQw4w9WgXcQ');
        });

        test('Insert Dailymotion video via the /Video dialog', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Insert Dailymotion via dialog',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Video');

            const dialog = kb.videoDialog;
            await dialog.getByLabel('Video URL').fill('https://www.dailymotion.com/video/x7ufrcj');
            await dialog.getByRole('button', { name: 'Insert' }).click();
            await expect(dialog).toBeHidden();

            const placeholder = kb.videoEmbedPlaceholders;
            await expect(placeholder).toHaveAttribute('data-video-provider', 'dailymotion');
            await expect(placeholder).toHaveAttribute('data-video-id', 'x7ufrcj');
        });

        test('Insert Vimeo video via the /Video dialog', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Insert Vimeo via dialog',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Video');

            const dialog = kb.videoDialog;
            await dialog.getByLabel('Video URL').fill('https://vimeo.com/76979871');
            await dialog.getByRole('button', { name: 'Insert' }).click();
            await expect(dialog).toBeHidden();

            const placeholder = kb.videoEmbedPlaceholders;
            await expect(placeholder).toHaveAttribute('data-video-provider', 'vimeo');
            await expect(placeholder).toHaveAttribute('data-video-id', '76979871');
        });

        test('Dialog rejects an unsupported URL and stays open', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Reject unsupported URL',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Video');

            const dialog = kb.videoDialog;
            await dialog.getByLabel('Video URL').fill('https://example.com/not-a-video');
            await dialog.getByRole('button', { name: 'Insert' }).click();

            await expect(dialog).toBeVisible();
            await expect(dialog.getByRole('alert')).toBeVisible();
            await expect(kb.videoEmbedPlaceholders).toHaveCount(0);
        });

        test('Dialog closes on Escape and inserts nothing', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Video dialog Escape',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Video');

            const dialog = kb.videoDialog;
            await expect(dialog).toBeVisible();

            await page.keyboard.press('Escape');
            await expect(dialog).toBeHidden();
            await expect(kb.videoEmbedPlaceholders).toHaveCount(0);
        });
    });

    test.describe('Paste recognition', () => {

        test('Pasting a YouTube URL converts it to an embed placeholder', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'YouTube paste-to-embed',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            // eslint-disable-next-line playwright/no-raw-locators
            await kb.editor.contentContainer.locator('.ProseMirror').evaluate((element) => {
                const dataTransfer = new DataTransfer();
                dataTransfer.setData('text/plain', 'https://youtu.be/dQw4w9WgXcQ');
                element.dispatchEvent(new ClipboardEvent('paste', {
                    bubbles: true,
                    cancelable: true,
                    clipboardData: dataTransfer,
                }));
            });

            const placeholder = kb.videoEmbedPlaceholders;
            await expect(placeholder).toHaveAttribute('data-video-provider', 'youtube');
            await expect(placeholder).toHaveAttribute('data-video-id', 'dQw4w9WgXcQ');
        });

        test('Pasting a Vimeo URL converts it to an embed placeholder', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Vimeo paste-to-embed',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            // eslint-disable-next-line playwright/no-raw-locators
            await kb.editor.contentContainer.locator('.ProseMirror').evaluate((element) => {
                const dataTransfer = new DataTransfer();
                dataTransfer.setData('text/plain', 'https://vimeo.com/76979871');
                element.dispatchEvent(new ClipboardEvent('paste', {
                    bubbles: true,
                    cancelable: true,
                    clipboardData: dataTransfer,
                }));
            });

            const placeholder = kb.videoEmbedPlaceholders;
            await expect(placeholder).toHaveAttribute('data-video-provider', 'vimeo');
            await expect(placeholder).toHaveAttribute('data-video-id', '76979871');
        });
    });

    test.describe('Server-side rendering', () => {

        test('Saved YouTube embed renders as a sandboxed iframe', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            // Pre-seed the answer with the canonical placeholder, exactly as
            // the editor would store it.
            const id = await api.createItem('KnowbaseItem', {
                name: 'Stored YouTube embed',
                entities_id: getWorkerEntityId(),
                answer: '<p>Watch:</p><div class="video-embed" data-video-provider="youtube" data-video-id="dQw4w9WgXcQ"></div>',
            });

            await kb.goto(id);

            const iframe = kb.videoEmbedIframes;
            await expect(iframe).toHaveCount(1);
            await expect(iframe).toHaveAttribute('src', /^https:\/\/www\.youtube-nocookie\.com\/embed\/dQw4w9WgXcQ$/);
            await expect(iframe).toHaveAttribute('loading', 'lazy');
            await expect(iframe).toHaveAttribute('sandbox', /allow-scripts/);
            await expect(iframe).toHaveAttribute('sandbox', /allow-same-origin/);
            await expect(iframe).toHaveAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        });

        test('Stored placeholder with an unknown provider is dropped at render', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Stored unknown provider',
                entities_id: getWorkerEntityId(),
                answer: '<p>Before</p><div data-video-provider="evil" data-video-id="exploit"></div><p>After</p>',
            });

            await kb.goto(id);

            await expect(kb.videoEmbedIframes).toHaveCount(0);
            await expect(kb.editor.contentContainer).toContainText('Before');
            await expect(kb.editor.contentContainer).toContainText('After');
        });
    });
});
