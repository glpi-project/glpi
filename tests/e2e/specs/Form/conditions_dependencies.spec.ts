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
import { FormPage } from '../../pages/FormPage';
import { Profiles } from '../../utils/Profiles';

test(`Can't delete a question used in conditions`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("question-used-in-conditions.json");
    await form.goto(info.getId());

    // Try to delete the first question: dialog should block deletion
    const first_question = form.getNthQuestion(0);
    await first_question.click();
    await first_question.getByRole('button', { name: 'Delete' }).click();

    const dialog = page.getByRole('dialog', { name: 'Item has conditions and cannot be deleted' });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'My second question' })).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'First section' })).toBeHidden();
    await dialog.getByRole('button', { name: 'Close' }).click();

    // Delete both conditions from the second question
    await form.doOpenQuestionConditionEditor(1);
    await form.doDeleteCondition(1);
    await form.doDeleteCondition(0);

    // Now deleting the first question should succeed (no dialog)
    const first_question_after_condition_removal = form.getNthQuestion(0);
    await first_question_after_condition_removal.click();
    await first_question_after_condition_removal.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByRole('dialog', { name: 'Item has conditions and cannot be deleted' })).toBeHidden();
});

test(`Can't delete a comment used in conditions`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("comment-used-in-conditions.json");
    await form.goto(info.getId());

    // Try to delete the comment: dialog should block deletion
    const comment = form.getRegion('Comment details').nth(0);
    await comment.click();
    await comment.getByRole('button', { name: 'Delete' }).click();

    const dialog = page.getByRole('dialog', { name: 'Item has conditions and cannot be deleted' });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'My question' })).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'First section' })).toBeHidden();
    await dialog.getByRole('button', { name: 'Close' }).click();

    // Delete the condition from the question
    await form.doOpenQuestionConditionEditor(0);
    await form.doDeleteCondition(0);

    // Now deleting the comment should succeed (no dialog)
    const comment_after_condition_removal = form.getRegion('Comment details').nth(0);
    await comment_after_condition_removal.click();
    await comment_after_condition_removal.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByRole('dialog', { name: 'Item has conditions and cannot be deleted' })).toBeHidden();
});

test(`Can't delete a section used in conditions`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("section-used-in-conditions.json");
    await form.goto(info.getId());

    // Try to delete the first section: dialog should block deletion
    const first_section = form.getRegion('Section details').nth(0);
    await first_section.click();
    await first_section.getByRole('button', { name: 'More actions' }).click();
    await first_section.getByRole('button', { name: 'Delete section' }).click();

    const dialog = page.getByRole('dialog', { name: 'Item has conditions and cannot be deleted' });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'Second section' })).toBeVisible();
    await dialog.getByRole('button', { name: 'Close' }).click();

    // Delete the condition from the second section
    await form.doOpenSectionConditionEditor(1);
    await form.doDeleteCondition(0);

    // Now deleting the first section should succeed (no dialog)
    const first_section_after_condition_removal = form.getRegion('Section details').nth(0);
    await first_section_after_condition_removal.click();
    await first_section_after_condition_removal.getByRole('button', { name: 'More actions' }).click();
    await first_section_after_condition_removal.getByRole('button', { name: 'Delete section' }).click();
    await expect(page.getByRole('dialog', { name: 'Item has conditions and cannot be deleted' })).toBeHidden();
});

test(`Can't delete a section, question or comment used in destination conditions`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("items-used-in-destination-conditions.json");
    await form.goto(info.getId());

    const dialog_name = 'Item has conditions and cannot be deleted';

    // Try to delete the question: dialog should block deletion
    const question = form.getNthQuestion(0);
    await question.click();
    await question.getByRole('button', { name: 'Delete' }).click();

    const question_dialog = page.getByRole('dialog', { name: dialog_name });
    await expect(question_dialog).toBeVisible();
    await expect(question_dialog.getByRole('link', { name: 'Ticket' })).toBeVisible();
    await question_dialog.getByRole('button', { name: 'Close' }).click();

    // Try to delete the section: dialog should block deletion
    const section = form.getRegion('Section details').nth(1);
    await section.click();
    await section.getByRole('button', { name: 'More actions' }).click();
    await section.getByRole('button', { name: 'Delete section' }).click();

    const section_dialog = page.getByRole('dialog', { name: dialog_name });
    await expect(section_dialog).toBeVisible();
    await expect(section_dialog.getByRole('link', { name: 'Ticket' })).toBeVisible();
    await section_dialog.getByRole('button', { name: 'Close' }).click();

    // Try to delete the comment: dialog should block deletion
    const comment = form.getRegion('Comment details').nth(0);
    await comment.click();
    await comment.getByRole('button', { name: 'Delete' }).click();

    const comment_dialog = page.getByRole('dialog', { name: dialog_name });
    await expect(comment_dialog).toBeVisible();
    await expect(comment_dialog.getByRole('link', { name: 'Ticket' })).toBeVisible();
    await comment_dialog.getByRole('button', { name: 'Close' }).click();
});

test(`Can't change type of a question used in conditions with unsupported operators`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("question-type-change-blocked.json");
    await form.goto(info.getId());

    // Try to change the urgency question type to Short answer: dialog should block the change
    const first_question = form.getNthQuestion(0);
    await first_question.getByRole('textbox', { name: 'Question name' }).click();
    const type_dropdown = form.getDropdownByLabel('Question type', first_question);
    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: 'Short answer' }).click();

    const dialog = page.getByRole('dialog', { name: 'Question has conditions and its type cannot be changed' });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'My second question' })).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'First section' })).toBeHidden();
    await dialog.getByRole('button', { name: 'Close' }).click();

    // Delete the condition from the second question
    await form.doOpenQuestionConditionEditor(1);
    await form.doDeleteCondition(0);

    // Now changing the type should succeed (no dialog)
    const first_question_after = form.getNthQuestion(0);
    await first_question_after.getByRole('textbox', { name: 'Question name' }).click();
    const type_dropdown_after = form.getDropdownByLabel('Question type', first_question_after);
    await form.doSetDropdownValue(type_dropdown_after, 'Short answer', false);
    await expect(page.getByRole('dialog', { name: 'Question has conditions and its type cannot be changed' })).toBeHidden();
});

test(`Can't change type of a question used in destination conditions with unsupported operators`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("question-type-change-blocked-destination.json");
    await form.goto(info.getId());

    // Try to change the urgency question type to Short answer: dialog should block the change
    const first_question = form.getNthQuestion(0);
    await first_question.getByRole('textbox', { name: 'Question name' }).click();
    const type_dropdown = form.getDropdownByLabel('Question type', first_question);
    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: 'Short answer' }).click();

    const dialog = page.getByRole('dialog', { name: 'Question has conditions and its type cannot be changed' });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'Ticket' })).toBeVisible();
    await dialog.getByRole('button', { name: 'Close' }).click();

    // Delete the destination condition
    await form.gotoDestinationTab(info.getId());
    await form.doOpenDestinationConditionEditor();
    await form.doDeleteCondition(0);
    await form.doSaveDestination();

    // Go back to Form tab and change type: should succeed
    await form.goto(info.getId());
    const first_question_after = form.getNthQuestion(0);
    await first_question_after.getByRole('textbox', { name: 'Question name' }).click();
    const type_dropdown_after = form.getDropdownByLabel('Question type', first_question_after);
    await form.doSetDropdownValue(type_dropdown_after, 'Short answer', false);
    await expect(page.getByRole('dialog', { name: 'Question has conditions and its type cannot be changed' })).toBeHidden();
});

test(`Can't delete a section, question or comment used in submit button conditions`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("items-used-in-submit-button-conditions.json");
    await form.goto(info.getId());

    const dialog_name = 'Item has conditions and cannot be deleted';

    // Try to delete the question: dialog should block deletion
    const question = form.getNthQuestion(0);
    await question.click();
    await question.getByRole('button', { name: 'Delete' }).click();

    const question_dialog = page.getByRole('dialog', { name: dialog_name });
    await expect(question_dialog).toBeVisible();
    await expect(question_dialog.getByRole('link', { name: 'Submit button visibility' })).toBeVisible();
    await question_dialog.getByRole('button', { name: 'Close' }).click();

    // Try to delete the section: dialog should block deletion
    const section = form.getRegion('Section details').nth(1);
    await section.click();
    await section.getByRole('button', { name: 'More actions' }).click();
    await section.getByRole('button', { name: 'Delete section' }).click();

    const section_dialog = page.getByRole('dialog', { name: dialog_name });
    await expect(section_dialog).toBeVisible();
    await expect(section_dialog.getByRole('link', { name: 'Submit button visibility' })).toBeVisible();
    await section_dialog.getByRole('button', { name: 'Close' }).click();

    // Try to delete the comment: dialog should block deletion
    const comment = form.getRegion('Comment details').nth(0);
    await comment.click();
    await comment.getByRole('button', { name: 'Delete' }).click();

    const comment_dialog = page.getByRole('dialog', { name: dialog_name });
    await expect(comment_dialog).toBeVisible();
    await expect(comment_dialog.getByRole('link', { name: 'Submit button visibility' })).toBeVisible();
    await comment_dialog.getByRole('button', { name: 'Close' }).click();
});

test(`Can't change type of a question used in submit button conditions with unsupported operators`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("question-type-change-blocked-submit-button.json");
    await form.goto(info.getId());

    const dialog_name = 'Question has conditions and its type cannot be changed';

    // Try to change the urgency question type to Short answer: dialog should block the change
    const first_question = form.getNthQuestion(0);
    await first_question.getByRole('textbox', { name: 'Question name' }).click();
    const type_dropdown = form.getDropdownByLabel('Question type', first_question);
    await type_dropdown.click();
    await page.getByRole('listbox').getByRole('option', { name: 'Short answer' }).click();

    const dialog = page.getByRole('dialog', { name: dialog_name });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('link', { name: 'Submit button visibility' })).toBeVisible();
    await dialog.getByRole('button', { name: 'Close' }).click();

    // Change the type of the second question to Long answer: should succeed (operator is compatible)
    const second_question = form.getNthQuestion(1);
    await second_question.getByRole('textbox', { name: 'Question name' }).click();
    const second_type_dropdown = form.getDropdownByLabel('Question type', second_question);
    await form.doSetDropdownValue(second_type_dropdown, 'Long answer', false);
    await expect(page.getByRole('dialog', { name: dialog_name })).toBeHidden();

    // Delete the submit button condition that blocks the type change
    await form.doOpenSubmitButtonConditions();
    await form.doDeleteCondition(0);

    // Now changing the type should succeed (no dialog)
    const first_question_after = form.getNthQuestion(0);
    await first_question_after.getByRole('textbox', { name: 'Question name' }).click();
    const type_dropdown_after = form.getDropdownByLabel('Question type', first_question_after);
    await form.doSetDropdownValue(type_dropdown_after, 'Short answer', false);
    await expect(page.getByRole('dialog', { name: dialog_name })).toBeHidden();
});

test(`Condition dependency check takes priority over non-empty section warning`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("condition-priority-over-non-empty-section.json");
    await form.goto(info.getId());

    // Try to delete the section that has conditions AND contains elements
    const section = form.getRegion('Section details').nth(1);
    await section.click();
    await section.getByRole('button', { name: 'More actions' }).click();
    await section.getByRole('button', { name: 'Delete section' }).click();

    // Should show the conditions dependency modal, NOT the non-empty warning modal
    const dialog = page.getByRole('dialog', { name: 'Item has conditions and cannot be deleted' });
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Close' }).click();

    // The non-empty section modal should NOT have appeared
    await expect(page.getByRole('dialog', { name: 'Delete non-empty section' })).toBeHidden();

    // Section should still exist
    await expect(form.getRegion('Form section')).toHaveCount(2);
});

test(`Blocks external dependencies when deleting non-empty section`, async ({
    page,
    profile,
    formImporter,
}) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);
    const info = await formImporter.importForm("external-deps-block-section-deletion.json");
    await form.goto(info.getId());

    // Try to delete the section that contains the question used in external conditions
    const section = form.getRegion('Section details').nth(1);
    await section.click();
    await section.getByRole('button', { name: 'More actions' }).click();
    await section.getByRole('button', { name: 'Delete section' }).click();

    // Should show the child conditions dependency modal
    const dialog = page.getByRole('dialog', { name: 'Child items have conditions and cannot be deleted' });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByText('Question in another section')).toBeVisible();
    await expect(dialog.getByText('Submit button visibility')).toBeVisible();
    await expect(dialog.getByText('Ticket')).toBeVisible();
    await dialog.getByRole('button', { name: 'Close' }).click();

    // The non-empty section modal should NOT have appeared
    await expect(page.getByRole('dialog', { name: 'Delete non-empty section' })).toBeHidden();

    // All 3 sections should still exist
    await expect(form.getRegion('Form section')).toHaveCount(3);
});
