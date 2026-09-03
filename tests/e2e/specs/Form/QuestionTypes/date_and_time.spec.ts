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

test.describe('Date and Time form question type', () => {
    const setupForm = async (form: FormPage, api: Api): Promise<Locator> => {
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Tests form for the date and time form question type suite - ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
        });
        await form.goto(form_id);

        // Add a question
        const question = await form.addQuestion('Test Date and Time question');

        // Change question type
        await form.setQuestionType(question, 'Date and time');

        return question;
    };

    const getCheckbox = (question: Locator, name: string): Locator => {
        return question.getByRole('checkbox', { name: name, exact: true })
            .filter({ visible: true })
        ;
    };

    const getDefaultValueInput = (question: Locator): Locator => {
        return question.getByLabel('Default value', { exact: true });
    };

    test('check behavior of the date and time question type', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const question = await setupForm(form, api);

        // Check the checkboxes
        await expect(getCheckbox(question, 'Date')).toBeChecked();
        await expect(getCheckbox(question, 'Time')).not.toBeChecked();
        await expect(getCheckbox(question, 'Current date')).not.toBeChecked();

        // Check the input type
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'date')
        ;

        // Switch to time
        await getCheckbox(question, 'Time').check();

        // Check the checkboxes
        await expect(getCheckbox(question, 'Date')).toBeChecked();
        await expect(getCheckbox(question, 'Time')).toBeChecked();
        await expect(getCheckbox(question, 'Current date and time'))
            .not.toBeChecked()
        ;

        // Check the input type
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'datetime-local')
        ;

        // Uncheck the date
        await getCheckbox(question, 'Date').uncheck();

        // Check the checkboxes
        await expect(getCheckbox(question, 'Date')).not.toBeChecked();
        await expect(getCheckbox(question, 'Time')).toBeChecked();
        await expect(getCheckbox(question, 'Current time')).not.toBeChecked();

        // Check the input type
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'time')
        ;

        // Switch to current date
        await getCheckbox(question, 'Current time').check();

        // Check the input type
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'text')
        ;
        await expect(getDefaultValueInput(question)).toBeDisabled();

        // Uncheck the time
        await getCheckbox(question, 'Time').uncheck();

        // Check the checkboxes
        await expect(getCheckbox(question, 'Date')).toBeChecked();
        await expect(getCheckbox(question, 'Time')).not.toBeChecked();
        await expect(getCheckbox(question, 'Current date')).toBeChecked();

        // Check the input type
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'text')
        ;
        await expect(getDefaultValueInput(question)).toBeDisabled();
    });

    test('check date and time value conversions', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        const question = await setupForm(form, api);

        // Start with date input (default)
        await expect(getCheckbox(question, 'Date')).toBeChecked();
        await expect(getCheckbox(question, 'Time')).not.toBeChecked();

        // Set a date value
        await getDefaultValueInput(question).fill('2023-12-15');

        // Add time - should convert to datetime-local
        await getCheckbox(question, 'Time').check();
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'datetime-local')
        ;
        expect(await getDefaultValueInput(question).inputValue())
            .toMatch(/^2023-12-15T\d{2}:\d{2}$/)
        ;

        // Set a specific time with the date
        await getDefaultValueInput(question).fill('2023-12-15T14:30');

        // Remove date - should convert to time only
        await getCheckbox(question, 'Date').uncheck();
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'time')
        ;
        await expect(getDefaultValueInput(question)).toHaveValue('14:30');

        // Add date back - should convert back to datetime-local
        await getCheckbox(question, 'Date').check();
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'datetime-local')
        ;
        expect(await getDefaultValueInput(question).inputValue())
            .toMatch(/^\d{4}-\d{2}-\d{2}T14:30$/)
        ;

        // Remove time - should convert to date only
        await getCheckbox(question, 'Time').uncheck();
        await expect(getDefaultValueInput(question))
            .toHaveAttribute('type', 'date')
        ;
        expect(await getDefaultValueInput(question).inputValue())
            .toMatch(/^\d{4}-\d{2}-\d{2}$/)
        ;

        // Check "Current date" - should disable the input
        await getCheckbox(question, 'Current date').check();
        await expect(getDefaultValueInput(question)).toBeDisabled();

        // Uncheck "Current date" and enable Time
        await getCheckbox(question, 'Current date').uncheck();
        await getCheckbox(question, 'Time').check();

        // Set datetime value then check "Current date and time" - should disable the input
        await getDefaultValueInput(question).fill('2023-12-15T16:45');
        await getCheckbox(question, 'Current date and time').check();
        await expect(getDefaultValueInput(question)).toBeDisabled();
    });
});
