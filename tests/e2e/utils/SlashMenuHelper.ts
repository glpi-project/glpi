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
import { waitForSlashMenuVisible, waitForSlashMenuHidden } from './EditorWaitStrategies';

export type SlashCommand =
    | 'Heading 1'
    | 'Heading 2'
    | 'Heading 3'
    | 'Bullet List'
    | 'Numbered List'
    | 'Quote'
    | 'Code Block'
    | 'Table'
    | 'Divider'
    | 'Image';

export class SlashMenuHelper {
    private readonly page: Page;
    private readonly editorHelper: TipTapEditorHelper;

    constructor(page: Page, editorHelper: TipTapEditorHelper) {
        this.page = page;
        this.editorHelper = editorHelper;
    }

    async open(): Promise<Locator> {
        const editor = this.editorHelper.getEditor();
        await editor.pressSequentially('/');
        return await waitForSlashMenuVisible(this.page);
    }

    async openWithFilter(query: string): Promise<Locator> {
        const editor = this.editorHelper.getEditor();
        await editor.pressSequentially(`/${query}`);
        return await waitForSlashMenuVisible(this.page);
    }

    async close(): Promise<void> {
        await this.page.keyboard.press('Control+a');
        await this.page.keyboard.press('Backspace');
        await waitForSlashMenuHidden(this.page);
    }

    async selectByClick(command: SlashCommand): Promise<void> {
        const menu = await waitForSlashMenuVisible(this.page);
        const button = menu.getByRole('button', { name: command });
        await expect(button).toBeVisible();
        await button.click();
        await waitForSlashMenuHidden(this.page);
    }

    async selectByKeyboard(command: SlashCommand): Promise<void> {
        const menu = await waitForSlashMenuVisible(this.page);
        // eslint-disable-next-line playwright/no-raw-locators
        const items = menu.locator('.slash-menu-item');
        const count = await items.count();

        let targetIndex = -1;
        for (let i = 0; i < count; i++) {
            const text = await items.nth(i).textContent();
            if (text?.includes(command)) {
                targetIndex = i;
                break;
            }
        }

        if (targetIndex === -1) {
            throw new Error(`Slash command "${command}" not found in menu`);
        }

        for (let i = 0; i < targetIndex; i++) {
            await this.page.keyboard.press('ArrowDown');
            await expect(items.nth(i + 1)).toHaveClass(/is-selected/);
        }

        await this.page.keyboard.press('Enter');
        await waitForSlashMenuHidden(this.page);
    }

    async insert(command: SlashCommand, method: 'click' | 'keyboard' = 'click'): Promise<void> {
        await this.open();
        if (method === 'click') {
            await this.selectByClick(command);
        } else {
            await this.selectByKeyboard(command);
        }
        const editor = this.editorHelper.getEditor();
        await editor.focus();
    }

    async assertCommandVisible(command: SlashCommand): Promise<void> {
        const menu = await waitForSlashMenuVisible(this.page);
        await expect(menu.getByRole('button', { name: command })).toBeVisible();
    }

    async assertCommandHidden(command: SlashCommand): Promise<void> {
        // eslint-disable-next-line playwright/no-raw-locators
        const menu = this.page.locator('.slash-menu');
        await expect(menu.getByRole('button', { name: command })).toBeHidden();
    }

    async assertSelectedItem(expectedText: string): Promise<void> {
        // eslint-disable-next-line playwright/no-raw-locators
        const selected = this.page.locator('.slash-menu-item.is-selected');
        await expect(selected).toContainText(expectedText);
    }

    async assertNoResults(): Promise<void> {
        // eslint-disable-next-line playwright/no-raw-locators
        const menu = this.page.locator('.slash-menu');
        // eslint-disable-next-line playwright/no-raw-locators
        await expect(menu.locator('.slash-menu-empty')).toContainText('No results');
    }
}
