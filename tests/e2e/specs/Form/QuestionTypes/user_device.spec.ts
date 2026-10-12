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
import { randomUUID } from 'crypto';
import { expect, test } from '../../../fixtures/glpi_fixture';
import { FormPage } from '../../../pages/FormPage';
import { Api } from '../../../utils/Api';
import { Profiles } from '../../../utils/Profiles';
import { getWorkerEntityId } from '../../../utils/WorkerEntities';

test.describe('User device form question type', () => {
    const setupForm = async (form: FormPage, api: Api): Promise<Locator> => {
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Tests form for the user device form question type suite - ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
        });
        await form.goto(form_id);

        // Add a question
        const question = await form.addQuestion('Test user device question');

        // Change question type
        await form.setQuestionType(question, 'Item');

        // Define question sub type
        await form.setSubQuestionType(question, 'User Devices');

        return question;
    };

    // Both dropdowns always exist in the DOM, only one of them is displayed at
    // a time. The underlying <select> is always disabled.
    const assertSingleDeviceDropdownIsDisplayed = async (
        form: FormPage,
        container: Locator,
    ): Promise<void> => {
        await expect(
            form.getDropdownByLabel('Select device...', container)
        ).toBeVisible();
        await expect(
            container.getByLabel('Select device...', { exact: true })
        ).toBeDisabled();
        await expect(
            form.getDropdownByLabel('Select devices...', container)
        ).toBeHidden();
    };

    const assertMultipleDevicesDropdownIsDisplayed = async (
        form: FormPage,
        container: Locator,
    ): Promise<void> => {
        await expect(
            form.getDropdownByLabel('Select devices...', container)
        ).toBeVisible();
        await expect(
            container.getByLabel('Select devices...', { exact: true })
        ).toBeDisabled();
        await expect(
            form.getDropdownByLabel('Select device...', container)
        ).toBeHidden();
    };

    test('should be able to switch between multiple devices and single device', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        let question = await setupForm(form, api);

        // Double switch
        await question.getByRole('checkbox', { name: 'Allow multiple devices' })
            .check()
        ;
        await question.getByRole('checkbox', { name: 'Allow multiple devices' })
            .uncheck()
        ;

        await assertSingleDeviceDropdownIsDisplayed(form, question);

        // Save and reload the page
        await form.doSaveFormEditorAndReload();

        // Focus on the question
        await page.getByRole('option', { name: 'Test user device question' })
            .click()
        ;
        question = form.getNthQuestion(0);

        // Check the switch
        await expect(
            question.getByRole('checkbox', { name: 'Allow multiple devices' })
        ).not.toBeChecked();

        // Check the dropdowns
        await assertSingleDeviceDropdownIsDisplayed(form, question);

        // Switch to multiple devices
        await question.getByRole('checkbox', { name: 'Allow multiple devices' })
            .check()
        ;

        // Check the dropdowns
        await assertMultipleDevicesDropdownIsDisplayed(form, question);

        // Save and reload the page
        await form.doSaveFormEditorAndReload();

        // Focus on the question
        await page.getByRole('option', { name: 'Test user device question' })
            .click()
        ;
        question = form.getNthQuestion(0);

        // Check the dropdowns
        await assertMultipleDevicesDropdownIsDisplayed(form, question);

        // Check the switch
        await expect(
            question.getByRole('checkbox', { name: 'Allow multiple devices' })
        ).toBeChecked();
    });

    test('can duplicate a single device question', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        await setupForm(form, api);

        // Duplicate the question
        await form.getButton('Duplicate question').click();

        // The original question and its copy. Asserting the count first
        // prevents the loop below from silently doing nothing.
        const questions = page.getByRole('option', { name: 'New question' });
        await expect(questions).toHaveCount(2);

        for (const question of await questions.all()) {
            // Focus on the question
            await question.getByRole('textbox', { name: 'Question name' })
                .click()
            ;

            // Check the dropdowns
            await assertSingleDeviceDropdownIsDisplayed(form, question);

            // Check the switch
            await expect(
                question.getByRole('checkbox', {
                    name: 'Allow multiple devices',
                })
            ).not.toBeChecked();
        }
    });

    test('can duplicate a multiple devices question', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const question = await setupForm(form, api);

        // Allow multiple actors
        await question.getByRole('checkbox', { name: 'Allow multiple devices' })
            .check()
        ;

        // Duplicate the question
        await form.getButton('Duplicate question').click();

        // The original question and its copy. Asserting the count first
        // prevents the loop below from silently doing nothing.
        const questions = page.getByRole('option', { name: 'New question' });
        await expect(questions).toHaveCount(2);

        for (const duplicated_question of await questions.all()) {
            // Focus on the question
            await duplicated_question
                .getByRole('textbox', { name: 'Question name' })
                .click()
            ;

            // Check the dropdowns
            await assertMultipleDevicesDropdownIsDisplayed(
                form,
                duplicated_question
            );

            // Check the switch
            await expect(
                duplicated_question.getByRole('checkbox', {
                    name: 'Allow multiple devices',
                })
            ).toBeChecked();
        }
    });
});
