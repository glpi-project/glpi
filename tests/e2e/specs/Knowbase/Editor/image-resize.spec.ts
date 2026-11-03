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
 * Drag the bottom-right resize handle of `image` by `delta_x` px.
 * The handle is centered on the image corner.
 */
async function dragBottomRightHandle(page: Page, image: Locator, delta_x: number): Promise<void> {
    const box = await getBox(image);
    const start_x = box.x + box.width + 3;
    const start_y = box.y + box.height + 3;
    await page.mouse.move(start_x, start_y);
    await page.mouse.down();
    await page.mouse.move(start_x + delta_x, start_y, { steps: 10 });
    await page.mouse.up();
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

test.describe('Knowledge Base Editor - Image Resize', () => {
    test('Dragging a corner resizes the image and keeps its ratio after reload', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'Image resize drag',
            entities_id: getWorkerEntityId(),
            answer: `<p>Content</p>${IMAGE_HTML}`,
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        const image = kb.editor.contentContainer.getByRole('img', { name: 'Components' });
        await image.click();
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

    test('The image cannot be smaller than the minimum size', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'Image resize minimum',
            entities_id: getWorkerEntityId(),
            answer: `<p>Content</p>${IMAGE_HTML}`,
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        const image = kb.editor.contentContainer.getByRole('img', { name: 'Components' });
        await image.click();
        await dragBottomRightHandle(page, image, -1000);
        await kb.editor.save();

        const saved = getSavedImageSize((await api.getItem('KnowbaseItem', id)).answer);
        expect(saved.width).toBeLessThan(150);
        expect(Math.min(saved.width, saved.height)).toBeGreaterThanOrEqual(50);
    });

    test('Undo restores the previous size on screen', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'Image resize undo',
            entities_id: getWorkerEntityId(),
            answer: `<p>Content</p>${IMAGE_HTML}`,
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        const image = kb.editor.contentContainer.getByRole('img', { name: 'Components' });
        await image.click();
        await dragBottomRightHandle(page, image, -150);
        await expect.poll(async () => (await image.boundingBox())?.width).toBeLessThan(300);

        await page.keyboard.press('Control+z');
        await expect.poll(async () => Math.round((await image.boundingBox())?.width ?? 0)).toBe(400);
    });

    test('Enter on a selected image opens the dialog and applies the new size', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'Image resize keyboard',
            entities_id: getWorkerEntityId(),
            answer: `<p>Content</p>${IMAGE_HTML}`,
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        const image = kb.editor.contentContainer.getByRole('img', { name: 'Components' });
        await image.click();
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

    test('The Edit image button opens the dialog with the current values', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'Image resize button',
            entities_id: getWorkerEntityId(),
            answer: `<p>Content</p>${IMAGE_HTML}`,
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        await kb.editor.contentContainer.getByRole('img', { name: 'Components' }).click();
        await expect(kb.bubbleMenu.getButton('Bold')).toBeHidden();
        await kb.bubbleMenu.clickButton('Edit image');

        await expect(kb.imageDialog).toBeVisible();
        await expect(kb.imageDialog.getByLabel('Width')).toHaveValue('400');
        await expect(kb.imageDialog.getByLabel('Height')).toHaveValue('193');
    });

    test('On a narrow screen, the image fits the column and keeps its ratio', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'Image resize narrow screen',
            entities_id: getWorkerEntityId(),
            answer: `<p>Content</p><img src="${IMAGE_SRC}" alt="Components" width="1000" height="482">`,
        });

        await page.setViewportSize({ width: 400, height: 800 });
        await kb.goto(id);

        const image = kb.editor.contentContainer.getByRole('img', { name: 'Components' });
        await expect(image).toBeVisible();
        const image_box = await getBox(image);
        const container_box = await getBox(kb.editor.contentContainer);
        expect(image_box.width).toBeLessThanOrEqual(container_box.width);
        expect(image_box.width / image_box.height).toBeCloseTo(IMAGE_RATIO, 1);
    });

    test('An image in a table cell stays inside the cell', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const kb = new KnowbaseItemPage(page);
        const id = await api.createItem('KnowbaseItem', {
            name: 'Image resize table cell',
            entities_id: getWorkerEntityId(),
            answer: `<table><tbody><tr><td>${IMAGE_HTML}</td><td><p>B</p></td></tr></tbody></table>`,
        });

        await kb.goto(id);
        await kb.editor.enterEditMode();
        const image = kb.editor.contentContainer.getByRole('img', { name: 'Components' });
        await image.click();
        await dragBottomRightHandle(page, image, 1000);

        const image_box = await getBox(image);
        const cell_box = await getBox(kb.editor.contentContainer.getByRole('cell').first());
        expect(image_box.x + image_box.width).toBeLessThanOrEqual(cell_box.x + cell_box.width);
    });
});
