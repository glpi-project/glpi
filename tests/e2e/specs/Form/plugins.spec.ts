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

test.describe('Form plugins', () => {
    test('can use plugin question types', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        // Create and go to form
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `My test form`,
            entities_id: getWorkerEntityId(),
        });
        await form.goto(form_id);

        // Create range question
        const question = await form.addQuestion('My range question');
        await form.doChangeQuestionType(question, 'Tester plugin');
        const sub_type = form.getDropdownByLabel('Question sub type', question);
        await form.doSetDropdownValue(sub_type, 'Range', false);
        await question.getByTestId('question-loading').waitFor({ state: 'detached' });

        // Save and preview form
        await form.doSaveFormEditor();
        await form.doPreviewForm();

        // Sumbmit form with the default value and go to ticket
        await page.getByRole('button', { name: 'Submit' }).click();
        await page.getByRole('link', { name: 'My test form' }).click();

        // Check value was submited
        await expect(page.getByTestId('content')).toContainText(
            '1) My range question: 50'
        );
    });

    test('can configure access policies from plugins', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);

        // Create and go to form
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Test plugin policies - ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
        });
        const tab = 'Glpi\\Form\\AccessControl\\FormAccessControl$1';
        await page.goto(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

        // Configure day of the week access policy
        const region = page.getByRole('region', {
            name: 'Restrict access to a specific day of the week',
        });
        const form = new FormPage(page);
        const day_dropdown = form.getDropdownByLabel('Day', region);
        await expect(day_dropdown).toContainText('Monday');
        await expect(region.getByRole('checkbox', { name: 'Active' })).not.toBeChecked();
        await form.doSetDropdownValue(day_dropdown, 'Thursday', false);
        await expect(region.getByRole('checkbox', { name: 'Active' })).toBeChecked();

        // Save and reload
        await page.getByRole('button', { name: 'Save changes' }).click();
        await page.reload();

        // Validate that the value was applied and that the policy is active.
        const region_after = page.getByRole('region', {
            name: 'Restrict access to a specific day of the week',
        });
        await expect(region_after.getByRole('checkbox', { name: 'Active' })).toBeChecked();
        const day_dropdown_after = form.getDropdownByLabel('Day', region_after);
        await expect(day_dropdown_after).toContainText('Thursday');
    });

    test('can configure destinations from plugins', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        // Create and go to form
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Test plugin destinations - ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
        });
        await form.gotoDestinationTab(form_id);

        // Add computer destination
        await page.getByRole('button', { name: 'Add Computer' }).click();

        // Set name and save
        const name_input = page.getByTestId('form-field-config[name]').getByRole('textbox', { name: 'Name' });
        await expect(name_input).not.toHaveValue('My computer name');
        await name_input.fill('My computer name');
        await page.getByRole('button', { name: 'Update item' }).click();

        // Validate value was saved
        await expect(name_input).toHaveValue('My computer name');
    });

    test('can configure destination config field from plugins', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);
        const form = new FormPage(page);

        // Create and go to form
        const form_id = await api.createItem('Glpi\\Form\\Form', {
            name: `Test plugin destination field - ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
        });
        await form.gotoDestinationTab(form_id);

        // Add external ID field
        await form.doOpenDestinationAccordionItem('Properties');
        const config = page.getByRole('region', { name: 'External ID configuration' });
        const ext_id_dropdown = form.getStrategyDropdown(config);
        await form.doSetDropdownValue(ext_id_dropdown, 'Specific external ID', false);
        await config.getByRole('textbox', { name: 'Specific external ID' }).last().fill('MY-EXTERNAL-ID');

        // Save
        await page.getByRole('button', { name: 'Update item' }).click();

        // Validate value was saved
        await form.doOpenDestinationAccordionItem('Properties');
        const config_after = page.getByRole('region', { name: 'External ID configuration' });
        const ext_id_after = form.getStrategyDropdown(config_after);
        await expect(ext_id_after).toContainText('Specific external ID');
        await expect(
            config_after.getByRole('textbox', { name: 'Specific external ID' }).last()
        ).toHaveValue('MY-EXTERNAL-ID');
    });
});
