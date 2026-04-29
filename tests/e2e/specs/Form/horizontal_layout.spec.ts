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

test.describe('Form editor horizontal layout', () => {
    let form: FormPage;
    let form_id: number;

    test.beforeEach(async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        form = new FormPage(page);

        form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Test horizontal layout - ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
        });
        await form.goto(form_id);
    });

    test('can add a new question and verify it is not within a horizontal block', async () => {
        await form.getButton('Add a question').click();

        await expect(form.getRegion('Horizontal blocks layout')).toHaveCount(0);
    });

    test('can create and verify a horizontal block with questions and a comment', async () => {
        await form.getButton('Add a horizontal layout').click();

        const horizontal = form.getRegion('Horizontal blocks layout');

        // Placeholder toolbars must be hidden when the placeholder is not active
        await expect(horizontal.getByRole('button', { name: 'Add a question' })).toHaveCount(0);

        // Click the first placeholder and add a question
        const placeholders = horizontal.getByRole('option', { name: 'Form horizontal block placeholder' });
        await placeholders.nth(0).click();
        await placeholders.nth(0).getByRole('button', { name: 'Add a question' }).click();

        const question = horizontal.getByRole('region', { name: 'Question details' });
        await question.getByRole('textbox', { name: 'Question name' }).fill('First question');
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Question type', question),
            'Short answer',
            false
        );
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Question sub type', question),
            'Text',
            false
        );

        // Click the remaining placeholder and add a comment
        const remaining_placeholder = horizontal.getByRole('option', { name: 'Form horizontal block placeholder' });
        await remaining_placeholder.click();
        await remaining_placeholder.getByRole('button', { name: 'Add a comment' }).click();

        const comment = horizontal.getByRole('region', { name: 'Comment details' });
        await comment.getByRole('textbox', { name: 'Comment title' }).fill('Comment title');
        const comment_desc = await form.initRichTextByLabel('Comment description', comment);
        await comment_desc.fill('Comment description');

        // Save and reload
        await form.doSaveFormEditorAndReload();

        // Verify horizontal block content after reload
        const reloaded_horizontal = form.getRegion('Horizontal blocks layout');
        const reloaded_question = reloaded_horizontal.getByRole('region', { name: 'Question details' });
        await expect(reloaded_question.getByRole('textbox', { name: 'Question name' })).toHaveValue('First question');

        const reloaded_comment = reloaded_horizontal.getByRole('region', { name: 'Comment details' });
        await expect(reloaded_comment.getByRole('textbox', { name: 'Comment title' })).toHaveValue('Comment title');
        await expect(reloaded_comment.getByLabel('Comment description')).toHaveText('<p>Comment description</p>');
    });

    test('should not allow adding more than 4 placeholders in a horizontal block', async () => {
        await form.getButton('Add a horizontal layout').click();

        const horizontal = form.getRegion('Horizontal blocks layout');
        const placeholders = horizontal.getByRole('option', { name: 'Form horizontal block placeholder' });

        await expect(placeholders).toHaveCount(2);

        for (let i = 3; i < 5; i++) {
            await horizontal.getByRole('button', { name: 'Add a slot' }).click();
            await expect(placeholders).toHaveCount(i);
        }

        await expect(horizontal.getByRole('button', { name: 'Add a slot' })).toHaveCount(0);
    });

    test('should not allow adding more than 4 questions in a horizontal block using placeholder toolbar', async () => {
        await form.getButton('Add a horizontal layout').click();

        const horizontal = form.getRegion('Horizontal blocks layout');

        // Add 2 more slots (total 4)
        await horizontal.getByRole('button', { name: 'Add a slot' }).click();
        await horizontal.getByRole('button', { name: 'Add a slot' }).click();

        for (let i = 0; i < 4; i++) {
            const placeholders = horizontal.getByRole('option', { name: 'Form horizontal block placeholder' });
            await placeholders.nth(0).click();
            await placeholders.nth(0).getByRole('button', { name: 'Add a question' }).click();
        }

        await expect(horizontal.getByRole('region', { name: 'Question details' })).toHaveCount(4);
    });

    test('should not allow adding more than 4 questions in a horizontal block using question duplicate', async ({ page }) => {
        // Assertions below verify button counts after duplications
        await form.getButton('Add a horizontal layout').click();

        const horizontal = form.getRegion('Horizontal blocks layout');

        // Click the first placeholder and add a question
        const placeholders = horizontal.getByRole('option', { name: 'Form horizontal block placeholder' });
        await placeholders.nth(0).click();
        await placeholders.nth(0).getByRole('button', { name: 'Add a question' }).click();

        // Duplicate the question 3 times
        for (let i = 0; i < 3; i++) {
            await page.getByRole('button', { name: 'Duplicate question' }).last().click();
        }

        // Check that the duplicate button disappears
        await expect(page.getByRole('button', { name: 'Duplicate question' })).toHaveCount(0);

        // Check that the new slot button disappears
        await expect(page.getByRole('button', { name: 'Add a slot' })).toHaveCount(0);
    });

    test('can save a form with a horizontal block with only one question', async () => {
        await form.getButton('Add a horizontal layout').click();

        const horizontal = form.getRegion('Horizontal blocks layout');

        // Click the first placeholder and add a question
        const placeholders = horizontal.getByRole('option', { name: 'Form horizontal block placeholder' });
        await placeholders.nth(0).click();
        await placeholders.nth(0).getByRole('button', { name: 'Add a question' }).click();

        const question = horizontal.getByRole('region', { name: 'Question details' });
        await question.getByRole('textbox', { name: 'Question name' }).fill('First question');
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Question type', question),
            'Short answer',
            false
        );
        await form.doSetDropdownValue(
            form.getDropdownByLabel('Question sub type', question),
            'Text',
            false
        );

        // Save and reload
        await form.doSaveFormEditorAndReload();

        // Verify the question persists
        const reloaded_horizontal = form.getRegion('Horizontal blocks layout');
        const reloaded_question = reloaded_horizontal.getByRole('region', { name: 'Question details' });
        await expect(reloaded_question.getByRole('textbox', { name: 'Question name' })).toHaveValue('First question');
    });

    test('can remove a horizontal block and verify the questions are still displayed', async () => {
        await form.getButton('Add a horizontal layout').click();

        const horizontal = form.getRegion('Horizontal blocks layout');

        // Click the first placeholder and add a question
        let placeholders = horizontal.getByRole('option', { name: 'Form horizontal block placeholder' });
        await placeholders.nth(0).click();
        await placeholders.nth(0).getByRole('button', { name: 'Add a question' }).click();

        const first_question = horizontal.getByRole('region', { name: 'Question details' });
        await first_question.getByRole('textbox', { name: 'Question name' }).fill('First question');

        // Click remaining placeholder and add a second question
        placeholders = horizontal.getByRole('option', { name: 'Form horizontal block placeholder' });
        await placeholders.nth(0).click();
        await placeholders.nth(0).getByRole('button', { name: 'Add a question' }).click();

        await horizontal.getByRole('region', { name: 'Question details' }).nth(1)
            .getByRole('textbox', { name: 'Question name' }).fill('Second question');

        // Save and reload
        await form.doSaveFormEditorAndReload();

        // Remove the horizontal block
        await form.getButton('Remove horizontal layout').click();

        // Save and reload
        await form.doSaveFormEditorAndReload();

        // Verify no horizontal block exists
        await expect(form.getRegion('Horizontal blocks layout')).toHaveCount(0);

        // Verify questions are still displayed
        const questions = form.getRegion('Question details');
        await expect(questions.nth(0).getByRole('textbox', { name: 'Question name' })).toHaveValue('First question');
        await expect(questions.nth(1).getByRole('textbox', { name: 'Question name' })).toHaveValue('Second question');
    });
});
