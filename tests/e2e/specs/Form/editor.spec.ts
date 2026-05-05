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

import { Locator, Page } from "@playwright/test";
import { randomUUID } from "crypto";
import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormPage } from "../../pages/FormPage";
import { pasteImageInRichText, assertPastedImageIsCorrectlyInserted } from "../../utils/ImagePasteHelpers";

test('Can create a form and fill its main details', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Go to form creation page
    await page.goto('/front/form/form.form.php');
    await form.getTab('Form').click();

    // Edit form details
    const form_details = form.getRegion('Form details');
    await form_details.getByRole('textbox', { name: 'Form name' })
        .fill('My form name');

    await expect(form.editor_active_checkbox).not.toBeChecked();
    await form.editor_active_checkbox.check();

    const description = await form.getFormHeader();
    await description.fill('My form description');

    // Save form and reload page
    await form.getButton('Add').click();
    await expect(form.editor_save_success_alert).toBeVisible();
    await page.reload();

    // Validate that the new values are displayed
    const form_details_after = form.getRegion('Form details');
    await expect(
        form_details_after.getByRole('textbox', { name: 'Form name' })
    ).toHaveValue('My form name');

    await expect(form.editor_active_checkbox).toBeChecked();

    const description_after = await form.getFormHeader();
    await expect(description_after).toHaveText('My form description');
});

test('Can enable child entities', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test child entities - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    const child_entities = page.getByRole('checkbox', { name: 'Child entities' });
    await expect(child_entities).not.toBeChecked();
    await child_entities.check();

    await form.doSaveFormEditorAndReload();

    await expect(
        page.getByRole('checkbox', { name: 'Child entities' })
    ).toBeChecked();
});

test('Can create and delete a question', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test create delete question - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Create a question
    const question = await form.addQuestion('My question');
    await question.getByRole('checkbox', { name: 'Mandatory' }).check();
    const description = await form.getQuestionDescription(question);
    await description.fill('My question description');

    // Save form and reload page
    await form.doSaveFormEditorAndReload();

    // Validate that the new values are displayed
    const question_after = form.getRegion('Question details');
    await question_after.getByRole('textbox', { name: 'Question name' }).click();
    await expect(
        question_after.getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('My question');
    await expect(
        question_after.getByRole('checkbox', { name: 'Mandatory' })
    ).toBeChecked();
    const description_after = await form.getQuestionDescription(question_after);
    await expect(description_after).toHaveText('My question description');

    // Delete question
    await question_after.click();
    await question_after.getByRole('button', { name: 'Delete' }).click();
    await expect(question_after).toBeHidden();

    // Save form and reload page
    await form.doSaveFormEditorAndReload();
    await expect(form.getRegion('Question details')).toBeHidden();
});

test('Can create an item question', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test item question - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Create a question
    const question = await form.addQuestion('My question');
    await question.getByRole('checkbox', { name: 'Mandatory' }).check();
    const description = await form.getQuestionDescription(question);
    await description.fill('My question description');

    // Change question type to "Item"
    const question_type_dropdown = form.getDropdownByLabel('Question type', question);
    await form.doSetDropdownValue(question_type_dropdown, 'Item', false);
    await expect(form.getDropdownByLabel('Select an itemtype')).toHaveText('Assets - Computers');

    // Save form and reload page
    await form.doSaveFormEditorAndReload();

    // Validate that the question type is "Item" after reload
    const question_after = form.getRegion('Question details');
    await question_after.getByRole('textbox', { name: 'Question name' }).click();
    const type_dropdown_after = form.getDropdownByLabel('Question type', question_after);
    await expect(type_dropdown_after).toContainText('Item');
});

test('Can move question', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test move question - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Create three questions
    await form.addQuestion('First question');
    await form.addQuestion('Second question');
    await form.addQuestion('Third question');

    // Drag the first question after the second question
    const questions = form.getRegion('Question details');
    const first_handle = questions.nth(0).getByLabel('Move question');
    const second_handle = questions.nth(1).getByLabel('Move question');
    await first_handle.dragTo(second_handle);

    // Save form and reload
    await form.doSaveFormEditorAndReload();

    // Verify order: Second question should now be first
    const reloaded_questions = form.getRegion('Question details');
    await expect(
        reloaded_questions.nth(0).getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('Second question');
    await expect(
        reloaded_questions.nth(1).getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('First question');
});

test('Can create and delete a section', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test create delete section - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // We must create at least one question before we can add a section
    await form.addQuestion('First question');

    // There is always one section when a form is created but it is hidden
    await expect(form.getRegion('Section details')).toBeHidden();

    // Create section
    const section = await form.addSection('Second section');
    const section_details = form.getRegion('Section details');
    await expect(section_details).toHaveCount(2);

    // Add description to the section
    const section_desc = await form.getSectionDescription(section);
    await section_desc.fill('Second section description');

    // Save and reload
    await form.doSaveFormEditorAndReload();

    // Validate values
    const sections_after = form.getRegion('Form section');
    await expect(sections_after).toHaveCount(2);
    const second_section = sections_after.nth(1);
    await expect(
        second_section.getByRole('textbox', { name: 'Section name' })
    ).toHaveValue('Second section');
    const desc_after = await form.getSectionDescription(second_section);
    await expect(desc_after).toHaveText('Second section description');

    // Delete section via "More actions" menu
    const second_section_details = form.getRegion('Section details').nth(1);
    await second_section_details.getByRole('button', { name: 'More actions' }).click();
    await page.getByRole('button', { name: 'Delete section' }).click();

    // Save and reload
    await form.doSaveFormEditorAndReload();
    await expect(form.getRegion('Section details')).toBeHidden();
});

test('Can delete a non-empty section with confirmation modal', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test delete non-empty section - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // We must create at least one question before we can add a section
    await form.addQuestion('First question');

    // Create section
    await form.addSection('Second section');

    // Add a question in the section
    await form.addQuestion('Second question');

    // Delete first section
    const first_section_details = form.getRegion('Section details').nth(0);
    await first_section_details.getByRole('button', { name: 'More actions' }).click();
    await first_section_details.getByRole('button', { name: 'Delete section' }).click();

    // The delete non-empty section modal should be displayed
    const modal = page.getByRole('dialog', { name: 'Delete non-empty section' });
    await expect(modal).toBeVisible();
    await expect(modal.getByTestId('delete-section-message')).toContainText('1');
    await expect(modal.getByTestId('delete-section-message')).toContainText('question');

    await modal.getByRole('button', { name: 'Delete section and all its elements' }).click();

    await expect(modal).toBeHidden();

    // Check that the section and its question have been deleted
    await expect(form.getRegion('Section details')).toBeHidden();
    await expect(
        form.getRegion('Question details').getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('Second question');

    // Save and reload
    await form.doSaveFormEditorAndReload();

    // Verify deletion persisted
    await expect(form.getRegion('Section details')).toBeHidden();
    await expect(
        form.getRegion('Question details').getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('Second question');
});

test('Can duplicate a section', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test duplicate section - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // We must create at least one question before we can add a section
    await form.addQuestion('First question');

    // Create section
    await form.addSection('Second section');
    await expect(form.getRegion('Section details')).toHaveCount(2);

    // Add two questions in the section
    await form.addQuestion('Second question');
    await form.addQuestion('Third question');

    // Change the last question type to "Item" to test select2 duplication
    const third_question = form.getRegion('Question details').nth(2);
    await third_question.click();
    const type_dropdown = form.getDropdownByLabel('Question type', third_question);
    await form.doSetDropdownValue(type_dropdown, 'Item', false);

    // Duplicate second section
    const second_section_details = form.getRegion('Section details').nth(1);
    await second_section_details.getByRole('button', { name: 'More actions' }).click();
    await page.getByRole('button', { name: 'Duplicate section' }).click();

    // Save and reload
    await form.doSaveFormEditorAndReload();

    // There should now be 3 sections
    await expect(form.getRegion('Section details')).toHaveCount(3);

    // Duplicate the third section (trying duplication of questions with lazy loaded select2)
    const third_section_details = form.getRegion('Section details').nth(2);
    await third_section_details.getByRole('button', { name: 'More actions' }).click();
    await page.getByRole('button', { name: 'Duplicate section' }).click();

    // There should now be 4 sections
    await expect(form.getRegion('Section details')).toHaveCount(4);

    // Check lazy loaded select2 in duplicated section
    const fourth_section = form.getRegion('Form section').nth(3);
    const item_question = fourth_section.getByRole('option', { name: 'Third question' });
    await expect(
        form.getDropdownByLabel('Select an item', item_question)
    ).toBeVisible();
    await item_question.getByRole('region', { name: 'Question details' }).click();
    await expect(
        form.getDropdownByLabel('Question type', item_question)
    ).toBeVisible();

    // Sections 2, 3 and 4 should be identical
    for (const section_index of [1, 2, 3]) {
        const section_container = form.getRegion('Form section').nth(section_index);

        // Validate section name
        await expect(
            section_container.getByRole('textbox', { name: 'Section name' })
        ).toHaveValue('Second section');

        // Validate questions
        const questions = section_container.getByRole('region', { name: 'Question details' });
        await expect(
            questions.nth(0).getByRole('textbox', { name: 'Question name' })
        ).toHaveValue('Second question');
        await expect(
            questions.nth(1).getByRole('textbox', { name: 'Question name' })
        ).toHaveValue('Third question');
    }
});

test('Can merge sections', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test merge sections - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // We must create at least one question before we can add a section
    await form.addQuestion('First question');

    // Create section
    await form.addSection('Second section');
    await expect(form.getRegion('Section details')).toHaveCount(2);

    // Add two questions in the new section
    await form.addQuestion('Second question');
    await form.addQuestion('Third question');

    // Merge the two sections
    const second_section_details = form.getRegion('Section details').nth(1);
    await second_section_details.getByRole('button', { name: 'More actions' }).click();
    await page.getByRole('button', { name: 'Merge with previous section' }).click();

    // Save and reload
    await form.doSaveFormEditorAndReload();

    // There should be only one hidden section
    const section = form.getRegion('Form section');
    await expect(section).toHaveCount(1);
    await expect(form.getRegion('Section details')).toBeHidden();

    // There should be 3 questions
    const questions = section.getByRole('region', { name: 'Question details' });
    await expect(questions).toHaveCount(3);
    await expect(
        questions.nth(0).getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('First question');
    await expect(
        questions.nth(1).getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('Second question');
    await expect(
        questions.nth(2).getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('Third question');
});

test('Can insert a section at the start of another section', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test insert section start - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // We must create at least one question before we can add a section
    await form.addQuestion('First question');

    // Create a second section
    await form.addSection('Second section');
    await expect(form.getRegion('Form section')).toHaveCount(2);

    // Add two questions in the new section
    await form.addQuestion('Second question');
    await form.addQuestion('Third question');

    // Move focus to the second section details
    await form.getRegion('Form section').nth(1)
        .getByRole('region', { name: 'Section details' }).click();

    // Create a third section
    await form.addSection('Third section');
    await expect(form.getRegion('Form section')).toHaveCount(3);

    // Save and reload before checking the values
    await form.doSaveFormEditorAndReload();

    const sections = form.getRegion('Form section');

    // The third section should "steal" the questions of the second section
    await expect(
        sections.nth(0).getByRole('region', { name: 'Question details' })
    ).toHaveCount(1);
    await expect(
        sections.nth(1).getByRole('region', { name: 'Question details' })
    ).toHaveCount(0);
    await expect(
        sections.nth(2).getByRole('region', { name: 'Question details' })
    ).toHaveCount(2);
});

test('Can insert a section in the middle of another section', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test insert section middle - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // We must create at least one question before we can add a section
    await form.addQuestion('First question');

    // Create a second section
    await form.addSection('Second section');
    await expect(form.getRegion('Form section')).toHaveCount(2);

    // Add two questions in the new section
    await form.addQuestion('Second question');
    await form.addQuestion('Third question');

    // Move focus to the second question
    await form.getRegion('Question details').nth(1).click();

    // Create a third section
    await form.addSection('Third section');
    await expect(form.getRegion('Form section')).toHaveCount(3);

    // Save and reload before checking the values
    await form.doSaveFormEditorAndReload();

    const sections = form.getRegion('Form section');

    // The third section should "steal" the third question of the second section
    await expect(
        sections.nth(0).getByRole('region', { name: 'Question details' })
    ).toHaveCount(1);
    await expect(
        sections.nth(1).getByRole('region', { name: 'Question details' })
    ).toHaveCount(1);
    await expect(
        sections.nth(2).getByRole('region', { name: 'Question details' })
    ).toHaveCount(1);
});

test('Can collapse sections', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test collapse sections - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // We must create at least one question before we can add a section
    await form.addQuestion('First question');

    // Create a second section
    await form.addSection('Second section');
    const sections = form.getRegion('Form section');
    await expect(sections).toHaveCount(2);

    // The first question should be visible
    await expect(form.getRegion('Question details')).toBeVisible();

    // Collapse the first section
    await sections.nth(0).getByRole('button', { name: 'Collapse section' }).click();
    await expect(form.getRegion('Question details')).toBeHidden();

    // Uncollapse
    await sections.nth(0).getByRole('button', { name: 'Collapse section' }).click();
    await expect(form.getRegion('Question details')).toBeVisible();
});

test('Can display correct element count in section badge', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test section badge - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Add first question
    await form.addQuestion('First question');

    // Add a section
    await form.addSection('Form section');

    const sections = form.getRegion('Form section');

    // Check badge is not visible when section is not collapsed
    const first_badge = sections.nth(0).getByTestId('section-block-badge');
    await expect(first_badge).toBeHidden();

    // Collapse the section
    await sections.nth(0).getByRole('button', { name: 'Collapse section' }).click();

    // Check that the badge shows "1 element"
    await expect(first_badge).toBeVisible();
    await expect(first_badge).toContainText('1 element');

    // Uncollapse the section
    await sections.nth(0).getByRole('button', { name: 'Collapse section' }).click();

    // Focus the first question to display hidden actions
    await sections.nth(0).getByRole('option', { name: 'New question' }).click();

    // Add a second question
    await form.addQuestion('Second question');

    // Add a comment
    await form.addComment('My comment');

    // Collapse the section again
    await sections.nth(0).getByRole('button', { name: 'Collapse section' }).click();

    // Check that the badge shows "3 elements" (2 questions + 1 comment)
    await expect(first_badge).toBeVisible();
    await expect(first_badge).toContainText('3 elements');

    // Uncollapse, delete one of the questions
    await sections.nth(0).getByRole('button', { name: 'Collapse section' }).click();
    const second_question = form.getRegion('Question details').nth(1);
    await second_question.click();
    await second_question.getByRole('button', { name: 'Delete' }).click();

    // Collapse the section again
    await sections.nth(0).getByRole('button', { name: 'Collapse section' }).click();

    // Check that the badge shows "2 elements" (1 question + 1 comment)
    await expect(first_badge).toBeVisible();
    await expect(first_badge).toContainText('2 elements');

    // Save and reload
    await form.doSaveFormEditorAndReload();

    // Collapse the section again
    const sections_after = form.getRegion('Form section');
    await sections_after.nth(0).getByRole('button', { name: 'Collapse section' }).click();

    // Check that the badge still shows "2 elements"
    const badge_after = sections_after.nth(0).getByTestId('section-block-badge');
    await expect(badge_after).toBeVisible();
    await expect(badge_after).toContainText('2 elements');
});

test('Can reorder sections', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test reorder sections - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // We must create at least one question before we can add a section
    await form.addQuestion('First question');

    // Create a second section
    await form.addSection('Second section');
    await expect(form.getRegion('Section details')).toHaveCount(2);

    // Add two questions to our section
    await form.addQuestion('Second question');
    await form.addQuestion('Third question');

    // Open "reorder sections" modal
    const first_section_details = form.getRegion('Section details').nth(0);
    await first_section_details.getByRole('button', { name: 'More actions' }).click();
    await page.getByRole('button', { name: 'Move section' }).click();

    const modal = page.getByRole('dialog', { name: 'Reorganize sections' });
    await expect(modal).toBeVisible();

    // Drag "First section" after "Second section"
    const first_item = modal.getByText('First section').first();
    const second_item = modal.getByText('Second section').first();
    await first_item.dragTo(second_item);

    // Click Save in the modal and wait for it to close
    await modal.getByRole('button', { name: 'Save' }).click();
    await expect(modal).toBeHidden();

    // Save and reload
    await form.doSaveFormEditorAndReload();

    const sections = form.getRegion('Form section');

    // The "Second section" is now displayed first
    await expect(
        sections.nth(0).getByRole('textbox', { name: 'Section name' })
    ).toHaveValue('Second section');
    await expect(
        sections.nth(0).getByRole('region', { name: 'Question details' })
    ).toHaveCount(2);

    // The "First section" is now displayed last
    await expect(
        sections.nth(1).getByRole('textbox', { name: 'Section name' })
    ).toHaveValue('First section');
    await expect(
        sections.nth(1).getByRole('region', { name: 'Question details' })
    ).toHaveCount(1);
});

test('Can duplicate a question and change its type', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test duplicate change type - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Create a question and set properties
    const question = await form.addQuestion('My question');
    await question.getByRole('checkbox', { name: 'Mandatory' }).check();
    const description = await form.getQuestionDescription(question);
    await description.fill('My question description');

    // Change the question type to "Long answer"
    const type_dropdown = form.getDropdownByLabel('Question type', question);
    await form.doSetDropdownValue(type_dropdown, 'Long answer', false);

    // Duplicate question
    await question.getByRole('button', { name: 'Duplicate question' }).click();

    // Validate both questions have correct values before save
    const questions = form.getRegion('Question details');
    await expect(questions).toHaveCount(2);

    for (let i = 0; i < 2; i++) {
        const q = questions.nth(i);
        await q.getByRole('textbox', { name: 'Question name' }).click();
        await expect(
            q.getByRole('textbox', { name: 'Question name' })
        ).toHaveValue('My question');
        await expect(
            q.getByRole('checkbox', { name: 'Mandatory' })
        ).toBeChecked();
        const desc = await form.getQuestionDescription(q);
        await expect(desc).toHaveText('My question description');
        await expect(
            form.getDropdownByLabel('Question type', q)
        ).toContainText('Long answer');
    }

    // Change the second question's type to "Date and time"
    const second_question = questions.nth(1);
    await second_question.getByRole('textbox', { name: 'Question name' }).click();
    const second_type_dd = form.getDropdownByLabel('Question type', second_question);
    await form.doSetDropdownValue(second_type_dd, 'Date and time', false);
    // The type change triggers two sequential async network requests
    // (one for category validation, one for type validation). Wait for the
    // loading overlay added by setQuestionTypeSpecificLoadingState to
    // disappear before saving, confirming both async operations have settled.
    await expect(second_question.getByTestId('question-loading')).not.toBeAttached();

    // Save and reload
    await form.doSaveFormEditorAndReload();

    // Validate values after reload
    const questions_after = form.getRegion('Question details');
    await expect(questions_after).toHaveCount(2);

    // First question should still be "Long answer"
    const q0 = questions_after.nth(0);
    await q0.getByRole('textbox', { name: 'Question name' }).click();
    await expect(
        q0.getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('My question');
    await expect(
        q0.getByRole('checkbox', { name: 'Mandatory' })
    ).toBeChecked();
    const desc0 = await form.getQuestionDescription(q0);
    await expect(desc0).toHaveText('My question description');
    await expect(
        form.getDropdownByLabel('Question type', q0)
    ).toContainText('Long answer');

    // Second question should be "Date and time"
    const q1 = questions_after.nth(1);
    await q1.getByRole('textbox', { name: 'Question name' }).click();
    await expect(
        q1.getByRole('textbox', { name: 'Question name' })
    ).toHaveValue('My question');
    await expect(
        q1.getByRole('checkbox', { name: 'Mandatory' })
    ).toBeChecked();
    const desc1 = await form.getQuestionDescription(q1);
    await expect(desc1).toHaveText('My question description');
    await expect(
        form.getDropdownByLabel('Question type', q1)
    ).toContainText('Date and time');
});

test('Can duplicate a section with questions and change their types', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test dup section change types - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Helper to verify section content
    async function verifySection(
        section_index: number,
        section_name: string,
        questions: { name: string, description: string, type: string }[]
    ): Promise<void> {
        const section = form.getRegion('Form section').nth(section_index);
        await expect(
            section.getByRole('textbox', { name: 'Section name' })
        ).toHaveValue(section_name);

        for (let i = 0; i < questions.length; i++) {
            const question = section.getByRole('region', { name: 'Question details' }).nth(i);
            await question.getByRole('textbox', { name: 'Question name' }).click();
            await expect(
                question.getByRole('textbox', { name: 'Question name' })
            ).toHaveValue(questions[i].name);
            await expect(
                question.getByRole('checkbox', { name: 'Mandatory' })
            ).toBeChecked();
            const desc = await form.getQuestionDescription(question);
            await expect(desc).toHaveText(questions[i].description);
            await expect(
                form.getDropdownByLabel('Question type', question)
            ).toContainText(questions[i].type);
        }
    }

    // Create first question with properties
    const q1 = await form.addQuestion('First question');
    await q1.getByRole('checkbox', { name: 'Mandatory' }).check();
    const desc1 = await form.getQuestionDescription(q1);
    await desc1.fill('First question description');
    const type_dd1 = form.getDropdownByLabel('Question type', q1);
    await form.doSetDropdownValue(type_dd1, 'Long answer', false);

    // Create second question with properties
    const q2 = await form.addQuestion('Second question');
    await q2.getByRole('checkbox', { name: 'Mandatory' }).check();
    const desc2 = await form.getQuestionDescription(q2);
    await desc2.fill('Second question description');
    const type_dd2 = form.getDropdownByLabel('Question type', q2);
    await form.doSetDropdownValue(type_dd2, 'Date and time', false);

    // Add a second section
    await form.addSection('');

    // Add third question in the new section
    const q3 = await form.addQuestion('Third question');
    await q3.getByRole('checkbox', { name: 'Mandatory' }).check();
    const desc3 = await form.getQuestionDescription(q3);
    await desc3.fill('Third question description');
    const type_dd3 = form.getDropdownByLabel('Question type', q3);
    await form.doSetDropdownValue(type_dd3, 'Actors', false);

    // Duplicate first section
    const first_section_details = form.getRegion('Section details').nth(0);
    await first_section_details.getByRole('button', { name: 'More actions' }).click();
    await page.getByRole('button', { name: 'Duplicate section' }).click();

    // Verify values before type changes
    await verifySection(0, 'First section', [
        { name: 'First question', description: 'First question description', type: 'Long answer' },
        { name: 'Second question', description: 'Second question description', type: 'Date and time' },
    ]);
    await verifySection(1, 'First section', [
        { name: 'First question', description: 'First question description', type: 'Long answer' },
        { name: 'Second question', description: 'Second question description', type: 'Date and time' },
    ]);
    await verifySection(2, '', [
        { name: 'Third question', description: 'Third question description', type: 'Actors' },
    ]);

    // Change question type of first question in first section
    const s0_q0 = form.getRegion('Form section').nth(0)
        .getByRole('region', { name: 'Question details' }).nth(0);
    await s0_q0.getByRole('textbox', { name: 'Question name' }).click();
    const s0_q0_dd = form.getDropdownByLabel('Question type', s0_q0);
    await form.doSetDropdownValue(s0_q0_dd, 'Date and time', false);

    // Change question type of first question in second section
    const s1_q0 = form.getRegion('Form section').nth(1)
        .getByRole('region', { name: 'Question details' }).nth(0);
    await s1_q0.getByRole('textbox', { name: 'Question name' }).click();
    const s1_q0_dd = form.getDropdownByLabel('Question type', s1_q0);
    await form.doSetDropdownValue(s1_q0_dd, 'Actors', false);

    // Change question type of first question in third section
    const s2_q0 = form.getRegion('Form section').nth(2)
        .getByRole('region', { name: 'Question details' }).nth(0);
    await s2_q0.getByRole('textbox', { name: 'Question name' }).click();
    const s2_q0_dd = form.getDropdownByLabel('Question type', s2_q0);
    await form.doSetDropdownValue(s2_q0_dd, 'Long answer', false);

    // Wait for all async question type changes to complete before saving
    await expect(page.getByTestId('question-loading')).toHaveCount(0);

    // Save and reload
    await form.doSaveFormEditorAndReload();

    // Validate values after save
    await verifySection(0, 'First section', [
        { name: 'First question', description: 'First question description', type: 'Date and time' },
        { name: 'Second question', description: 'Second question description', type: 'Date and time' },
    ]);
    await verifySection(1, 'First section', [
        { name: 'First question', description: 'First question description', type: 'Actors' },
        { name: 'Second question', description: 'Second question description', type: 'Date and time' },
    ]);
    await verifySection(2, '', [
        { name: 'Third question', description: 'Third question description', type: 'Long answer' },
    ]);
});

test('Can change render layout', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create form with a question in the default section
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test render layout - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });

    // Get default section and add a question
    const sections = await api.getSubItems(
        'Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section'
    );
    const default_section_id = sections[0].id;

    await api.createItem('Glpi\\Form\\Question', {
        name: 'First question',
        type: 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
        vertical_rank: 0,
        forms_sections_id: default_section_id,
    });

    // Add a second section with a question
    const second_section_id = await api.createItem('Glpi\\Form\\Section', {
        name: 'Second section',
        rank: 1,
        forms_forms_id: form_id,
    });
    await api.createItem('Glpi\\Form\\Question', {
        name: 'Second question',
        type: 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
        vertical_rank: 0,
        forms_sections_id: second_section_id,
    });

    // Visit form editor
    await form.goto(form_id);

    // Validate default render layout
    const layout_dropdown = form.getDropdownByLabel('Render layout');
    await expect(layout_dropdown).toContainText('Section by section');

    // Go to preview — use direct navigation instead of clicking the link
    await page.goto(`/Form/Render/${form_id}`);

    // Validate section-by-section layout
    await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Second section' })).toBeHidden();
    await expect(page.getByRole('button', { name: 'Continue' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeHidden();

    // Go back to form editor
    await form.goto(form_id);

    // Change render layout to "Single page"
    const layout_dd = form.getDropdownByLabel('Render layout');
    await form.doSetDropdownValue(layout_dd, 'Single page', false);

    // Save form
    await form.doSaveFormEditor();

    // Go to preview
    await page.goto(`/Form/Render/${form_id}`);

    // Validate single-page layout
    await expect(page.getByRole('heading', { name: 'First section' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Second section' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Continue' })).toBeHidden();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
});

test('Can delete a question that has a validation constraint', async ({ page, profile, formImporter }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const info = await formImporter.importForm('form-with-validation-2025-10-08.json');
    await form.goto(info.getId());

    // Focus question to display hidden actions
    const question = form.getRegion('Question details');
    await question.click();

    // Delete question
    await question.getByRole('button', { name: 'Delete' }).click();
    await expect(question).toBeHidden();
});

test('Can save a form with many inputs', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create form and get its default section
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test many inputs - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    const sections = await api.getSubItems(
        'Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section'
    );
    const section_id = sections[0].id;

    // Create 60 questions with ItemDropdown type via API
    for (let i = 0; i < 60; i++) {
        await api.createItem('Glpi\\Form\\Question', {
            name: `Question ${i}`,
            type: 'Glpi\\Form\\QuestionType\\QuestionTypeItemDropdown',
            vertical_rank: i,
            forms_sections_id: section_id,
            extra_data: JSON.stringify({
                itemtype: 'ITILCategory',
                categories_filter: [],
                root_items_id: 0,
                subtree_depth: 0,
                selectable_tree_root: true,
            }),
        });
    }

    // Visit the form editor
    await form.goto(form_id);

    // Wait for all questions to load — the Save button should be visible
    await expect(form.editor_save_button).toBeVisible({ timeout: 30000 });

    // Verify there are many form inputs (more than 1000)
    // eslint-disable-next-line playwright/no-raw-locators
    const input_count = await page.locator('form input, form select, form textarea').count();
    expect(input_count).toBeGreaterThan(1000);

    // Save the form
    await form.doSaveFormEditor();
});

test('Can change type to item', async ({ page, profile, formImporter }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const info = await formImporter.importForm('form-with-text-question.json');
    await form.goto(info.getId());

    // Select the question
    const question = form.getRegion('Question details');
    await question.click();

    // Change the question type
    const type_dropdown = form.getDropdownByLabel('Question type', question);
    await form.doSetDropdownValue(type_dropdown, 'Item', false);

    // Verify the item type dropdown appears
    const itemtype_dropdown = form.getDropdownByLabel('Select an itemtype');
    await expect(itemtype_dropdown).toBeVisible();

    // Save the form
    await form.doSaveFormEditor();
});

test('Can duplicate a question', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test duplicate question - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Create a question and set its properties
    const question = await form.addQuestion('My question');
    await question.getByRole('checkbox', { name: 'Mandatory' }).check();
    const description = await form.getQuestionDescription(question);
    await description.fill('My question description');

    // Duplicate question
    await question.getByRole('button', { name: 'Duplicate question' }).click();

    // Save form and reload page
    await form.doSaveFormEditorAndReload();

    // Both questions should be identical
    const questions = form.getRegion('Question details');
    await expect(questions).toHaveCount(2);

    for (let i = 0; i < 2; i++) {
        const q = questions.nth(i);
        await q.click();

        await expect(
            q.getByRole('textbox', { name: 'Question name' })
        ).toHaveValue('My question');
        await expect(
            q.getByRole('checkbox', { name: 'Mandatory' })
        ).toBeChecked();
        const desc = await form.getQuestionDescription(q);
        await expect(desc).toHaveText('My question description');
    }
});

test('Can paste image in form description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    await assertPasteImageInRichText(
        page,
        form,
        () => form.getFormHeader(),
        '_uploader_header'
    );
});

test('Can paste image in section description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Glpi\\Form\\Section', {
        name: 'Second section',
        forms_forms_id: form_id,
    });

    await form.goto(form_id);

    // Retrieve section
    const section = form.getLastSection();

    await assertPasteImageInRichText(
        page,
        form,
        () => form.getSectionDescription(section),
        '_sections[1][_uploader_description][]'
    );
});

test('Can paste image in new section description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Add a section to the form
    await form.addQuestion('First question');
    const section = await form.addSection('Second section');

    await assertPasteImageInRichText(
        page,
        form,
        () => form.getSectionDescription(section),
        '_sections[1][_uploader_description][]'
    );
});

test('Can paste image in question description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    const sections = await api.getSubItems('Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section');
    const section_id = sections[0].id;
    await api.createItem('Glpi\\Form\\Question', {
        name: 'First question',
        forms_sections_id: section_id,
        type: 'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
    });
    await form.goto(form_id);

    // Retrieve question
    const question = form.getLastQuestion();

    await assertPasteImageInRichText(
        page,
        form,
        () => form.getQuestionDescription(question),
        '_questions[0][_uploader_description][]'
    );
});

test('Can paste image in new question description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Add a question to the form
    const question = await form.addQuestion('First question');

    await assertPasteImageInRichText(
        page,
        form,
        () => form.getQuestionDescription(question),
        '_questions[0][_uploader_description][]'
    );
});

test('Can duplicate a question with an image in description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Add a question to the form
    const question = await form.addQuestion('First question');

    // Paste image in question description
    await pasteImageInRichText(
        page,
        () => form.getQuestionDescription(question),
        '_questions[0][_uploader_description][]'
    );

    // Duplicate the question
    await form.getButton('Duplicate question').click();

    // Retrieve duplicated question
    const duplicatedQuestion = form.getRegion('Question details').nth(1);

    // Save and reload to verify persistence
    await form.doSaveFormEditor();
    await page.reload();

    // Verify the pasted image is displayed in duplicated question
    await assertPastedImageIsCorrectlyInserted(
        () => form.getQuestionDescription(duplicatedQuestion)
    );
});

test('Can paste image in comment description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    const sections = await api.getSubItems('Glpi\\Form\\Form', form_id, 'Glpi\\Form\\Section');
    const section_id = sections[0].id;
    await api.createItem('Glpi\\Form\\Comment', {
        name: 'First comment',
        forms_sections_id: section_id,
    });
    await form.goto(form_id);

    // Retrieve comment
    const comment = form.getLastComment();

    await assertPasteImageInRichText(
        page,
        form,
        () => form.getCommentDescription(comment),
        '_comments[0][_uploader_description][]'
    );
});

test('Can paste image in new comment description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Add a comment to the form
    const comment = await form.addComment('First comment');

    await assertPasteImageInRichText(
        page,
        form,
        () => form.getCommentDescription(comment),
        '_comments[0][_uploader_description][]'
    );
});

test('Can duplicate a comment with an image in description', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    // Add a comment to the form
    const comment = await form.addComment('First comment');

    // Paste image in comment description
    await pasteImageInRichText(
        page,
        () => form.getCommentDescription(comment),
        '_comments[0][_uploader_description][]'
    );

    // Duplicate the comment
    await form.getButton('Duplicate comment').click();

    // Retrieve duplicated comment
    const duplicatedComment = form.getRegion('Comment details').nth(1);

    // Save and reload to verify persistence
    await form.doSaveFormEditor();
    await page.reload();

    // Verify the pasted image is displayed in duplicated comment
    await assertPastedImageIsCorrectlyInserted(
        () => form.getCommentDescription(duplicatedComment)
    );
});

async function assertPasteImageInRichText(page: Page, form: FormPage, getRichText: () => Promise<Locator>, expectedProperty: string): Promise<void> {
    // Paste image in the rich text editor
    await pasteImageInRichText(page, getRichText, expectedProperty);

    // Save and reload to verify persistence
    await form.doSaveFormEditor();
    await page.reload();

    // Verify the pasted image is displayed
    await assertPastedImageIsCorrectlyInserted(getRichText);
}
