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
import { test, expect } from '../../fixtures/glpi_fixture';
import { FormPage } from '../../pages/FormPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Can set the conditional validation of a question', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test validation strategies - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    await form.addQuestion('My first question');
    await form.doSaveFormEditorAndReload();

    // Select 'Valid if...' (editor should be displayed)
    await form.doInitValidationConfiguration(0);
    await expect(form.getRadio('No validation')).toBeVisible();
    await expect(form.getRadio('Valid if...')).toBeVisible();
    await expect(form.getRadio('Invalid if...')).toBeVisible();
    await expect(form.getRadio('No validation')).toBeChecked();
    await expect(page.getByTestId('conditions-editor').filter({ visible: true })).toBeHidden();

    await form.doSetValidationStrategy('Valid if...');
    await expect(form.getRadio('Valid if...')).toBeChecked();
    await expect(page.getByTestId('conditions-editor').filter({ visible: true })).toBeVisible();

    await form.doSaveFormEditorAndReload();
    await form.doOpenValidationConditionEditor(0);
    await expect(form.getRadio('Valid if...')).toBeChecked();
    await expect(page.getByTestId('conditions-editor').filter({ visible: true })).toBeVisible();
    await form.doCloseValidationConditionEditor(0);

    // Select 'Invalid if...' (editor should be displayed)
    await form.doOpenValidationConditionEditor(0);
    await expect(form.getRadio('Valid if...')).toBeChecked();
    await form.doSetValidationStrategy('Invalid if...');
    await expect(form.getRadio('Invalid if...')).toBeChecked();
    await expect(page.getByTestId('conditions-editor').filter({ visible: true })).toBeVisible();

    await form.doSaveFormEditorAndReload();
    await form.doOpenValidationConditionEditor(0);
    await expect(form.getRadio('Invalid if...')).toBeChecked();
    await expect(page.getByTestId('conditions-editor').filter({ visible: true })).toBeVisible();
    await form.doCloseValidationConditionEditor(0);

    // Select 'No validation' (editor should be hidden)
    await form.doOpenValidationConditionEditor(0);
    await expect(form.getRadio('Invalid if...')).toBeChecked();
    await form.doSetValidationStrategy('No validation');
    await expect(form.getRadio('No validation')).toBeChecked();
    await expect(page.getByTestId('conditions-editor').filter({ visible: true })).toBeHidden();

    await form.doSaveFormEditorAndReload();
    await form.doInitValidationConfiguration(0);
    await expect(form.getRadio('No validation')).toBeChecked();
    await expect(page.getByTestId('conditions-editor').filter({ visible: true })).toBeHidden();
});

test('Can use the editor to add or delete conditions on a question', async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("three-short-text-questions.json");
    await form.goto(info.getId());

    // Add conditions on the third question
    await form.doInitValidationConfiguration(2);
    await form.doSetValidationStrategy('Valid if...');
    await form.doFillValidationCondition(
        0, null, 'Match regular expression', '/^I love GLPI$/'
    );
    await form.doAddValidationCondition();
    await form.doFillValidationCondition(
        1, 'Or', 'Match regular expression', '/^GLPI is great$/'
    );

    await form.doSaveFormEditorAndReload();
    await form.doOpenValidationConditionEditor(2);

    const conditions = form.getVisibleValidationConditions();
    await expect(form.getValidationConditionValueOperator(conditions.nth(0)))
        .toHaveText('Match regular expression');
    await expect(form.getValidationConditionTextValue(conditions.nth(0)))
        .toHaveValue('/^I love GLPI$/');
    await expect(form.getValidationConditionLogicOperator(conditions.nth(1)))
        .toHaveText('Or');
    await expect(form.getValidationConditionValueOperator(conditions.nth(1)))
        .toHaveText('Match regular expression');
    await expect(form.getValidationConditionTextValue(conditions.nth(1)))
        .toHaveValue('/^GLPI is great$/');

    // Delete the first condition
    await form.doDeleteValidationCondition(0);
    await expect(form.getValidationConditionValueOperator(conditions.nth(0)))
        .toHaveText('Match regular expression');
    await expect(form.getValidationConditionTextValue(conditions.nth(0)))
        .toHaveValue('/^GLPI is great$/');
    await expect(conditions).toHaveCount(1);

    // Save and reload, then verify only one condition remains
    await form.doSaveFormEditorAndReload();
    await form.doOpenValidationConditionEditor(2);

    const final_conditions = form.getVisibleValidationConditions();
    await expect(form.getValidationConditionValueOperator(final_conditions.nth(0)))
        .toHaveText('Match regular expression');
    await expect(form.getValidationConditionTextValue(final_conditions.nth(0)))
        .toHaveValue('/^GLPI is great$/');
    await expect(final_conditions).toHaveCount(1);
});

test('Conditions are applied on questions', async ({
    page,
    profile,
    api,
}, testInfo) => {
    testInfo.setTimeout(60_000);
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test validation applied - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
        is_active: true,
    });
    await form.goto(form_id);

    // Add three mandatory questions
    const q1 = await form.addQuestion('My question that has no validation');
    await q1.getByRole('checkbox', { name: 'Mandatory' }).check();

    const q2 = await form.addQuestion('My question that is valid if some criteria are met');
    await q2.getByRole('checkbox', { name: 'Mandatory' }).check();

    const q3 = await form.addQuestion('My question that is invalid if some criteria are met');
    await q3.getByRole('checkbox', { name: 'Mandatory' }).check();

    // Set "No validation" on the first question
    await form.doInitValidationConfiguration(0);
    await form.doSetValidationStrategy('No validation');
    await form.doCloseValidationConditionEditor(0);

    // Set "Valid if..." on the second question
    await form.doInitValidationConfiguration(1);
    await form.doSetValidationStrategy('Valid if...');
    await form.doFillValidationCondition(
        0, null, 'Match regular expression', '/^I love GLPI$/'
    );
    await form.doCloseValidationConditionEditor(1);

    // Set "Invalid if..." on the third question
    await form.doInitValidationConfiguration(2);
    await form.doSetValidationStrategy('Invalid if...');
    await form.doFillValidationCondition(
        0, null, 'Match regular expression', '/^I love GLPI$/'
    );
    await form.doCloseValidationConditionEditor(2);

    await form.doSaveFormEditor();
    await form.doPreviewForm();

    // Submit the form without filling any answers
    await page.getByRole('button', { name: 'Submit' }).click();

    // All mandatory fields should show errors
    const textbox_q1 = page.getByRole('textbox', { name: 'My question that has no validation' });
    const textbox_q2 = page.getByRole('textbox', { name: 'My question that is valid if some criteria are met' });
    const textbox_q3 = page.getByRole('textbox', { name: 'My question that is invalid if some criteria are met' });

    await expect(textbox_q1).toHaveAttribute('aria-invalid', 'true');
    await expect(textbox_q2).toHaveAttribute('aria-invalid', 'true');
    await expect(textbox_q3).toHaveAttribute('aria-invalid', 'true');

    await expect(form.getValidationErrorMessage(textbox_q1)).toContainText('This field is mandatory');
    await expect(form.getValidationErrorMessage(textbox_q2)).toContainText('This field is mandatory');
    await expect(form.getValidationErrorMessage(textbox_q3)).toContainText('This field is mandatory');

    // Fill all questions with a value that does NOT match the regex
    await textbox_q1.fill('GLPI is great');
    await textbox_q2.fill('GLPI is great');
    await textbox_q3.fill('GLPI is great');

    // Submit the form again
    await page.getByRole('button', { name: 'Submit' }).click();

    // Q1 should be valid (no validation), Q2 should be invalid (Valid if... not met), Q3 should be valid (Invalid if... not met)
    await expect(textbox_q1).not.toHaveAttribute('aria-invalid');
    await expect(textbox_q2).toHaveAttribute('aria-invalid', 'true');
    await expect(textbox_q3).not.toHaveAttribute('aria-invalid');

    await expect(form.getValidationErrorMessage(textbox_q2)).toContainText('The value must match the requested format');

    // Fill Q2 and Q3 with a value that matches the regex
    await textbox_q2.fill('I love GLPI');
    await textbox_q3.fill('I love GLPI');

    // Submit the form again
    await page.getByRole('button', { name: 'Submit' }).click();

    // Q1 valid, Q2 valid (Valid if... met), Q3 invalid (Invalid if... met)
    await expect(textbox_q1).not.toHaveAttribute('aria-invalid');
    await expect(textbox_q2).not.toHaveAttribute('aria-invalid');
    await expect(textbox_q3).toHaveAttribute('aria-invalid', 'true');

    await expect(form.getValidationErrorMessage(textbox_q3)).toContainText('The value must not match the requested format');
});

test('Conditions count badge is updated when conditions are added or removed', async ({
    page,
    profile,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test validation count badge - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    await form.addQuestion('My first question');
    await form.addQuestion('My second question');

    // Initialize validation on the second question with "Valid if..."
    await form.doInitValidationConfiguration(1);
    await form.doSetValidationStrategy('Valid if...');
    await form.doCloseValidationConditionEditor(1);

    // Verify initial count is 0
    await expect(form.getValidationConditionsCountBadge(1)).toHaveText('0');

    // Add first condition
    await form.doOpenValidationConditionEditor(1);
    await form.doFillValidationCondition(
        0, null, 'Do not match regular expression', '/^Expected answer 1$/'
    );
    await form.doCloseValidationConditionEditor(1);
    await expect(form.getValidationConditionsCountBadge(1)).toHaveText('1');

    // Add second condition
    await form.doOpenValidationConditionEditor(1);
    await form.doAddValidationCondition();
    await form.doFillValidationCondition(
        1, null, 'Match regular expression', '/^Expected answer 2$/'
    );
    await form.doCloseValidationConditionEditor(1);
    await expect(form.getValidationConditionsCountBadge(1)).toHaveText('2');

    // Delete first condition
    await form.doOpenValidationConditionEditor(1);
    await form.doDeleteValidationCondition(0);
    await form.doCloseValidationConditionEditor(1);
    await expect(form.getValidationConditionsCountBadge(1)).toHaveText('1');

    // Test persistence after reload
    await form.doSaveFormEditorAndReload();
    await form.getNthQuestion(1).click();
    await expect(form.getValidationConditionsCountBadge(1)).toHaveText('1');

    // Delete the remaining condition
    await form.doOpenValidationConditionEditor(1);
    await form.doDeleteValidationCondition(0);
    await form.doCloseValidationConditionEditor(1);
    await expect(form.getValidationConditionsCountBadge(1)).toHaveText('0');
});
