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

import { expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

test('Can enter and exit translation mode', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Translation Mode Test',
        entities_id: getWorkerEntityId(),
        answer: '<p>English content</p>',
    });

    await kb.goto(id);

    // Click translations link to enter translation mode
    const translations_link = page.getByTestId('translations-count');
    await translations_link.click();

    // Alert should appear
    const alert = page.getByTestId('translation-mode-alert');
    await expect(alert).toBeVisible();

    // Language dropdown should be visible
    const language_select = page.getByTestId('translation-language-select');
    await expect(language_select).toBeVisible();

    // Close translation mode
    const close_btn = page.getByTestId('translation-mode-close');
    await close_btn.click();

    // Alert should be hidden
    await expect(alert).toBeHidden();

    // Original content should be restored
    await expect(page.getByTestId('content')).toContainText('English content');
});

test('Can create a new translation', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Translatable Article',
        entities_id: getWorkerEntityId(),
        answer: '<p>Original English content</p>',
    });

    await kb.goto(id);

    // Enter translation mode
    const translations_link = page.getByTestId('translations-count');
    await expect(translations_link).toContainText('0');
    await translations_link.click();

    const alert = page.getByTestId('translation-mode-alert');
    await expect(alert).toBeVisible();

    // Select French language
    const language_select = page.getByTestId('translation-language-select');
    await language_select.selectOption('fr_FR');

    // Type translated title
    const subject = page.getByTestId('subject');
    await subject.click();
    await page.keyboard.press('Control+a');
    await page.keyboard.type('Article Traduisible');

    // Type translated content
    await kb.editor.setContent('Contenu en français');

    // Save
    const save_button = page.getByTestId('translation-save-btn');
    await save_button.click();
    await expect(kb.getAlert('Translation saved successfully')).toBeVisible();

    // Verify translation count updated
    await expect(translations_link).toContainText('1');

    // Verify saved content is displayed
    await expect(page.getByTestId('content')).toContainText('Contenu en français');
    await expect(subject).toHaveText('Article Traduisible');
});

test('Can switch between languages in translation mode', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Multi-lang Article',
        entities_id: getWorkerEntityId(),
        answer: '<p>Base content</p>',
    });

    // Create a French translation via API
    await api.createItem('KnowbaseItemTranslation', {
        knowbaseitems_id: id,
        language: 'fr_FR',
        name: 'Article Multi-langue',
        answer: '<p>Contenu de base</p>',
    });

    await kb.goto(id);

    // Enter translation mode
    await page.getByTestId('translations-count').click();
    const alert = page.getByTestId('translation-mode-alert');
    await expect(alert).toBeVisible();

    // French should be pre-selected since it has a translation
    const language_select = page.getByTestId('translation-language-select');
    await expect(language_select).toHaveValue('fr_FR');

    // Verify French content is loaded
    await expect(page.getByTestId('content')).toContainText('Contenu de base');
    await expect(page.getByTestId('subject')).toHaveText('Article Multi-langue');

    // Exit translation mode and verify original content restored
    await page.getByTestId('translation-mode-close').click();
    await expect(page.getByTestId('content')).toContainText('Base content');
    await expect(page.getByTestId('subject')).toHaveText('Multi-lang Article');
});

test('Can delete a translation', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const id = await api.createItem('KnowbaseItem', {
        name: 'Delete Translation Test',
        entities_id: getWorkerEntityId(),
        answer: '<p>Original content</p>',
    });

    // Create a Spanish translation
    await api.createItem('KnowbaseItemTranslation', {
        knowbaseitems_id: id,
        language: 'es_ES',
        name: 'Artículo de prueba',
        answer: '<p>Contenido en español</p>',
    });

    await kb.goto(id);

    // Enter translation mode
    await page.getByTestId('translations-count').click();
    const alert = page.getByTestId('translation-mode-alert');
    await expect(alert).toBeVisible();

    // Spanish should be selected
    const language_select = page.getByTestId('translation-language-select');
    await expect(language_select).toHaveValue('es_ES');

    // Delete button should be visible for existing translation
    const delete_btn = page.getByTestId('translation-delete-btn');
    await expect(delete_btn).toBeVisible();

    // Click delete
    await delete_btn.click();

    // Confirm deletion
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();
    await modal.getByRole('button', { name: 'Delete' }).click();
    await expect(kb.getAlert('Translation deleted successfully ')).toBeVisible();

    // Translation count should be updated
    const translations_link = page.getByTestId('translations-count');
    await expect(translations_link).toContainText('0');
});
