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
import { test } from '../../fixtures/glpi_fixture';
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormPage } from "../../pages/FormPage";
import { pasteImageInRichText, assertPastedImageIsCorrectlyInserted } from "../../utils/ImagePasteHelpers";

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
