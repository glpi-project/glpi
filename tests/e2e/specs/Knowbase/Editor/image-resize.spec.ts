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
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

// 1024 x 494 px
const IMAGE_SRC = '/pics/screenshots/components.png';
const IMAGE_RATIO = 1024 / 494;
const IMAGE_HTML = `<img src="${IMAGE_SRC}" alt="Components" width="400" height="193">`;

async function getBox(locator: Locator): Promise<{ x: number, y: number, width: number, height: number }> {
    const box = await locator.boundingBox();
    if (box === null) {
        throw new Error('Element is not visible');
    }
    return box;
}

/**
 * Horizontal distance in px between the centers of `a` and `b`.
 */
async function getCenterOffset(a: Locator, b: Locator): Promise<number> {
    const box_a = await getBox(a);
    const box_b = await getBox(b);
    return Math.abs((box_a.x + box_a.width / 2) - (box_b.x + box_b.width / 2));
}

/**
 * Drag the bottom-right resize handle of `image` by `delta_x` px.
 * The handle is centered on the image corner.
 */
async function dragBottomRightHandle(page: Page, image: Locator, delta_x: number, release = true): Promise<void> {
    const box = await getBox(image);
    const start_x = box.x + box.width + 3;
    const start_y = box.y + box.height + 3;
    await page.mouse.move(start_x, start_y);
    await page.mouse.down();
    await page.mouse.move(start_x + delta_x, start_y, { steps: 10 });
    if (release) {
        await page.mouse.up();
    }
}

/**
 * `width` and `height` of the first image of the saved answer.
 */
function getSavedImageSize(answer: string): { width: number, height: number } {
    const html = answer.replaceAll('&#34;', '"').replaceAll('&quot;', '"');
    const width = /<img[^>]*\swidth="(\d+)"/.exec(html);
    const height = /<img[^>]*\sheight="(\d+)"/.exec(html);
    if (width === null || height === null) {
        throw new Error(`No sized image in: ${answer}`);
    }
    return { width: Number(width[1]), height: Number(height[1]) };
}

async function countResizeHandles(kb: KnowbaseItemPage): Promise<number> {
    return kb.editor.contentContainer.evaluate(
        (container) => container.querySelectorAll('[data-resize-handle]').length
    );
}

async function createArticle(api: Api, name: string, answer = `<p>Content</p>${IMAGE_HTML}`): Promise<number> {
    return api.createItem('KnowbaseItem', { name, entities_id: getWorkerEntityId(), answer });
}

/**
 * Open the article in edit mode and select its image.
 */
async function selectImageInEditor(kb: KnowbaseItemPage, id: number): Promise<Locator> {
    await kb.goto(id);
    await kb.editor.enterEditMode();
    const image = kb.editor.contentContainer.getByRole('img', { name: 'Components' });
    await image.click();
    return image;
}

test.describe('Knowledge Base Editor - Image Resize', () => {
    test.beforeEach(async ({ profile }) => {
        await profile.set(Profiles.SuperAdmin);
    });

    test('Dragging a corner resizes the image and keeps its ratio after reload', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize drag');

        const image = await selectImageInEditor(kb, id);
        await dragBottomRightHandle(page, image, -150);
        await kb.editor.save();

        const saved = getSavedImageSize((await api.getItem('KnowbaseItem', id)).answer);
        expect(saved.width).toBeLessThan(400);
        expect(saved.width).toBeGreaterThan(200);
        expect(saved.width / saved.height).toBeCloseTo(IMAGE_RATIO, 1);

        await kb.goto(id);
        const box = await kb.editor.contentContainer.getByRole('img', { name: 'Components' }).boundingBox();
        expect(box?.width).toBeCloseTo(saved.width, 0);
        expect(await countResizeHandles(kb)).toBe(0);
    });

    test('The image cannot be smaller than the minimum size', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize minimum');

        const image = await selectImageInEditor(kb, id);
        await dragBottomRightHandle(page, image, -1000);
        await kb.editor.save();

        const saved = getSavedImageSize((await api.getItem('KnowbaseItem', id)).answer);
        expect(saved.width).toBeLessThan(150);
        expect(Math.min(saved.width, saved.height)).toBeGreaterThanOrEqual(50);
    });

    test('Undo restores the previous size on screen', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize undo');

        const image = await selectImageInEditor(kb, id);
        await dragBottomRightHandle(page, image, -150);
        await expect.poll(async () => (await image.boundingBox())?.width).toBeLessThan(300);

        await page.keyboard.press('Control+z');
        await expect.poll(async () => Math.round((await image.boundingBox())?.width ?? 0)).toBe(400);
    });

    test('Enter on a selected image opens the dialog and applies the new size', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize keyboard');

        const image = await selectImageInEditor(kb, id);
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
        await expect.poll(async () => Math.round((await image.boundingBox())?.width ?? 0)).toBe(200);

        await kb.editor.save();
        const saved = getSavedImageSize((await api.getItem('KnowbaseItem', id)).answer);
        expect(saved.width).toBe(200);
        expect(saved.height).toBe(Math.round(200 / IMAGE_RATIO));
    });

    test('The Edit image button opens the dialog with the current values', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize button');

        const image = await selectImageInEditor(kb, id);
        await kb.bubbleMenu.assertButtonHidden('Bold');

        const menu = await kb.bubbleMenu.assertVisible();
        await expect(menu).toHaveAccessibleName('Image');
        expect(await getCenterOffset(menu, image)).toBeLessThan(1);

        await kb.bubbleMenu.clickButton('Edit image');

        await expect(kb.imageDialog).toBeVisible();
        await expect(kb.imageDialog.getByLabel('Width')).toHaveValue('400');
        await expect(kb.imageDialog.getByLabel('Height')).toHaveValue('193');
    });

    test('The menu stays centered above the image during a drag', async ({ page, api }) => {
        const kb = new KnowbaseItemPage(page);
        const id = await createArticle(api, 'Image resize menu follows drag');

        const image = await selectImageInEditor(kb, id);
        const menu = await kb.bubbleMenu.assertVisible();
        await expect(menu).toHaveAccessibleName('Image');

        // Mouse still down: the new size is not committed yet.
        await dragBottomRightHandle(page, image, -200, false);
        await expect.poll(() => getCenterOffset(menu, image)).toBeLessThan(2);
        expect((await getBox(image)).width).toBeLessThan(250);

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

        const image = kb.editor.contentContainer.getByRole('img', { name: 'Components' });
        await expect(image).toBeVisible();
        const image_box = await getBox(image);
        const container_box = await getBox(kb.editor.contentContainer);
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

        const image = await selectImageInEditor(kb, id);
        await dragBottomRightHandle(page, image, 1000);

        const image_box = await getBox(image);
        const cell_box = await getBox(kb.editor.contentContainer.getByRole('cell').first());
        expect(image_box.x + image_box.width).toBeLessThanOrEqual(cell_box.x + cell_box.width);
    });
});
