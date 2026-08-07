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
import { GlpiPage } from "./GlpiPage";

export class IllustrationPickerPage extends GlpiPage
{
    public readonly select_illustration_button: Locator;
    public readonly picker_modal: Locator;
    public readonly search_input: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.select_illustration_button = this.getButton("Select an illustration");
        this.picker_modal = page.getByTestId("illustration-picker-modal");
        this.search_input = this.picker_modal.getByRole('textbox', { name: 'Search' });
    }

    public async gotoFormServiceCatalogTab(form_id: number): Promise<void>
    {
        const tab = "Glpi\\Form\\ServiceCatalog\\ServiceCatalog$1";
        await this.page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=${tab}`
        );
    }

    public async doOpenIllustrationPicker(): Promise<void>
    {
        await this.select_illustration_button.click();
        await expect(this.picker_modal).toBeVisible();
        await expect(this.picker_modal).toHaveAttribute('data-cy-shown', 'true');
    }

    public async doSelectIllustration(name: string): Promise<void>
    {
        await this.picker_modal.getByRole('img', { name }).click();
        await expect(this.picker_modal).toHaveAttribute('data-cy-shown', 'false');
    }

    public async doGoToPage(page_number: number): Promise<void>
    {
        await this.picker_modal.getByRole('button', {
            name: `Go to page ${page_number}`,
        }).click();
    }

    public async doSearchIllustrations(query: string): Promise<void>
    {
        await expect(this.search_input).toBeFocused();
        await this.search_input.fill(query);
    }

    public async doUploadCustomIllustration(file: string): Promise<void>
    {
        await this.picker_modal.getByRole('tab', { name: 'Upload your own illustration' }).click();
        await this.doAddFileToUploadArea(file, this.picker_modal);
        await this.picker_modal.getByRole('button', { name: 'Use selected file' }).click();
        await expect(this.picker_modal).toHaveAttribute('data-cy-shown', 'false');
    }

    public getIllustration(name: string): Locator
    {
        return this.page.getByRole('img', { name });
    }

    public getModalImages(): Locator
    {
        return this.picker_modal.getByRole('img');
    }

    public getCustomPreview(): Locator
    {
        return this.page.getByTestId('illustration-custom-preview');
    }
}
