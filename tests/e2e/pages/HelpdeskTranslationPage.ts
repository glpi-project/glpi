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

import { expect, type Locator, type Page } from '@playwright/test';
import { GlpiPage } from './GlpiPage';

/**
 * Columns of the languages table, which carry no accessible name, thus they
 * can only be reached by their position.
 */
export enum LanguageColumn
{
    Language = 0,
    Translated = 1,
    TranslationsToDo = 2,
    ObsoleteTranslations = 3,
}

/**
 * The "Helpdesk translations" tab of the general configuration.
 *
 * The tab lists one row per translated language. Each row opens a modal that
 * holds one entry per translatable value of the helpdesk home, grouped by
 * category.
 *
 * Warning: these translations are global to the whole application, thus only
 * isolated tests (`*.spec.isolated.ts`) may use this page.
 */
export class HelpdeskTranslationPage extends GlpiPage
{
    public readonly add_language_button: Locator;
    public readonly translations_region: Locator;
    public readonly edit_translation_buttons: Locator;

    public constructor(page: Page)
    {
        super(page);

        // Unlike the same button of the form translations, this one carries no
        // aria-label, thus its accessible name also holds the glyph of its
        // icon and cannot be matched exactly.
        this.add_language_button = this.page
            .getByRole('button', { name: 'Add language' })
            .filter({ visible: true })
        ;
        this.translations_region = this.getRegion('Helpdesk translations');
        this.edit_translation_buttons = this.getButton('Edit translation');
    }

    public async goto(): Promise<void>
    {
        await this.page.goto('/front/config.form.php');

        // The name of the tab gains a count once a language is translated
        // ("Helpdesk translations (1)"), thus it cannot be matched exactly.
        await this.page
            .getByRole('tab', { name: 'Helpdesk translations' })
            .filter({ visible: true })
            .click()
        ;
        await expect(this.translations_region).toBeVisible();
    }

    /**
     * Add a language, which also opens its translation modal: the controller
     * redirects with an "open_translation" parameter that the page acts on.
     */
    public async addLanguage(language: string): Promise<void>
    {
        await this.add_language_button.click();

        // The page also holds the translation modal of every language already
        // added, thus the fields must be looked for in this modal only.
        const modal = this.page.getByRole('dialog', { name: 'Add language' });
        await expect(modal).toBeVisible();

        await this.doSetDropdownValue(
            this.getDropdownByLabel('Select language to translate', modal),
            language,
        );
        await modal.getByRole('button', { name: 'Add', exact: true }).click();

        await expect(this.getModal(language)).toBeVisible();
    }

    public async openLanguage(language: string): Promise<void>
    {
        await this.getLanguageRow(language)
            .getByRole('button', { name: 'Edit translation' })
            .click()
        ;
        await expect(this.getModal(language)).toBeVisible();
    }

    public async closeModal(language: string): Promise<void>
    {
        const modal = this.getModal(language);
        await modal.getByRole('button', { name: 'Close' }).click();
        await expect(modal).toBeHidden();
    }

    public getModal(language: string): Locator
    {
        return this.page.getByRole('dialog', {
            name: `Helpdesk translations: ${language}`,
        });
    }

    /**
     * Row of the languages table.
     *
     * The language name is the label of the button that opens the modal, and
     * the button's accessible name is its aria-label, thus the language must be
     * matched on the text.
     */
    public getLanguageRow(language: string): Locator
    {
        return this.translations_region
            .getByRole('row')
            .filter({ hasText: language })
        ;
    }

    public getLanguageCell(language: string, column: LanguageColumn): Locator
    {
        return this.getLanguageRow(language).getByRole('cell').nth(column);
    }

    /**
     * Row of a translatable value, inside the modal of a language.
     *
     * Each group of values is preceded by a category row that repeats the name
     * of the translated item, thus a row is only a translation entry when it
     * holds a "Translation name" cell.
     */
    public getTranslationRow(language: string, default_value: string): Locator
    {
        return this.getModal(language)
            .getByRole('row')
            .filter({ has: this.page.getByRole('cell', { name: 'Translation name' }) })
            .filter({ hasText: default_value })
        ;
    }

    public getTranslationInput(row: Locator): Locator
    {
        return row
            .getByRole('cell', { name: 'Translated value' })
            .getByRole('textbox', { name: 'Enter translation' })
        ;
    }

    /**
     * Rich text editors of this modal are only initialized when they are used.
     */
    public async getTranslationRichText(row: Locator): Promise<Locator>
    {
        return await this.initRichTextByLabel('Enter translation', row);
    }

    public async saveTranslation(language: string): Promise<void>
    {
        await this.getModal(language)
            .getByRole('button', { name: 'Save translation' })
            .click()
        ;
        await expect(this.getAlert('Item successfully updated')).toBeVisible();
    }

    public async deleteTranslation(language: string): Promise<void>
    {
        await this.getModal(language)
            .getByRole('button', { name: 'Delete translation' })
            .click()
        ;
        await expect(this.getAlert('Item successfully purged')).toBeVisible();
    }

    /**
     * Remove every translated language of the application.
     *
     * These translations are global, thus an isolated test must call this to
     * restore the initial state.
     */
    public async deleteAllTranslations(): Promise<void>
    {
        await this.goto();

        // The list is rebuilt after each deletion, thus it must be read again
        // on each turn.
        while (await this.edit_translation_buttons.count() > 0) {
            const language = await this.edit_translation_buttons
                .first()
                .innerText()
            ;
            await this.openLanguage(language);
            await this.deleteTranslation(language);
        }
    }
}
