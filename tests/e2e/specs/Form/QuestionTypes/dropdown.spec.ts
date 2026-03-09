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

import { Locator } from '@playwright/test';
import { test, expect } from '../../../fixtures/glpi_fixture';
import { FormPage } from '../../../pages/FormPage';
import { FormPreviewPage } from '../../../pages/FormRenderPage';
import { Profiles } from '../../../utils/Profiles';

test.describe('Dropdown form question type', () => {
    let form: FormPage;
    let question: Locator;
    let form_id: number;

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form = new FormPage(page);

        const info = await formImporter.importForm('question_types/dropdown-editor-test.json');
        form_id = info.getId();
        await form.goto(form_id);

        question = form.getLastQuestion();
        await question.click({ position: { x: 0, y: 0 } });

        await expect(form.getSingleDropdownDefault(question)).toBeVisible();
        await expect(form.getDropdownOptionInputs(question)).toBeVisible();
    });

    test('Adding and selecting options (simple)', async () => {
        // Add a simple option
        await form.doAddDropdownOptions(question, ['Option 1']);
        await form.expectSingleDropdownSelection(question, null);
        await form.expectDropdownOptionLabels(question, ['Option 1']);

        // Add another option and select a value
        await form.doAddDropdownOptions(question, ['Option 2']);
        await form.doSelectSingleDropdownOption(question, 'Option 1');
        await form.expectSingleDropdownSelection(question, 'Option 1');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2']);

        // Add a third option
        await form.doAddDropdownOptions(question, ['Option 3']);
        await form.expectSingleDropdownSelection(question, 'Option 1');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Select the third option
        await form.doSelectSingleDropdownOption(question, 'Option 3');
        await form.expectSingleDropdownSelection(question, 'Option 3');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Save and reload
        await form.doSaveFormEditor();
        await form.page.reload();

        // Open the question details
        question = form.getLastQuestion();
        await question.click({ position: { x: 0, y: 0 } });

        // Validate options again
        await form.expectSingleDropdownSelection(question, 'Option 3');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);
    });

    test('Adding and selecting options (multiple)', async () => {
        // Add multiples options
        await form.doEnableMultipleDropdownMode(question);
        await form.doAddDropdownOptions(question, ['Option 1', 'Option 2', 'Option 3']);
        await form.expectMultipleDropdownSelection(question, [], ['Option 1', 'Option 2', 'Option 3']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Select an option
        await form.doSelectMultipleDropdownOption(question, 'Option 1');
        await form.expectMultipleDropdownSelection(question, ['Option 1'], ['Option 2', 'Option 3']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Select another option
        await form.doSelectMultipleDropdownOption(question, 'Option 2');
        await form.expectMultipleDropdownSelection(question, ['Option 1', 'Option 2'], ['Option 3']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Unselect an option
        await form.doDeselectMultipleDropdownOption(question, 'Option 1');
        await form.expectMultipleDropdownSelection(question, ['Option 2'], ['Option 1', 'Option 3']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Select another option
        await form.doSelectMultipleDropdownOption(question, 'Option 3');
        await form.expectMultipleDropdownSelection(question, ['Option 2', 'Option 3'], ['Option 1']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Save and reload
        await form.doSaveFormEditor();
        await form.page.reload();

        question = form.getLastQuestion();
        await question.click({ position: { x: 0, y: 0 } });

        // Validate options again
        await form.expectMultipleDropdownSelection(question, ['Option 2', 'Option 3'], ['Option 1']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);
    });

    test('Transferring options from simple to multiple and vice versa', async () => {
        // Add multiple options
        await form.doAddDropdownOptions(question, ['Option 1', 'Option 2', 'Option 3']);
        await form.doSelectSingleDropdownOption(question, 'Option 1');
        await form.expectSingleDropdownSelection(question, 'Option 1');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Switch to multiple mode
        await form.doEnableMultipleDropdownMode(question);
        await form.expectMultipleDropdownSelection(question, ['Option 1'], ['Option 2', 'Option 3']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Select a value
        await form.doSelectMultipleDropdownOption(question, 'Option 2');
        await form.expectMultipleDropdownSelection(question, ['Option 1', 'Option 2'], ['Option 3']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Switch bak to single mode
        await form.doDisableMultipleDropdownMode(question);
        await form.expectSingleDropdownSelection(question, 'Option 2');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);
    });

    test('Deleting options', async () => {
        // Add multiple options
        await form.doAddDropdownOptions(question, ['Option 1', 'Option 2', 'Option 3']);
        await form.doSelectSingleDropdownOption(question, 'Option 1');
        await form.expectSingleDropdownSelection(question, 'Option 1');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Delete an option
        await question.getByRole('button', { name: 'Remove option' }).first().click();
        await form.expectSingleDropdownSelection(question, null);
        await form.expectDropdownOptionLabels(question, ['Option 2', 'Option 3']);
    });

    test('End user view (simple)', async () => {
        // Add multiple options and select one as default
        await form.doAddDropdownOptions(question, ['Option 1', 'Option 2', 'Option 3']);
        await form.doSelectSingleDropdownOption(question, 'Option 1');
        await form.expectSingleDropdownSelection(question, 'Option 1');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Save and preview
        await form.doSaveFormEditor();
        await form.doPreviewForm();
        const preview = new FormPreviewPage(form.page);
        await expect(form.page.getByRole('heading', { name: 'Test dropdown question' })).toBeVisible();

        // Default value should be selected
        const preview_dropdown = preview.getDropdownByLabel('Test dropdown question');
        await expect(preview_dropdown).toContainText('Option 1');

        await preview_dropdown.click();
        await expect(form.page.getByRole('option', { name: 'Option 2' })).toBeVisible();
        await expect(form.page.getByRole('option', { name: 'Option 3' })).toBeVisible();
    });

    test('End user view (multiple)', async () => {
        // Add multiple options and select multiple values as default
        await form.doEnableMultipleDropdownMode(question);
        await form.doAddDropdownOptions(question, ['Option 1', 'Option 2', 'Option 3']);
        await form.doSelectMultipleDropdownOption(question, 'Option 1');
        await form.doSelectMultipleDropdownOption(question, 'Option 3');
        await form.expectMultipleDropdownSelection(question, ['Option 1', 'Option 3'], ['Option 2']);
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Save and preview
        await form.doSaveFormEditor();
        await form.doPreviewForm();
        await expect(form.page.getByRole('heading', { name: 'Test dropdown question' })).toBeVisible();

        // Default values should be selected
        await expect(form.page.getByRole('listitem', { name: 'Option 1' })).toBeVisible();
        await expect(form.page.getByRole('listitem', { name: 'Option 2' })).toBeHidden();
        await expect(form.page.getByRole('listitem', { name: 'Option 3' })).toBeVisible();

        // Select another value
        await form.page.getByRole('listitem', { name: 'Option 1' }).click();
        await expect(form.page.getByRole('option', { name: 'Option 1' })).toBeVisible();
        await expect(form.page.getByRole('option', { name: 'Option 2' })).toBeVisible();
        await expect(form.page.getByRole('option', { name: 'Option 3' })).toBeVisible();
    });

    test('Default option selection and reset to empty value', async () => {
        // Add multiple options and select multiple values as default
        await form.doAddDropdownOptions(question, ['Option 1', 'Option 2', 'Option 3']);
        await form.doSetDropdownValue(
            form.getSingleDropdownDefault(question),
            'Option 1'
        );
        await form.expectSingleDropdownSelection(question, 'Option 1');
        await form.expectDropdownOptionLabels(question, ['Option 1', 'Option 2', 'Option 3']);

        // Save and preview
        await form.doSaveFormEditor();
        await form.page.reload();

        question = form.getLastQuestion();
        await question.click({ position: { x: 0, y: 0 } });

        await expect(form.getSingleDropdownDefault(question)).toContainText('Option 1');

        await form.doSetDropdownValue(
            form.getSingleDropdownDefault(question),
            '-----'
        );

        await form.doSaveFormEditor();
        await form.page.reload();

        question = form.getLastQuestion();
        await question.click({ position: { x: 0, y: 0 } });

        await expect(form.getSingleDropdownDefault(question)).toContainText('-----');
    });

    test('Visibility conditions with default option', async ({ page }) => {
        await form.doAddDropdownOptions(question, ['Option 1', 'Option 2', 'Option 3']);

        await form.doSetDropdownValue(
            form.getSingleDropdownDefault(question),
            'Option 2'
        );

        await addVisibilityConditionAndPreview();

        await expect(page.getByRole('heading', { name: 'Test visibility question' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
    });

    test('Visibility conditions without default option', async ({ page }) => {
        await form.doAddDropdownOptions(question, ['Option 1', 'Option 2', 'Option 3']);

        await addVisibilityConditionAndPreview();

        await expect(page.getByRole('heading', { name: 'Test visibility question' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'Submit' })).toBeHidden();

        const preview = new FormPreviewPage(page);
        await preview.doSetDropdownValue(
            preview.getDropdownByLabel('Test dropdown question'),
            'Option 2'
        );

        await expect(page.getByRole('heading', { name: 'Test visibility question' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
    });

    async function addVisibilityConditionAndPreview(): Promise<void> {
        await form.addQuestion('Test visibility question');

        await form.doInitVisibilityConditionsDropdown(1);
        await form.doSetVisibilityStrategy('Visible if...');
        await form.doFillVisibilityCondition('Test dropdown question', 'Is equal to', 'Option 2');

        await form.doOpenSubmitButtonConditions();
        await form.doSetVisibilityStrategy('Visible if...');
        await form.doFillVisibilityCondition('Test dropdown question', 'Is equal to', 'Option 2');

        await form.doSaveFormEditor();
        await form.doPreviewForm();
    }
});
