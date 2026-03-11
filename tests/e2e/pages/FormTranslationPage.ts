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

export class FormTranslationPage extends GlpiPage
{
    public readonly add_language_button: Locator;
    public readonly save_translation_button: Locator;

    public constructor(page: Page)
    {
        super(page);

        // Define locators
        this.add_language_button = this.getButton("Add language");
        this.save_translation_button = this.getButton("Save translation");
    }

    public async goto(id: number): Promise<void>
    {
        const tab = "Glpi\\Form\\FormTranslation$1";
        await this.page.goto(`/front/form/form.form.php?id=${id}&forcetab=${tab}`);
    }

    public async openLanguage(language: string): Promise<void>
    {
        await this.page.getByRole('button', { name: 'Edit translation' })
            .filter({ hasText: language })
            .click();
    }

    public async addLanguage(language: string): Promise<void>
    {
        await this.add_language_button.click();
        await this.doSetDropdownValue(
            this.getDropdownByLabel('Select language to translate'),
            language
        );
        await this.getButton('Add').click();
    }

    public async expectLanguageDropdownOpened(language: string): Promise<void>
    {
        const dropdown = this.page.getByRole('dialog', { name: `Form translations: ${language}` });
        await expect(dropdown).toBeVisible();
    }

    public async getTranslationRow(translation_name: string): Promise<Locator>
    {
        return this.page.getByRole('row', { name: `Translation row for ${translation_name}` });
    }

    public async getTranslationRichTextByLabel(translation_name: string): Promise<Locator>
    {
        // Initialize rich text if not done yet
        await this.initRichTextByLabel('Enter translation', await this.getTranslationRow(translation_name));
        return this.getRichTextByLabel('Enter translation');
    }

    public async saveTranslation(): Promise<void>
    {
        await this.save_translation_button.click();
    }
}
