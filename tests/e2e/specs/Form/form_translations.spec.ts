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

import { randomUUID } from "crypto";
import { expect, test } from '../../fixtures/glpi_fixture';
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormTranslationPage } from "../../pages/FormTranslationPage";
import { pasteImageInRichText, assertPastedImageIsCorrectlyInserted } from "../../utils/ImagePasteHelpers";

test('Can copy default value to translation', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form_translation = new FormTranslationPage(page);

    // Create a form and go to its translation page
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        header: `Form description`,
        entities_id: getWorkerEntityId(),
    });
    await form_translation.goto(form_id);

    // Add a language
    await form_translation.addLanguage('Français');
    await form_translation.expectLanguageDropdownOpened('Français');

    // Check we can copy default value to translation for "Form title"
    const translationRow = await form_translation.getTranslationRow('Form title');
    const copyDefaultValueButton = translationRow.getByRole('button', { name: 'Copy default value to translation' });
    const translationInput = translationRow.getByRole('cell', { name: 'Translated value' }).getByRole('textbox');
    await expect(copyDefaultValueButton).toBeVisible();
    await expect(translationInput).toBeEmpty();

    await copyDefaultValueButton.click();
    await expect(translationInput).toHaveValue(`Form - ${uuid}`);

    // Check we can copy default value to translation for "Form description"
    const descriptionRow = await form_translation.getTranslationRow('Form description');
    const copyDefaultValueButtonDesc = descriptionRow.getByRole('button', { name: 'Copy default value to translation' });
    const translationInputDesc = descriptionRow.getByRole('cell', { name: 'Translated value' }).getByRole('textbox');
    await expect(copyDefaultValueButtonDesc).toBeVisible();
    await expect(translationInputDesc).toHaveText('Enter translation');

    await copyDefaultValueButtonDesc.click();

    // Ensure translationInputDesc has been replaced with a tinymce editor
    await expect(translationInputDesc).toHaveCount(0);
    const richText = await form_translation.getRichTextByLabel('Enter translation');
    await expect(richText).toHaveText('Form description');

    // Now test with already initialized tinymce
    await richText.fill('');
    await expect(richText).toBeEmpty();
    await copyDefaultValueButtonDesc.click();
    await expect(translationInputDesc).toHaveCount(0);
    await expect(richText).toHaveText('Form description');

    // Save the translation and open it again to ensure values are persisted
    await form_translation.saveTranslation();
    await form_translation.openLanguage('Français');
    await form_translation.expectLanguageDropdownOpened('Français');

    // Check values
    await expect(translationInput).toHaveValue(`Form - ${uuid}`);
    await expect(translationInputDesc).toHaveText('Form description');
});

// We test with two languages having different plural forms
const language_to_test = ['Français', '日本語'];
for (const language of language_to_test) {
    test(`Can paste image in form translation (${language})`, async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form_translation = new FormTranslationPage(page);

        // Create a form and go to its translation page
        const uuid = randomUUID();
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Form - ${uuid}`,
            header: `Form description`,
            entities_id: getWorkerEntityId(),
        });
        await form_translation.goto(form_id);

        // Add a language
        await form_translation.addLanguage(language);
        await form_translation.expectLanguageDropdownOpened(language);

        // Retrieve textarea name attribute to verify upload later
        const translationRow = await form_translation.getTranslationRow('Form description');
        const textarea = translationRow.getByRole('cell', { name: 'Translated value' })
            .getByLabel('Enter translation');
        const textareaName = await textarea.getAttribute('name');

        // Paste image in form description translation
        await pasteImageInRichText(
            page,
            () => form_translation.getTranslationRichTextByLabel('Form description'),
            `_uploader_${textareaName}`
        );

        // Save the translation and open it again to ensure values are persisted
        await form_translation.saveTranslation();
        await form_translation.openLanguage(language);
        await form_translation.expectLanguageDropdownOpened(language);

        // Verify the pasted image is displayed
        await assertPastedImageIsCorrectlyInserted(
            () => form_translation.getTranslationRichTextByLabel('Form description')
        );
    });
}
