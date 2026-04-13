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

import { expect, Locator, Page } from "@playwright/test";
import path from 'path';
import { GlpiPage } from "./GlpiPage";
import { TipTapEditorHelper } from "../utils/TipTapEditorHelper";
import { SlashMenuHelper } from "../utils/SlashMenuHelper";
import { BubbleMenuHelper } from "../utils/BubbleMenuHelper";

export class KnowbaseItemPage extends GlpiPage
{
    private _editorHelper: TipTapEditorHelper | null = null;
    private _slashMenuHelper: SlashMenuHelper | null = null;
    private _bubbleMenuHelper: BubbleMenuHelper | null = null;

    public constructor(page: Page)
    {
        super(page);
    }

    public get editor(): TipTapEditorHelper
    {
        if (!this._editorHelper) {
            this._editorHelper = new TipTapEditorHelper(this.page);
        }
        return this._editorHelper;
    }

    public get slashMenu(): SlashMenuHelper
    {
        if (!this._slashMenuHelper) {
            this._slashMenuHelper = new SlashMenuHelper(this.page, this.editor);
        }
        return this._slashMenuHelper;
    }

    public get bubbleMenu(): BubbleMenuHelper
    {
        if (!this._bubbleMenuHelper) {
            this._bubbleMenuHelper = new BubbleMenuHelper(this.page, this.editor);
        }
        return this._bubbleMenuHelper;
    }

    public get subject(): Locator
    {
        // eslint-disable-next-line playwright/no-raw-locators -- Semantic data attribute used by ArticleController.js, not a test ID
        return this.page.locator('[data-glpi-kb-subject]');
    }

    public async goto(id: number): Promise<void>
    {
        await this.page.goto(
            `/front/knowbaseitem.form.php?id=${id}&forcetab=KnowbaseItem$1`,
            { waitUntil: 'domcontentloaded' }
        );
    }

    public async doToggleFaqStatus(): Promise<void>
    {
        const faq_toggle = this.getButton('Add to FAQ');
        const response_promise = this.page.waitForResponse(
            response => response.url().includes('/ToggleField')
        );
        await faq_toggle.click();
        await response_promise;
    }

    public async doToggleFavoriteStatus(): Promise<void>
    {
        const favorite_toggle = this.getButton('Add to favorites');
        const response_promise = this.page.waitForResponse(
            response => response.url().includes('/ToggleFavorite')
        );
        await favorite_toggle.click();
        await response_promise;
    }

    public get childEntitiesCheckbox(): Locator
    {
        return this.page.getByRole('checkbox', { name: 'Child entities' });
    }

    public async doToggleChildEntities(): Promise<void>
    {
        // Wait for ArticleController to finish initialization (it removes pe-none after attaching all listeners)
        // eslint-disable-next-line playwright/no-raw-locators -- No semantic alternative for article container
        await this.page.locator('[data-glpi-knowbase-article]:not(.pe-none)').waitFor();
        await this.childEntitiesCheckbox.click();
    }

    public async doOpenCommentsPanel(): Promise<void>
    {
        await this.page.getByTitle('More actions').click();
        await this.getButton('Comments').click();
    }

    public getCommentByContent(content: string): Locator
    {
        return this.page.getByText(content).filter({
            'visible': true,
        });
    }

    public getCommentsCounter(): Locator
    {
        return this.page.getByTestId('comments-counter');
    }

    public getNoCommentsMessage(): Locator
    {
        return this.page.getByText('No comments yet.');
    }

    public getComment(content: string): Locator
    {
        return this.page.getByTestId('comment').filter({
            hasText: content
        });
    }

    public getNewCommentTextarea(): Locator
    {
        return this.page.getByPlaceholder("Add a comment...");
    }

    public async doSelectFilesForKbUpload(files: string[], modal: Locator): Promise<void>
    {
        const filePaths = files.map(file => path.join(__dirname, `../../fixtures/${file}`));

        // Use filechooser event - click the label to trigger the hidden file input
        const fileChooserPromise = this.page.waitForEvent('filechooser');
        await modal.getByText('Drop files here or click to browse').click();
        const fileChooser = await fileChooserPromise;
        await fileChooser.setFiles(filePaths);

        // Wait for files to be processed and appear in preview
        await expect(modal.getByRole('listitem')).toHaveCount(files.length);

        // Wait for all uploads to tmp to complete (button becomes enabled)
        await expect(modal.getByRole('button', { name: 'Upload Documents' })).toBeEnabled();
    }

    public async doAddFileToKbUploadArea(file: string, modal: Locator): Promise<void>
    {
        await this.doSelectFilesForKbUpload([file], modal);
        await modal.getByRole('button', { name: 'Upload Documents' }).click();
        await expect(modal).toBeHidden();
        // Wait for page reload after upload
        await this.page.waitForLoadState('load');
    }

    public async doAddFilesToKbUploadArea(files: string[], modal: Locator): Promise<void>
    {
        await this.doSelectFilesForKbUpload(files, modal);
        await modal.getByRole('button', { name: 'Upload Documents' }).click();
        await expect(modal).toBeHidden();
        // Wait for page reload after upload
        await this.page.waitForLoadState('load');
    }

    public async doEnableSchedulePanel(): Promise<void>
    {
        await this.page.getByTitle('More actions').click();
        await this.getButton('Schedule visibility').click();
        await expect(this.page.getByTestId('schedule-panel')).toBeVisible();
    }

    public async doApplyVisibilityDates(): Promise<void>
    {
        // Save values
        await this.page.getByTestId('schedule-apply-btn').click();
        await expect(this.getAlert('Visibility dates updated')).toBeVisible();
    }

    public getVisibilityDatesIndicator(): Locator
    {
        return this.getLink("Scheduled");
    }

    public getScheduledStartDateInput(): Locator
    {
        return this.page.getByPlaceholder('No start date').filter({visible: true});
    }

    public getScheduledEndDateInput(): Locator
    {
        return this.page.getByPlaceholder('No end date').filter({visible: true});
    }
}

