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
            await expect(placeholder).toHaveCount(1);
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
            await expect(placeholder).toHaveCount(1);
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

    test.describe('Server-side rendering', () => {

        test('Saved YouTube embed renders as a sandboxed iframe', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

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
            await expect(iframe).toHaveAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation allow-popups');
            await expect(iframe).toHaveAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        });

        test('Saved Dailymotion embed renders as a sandboxed iframe', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Stored Dailymotion embed',
                entities_id: getWorkerEntityId(),
                answer: '<p>Watch:</p><div class="video-embed" data-video-provider="dailymotion" data-video-id="x7ufrcj"></div>',
            });

            await kb.goto(id);

            const iframe = kb.videoEmbedIframes;
            await expect(iframe).toHaveCount(1);
            await expect(iframe).toHaveAttribute('src', /^https:\/\/www\.dailymotion\.com\/embed\/video\/x7ufrcj$/);
            await expect(iframe).toHaveAttribute('loading', 'lazy');
            await expect(iframe).toHaveAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation allow-popups');
            await expect(iframe).toHaveAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        });

        test('Saved Vimeo embed renders as a sandboxed iframe', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Stored Vimeo embed',
                entities_id: getWorkerEntityId(),
                answer: '<p>Watch:</p><div class="video-embed" data-video-provider="vimeo" data-video-id="76979871"></div>',
            });

            await kb.goto(id);

            const iframe = kb.videoEmbedIframes;
            await expect(iframe).toHaveCount(1);
            await expect(iframe).toHaveAttribute('src', /^https:\/\/player\.vimeo\.com\/video\/76979871\?dnt=1$/);
            await expect(iframe).toHaveAttribute('loading', 'lazy');
            await expect(iframe).toHaveAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation allow-popups');
            await expect(iframe).toHaveAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        });

        test('Stored placeholder with an unknown provider is dropped at render', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Stored unknown provider',
                entities_id: getWorkerEntityId(),
                answer: '<p>Before</p><div class="video-embed" data-video-provider="evil" data-video-id="exploit"></div><p>After</p>',
            });

            await kb.goto(id);

            await expect(kb.videoEmbedIframes).toHaveCount(0);
            await expect(kb.editor.contentContainer).toContainText('Before');
            await expect(kb.editor.contentContainer).toContainText('After');
        });

        test('Video inserted via dialog survives save and re-edit without duplication', async ({ page, profile, api }) => {
            await profile.set(Profiles.SuperAdmin);
            const kb = new KnowbaseItemPage(page);

            const id = await api.createItem('KnowbaseItem', {
                name: 'Round-trip video embed',
                entities_id: getWorkerEntityId(),
                answer: '<p>Content</p>',
            });

            await kb.goto(id);
            await kb.editor.enterEditMode();
            await kb.editor.clearContent();

            await kb.slashMenu.open();
            await kb.slashMenu.selectByClick('Video');

            const dialog = kb.videoDialog;
            await dialog.getByLabel('Video URL').fill('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
            await dialog.getByRole('button', { name: 'Insert' }).click();
            await expect(dialog).toBeHidden();

            await kb.editor.save();

            await expect(kb.videoEmbedIframes).toHaveCount(1);

            await kb.editor.enterEditMode();

            await expect(kb.videoEmbedPlaceholders).toHaveCount(1);
            await expect(kb.videoEmbedPlaceholders).toHaveAttribute('data-video-provider', 'youtube');
            await expect(kb.videoEmbedPlaceholders).toHaveAttribute('data-video-id', 'dQw4w9WgXcQ');

            await kb.editor.save();

            await expect(kb.videoEmbedIframes).toHaveCount(1);
        });
    });
});
