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
import { test, expect } from '../../../fixtures/glpi_fixture';
import { Profiles } from "../../../utils/Profiles";
import { FormPage } from "../../../pages/FormPage";
import { getWorkerEntityId } from "../../../utils/WorkerEntities";

test.describe('ITILTask configuration', () => {
    let form_page: FormPage;
    let task_template_name: string;

    test.beforeEach(async ({ page, profile, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const info = await formImporter.importForm('destination_config_fields/simple-form-config.json');

        task_template_name = `Task template 1 - ${randomUUID()}`;
        await api.createItem('TaskTemplate', {
            name: task_template_name,
            content: 'My Task template content',
            entities_id: getWorkerEntityId(),
            is_recursive: 1,
        });

        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        const config = form_page.getRegion('Tasks configuration');
        const task_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(task_dropdown).toHaveText('No Task');
        await expect(form_page.getDropdownByLabel('Select task templates...', config)).toBeHidden();

        // Switch to "Specific Task templates"
        await form_page.doSetDropdownValue(task_dropdown, 'Specific Task templates');
        const specific_dropdown = form_page.getDropdownByLabel('Select task templates...', config);
        await form_page.doSearchAndClickDropdownValue(specific_dropdown, task_template_name, false);
        await form_page.doSaveDestination();
        await expect(task_dropdown).toHaveText('Specific Task templates');
        await expect(specific_dropdown).toContainText(task_template_name);

        // Switch to "No Task"
        await form_page.doSetDropdownValue(task_dropdown, 'No Task');
        await form_page.doSaveDestination();
        await expect(task_dropdown).toHaveText('No Task');
    });

    test('Can create ticket using specific task template', async ({ page }) => {
        const config = form_page.getRegion('Tasks configuration');
        const task_dropdown = form_page.getStrategyDropdown(config);

        // Switch to "Specific Task templates"
        await form_page.doSetDropdownValue(task_dropdown, 'Specific Task templates');
        const specific_dropdown = form_page.getDropdownByLabel('Select task templates...', config);
        await form_page.doSearchAndClickDropdownValue(specific_dropdown, task_template_name, false);
        await form_page.doSaveDestination();

        // Go to preview
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        // Check if followup template content is displayed
        await expect(page.getByText('My Task template content')).toBeVisible();
    });
});
