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
import { TipTapEditorHelper } from './TipTapEditorHelper';
import { waitForBubbleMenuVisible, waitForBubbleMenuHidden } from './EditorWaitStrategies';

export type BubbleMenuCommand =
    | 'Bold'
    | 'Italic'
    | 'Strikethrough'
    | 'Code'
    | 'Heading 1'
    | 'Heading 2'
    | 'Heading 3'
    | 'Bullet List'
    | 'Numbered List'
    | 'Quote'
    | 'Link'
    | 'Remove link';

export class BubbleMenuHelper {
    private readonly page: Page;
    private readonly editorHelper: TipTapEditorHelper;

    constructor(page: Page, editorHelper: TipTapEditorHelper) {
        this.page = page;
        this.editorHelper = editorHelper;
    }

    async selectAllContent(): Promise<void> {
        const editor = this.editorHelper.getEditor();
        await editor.click();
        await this.page.keyboard.press('Control+a');
        await waitForBubbleMenuVisible(this.page);
    }

    async waitForVisible(): Promise<Locator> {
        return await waitForBubbleMenuVisible(this.page);
    }

    async waitForHidden(): Promise<void> {
        await waitForBubbleMenuHidden(this.page);
    }

    async clickButton(command: BubbleMenuCommand): Promise<void> {
        const menu = await waitForBubbleMenuVisible(this.page);
        // eslint-disable-next-line playwright/no-raw-locators
        const button = menu.locator(`button[title="${command}"]`);
        await expect(button).toBeVisible();
        await button.click();
    }

    async assertButtonActive(command: BubbleMenuCommand): Promise<void> {
        const menu = await waitForBubbleMenuVisible(this.page);
        // eslint-disable-next-line playwright/no-raw-locators
        const button = menu.locator(`button[title="${command}"]`);
        await expect(button).toHaveClass(/is-active/);
    }

    async assertButtonInactive(command: BubbleMenuCommand): Promise<void> {
        const menu = await waitForBubbleMenuVisible(this.page);
        // eslint-disable-next-line playwright/no-raw-locators
        const button = menu.locator(`button[title="${command}"]`);
        await expect(button).not.toHaveClass(/is-active/);
    }

    async assertButtonVisible(command: BubbleMenuCommand): Promise<void> {
        const menu = await waitForBubbleMenuVisible(this.page);
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(menu.locator(`button[title="${command}"]`)).toBeVisible();
    }

    async assertButtonHidden(command: BubbleMenuCommand): Promise<void> {
        const menu = await waitForBubbleMenuVisible(this.page);
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(menu.locator(`button[title="${command}"]`)).toBeHidden();
    }

    async setLink(url: string): Promise<void> {
        this.page.once('dialog', async dialog => {
            await dialog.accept(url);
        });
        await this.clickButton('Link');
    }

    async removeLink(): Promise<void> {
        await this.clickButton('Remove link');
    }
}
