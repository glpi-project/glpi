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

import { Locator } from "@playwright/test";
import { randomUUID } from "crypto";
import { expect, test } from '../../../fixtures/glpi_fixture';
import { Api } from "../../../utils/Api";
import { Profiles } from "../../../utils/Profiles";
import { getWorkerEntityId } from '../../../utils/WorkerEntities';
import { FormPage } from "../../../pages/FormPage";

test('Can reorder options of a dropdown question and keep the order after saving', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    // Create a form and navigate to its editor
    const uuid = randomUUID();
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Form - ${uuid}`,
        entities_id: getWorkerEntityId(),
    });
    await form.goto(form_id);

    const question = await form.addQuestion('Dropdown question');
    await form.setQuestionType(question, 'Dropdown');

    // Add three options to the dropdown question
    for (let i = 1; i <= 3; i++) {
        await question.getByRole('textbox', { name: 'Selectable option' }).last().fill(`Option ${i}`);
    }

    await question.getByRole('button', { name: 'Move option', exact: true }).last().click();

    // Move last option to the first position
    await question.getByRole('button', { name: 'Move option', exact: true }).last()
        .dragTo(question.getByRole('button', { name: 'Move option', exact: true }).first());

    // Save and reload the form
    await form.doSaveFormEditor();
    await form.goto(form_id);

    // Focus on the question to load its options
    await form.getLastQuestion().click({ position: { x: 0, y: 0 } });

    // Assert that the order of the options is correct after reloading
    const options = question.getByRole('textbox', { name: 'Selectable option' });
    await expect(options.nth(0)).toHaveValue('Option 3');
    await expect(options.nth(1)).toHaveValue('Option 1');
    await expect(options.nth(2)).toHaveValue('Option 2');
    await expect(options.nth(3)).toHaveValue('');
});

for (const questionType of ['Radio', 'Checkbox', 'Dropdown']) {
    test(`Can paste multi-line text to create multiple options for ${questionType} question`, async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        // Create a form and navigate to its editor
        const uuid = randomUUID();
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Form - ${uuid}`,
            entities_id: getWorkerEntityId(),
        });
        await form.goto(form_id);

        const question = await form.addQuestion(`${questionType} question`);
        await form.setQuestionType(question, questionType);

        // Simulate pasting multi-line text into the first option
        const firstOption = question.getByRole('textbox', { name: 'Selectable option' }).last();
        await firstOption.click();

        const pastedText = "Option A\nOption B\nOption C";
        await firstOption.evaluate((input, text) => {
            const clipboardData = new DataTransfer();
            clipboardData.setData('text/plain', text);
            const pasteEvent = new ClipboardEvent('paste', {
                clipboardData,
                bubbles: true,
                cancelable: true,
            });
            input.dispatchEvent(pasteEvent);
        }, pastedText);

        // Assert that three options were created from the pasted text
        const options = question.getByRole('textbox', { name: 'Selectable option' });
        await expect(options.nth(0)).toHaveValue('Option A');
        await expect(options.nth(1)).toHaveValue('Option B');
        await expect(options.nth(2)).toHaveValue('Option C');

        // Save and reload the form
        await form.doSaveFormEditor();
        await form.goto(form_id);

        // Focus on the question to load its options
        await form.getLastQuestion().click({ position: { x: 0, y: 0 } });

        // Assert that all options are preserved after reload
        const savedOptions = question.getByRole('textbox', { name: 'Selectable option' });
        await expect(savedOptions.nth(0)).toHaveValue('Option A');
        await expect(savedOptions.nth(1)).toHaveValue('Option B');
        await expect(savedOptions.nth(2)).toHaveValue('Option C');
    });
}

for (const questionType of ['Radio', 'Checkbox', 'Dropdown']) {
    test(`Can copy an option's uuid to the clipboard for ${questionType} question`, async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
        const form = new FormPage(page);

        // Create a form and navigate to its editor
        const uuid = randomUUID();
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Form - ${uuid}`,
            entities_id: getWorkerEntityId(),
        });
        await form.goto(form_id);

        const question = await form.addQuestion(`${questionType} question`);
        await form.setQuestionType(question, questionType);

        // Add an option to the question
        const optionInput = question.getByRole('textbox', { name: 'Selectable option' }).first();
        await question.getByRole('textbox', { name: 'Selectable option' }).last().fill('Option 1');

        // The option's uuid is the last bracket key of its text input name
        // (e.g. _questions[0][extra_data][options][<uuid>]), which is the value
        // the button copies. This holds for every selectable type, unlike the
        // default-value control (a hidden radio / a select2 for dropdowns).
        const optionName = await optionInput.getAttribute('name');
        const expectedUuid = optionName?.match(/\[([^\]]+)\]$/)?.[1];

        const copyButton = question.getByRole('button', { name: 'Copy UUID' }).first();

        // The button stays hidden until its option row is hovered
        await page.mouse.move(0, 0);
        await expect(copyButton).toHaveCSS('opacity', '0');
        await optionInput.hover();
        await expect(copyButton).toHaveCSS('opacity', '1');

        // Click the option's "Copy UUID" button
        await copyButton.click();

        // Assert the generic clipboard handler confirmed the copy
        await expect(
            page.getByRole('alert').filter({ hasText: 'Copied to clipboard' })
        ).toBeVisible();

        // Assert the clipboard holds the option's (non-empty) uuid
        const clipboardContent = await page.evaluate(() => navigator.clipboard.readText());
        expect(clipboardContent).not.toBe('');
        expect(clipboardContent).toBe(expectedUuid);
    });
}

test.describe('Selectable form question types', () => {
    const setupForm = async (form: FormPage, api: Api): Promise<number> => {
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            'name': `Tests form for the selectable form question types suite - ${randomUUID()}`,
            'entities_id': getWorkerEntityId(),
        });
        await form.goto(form_id);

        return form_id;
    };

    const getSelectableOptions = (question: Locator): Locator => {
        return question.getByRole('textbox', {
            name: 'Selectable option',
            exact: true,
        });
    };

    const getDefaultOptionRadios = (question: Locator): Locator => {
        return question.getByRole('radio', {
            name: 'Default option',
            exact: true,
        });
    };

    const getDefaultOptionCheckboxes = (question: Locator): Locator => {
        return question.getByRole('checkbox', {
            name: 'Default option',
            exact: true,
        });
    };

    test('should configure a radio question type', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Configure question
        let question = await form.addQuestion("Test selectable question");
        await form.setQuestionType(question, 'Radio');

        // Add options
        for (let index = 0; index < 3; index++) {
            await expect(getDefaultOptionRadios(question).nth(index))
                .toBeDisabled()
            ;
            await getSelectableOptions(question).nth(index)
                .fill(`Option ${index}`)
            ;
        }

        // Save the form and reload the page
        await form.doSaveFormEditorAndReload();
        question = form.getNthQuestion(0);

        // Check if options are still there
        for (let index = 0; index < 3; index++) {
            await expect(getDefaultOptionRadios(question).nth(index))
                .not.toBeChecked()
            ;
            await expect(getSelectableOptions(question).nth(index))
                .toHaveValue(`Option ${index}`)
            ;
        }

        // Check the second option
        await getDefaultOptionRadios(question).nth(1).check();

        // Save the form and reload the page
        await form.doSaveFormEditorAndReload();
        question = form.getNthQuestion(0);

        // Check if the second option is still checked
        await expect(getDefaultOptionRadios(question).nth(1)).toBeChecked();

        // Fill form
        await form.doPreviewForm();

        // Check if the question is displayed
        await expect(page.getByText('Test selectable question').first())
            .toBeVisible()
        ;

        // Check if the options are displayed
        for (let index = 0; index < 3; index++) {
            await expect(form.getRadio(`Option ${index}`)).toBeVisible();
        }

        // Check if the second option is checked
        await expect(form.getRadio('Option 1')).toBeChecked();

        // Check the first option
        await form.getRadio('Option 0').check();

        // Check if the second option is not checked anymore
        await expect(form.getRadio('Option 1')).not.toBeChecked();

        // Submit the form
        await form.getButton('Submit').click();

        // Check if the success message is displayed
        const alert = form.getAlert('Item successfully created');
        await expect(alert).toBeVisible();
        await alert.getByRole('link').click();

        // Check if the option is saved
        await expect(page.getByText(': Option 0').first()).toBeVisible();
    });

    test('should configure a checkbox question type', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Configure question
        let question = await form.addQuestion("Test selectable question");
        await form.setQuestionType(question, 'Checkbox');

        // Add options
        for (let index = 0; index < 3; index++) {
            await expect(getDefaultOptionCheckboxes(question).nth(index))
                .toBeDisabled()
            ;
            await getSelectableOptions(question).nth(index)
                .fill(`Option ${index}`)
            ;
        }

        // Save the form and reload the page
        await form.doSaveFormEditorAndReload();
        question = form.getNthQuestion(0);

        // Check if options are still there
        for (let index = 0; index < 3; index++) {
            await expect(getDefaultOptionCheckboxes(question).nth(index))
                .not.toBeChecked()
            ;
            await expect(getSelectableOptions(question).nth(index))
                .toHaveValue(`Option ${index}`)
            ;
        }

        // Check the second and third options
        await getDefaultOptionCheckboxes(question).nth(1).check();
        await getDefaultOptionCheckboxes(question).nth(2).check();

        // Save the form and reload the page
        await form.doSaveFormEditorAndReload();
        question = form.getNthQuestion(0);

        // Check if the second option is still checked
        await expect(getDefaultOptionCheckboxes(question).nth(1)).toBeChecked();

        // Fill form
        await form.doPreviewForm();

        // Check if the question is displayed
        await expect(page.getByText('Test selectable question').first())
            .toBeVisible()
        ;

        // Check if the options are displayed
        for (let index = 0; index < 3; index++) {
            await expect(form.getCheckbox(`Option ${index}`)).toBeVisible();
        }

        // Check if the second and third options are checked
        await expect(form.getCheckbox('Option 1')).toBeChecked();
        await expect(form.getCheckbox('Option 2')).toBeChecked();

        // Check the first option
        await form.getCheckbox('Option 0').check();

        // Uncheck the third option
        await form.getCheckbox('Option 2').uncheck();

        // Check if the second option is still checked
        await expect(form.getCheckbox('Option 1')).toBeChecked();

        // Submit the form
        await form.getButton('Submit').click();

        // Check if the success message is displayed
        const alert = form.getAlert('Item successfully created');
        await expect(alert).toBeVisible();
        await alert.getByRole('link').click();

        // Check if the option is saved
        await expect(page.getByText(': Option 0, Option 1').first())
            .toBeVisible()
        ;
    });

    test('can duplicate a radio question', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Set the question name
        const question = await form.addQuestion("Test radio question");

        // Change question type
        await form.setQuestionType(question, "Radio");

        // Add two options
        await getSelectableOptions(question).nth(0).fill("Option 1");
        await getSelectableOptions(question).nth(1).fill("Option 2");

        // Define second option as default
        await getDefaultOptionRadios(question).nth(1).check();

        // Duplicate the question
        await form.getButton('Duplicate question').click();

        // Check the source question
        let source_question = form.getNthQuestion(0);
        await expect(
            source_question.getByRole('textbox', { name: 'Question name' })
        ).toHaveValue("Test radio question");
        await expect(getSelectableOptions(source_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(source_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(getDefaultOptionRadios(source_question).nth(1))
            .toBeChecked()
        ;

        // Check the duplicated question
        let duplicated_question = form.getNthQuestion(1);
        await expect(
            duplicated_question.getByRole('textbox', { name: 'Question name' })
        ).toHaveValue("Test radio question");
        await expect(getSelectableOptions(duplicated_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(duplicated_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(getDefaultOptionRadios(duplicated_question).nth(1))
            .toBeChecked()
        ;

        // Define first option as default for the duplicated question
        await getDefaultOptionRadios(duplicated_question).nth(0).check();

        // Check the source question
        await expect(getDefaultOptionRadios(source_question).nth(0))
            .not.toBeChecked()
        ;
        await expect(getDefaultOptionRadios(source_question).nth(1))
            .toBeChecked()
        ;

        // Save the form and reload it
        await form.doSaveFormEditorAndReload();

        // Check options for the source question
        source_question = page.getByRole('option', {
            name: "Test radio question",
            exact: true,
        }).nth(0);
        await expect(getSelectableOptions(source_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(source_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(getDefaultOptionRadios(source_question).nth(0))
            .not.toBeChecked()
        ;
        await expect(getDefaultOptionRadios(source_question).nth(1))
            .toBeChecked()
        ;

        // Check options for the duplicated question
        duplicated_question = page.getByRole('option', {
            name: "Test radio question",
            exact: true,
        }).nth(1);
        await expect(getSelectableOptions(duplicated_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(duplicated_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(getDefaultOptionRadios(duplicated_question).nth(0))
            .toBeChecked()
        ;
        await expect(getDefaultOptionRadios(duplicated_question).nth(1))
            .not.toBeChecked()
        ;
    });

    test('can duplicate a checkbox question', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Set the question name
        const question = await form.addQuestion("Test checkbox question");

        // Change question type
        await form.setQuestionType(question, "Checkbox");

        // Add three options
        await getSelectableOptions(question).nth(0).fill("Option 1");
        await getSelectableOptions(question).nth(1).fill("Option 2");
        await getSelectableOptions(question).nth(2).fill("Option 3");

        // Define second and third options as default
        await getDefaultOptionCheckboxes(question).nth(1).check();
        await getDefaultOptionCheckboxes(question).nth(2).check();

        // Duplicate the question
        await form.getButton('Duplicate question').click();

        // Check the source question
        let source_question = form.getNthQuestion(0);
        await expect(
            source_question.getByRole('textbox', { name: 'Question name' })
        ).toHaveValue("Test checkbox question");
        await expect(getSelectableOptions(source_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(source_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(getSelectableOptions(source_question).nth(2))
            .toHaveValue("Option 3")
        ;
        await expect(getDefaultOptionCheckboxes(source_question).nth(1))
            .toBeChecked()
        ;
        await expect(getDefaultOptionCheckboxes(source_question).nth(2))
            .toBeChecked()
        ;

        // Check the duplicated question
        let duplicated_question = form.getNthQuestion(1);
        await expect(
            duplicated_question.getByRole('textbox', { name: 'Question name' })
        ).toHaveValue("Test checkbox question");
        await expect(getSelectableOptions(duplicated_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(duplicated_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(getSelectableOptions(duplicated_question).nth(2))
            .toHaveValue("Option 3")
        ;
        await expect(getDefaultOptionCheckboxes(duplicated_question).nth(1))
            .toBeChecked()
        ;
        await expect(getDefaultOptionCheckboxes(duplicated_question).nth(2))
            .toBeChecked()
        ;

        // Define first option as default for the duplicated question
        await getDefaultOptionCheckboxes(duplicated_question).nth(0).check();

        // Check the source question
        await expect(getDefaultOptionCheckboxes(source_question).nth(0))
            .not.toBeChecked()
        ;
        await expect(getDefaultOptionCheckboxes(source_question).nth(1))
            .toBeChecked()
        ;
        await expect(getDefaultOptionCheckboxes(source_question).nth(2))
            .toBeChecked()
        ;

        // Save the form and reload it
        await form.doSaveFormEditorAndReload();

        // Check options for the source question
        source_question = page.getByRole('option', {
            name: "Test checkbox question",
            exact: true,
        }).nth(0);
        await expect(getSelectableOptions(source_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(source_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(getSelectableOptions(source_question).nth(2))
            .toHaveValue("Option 3")
        ;
        await expect(getDefaultOptionCheckboxes(source_question).nth(0))
            .not.toBeChecked()
        ;
        await expect(getDefaultOptionCheckboxes(source_question).nth(1))
            .toBeChecked()
        ;
        await expect(getDefaultOptionCheckboxes(source_question).nth(2))
            .toBeChecked()
        ;

        // Check options for the duplicated question
        duplicated_question = page.getByRole('option', {
            name: "Test checkbox question",
            exact: true,
        }).nth(1);
        await expect(getSelectableOptions(duplicated_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(duplicated_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(getSelectableOptions(duplicated_question).nth(2))
            .toHaveValue("Option 3")
        ;
        await expect(getDefaultOptionCheckboxes(duplicated_question).nth(0))
            .toBeChecked()
        ;
        await expect(getDefaultOptionCheckboxes(duplicated_question).nth(1))
            .toBeChecked()
        ;
        await expect(getDefaultOptionCheckboxes(duplicated_question).nth(2))
            .toBeChecked()
        ;
    });

    test('can duplicate a dropdown question', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Set the question name
        const question = await form.addQuestion("Test dropdown question");

        // Change question type
        await form.setQuestionType(question, "Dropdown");

        // Add two options
        await getSelectableOptions(question).nth(0).fill("Option 1");
        await getSelectableOptions(question).nth(1).fill("Option 2");

        // Define second option as default
        await form.doSelectSingleDropdownOption(question, "Option 2");

        // Duplicate the question
        await form.getButton('Duplicate question').click();

        // Check the source question
        const source_question = form.getNthQuestion(0);
        const source_name = source_question.getByRole('textbox', {
            name: 'Question name',
        });
        await expect(source_name).toHaveValue("Test dropdown question");
        await source_name.click();
        await expect(getSelectableOptions(source_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(source_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(form.getSingleDropdownDefault(source_question))
            .toHaveText("Option 2")
        ;

        // Check the duplicated question
        const duplicated_question = form.getNthQuestion(1);
        const duplicated_name = duplicated_question.getByRole('textbox', {
            name: 'Question name',
        });
        await expect(duplicated_name).toHaveValue("Test dropdown question");
        await duplicated_name.click();
        await expect(getSelectableOptions(duplicated_question).nth(0))
            .toHaveValue("Option 1")
        ;
        await expect(getSelectableOptions(duplicated_question).nth(1))
            .toHaveValue("Option 2")
        ;
        await expect(form.getSingleDropdownDefault(duplicated_question))
            .toHaveText("Option 2")
        ;

        // Define first option as default for the duplicated question
        await form.doSelectSingleDropdownOption(duplicated_question, "Option 1");

        // Check the source question
        await expect(form.getSingleDropdownDefault(source_question))
            .toHaveText("Option 2")
        ;

        // Save the form
        await form.doSaveFormEditor();
    });
});
