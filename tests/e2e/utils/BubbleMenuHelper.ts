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
    | 'Remove link'
    | 'Comment';

export class BubbleMenuHelper {
    private readonly page: Page;
    private readonly editorHelper: TipTapEditorHelper;

    private static readonly COMMAND_TO_DATA_COMMAND: Record<BubbleMenuCommand, string> = {
        'Bold': 'toggleBold',
        'Italic': 'toggleItalic',
        'Strikethrough': 'toggleStrike',
        'Code': 'toggleCode',
        'Heading 1': 'toggleHeading1',
        'Heading 2': 'toggleHeading2',
        'Heading 3': 'toggleHeading3',
        'Bullet List': 'toggleBulletList',
        'Numbered List': 'toggleOrderedList',
        'Quote': 'toggleBlockquote',
        'Link': 'setLink',
        'Remove link': 'unsetLink',
        'Comment': 'comment',
    };

    constructor(page: Page, editorHelper: TipTapEditorHelper) {
        this.page = page;
        this.editorHelper = editorHelper;
    }

    private getMenu(): Locator {
        // eslint-disable-next-line playwright/no-raw-locators
        return this.page.locator('.bubble-menu');
    }

    getButton(command: BubbleMenuCommand): Locator {
        const menu = this.getMenu();
        const dataCommand = BubbleMenuHelper.COMMAND_TO_DATA_COMMAND[command];
        return menu.locator(`[data-command="${dataCommand}"]`);
    }

    async selectAllContent(): Promise<void> {
        const editor = this.editorHelper.getEditor();
        await editor.click();
        await this.page.keyboard.press('Control+a');
        await expect(this.getMenu()).toBeVisible();
    }

    async assertVisible(): Promise<Locator> {
        const menu = this.getMenu();
        await expect(menu).toBeVisible();
        return menu;
    }

    async assertHidden(): Promise<void> {
        await expect(this.getMenu()).toBeHidden();
    }

    async clickButton(command: BubbleMenuCommand): Promise<void> {
        await this.assertVisible();
        const button = this.getButton(command);
        await expect(button).toBeVisible();
        await button.click();
    }

    async assertButtonActive(command: BubbleMenuCommand): Promise<void> {
        await this.assertVisible();
        await expect(this.getButton(command)).toHaveClass(/is-active/);
    }

    async assertButtonInactive(command: BubbleMenuCommand): Promise<void> {
        await this.assertVisible();
        await expect(this.getButton(command)).not.toHaveClass(/is-active/);
    }

    async assertButtonDisabled(command: BubbleMenuCommand): Promise<void> {
        await this.assertVisible();
        await expect(this.getButton(command)).toBeDisabled();
    }

    async assertButtonVisible(command: BubbleMenuCommand): Promise<void> {
        await this.assertVisible();
        await expect(this.getButton(command)).toBeVisible();
    }

    async assertButtonHidden(command: BubbleMenuCommand): Promise<void> {
        await this.assertVisible();
        await expect(this.getButton(command)).toBeHidden();
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
