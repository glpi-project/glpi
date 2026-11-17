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

import { Page, Locator, expect } from '@playwright/test';

export class TipTapEditorHelper {
    private readonly page: Page;
    readonly contentContainer: Locator;
    private editor: Locator | null = null;

    constructor(page: Page) {
        this.page = page;
        // eslint-disable-next-line playwright/no-raw-locators
        this.contentContainer = page.locator('[data-glpi-kb-content]');
    }

    async enterEditMode(): Promise<Locator> {
        const editButton = this.page.getByTestId('edit-button');
        await editButton.click();

        // eslint-disable-next-line playwright/no-raw-locators
        const editor = this.contentContainer.locator('.ProseMirror');
        await expect(editor).toBeVisible();
        await expect(editor).toHaveAttribute('contenteditable', 'true');
        this.editor = editor;

        await this.editor.click();
        await expect(this.page.getByTestId('save-button')).toBeVisible();
        return this.editor;
    }

    getEditor(): Locator {
        if (!this.editor) {
            // eslint-disable-next-line playwright/no-raw-locators
            const editor_fallback = this.contentContainer.locator('.ProseMirror');
            if (editor_fallback) {
                return editor_fallback;
            }

            throw new Error('Editor not initialized. Call enterEditMode() first.');
        }
        return this.editor;
    }

    async clearContent(): Promise<void> {
        const editor = this.getEditor();
        await editor.click();
        await this.page.keyboard.press('Control+a');
        await this.page.keyboard.press('Backspace');
    }

    async typeText(text: string): Promise<void> {
        const editor = this.getEditor();
        await editor.pressSequentially(text);
    }

    async pressKey(key: string): Promise<void> {
        await this.page.keyboard.press(key);
    }

    async setContent(text: string): Promise<void> {
        await this.clearContent();
        await this.typeText(text);
    }

    async save(): Promise<void> {
        const saveButton = this.page.getByTestId('save-button');
        const saveResponse = this.page.waitForResponse(
            response => response.url().includes('/Answer') && response.request().method() === 'POST'
        );
        await saveButton.click();
        await saveResponse;
        await expect(saveButton).toBeHidden();
        await expect(this.page.getByTestId('edit-button')).toBeVisible();
    }

    async cancel(): Promise<void> {
        const cancelButton = this.page.getByTestId('cancel-button');
        await cancelButton.click();
        await expect(cancelButton).toBeHidden();
        await expect(this.page.getByTestId('edit-button')).toBeVisible();
    }

    getImage(name: string): Locator {
        return this.contentContainer.getByRole('img', { name });
    }

    async doSelectImage(name: string): Promise<Locator> {
        const image = this.getImage(name);
        await image.click();
        return image;
    }

    async getResizeHandleCount(): Promise<number> {
        return this.contentContainer.evaluate(
            (container) => container.querySelectorAll('[data-resize-handle]').length
        );
    }

    /**
     * Drag the bottom-right resize handle of `image` by `delta_x` px.
     * The handle is centered on the image corner.
     */
    async doDragImageCorner(image: Locator, delta_x: number, release = true): Promise<void> {
        const corner = await this.getImageCorner(image);
        await this.page.mouse.move(corner.x, corner.y);
        await this.page.mouse.down();
        await this.page.mouse.move(corner.x + delta_x, corner.y, { steps: 10 });
        if (release) {
            await this.page.mouse.up();
        }
    }

    /**
     * Put a finger on the bottom-right resize handle of `image`.
     * Playwright has no touch drag: the touch events go through CDP.
     */
    async doStartTouchDragOnImageCorner(image: Locator): Promise<{
        moveBy: (delta_x: number) => Promise<void>,
        liftOtherFinger: () => Promise<void>,
        end: () => Promise<void>,
        cancel: () => Promise<void>,
    }> {
        const start = await this.getImageCorner(image);
        const cdp = await this.page.context().newCDPSession(this.page);
        await cdp.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [start] });
        let x = start.x;

        return {
            moveBy: async (delta_x: number) => {
                const from = x;
                for (let step = 1; step <= 10; step++) {
                    x = from + delta_x * step / 10;
                    await cdp.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x, y: start.y }] });
                }
            },
            // CDP cannot lift one finger only: the page sends the touchend of a second finger.
            liftOtherFinger: async () => {
                await this.page.evaluate(() => {
                    const touch = new Touch({ identifier: 999, target: document.body, clientX: 10, clientY: 10 });
                    document.body.dispatchEvent(new TouchEvent('touchend', { bubbles: true, changedTouches: [touch] }));
                });
            },
            end: async () => {
                await cdp.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
                await cdp.detach();
            },
            cancel: async () => {
                await cdp.send('Input.dispatchTouchEvent', { type: 'touchCancel', touchPoints: [] });
                await cdp.detach();
            },
        };
    }

    async doPasteHtml(html: string): Promise<void> {
        await this.getEditor().evaluate((editor, html) => {
            const data = new DataTransfer();
            data.setData('text/html', html);
            editor.dispatchEvent(new ClipboardEvent('paste', { bubbles: true, cancelable: true, clipboardData: data }));
        }, html);
    }

    async doPastePngFile(base64: string): Promise<void> {
        await this.getEditor().evaluate((editor, base64) => {
            const bytes = Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
            const data = new DataTransfer();
            data.items.add(new File([bytes], 'paste.png', { type: 'image/png' }));
            editor.dispatchEvent(new ClipboardEvent('paste', { bubbles: true, cancelable: true, clipboardData: data }));
        }, base64);
    }

    async getBox(locator: Locator): Promise<{ x: number, y: number, width: number, height: number }> {
        const box = await locator.boundingBox();
        if (box === null) {
            throw new Error('Element is not visible');
        }
        return box;
    }

    private async getImageCorner(image: Locator): Promise<{ x: number, y: number }> {
        const box = await this.getBox(image);
        return { x: box.x + box.width + 3, y: box.y + box.height + 3 };
    }

    async assertHasHeading(level: 1 | 2 | 3 | 4 | 5 | 6, text: string): Promise<void> {
        await expect(
            this.contentContainer.getByRole('heading', { level }).filter({ hasText: text })
        ).toBeVisible();
    }

    async assertHasListItem(text: string): Promise<void> {
        await expect(
            this.contentContainer.getByRole('listitem').filter({ hasText: text })
        ).toBeVisible();
    }

    async assertHasBlockquote(text: string): Promise<void> {
        await expect(
            // eslint-disable-next-line playwright/no-raw-locators
            this.contentContainer.locator('blockquote').filter({ hasText: text })
        ).toBeVisible();
    }

    async assertHasCodeBlock(): Promise<void> {
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(this.contentContainer.locator('pre code')).toBeVisible();
    }

    async assertHasTable(): Promise<void> {
        await expect(this.contentContainer.getByRole('table')).toBeVisible();
    }

    async assertHasDivider(): Promise<void> {
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(this.contentContainer.locator('hr')).toBeVisible();
    }

    async assertContainsText(text: string): Promise<void> {
        await expect(this.contentContainer).toContainText(text);
    }

    async assertHasBold(text: string): Promise<void> {
        await expect(
            // eslint-disable-next-line playwright/no-raw-locators
            this.contentContainer.locator('strong').filter({ hasText: text })
        ).toBeVisible();
    }

    async assertHasItalic(text: string): Promise<void> {
        await expect(
            // eslint-disable-next-line playwright/no-raw-locators
            this.contentContainer.locator('em').filter({ hasText: text })
        ).toBeVisible();
    }

    async assertHasStrikethrough(text: string): Promise<void> {
        await expect(
            // eslint-disable-next-line playwright/no-raw-locators
            this.contentContainer.locator('s').filter({ hasText: text })
        ).toBeVisible();
    }

    async assertHasCode(text: string): Promise<void> {
        await expect(
            // eslint-disable-next-line playwright/no-raw-locators
            this.contentContainer.locator('code').filter({ hasText: text })
        ).toBeVisible();
    }

    async assertHasLink(text: string, href: string): Promise<void> {
        await expect(
            this.contentContainer.getByRole('link', { name: text })
        ).toHaveAttribute('href', href);
    }

    async assertLinkOpensInNewTab(text: string): Promise<void> {
        await expect(
            this.contentContainer.getByRole('link', { name: text })
        ).toHaveAttribute('target', '_blank');
    }

    async assertLinkOpensInSameTab(text: string): Promise<void> {
        await expect(
            this.contentContainer.getByRole('link', { name: text })
        ).not.toHaveAttribute('target');
    }
}
