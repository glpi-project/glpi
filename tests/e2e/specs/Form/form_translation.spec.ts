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

import { test, expect } from '../../fixtures/glpi_fixture';
import { Page } from "@playwright/test";
import { Profiles } from "../../utils/Profiles";
import { getWorkerUserId } from '../../utils/WorkerEntities';
import { GlpiPage } from "../../pages/GlpiPage";
import { FormPage } from "../../pages/FormPage";

async function setUserLanguage(
    page: Page,
    language: string
): Promise<void> {
    const user_id = getWorkerUserId();
    await page.request.post('/front/preference.php', {
        form: {
            id: String(user_id),
            language: language,
            update: 'Update',
        },
    });
}

async function addFrenchTranslations(page: Page): Promise<void> {
    const glpi = new GlpiPage(page);

    await page.getByRole('button', { name: 'Add language' }).click();
    const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
    await glpi.doSetDropdownValue(language_dropdown, 'Français');
    await page.getByRole('button', { name: 'Add', exact: true }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Fill form title translation
    const title_row = page.getByRole('row', { name: 'Translation row for Form title' });
    await title_row.getByRole('textbox', { name: 'Enter translation' })
        .fill('Tester les traductions de formulaire');

    // Fill form description translation (TinyMCE - init on demand)
    const desc_row = page.getByRole('row', { name: 'Translation row for Form description' });
    const desc_body = await glpi.initRichTextByLabel('Enter translation', desc_row);
    await desc_body.pressSequentially('Ce formulaire est utilisé pour tester les traductions de formulaire');

    await page.getByRole('button', { name: 'Save translation' }).click();
    await expect(page.getByRole('alert').filter({ hasText: 'Item successfully updated' })).toBeVisible();
    await page.getByRole('button', { name: 'Close' }).click();
}

async function expectTranslations(page: Page, title: string, description: string): Promise<void> {
    await expect(page.getByTestId('form-title')).toContainText(title);
    await expect(page.getByTestId('form-description')).toContainText(description);
}

test.describe('Form translations', () => {
    let form_id: number;

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        const info = await formImporter.importForm('form-translations-base.json');
        form_id = info.getId();
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`
        );
    });

    test.afterEach(async ({ page }) => {
        await setUserLanguage(page, 'en_GB');
    });

    test('can add a new language translation', async ({ page }) => {
        const glpi = new GlpiPage(page);

        await page.getByRole('button', { name: 'Add language' }).click();
        const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
        await glpi.doSetDropdownValue(language_dropdown, 'Français');
        await page.getByRole('button', { name: 'Add', exact: true }).click();

        await expect(page.getByRole('table', { name: 'Form translations' })).toBeVisible();
        const table = page.getByRole('table', { name: 'Form translations' });
        await expect(table.getByRole('cell', { name: 'Translation name' })).toHaveCount(2);
        await expect(table.getByRole('cell', { name: 'Default value' })).toHaveCount(2);
        await expect(table.getByRole('cell', { name: 'Translated value' })).toHaveCount(2);

        // Close modal
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await dialog.getByRole('button', { name: 'Close' }).click();
        await expect(dialog).toBeHidden();

        // Verify language row
        const translations_region = page.getByRole('region', { name: 'Form translations' });
        const french_row = translations_region.getByRole('row').filter({ hasText: 'Français' });
        await expect(french_row).toContainText('Français');
        await expect(french_row.getByRole('progressbar')).toContainText('0 %');
    });

    test('can add new translations', async ({ page }) => {
        await addFrenchTranslations(page);

        // Re-open modal
        await page.getByRole('button', { name: 'Edit translation' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        // Check saved translations
        const title_row = page.getByRole('row', { name: 'Translation row for Form title' });
        await expect(
            title_row.getByRole('textbox', { name: 'Enter translation' })
        ).toHaveValue('Tester les traductions de formulaire');

        const desc_row = page.getByRole('row', { name: 'Translation row for Form description' });
        await expect(
            desc_row.getByLabel('Enter translation')
        ).toHaveValue('<p>Ce formulaire est utilisé pour tester les traductions de formulaire</p>');
    });

    test('can view translations on form preview with default language', async ({ page }) => {
        await addFrenchTranslations(page);
        await page.goto(`/Form/Render/${form_id}`);
        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
    });

    test('can view translations on form preview in French', async ({ page }) => {
        await addFrenchTranslations(page);
        await setUserLanguage(page, 'fr_FR');
        await page.goto(`/Form/Render/${form_id}`);
        await expectTranslations(
            page,
            'Tester les traductions de formulaire',
            'Ce formulaire est utilisé pour tester les traductions de formulaire'
        );
    });

    test('can view translations on form preview in Spanish', async ({ page }) => {
        await addFrenchTranslations(page);
        await setUserLanguage(page, 'es_ES');
        await page.goto(`/Form/Render/${form_id}`);
        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
    });

    test('can delete a form translation', async ({ page }) => {
        await addFrenchTranslations(page);

        await page.getByRole('button', { name: 'Edit translation' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.getByRole('button', { name: 'Delete translation' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Item successfully purged' })).toBeVisible();
        await page.getByRole('button', { name: 'Close' }).click();

        await expect(page.getByRole('link', { name: 'Français' })).toHaveCount(0);
    });

    test('check form translation stats', async ({ page }) => {
        const glpi = new GlpiPage(page);
        const translations_region = page.getByRole('region', { name: 'Form translations' });

        // Add language
        await page.getByRole('button', { name: 'Add language' }).click();
        const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
        await glpi.doSetDropdownValue(language_dropdown, 'Français');
        await page.getByRole('button', { name: 'Add', exact: true }).click();

        // Close modal
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await dialog.getByRole('button', { name: 'Close' }).click();
        await expect(dialog).toBeHidden();

        // Check initial stats
        const french_row = translations_region.getByRole('row').filter({ hasText: 'Français' });
        await expect(french_row.getByRole('progressbar')).toContainText('0 %');

        // Add one translation
        await page.getByRole('button', { name: 'Edit translation' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        const title_row = page.getByRole('row', { name: 'Translation row for Form title' });
        await title_row.getByRole('textbox', { name: 'Enter translation' })
            .fill('Tester les traductions de formulaire');

        await page.getByRole('button', { name: 'Save translation' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Item successfully updated' })).toBeVisible();
        await page.getByRole('button', { name: 'Close' }).click();

        // Reload translation tab
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`
        );

        // Check updated stats
        const french_row_after = page.getByRole('region', { name: 'Form translations' })
            .getByRole('row')
            .filter({ hasText: 'Français' });
        await expect(french_row_after.getByRole('progressbar')).toContainText('50 %');
    });

    test('can detect translations to review when default value changes', async ({ page, api }) => {
        const glpi = new GlpiPage(page);
        await addFrenchTranslations(page);

        // Add German language
        await page.getByRole('button', { name: 'Add language' }).click();
        const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
        await glpi.doSetDropdownValue(language_dropdown, 'Deutsch');
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await page.getByRole('dialog').getByRole('button', { name: 'Close' }).click();

        // Update form name
        await api.updateItem('Glpi\\Form\\Form', form_id, {
            name: 'Tests form translations updated',
        });

        // Reload
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`
        );

        // Check that French has a to-review translation
        const translations_region = page.getByRole('region', { name: 'Form translations' });
        const language_rows = translations_region.getByRole('row').filter({
            has: page.getByRole('button', { name: 'Edit translation' }),
        });

        // Open French translation and verify to-review marker
        await language_rows.nth(0).getByRole('button', { name: 'Edit translation' }).click();
        const french_dialog = page.getByRole('dialog');
        await expect(french_dialog).toBeVisible();
        const french_title_row = french_dialog.getByRole('row', { name: 'Translation row for Form title' });
        await expect(french_title_row.getByTestId('translation-obsolete-marker')).toBeVisible();
        await french_dialog.getByRole('button', { name: 'Close' }).click();
        await expect(french_dialog).toBeHidden();

        // Open German translation and verify no to-review marker
        await language_rows.nth(1).getByRole('button', { name: 'Edit translation' }).click();
        const german_dialog = page.getByRole('dialog');
        await expect(german_dialog).toBeVisible();
        const german_title_row = german_dialog.getByRole('row', { name: 'Translation row for Form title' });
        await expect(german_title_row.getByTestId('translation-obsolete-marker')).toHaveCount(0);
    });

    test('can translate options of a radio question', async ({ page }) => {
        const form = new FormPage(page);

        // Go to form editor and add a radio question with options
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\Form$1`
        );
        const question = await form.addQuestion('Radio question');
        await form.doChangeQuestionType(question, 'Radio');
        await form.doAddDropdownOptions(question, [
            'First option', 'Second option', 'Third option',
        ]);
        await form.doSaveFormEditor();

        // Go to translations tab
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`
        );

        // Add French and translate radio options
        const glpi = new GlpiPage(page);
        await page.getByRole('button', { name: 'Add language' }).click();
        const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
        await glpi.doSetDropdownValue(language_dropdown, 'Français');
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        const radio_options = page.getByRole('row', { name: 'Translation row for Radio Option' });

        const option_1_row = radio_options.filter({ hasText: 'First option' });
        await option_1_row.getByRole('textbox', { name: 'Enter translation' })
            .fill('Première option');

        const option_2_row = radio_options.filter({ hasText: 'Second option' });
        await option_2_row.getByRole('textbox', { name: 'Enter translation' })
            .fill('Deuxième option');

        const option_3_row = radio_options.filter({ hasText: 'Third option' });
        await option_3_row.getByRole('textbox', { name: 'Enter translation' })
            .fill('Troisième option');

        await page.getByRole('button', { name: 'Save translation' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Item successfully updated' })).toBeVisible();

        // Preview in default language
        await page.goto(`/Form/Render/${form_id}`);
        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
        await expect(page.getByRole('heading', { name: 'Radio question' })).toBeVisible();
        await expect(page.getByRole('radio', { name: 'First option' })).toBeVisible();
        await expect(page.getByRole('radio', { name: 'Second option' })).toBeVisible();
        await expect(page.getByRole('radio', { name: 'Third option' })).toBeVisible();

        // Switch to French
        await setUserLanguage(page, 'fr_FR');
        await page.goto(`/Form/Render/${form_id}`);

        await expect(page.getByRole('heading', { name: 'Radio question' })).toBeVisible();
        await expect(page.getByRole('radio', { name: 'Première option' })).toBeVisible();
        await expect(page.getByRole('radio', { name: 'Deuxième option' })).toBeVisible();
        await expect(page.getByRole('radio', { name: 'Troisième option' })).toBeVisible();
    });

    test('can translate default value of a long answer question', async ({ page }) => {
        const form = new FormPage(page);

        // Go to form editor and add a long answer question with default value
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\Form$1`
        );
        const question = await form.addQuestion('Long answer question');
        await form.doChangeQuestionType(question, 'Long answer');
        const default_value = await form.initRichTextByLabel('Default value', question);
        await default_value.pressSequentially('This is a long answer question');
        await form.doSaveFormEditor();

        // Go to translations tab
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`
        );

        // Add French and translate default value
        const glpi = new GlpiPage(page);
        await page.getByRole('button', { name: 'Add language' }).click();
        const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
        await glpi.doSetDropdownValue(language_dropdown, 'Français');
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        const default_value_row = page.getByRole('row', { name: 'Translation row for Default value' });
        const trans_body = await glpi.initRichTextByLabel('Enter translation', default_value_row);
        await trans_body.pressSequentially('Ceci est une question de texte long');

        await page.getByRole('button', { name: 'Save translation' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Item successfully updated' })).toBeVisible();

        // Preview in default language
        await page.goto(`/Form/Render/${form_id}`);
        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
        await expect(page.getByRole('heading', { name: 'Long answer question' })).toBeVisible();
        const en_body = glpi.getRichTextByLabel('Long answer question');
        await expect(en_body).toHaveText('This is a long answer question');

        // Switch to French
        await setUserLanguage(page, 'fr_FR');
        await page.goto(`/Form/Render/${form_id}`);

        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
        await expect(page.getByRole('heading', { name: 'Long answer question' })).toBeVisible();
        const fr_body = glpi.getRichTextByLabel('Long answer question');
        await expect(fr_body).toHaveText('Ceci est une question de texte long');
    });

    test('can translate default value of a short answer question', async ({ page }) => {
        const form = new FormPage(page);

        // Go to form editor and add a short answer question with default value
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\Form$1`
        );
        const question = await form.addQuestion('Short answer question');
        await question.getByRole('textbox', { name: 'Default value' }).fill('This is a short answer question');
        await form.doSaveFormEditor();

        // Go to translations tab
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`
        );

        // Add French and translate default value
        const glpi = new GlpiPage(page);
        await page.getByRole('button', { name: 'Add language' }).click();
        const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
        await glpi.doSetDropdownValue(language_dropdown, 'Français');
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        const default_value_row = page.getByRole('row', { name: 'Translation row for Default value' });
        await default_value_row.getByRole('textbox', { name: 'Enter translation' })
            .fill('Ceci est une question de réponse courte');

        await page.getByRole('button', { name: 'Save translation' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Item successfully updated' })).toBeVisible();

        // Preview in default language
        await page.goto(`/Form/Render/${form_id}`);
        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
        await expect(page.getByRole('heading', { name: 'Short answer question' })).toBeVisible();
        await expect(page.getByRole('textbox', { name: 'Short answer question' })).toHaveValue('This is a short answer question');

        // Switch to French
        await setUserLanguage(page, 'fr_FR');
        await page.goto(`/Form/Render/${form_id}`);

        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
        await expect(page.getByRole('heading', { name: 'Short answer question' })).toBeVisible();
        await expect(page.getByRole('textbox', { name: 'Short answer question' })).toHaveValue('Ceci est une question de réponse courte');
    });

    test('can translate description of a question', async ({ page }) => {
        const form = new FormPage(page);

        // Go to form editor and add a question with description
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\Form$1`
        );
        const question = await form.addQuestion('Question with description');
        const desc = await form.getQuestionDescription(question);
        await desc.pressSequentially('This is a question with description');
        await form.doSaveFormEditor();

        // Go to translations tab
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`
        );

        // Add French and translate question description
        const glpi = new GlpiPage(page);
        await page.getByRole('button', { name: 'Add language' }).click();
        const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
        await glpi.doSetDropdownValue(language_dropdown, 'Français');
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        const desc_row = page.getByRole('row', { name: 'Translation row for Question description' });
        const trans_body = await glpi.initRichTextByLabel('Enter translation', desc_row);
        await trans_body.pressSequentially('Ceci est une question avec une description');

        await page.getByRole('button', { name: 'Save translation' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Item successfully updated' })).toBeVisible();

        // Preview in default language
        await page.goto(`/Form/Render/${form_id}`);
        await expect(page.getByRole('note', { name: 'Question description' })).toContainText('This is a question with description');

        // Switch to French
        await setUserLanguage(page, 'fr_FR');
        await page.goto(`/Form/Render/${form_id}`);

        await expect(page.getByRole('note', { name: 'Question description' })).toContainText('Ceci est une question avec une description');
    });

    test('can translate options of a dropdown question', async ({ page }) => {
        const form = new FormPage(page);

        // Go to form editor and add a dropdown question with options
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\Form$1`
        );
        const question = await form.addQuestion('Dropdown question');
        await form.doChangeQuestionType(question, 'Dropdown');
        await form.doAddDropdownOptions(question, [
            'First option', 'Second option', 'Third option',
        ]);
        await form.doSaveFormEditor();

        // Go to translations tab
        await page.goto(
            `/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`
        );

        // Add French and translate dropdown options
        const glpi = new GlpiPage(page);
        await page.getByRole('button', { name: 'Add language' }).click();
        const language_dropdown = glpi.getDropdownByLabel('Select language to translate');
        await glpi.doSetDropdownValue(language_dropdown, 'Français');
        await page.getByRole('button', { name: 'Add', exact: true }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        const dropdown_options = page.getByRole('row', { name: 'Translation row for Dropdown Option' });

        const option_1_row = dropdown_options.filter({ hasText: 'First option' });
        await option_1_row.getByRole('textbox', { name: 'Enter translation' })
            .fill('Première option');

        const option_2_row = dropdown_options.filter({ hasText: 'Second option' });
        await option_2_row.getByRole('textbox', { name: 'Enter translation' })
            .fill('Deuxième option');

        const option_3_row = dropdown_options.filter({ hasText: 'Third option' });
        await option_3_row.getByRole('textbox', { name: 'Enter translation' })
            .fill('Troisième option');

        await page.getByRole('button', { name: 'Save translation' }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Item successfully updated' })).toBeVisible();

        // Preview in default language
        await page.goto(`/Form/Render/${form_id}`);
        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
        await expect(page.getByRole('heading', { name: 'Dropdown question' })).toBeVisible();

        // Switch to French
        await setUserLanguage(page, 'fr_FR');
        await page.goto(`/Form/Render/${form_id}`);

        await expectTranslations(page, 'Tests form translations', 'This form is used to test form translations');
    });
});
