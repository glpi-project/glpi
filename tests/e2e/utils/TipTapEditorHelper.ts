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
import { waitForFullEditorInit, EDITOR_TIMEOUTS } from './EditorWaitStrategies';

export class TipTapEditorHelper {
    private readonly page: Page;
    private readonly contentContainer: Locator;
    private editor: Locator | null = null;

    constructor(page: Page) {
        this.page = page;
        this.contentContainer = page.getByTestId('content');
    }

    async enterEditMode(): Promise<Locator> {
        const editButton = this.page.getByTestId('edit-button');
        this.editor = await waitForFullEditorInit(
            this.page,
            this.contentContainer,
            editButton
        );
        return this.editor;
    }

    getEditor(): Locator {
        if (!this.editor) {
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
            response => response.url().includes('/Answer') && response.request().method() === 'POST',
            { timeout: EDITOR_TIMEOUTS.SAVE_COMPLETE }
        );
        await saveButton.click();
        await saveResponse;
        await expect(saveButton).toBeHidden({ timeout: EDITOR_TIMEOUTS.EDITOR_INIT });
        await expect(this.page.getByTestId('edit-button')).toBeVisible();
    }

    async cancel(): Promise<void> {
        const cancelButton = this.page.getByTestId('cancel-button');
        await cancelButton.click();
        await expect(cancelButton).toBeHidden();
        await expect(this.page.getByTestId('edit-button')).toBeVisible();
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
}
