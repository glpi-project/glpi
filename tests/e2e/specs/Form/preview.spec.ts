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
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormPage } from "../../pages/FormPage";

async function expectPreviewButton(form: FormPage): Promise<void> {
    const page = form.page;

    // Check the preview button
    await expect(page.getByRole('button', { name: 'Preview', exact: true })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Save and preview' })).toBeVisible();

    // Save the form and wait for the server response.
    // The "Item successfully updated" alert may not appear when only sub-items
    // (questions, sections, comments) changed and two saves happen within the
    // same second: the date_mod value stays identical, so the framework skips
    // the main record UPDATE and the success message.
    const response_promise = page.waitForResponse(
        resp => resp.request().method() === 'POST'
    );
    // Use dispatchEvent to bypass any toast alert that may overlap the button.
    // This is the only way to avoid flakyness or slow test here as we can't
    // predict when and how many toast alerts will appear.
    await form.editor_save_button.dispatchEvent('click');
    const response = await response_promise;
    expect(response.ok()).toBeTruthy();

    await expect(page.getByRole('link', { name: 'Save and preview' })).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Preview' })).toBeVisible();
}

let form: FormPage;
let form_id: number;

test.beforeEach(async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    form = new FormPage(page);

    form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test form unsaved changes - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });

    await form.goto(form_id);

    // Wait for the form editor JS to be fully initialized
    await page.getByTestId('form-editor-container').waitFor({ state: 'attached' });
    await expect(page.getByTestId('form-editor-container')).toHaveClass(/initialized/);
});

test('Unsaved changes handling in form header', async () => {
    const page = form.page;

    await page.getByRole('textbox', { name: 'Form name' }).pressSequentially('Test form');
    await page.getByRole('textbox', { name: 'Form name' }).blur();
    await expectPreviewButton(form);

    const form_description = await form.getFormHeader();
    await form_description.pressSequentially('My form description');

    // Click on another field to save the TinyMCE content
    await page.getByRole('textbox', { name: 'Form name' }).click();

    await expectPreviewButton(form);
});

test('Unsaved changes handling in sections', async () => {
    const page = form.page;

    // Add a question
    await form.getButton('Add a question').click();
    await expectPreviewButton(form);

    // Focus question
    await page.getByRole('textbox', { name: 'Question name' }).click();

    // Add a section
    await form.getButton('Add a section').click();
    await expectPreviewButton(form);

    const first_section = page.getByRole('region', { name: 'Form section' }).first();

    // Set the section name
    await first_section.getByRole('textbox', { name: 'Section name' }).clear();
    await first_section.getByRole('textbox', { name: 'Section name' }).fill('Test section');
    await first_section.getByRole('textbox', { name: 'Section name' }).blur();
    await expectPreviewButton(form);

    // Set the section description
    const section_description = await form.getSectionDescription(first_section);
    await section_description.pressSequentially('My section description');

    // Click on another field to save the TinyMCE content
    await first_section.getByRole('textbox', { name: 'Section name' }).click();
    await expectPreviewButton(form);

    // Merge second section with the first
    const second_section = page.getByRole('region', { name: 'Form section' }).nth(1);
    await second_section.getByRole('button', { name: 'More actions' }).click();
    await page.getByRole('button', { name: 'Merge with previous section' }).click();
    await expectPreviewButton(form);
});

test('Unsaved changes handling in questions', async () => {
    const page = form.page;

    // Add a question
    const question = await form.addQuestion('');
    await expectPreviewButton(form);
    await page.getByRole('textbox', { name: 'Question name' }).click();

    // Edit the question name
    await page.getByRole('textbox', { name: 'Question name' }).fill('Test question');
    await page.getByRole('textbox', { name: 'Question name' }).blur();
    await expectPreviewButton(form);
    await page.getByRole('textbox', { name: 'Question name' }).click();

    // Type the question description
    const question_description = await form.getQuestionDescription(question);
    await question_description.pressSequentially('My question description');

    // Click on another field to save the TinyMCE content
    await page.getByRole('textbox', { name: 'Question name' }).click();
    await expectPreviewButton(form);
    await page.getByRole('textbox', { name: 'Question name' }).click();

    // Check the mandatory checkbox
    await question.getByRole('checkbox', { name: 'Mandatory' }).check();
    await expectPreviewButton(form);
    await page.getByRole('textbox', { name: 'Question name' }).click();

    // Change the question type to Short answer > Emails
    await form.doChangeQuestionType(question, 'Short answer');
    const sub_type = form.getDropdownByLabel('Question sub type', question);
    await form.doSetDropdownValue(sub_type, 'Emails', false);
    await question.getByTestId('question-loading').waitFor({ state: 'detached' });
    await expectPreviewButton(form);
    await page.getByRole('textbox', { name: 'Question name' }).click();

    // Change the category question type to Long answer
    await form.doChangeQuestionType(question, 'Long answer');
    await expectPreviewButton(form);
});

test('Unsaved changes handling in comments', async () => {
    const page = form.page;

    // Add a comment
    const comment = await form.addComment('');

    // Edit the comment name
    await page.getByRole('textbox', { name: 'Comment title' }).fill('Test comment');
    await page.getByRole('textbox', { name: 'Comment title' }).blur();
    await expectPreviewButton(form);
    await page.getByRole('textbox', { name: 'Comment title' }).click();

    // Type the comment description
    const comment_description = await form.getCommentDescription(comment);
    await comment_description.pressSequentially('My comment description');

    // Click on another field to save the TinyMCE content
    await page.getByRole('textbox', { name: 'Comment title' }).click();
    await expectPreviewButton(form);
});
