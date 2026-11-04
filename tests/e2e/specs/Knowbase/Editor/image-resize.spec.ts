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

import { Locator, Page } from "@playwright/test";
import { expect, test } from "../../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../../pages/KnowbaseItemPage";
import { Api } from "../../../utils/Api";
import { TEST_IMAGE_BASE64 } from "../../../utils/ImagePasteHelpers";
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

// 1024 x 494 px
const IMAGE_SRC = '/pics/screenshots/components.png';
const IMAGE_RATIO = 1024 / 494;
const IMAGE_HTML = `<img src="${IMAGE_SRC}" alt="Components" width="400" height="193">`;
async function getWidth(kb: KnowbaseItemPage, image: Locator): Promise<number> {
    return Math.round((await kb.editor.getBox(image)).width);
}

/**
 * Horizontal distance in px between the centers of `a` and `b`.
 */
async function getCenterOffset(kb: KnowbaseItemPage, a: Locator, b: Locator): Promise<number> {
    const box_a = await kb.editor.getBox(a);
    const box_b = await kb.editor.getBox(b);
    return Math.abs((box_a.x + box_a.width / 2) - (box_b.x + box_b.width / 2));
}

/**
 * `width` and `height` of the first image of the saved answer.
 */
async function getSavedImageSize(api: Api, id: number): Promise<{ width: number, height: number }> {
    const answer = (await api.getItem('KnowbaseItem', id)).answer;
    const html = answer.replaceAll('&#34;', '"').replaceAll('&quot;', '"');
    const width = /<img[^>]*\swidth="(\d+)"/.exec(html);
    const height = /<img[^>]*\sheight="(\d+)"/.exec(html);
    if (width === null || height === null) {
        throw new Error(`No sized image in: ${answer}`);
    }
    return { width: Number(width[1]), height: Number(height[1]) };
}

/**
 * Hold the image uploads until `release()`, then abort or continue them.
 * `requested` resolves when the first upload request is sent.
 */
async function holdUpload(page: Page, then: 'abort' | 'continue'): Promise<{ release: () => void, requested: Promise<void> }> {
    let release = () => {};
    const held = new Promise<void>((resolve) => {
        release = resolve;
    });
    let on_request = () => {};
    const requested = new Promise<void>((resolve) => {
        on_request = resolve;
    });
    await page.route('**/UploadInlineImage**', async (route) => {
        on_request();
        await held;
        await (then === 'abort' ? route.abort() : route.continue());
    });
    return { release, requested };
}

async function createArticle(api: Api, name: string, answer = `<p>Content</p>${IMAGE_HTML}`): Promise<number> {
    return api.createItem('KnowbaseItem', { name, entities_id: getWorkerEntityId(), answer });
}

test.describe('Knowledge Base Editor - Image Resize', () => {
    test.beforeEach(async ({ profile }) => {
        await profile.set(Profiles.SuperAdmin);
    });

    test('Dragging a corner resizes the image and keeps its ratio after reload', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize drag');

        const image = await kb.doSelectImageInEditMode(id, 'Components');
        await kb.editor.doDragImageCorner(image, -150);
        await kb.editor.save();

        const saved = await getSavedImageSize(api, id);
        expect(saved.width).toBeLessThan(400);
        expect(saved.width).toBeGreaterThan(200);
        expect(saved.width / saved.height).toBeCloseTo(IMAGE_RATIO, 1);

        await kb.goto(id);
        const box = await kb.editor.getImage('Components').boundingBox();
        expect(box?.width).toBeCloseTo(saved.width, 0);
        expect(await kb.editor.getResizeHandleCount()).toBe(0);
    });

    test('The image cannot be smaller than the minimum size', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize minimum');

        const image = await kb.doSelectImageInEditMode(id, 'Components');
        await kb.editor.doDragImageCorner(image, -1000);
        await kb.editor.save();

        const saved = await getSavedImageSize(api, id);
        expect(saved.width).toBeLessThan(150);
        expect(Math.min(saved.width, saved.height)).toBeGreaterThanOrEqual(50);
    });

    test('Undo restores the previous size on screen', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize undo');

        const image = await kb.doSelectImageInEditMode(id, 'Components');
        await kb.editor.doDragImageCorner(image, -150);
        await expect.poll(() => getWidth(kb, image)).toBeLessThan(300);

        await page.keyboard.press('Control+z');
        await expect.poll(() => getWidth(kb, image)).toBe(400);
    });

    test('Enter on a selected image opens the dialog and applies the new size', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize keyboard');

        const image = await kb.doSelectImageInEditMode(id, 'Components');
        await page.keyboard.press('Enter');

        const dialog = kb.imageDialog;
        await expect(dialog).toBeVisible();
        await expect(dialog.getByLabel('Source')).toHaveValue(IMAGE_SRC);
        await expect(dialog.getByLabel('Width')).toHaveValue('400');
        await expect(dialog.getByLabel('Width')).toBeFocused();
        await dialog.getByLabel('Width').fill('200');
        await dialog.getByRole('button', { name: 'Save' }).click();
        await expect(dialog).toBeHidden();

        await expect(kb.editor.contentContainer.getByRole('img')).toHaveCount(1);
        await expect.poll(() => getWidth(kb, image)).toBe(200);

        await kb.editor.save();
        const saved = await getSavedImageSize(api, id);
        expect(saved.width).toBe(200);
        expect(saved.height).toBe(Math.round(200 / IMAGE_RATIO));
    });

    test('Shift+Enter on a selected image does not open the dialog', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize shift enter');

        await kb.doSelectImageInEditMode(id, 'Components');
        await page.keyboard.press('Shift+Enter');
        await expect(kb.imageDialog).toBeHidden();
    });

    test('Escape on a selected image puts the caret in the next paragraph', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize escape', `<p>Content</p>${IMAGE_HTML}<p>After</p>`);
        const warnings: string[] = [];
        page.on('console', (message) => {
            if (message.type() === 'warning') {
                warnings.push(message.text());
            }
        });

        await kb.doSelectImageInEditMode(id, 'Components');
        await kb.bubbleMenu.assertVisible();
        await page.keyboard.press('Escape');
        await kb.bubbleMenu.assertHidden();
        await page.keyboard.type('X');

        await expect(kb.editor.contentContainer.getByText('XAfter')).toBeVisible();
        expect(warnings.filter((text) => text.includes('TextSelection'))).toEqual([]);
    });

    test('A size below the minimum is refused', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize dialog minimum');

        await kb.doSelectImageInEditMode(id, 'Components');
        await page.keyboard.press('Enter');
        const dialog = kb.imageDialog;
        await dialog.getByLabel('Width').fill('0');
        await dialog.getByRole('button', { name: 'Save' }).click();

        await expect(dialog.getByRole('alert').filter({ hasText: 'The minimum value is 50.' })).toBeVisible();
        await expect(dialog.getByLabel('Width')).toHaveAttribute('aria-invalid', 'true');
        await expect(dialog).toBeVisible();
    });

    test('The dialog keeps the stored ratio of an image that does not load', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(
            api,
            'Image resize dialog broken image',
            '<p>Content</p><img src="/pics/does-not-exist.png" alt="Components" width="400" height="200">',
        );

        await kb.doSelectImageInEditMode(id, 'Components');
        await page.keyboard.press('Enter');
        const dialog = kb.imageDialog;
        await dialog.getByLabel('Width').fill('200');
        await expect(dialog.getByLabel('Height')).toHaveValue('100');
    });

    test('Saving the dialog edits the right image after an upload removed another one', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize dialog stale position');

        const upload = await holdUpload(page, 'abort');

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.contentContainer.getByText('Content').click();
        await page.keyboard.press('Home');

        // The pasted image goes before the article image, then its upload fails.
        await kb.editor.doPasteHtml(`<img src="data:image/png;base64,${TEST_IMAGE_BASE64}">`);
        await upload.requested;

        const image = await kb.editor.doSelectImage('Components');
        await page.keyboard.press('Enter');
        const dialog = kb.imageDialog;
        await dialog.getByLabel('Width').fill('200');

        upload.release();
        await expect(kb.editor.contentContainer.getByRole('img')).toHaveCount(1);
        await dialog.getByRole('button', { name: 'Save' }).click();
        await expect(dialog).toBeHidden();

        await expect.poll(() => getWidth(kb, image)).toBe(200);
    });

    test('An upload that ends while the dialog is open does not replace the edited image', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize dialog upload ends');

        const upload = await holdUpload(page, 'continue');

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.contentContainer.getByText('Content').click();
        await kb.editor.doPastePngFile(TEST_IMAGE_BASE64);
        await upload.requested;

        await kb.editor.doSelectImage('Components');
        await page.keyboard.press('Enter');
        const dialog = kb.imageDialog;
        await expect(dialog.getByLabel('Width')).toBeFocused();

        upload.release();
        await expect(kb.editor.contentContainer.getByRole('img')).toHaveCount(2);
        await expect(kb.editor.getImage('Components')).toBeVisible();
        await expect(dialog.getByLabel('Width')).toBeFocused();
    });

    test('Editing the alt text of an image without size keeps it without size', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize no size', `<p>Content</p><img src="${IMAGE_SRC}" alt="Components">`);

        await kb.doSelectImageInEditMode(id, 'Components');
        await page.keyboard.press('Enter');
        const dialog = kb.imageDialog;
        await dialog.getByLabel('Alternative description').fill('New alt');
        await dialog.getByRole('button', { name: 'Save' }).click();
        await expect(dialog).toBeHidden();
        await kb.editor.save();

        const answer = (await api.getItem('KnowbaseItem', id)).answer;
        expect(answer).toContain('New alt');
        expect(answer).not.toMatch(/<img[^>]*\s(width|height)=/);
    });

    test.describe('With a touch screen', () => {
        test.use({ hasTouch: true });

        test('A touch drag on a corner saves the new size', async ({ page, api }) => {
            const kb = new KnowbaseItemPage(page);
            const id = await createArticle(api, 'Image resize touch');

            const image = await kb.doSelectImageInEditMode(id, 'Components');
            const drag = await kb.editor.doStartTouchDragOnImageCorner(image);
            await drag.moveBy(-150);
            await drag.end();
            await kb.editor.save();

            const saved = await getSavedImageSize(api, id);
            expect(saved.width).toBeLessThan(300);
        });

        test('A second finger that lifts does not end the drag', async ({ page, api }) => {
            const kb = new KnowbaseItemPage(page);
            const id = await createArticle(api, 'Image resize two fingers');

            const image = await kb.doSelectImageInEditMode(id, 'Components');
            const drag = await kb.editor.doStartTouchDragOnImageCorner(image);
            await drag.moveBy(-30);
            await drag.liftOtherFinger();
            await drag.moveBy(-120);
            await drag.end();
            await kb.editor.save();

            const saved = await getSavedImageSize(api, id);
            expect(saved.width).toBeLessThan(300);
        });

        test('A cancelled touch drag keeps the old size', async ({ page, api }) => {
            const kb = new KnowbaseItemPage(page);
            const id = await createArticle(api, 'Image resize touch cancel');

            const image = await kb.doSelectImageInEditMode(id, 'Components');
            const drag = await kb.editor.doStartTouchDragOnImageCorner(image);
            await drag.moveBy(-150);
            await drag.cancel();

            await expect.poll(() => getWidth(kb, image)).toBe(400);
            await kb.editor.save();
            const saved = await getSavedImageSize(api, id);
            expect(saved.width).toBe(400);
        });
    });

    test('The Edit image button opens the dialog with the current values', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize button');

        const image = await kb.doSelectImageInEditMode(id, 'Components');
        await kb.bubbleMenu.assertButtonHidden('Bold');

        const menu = await kb.bubbleMenu.assertVisible();
        await expect(menu).toHaveAccessibleName('Image');
        expect(await getCenterOffset(kb, menu, image)).toBeLessThan(1);

        await kb.bubbleMenu.clickButton('Edit image');

        await expect(kb.imageDialog).toBeVisible();
        await expect(kb.imageDialog.getByLabel('Width')).toHaveValue('400');
        await expect(kb.imageDialog.getByLabel('Height')).toHaveValue('193');
    });

    test('The menu stays centered above the image during a drag', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize menu follows drag');

        const image = await kb.doSelectImageInEditMode(id, 'Components');
        const menu = await kb.bubbleMenu.assertVisible();
        await expect(menu).toHaveAccessibleName('Image');

        // Mouse still down: the new size is not committed yet.
        await kb.editor.doDragImageCorner(image, -200, false);
        await expect.poll(() => getCenterOffset(kb, menu, image)).toBeLessThan(2);
        expect((await kb.editor.getBox(image)).width).toBeLessThan(250);

        await page.mouse.up();
    });

    test('On a narrow screen, the image fits the column and keeps its ratio', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(
            api,
            'Image resize narrow screen',
            `<p>Content</p><img src="${IMAGE_SRC}" alt="Components" width="1000" height="482">`,
        );

        await page.setViewportSize({ width: 400, height: 800 });
        await kb.goto(id);

        const image = kb.editor.getImage('Components');
        await expect(image).toBeVisible();
        const image_box = await kb.editor.getBox(image);
        const container_box = await kb.editor.getBox(kb.editor.contentContainer);
        expect(image_box.width).toBeLessThanOrEqual(container_box.width);
        expect(image_box.width / image_box.height).toBeCloseTo(IMAGE_RATIO, 1);
    });

    test('An image in a table cell stays inside the cell', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(
            api,
            'Image resize table cell',
            `<table><tbody><tr><td>${IMAGE_HTML}</td><td><p>B</p></td></tr></tbody></table>`,
        );

        const image = await kb.doSelectImageInEditMode(id, 'Components');
        await kb.editor.doDragImageCorner(image, 1000);

        const image_box = await kb.editor.getBox(image);
        const cell_box = await kb.editor.getBox(kb.editor.contentContainer.getByRole('cell').first());
        expect(image_box.x + image_box.width).toBeLessThanOrEqual(cell_box.x + cell_box.width);
    });
});
