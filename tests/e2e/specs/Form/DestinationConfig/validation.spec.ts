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
import { getWorkerEntityId } from '../../../utils/WorkerEntities';
import { FormPage } from "../../../pages/FormPage";

test.describe('Validation configuration', () => {
    let form_page: FormPage;
    let user_name: string;
    let group_name: string;
    let template_name: string;
    let step_name: string;

    test.beforeEach(async ({ page, profile, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const entity_id = getWorkerEntityId();

        const info = await formImporter.importForm('destination_config_fields/validation-config.json');

        const unique = randomUUID();
        user_name = `Validation test user - ${unique}`;
        group_name = `Validation test group - ${unique}`;
        template_name = `Validation test template - ${unique}`;
        step_name = `Validation test step - ${unique}`;

        const user_id = await api.createItem('User', { name: user_name });
        await api.createItem('Profile_User', {
            users_id: user_id,
            profiles_id: Profiles.SuperAdmin,
            entities_id: entity_id,
            is_recursive: 1,
        });
        await api.createItem('Group', {
            name: group_name,
            entities_id: entity_id,
        });
        await api.createItem('ITILValidationTemplate', {
            name: template_name,
            entities_id: entity_id,
            is_recursive: 1,
        });
        await api.createItem('ValidationStep', {
            name: step_name,
            minimal_required_validation_percent: 100,
        });

        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        const config = form_page.getRegion('Approval configuration');
        const validation_dropdown = form_page.getDropdownByLabel('Select strategy...', config);

        // Default value
        await expect(validation_dropdown).toHaveText('No approval');

        // Hidden dropdowns
        await expect(form_page.getDropdownByLabel('Select approval templates...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select actors...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select questions...', config)).toBeHidden();

        // Specific Approval templates
        await form_page.doSetDropdownValue(validation_dropdown, 'Specific Approval templates');
        const templates_dropdown = form_page.getDropdownByLabel('Select approval templates...', config);
        await form_page.doSearchAndClickDropdownValue(templates_dropdown, template_name, false);
        await form_page.doSaveDestination();
        await expect(validation_dropdown).toContainText('Specific Approval templates');
        await expect(templates_dropdown).toContainText(template_name);

        // Specific actors
        await form_page.doSetDropdownValue(validation_dropdown, 'Specific actors');
        const step_dropdown = form_page.getDropdownByLabel('Select validation step...', config);
        const actors_dropdown = form_page.getDropdownByLabel('Select actors...', config);
        await form_page.doSearchAndClickDropdownValue(step_dropdown, step_name, false);
        await form_page.doSearchAndClickDropdownValue(actors_dropdown, user_name, false);
        await form_page.doSearchAndClickDropdownValue(actors_dropdown, group_name, false);
        await form_page.doSaveDestination();
        await expect(validation_dropdown).toHaveText('Specific actors');
        await expect(step_dropdown).toContainText(step_name);
        await expect(actors_dropdown).toContainText(user_name);
        await expect(actors_dropdown).toContainText(group_name);

        // Answer from specific questions
        await form_page.doSetDropdownValue(validation_dropdown, 'Answer from specific questions');
        const questions_dropdown = form_page.getDropdownByLabel('Select questions...', config);
        await form_page.doSetDropdownValue(questions_dropdown, 'My User question');
        await form_page.doSetDropdownValue(questions_dropdown, 'My Assignee question');
        const step_dropdown_2 = form_page.getDropdownByLabel('Select validation step...', config);
        await form_page.doSearchAndClickDropdownValue(step_dropdown_2, step_name, false);
        await form_page.doSaveDestination();
        await expect(validation_dropdown).toHaveText('Answer from specific questions');
        await expect(step_dropdown_2).toContainText(step_name);
        await expect(questions_dropdown).toContainText('My User question');
        await expect(questions_dropdown).toContainText('My Assignee question');
    });

    test('Can define multiple strategies at once', async () => {
        const config = form_page.getRegion('Approval configuration');

        const get_nth_strategy = (n: number) => {
            // eslint-disable-next-line playwright/no-raw-locators
            return config.getByTestId('strategy-dropdown').nth(n).locator('+ span').getByRole('combobox');
        };

        // First strategy: Specific actors
        const first_strategy = get_nth_strategy(0);
        await form_page.doSetDropdownValue(first_strategy, 'Specific actors');
        const actors_dropdown = form_page.getDropdownByLabel('Select actors...', config).first();
        await form_page.doSearchAndClickDropdownValue(actors_dropdown, user_name, false);
        await form_page.doSearchAndClickDropdownValue(actors_dropdown, group_name, false);

        // Second strategy: Specific Approval templates
        await config.getByRole('button', { name: 'Combine with another option' }).click();
        const second_strategy = get_nth_strategy(1);
        await form_page.doSetDropdownValue(second_strategy, 'Specific Approval templates');
        const templates_dropdown = form_page.getDropdownByLabel('Select approval templates...', config);
        await form_page.doSearchAndClickDropdownValue(templates_dropdown, template_name, false);

        // Third strategy: Answer from specific questions
        await config.getByRole('button', { name: 'Combine with another option' }).click();
        const third_strategy = get_nth_strategy(2);
        await form_page.doSetDropdownValue(third_strategy, 'Answer from specific questions');
        const questions_dropdown = form_page.getDropdownByLabel('Select questions...', config);
        await form_page.doSetDropdownValue(questions_dropdown, 'My User question');
        await form_page.doSetDropdownValue(questions_dropdown, 'My Assignee question');

        // Fourth strategy: Specific actors (another)
        await config.getByRole('button', { name: 'Combine with another option' }).click();
        const fourth_strategy = get_nth_strategy(3);
        await form_page.doSetDropdownValue(fourth_strategy, 'Specific actors');
        const actors_dropdown_2 = form_page.getDropdownByLabel('Select actors...', config).last();
        await form_page.doSearchAndClickDropdownValue(actors_dropdown_2, user_name, false);

        // Save
        await form_page.doSaveDestination();

        // Verify all 4 strategies
        await expect(get_nth_strategy(0)).toHaveText('Specific actors');
        await expect(form_page.getDropdownByLabel('Select actors...', config).first()).toContainText(user_name);
        await expect(form_page.getDropdownByLabel('Select actors...', config).first()).toContainText(group_name);

        await expect(get_nth_strategy(1)).toHaveText('Specific Approval templates');
        await expect(templates_dropdown).toContainText(template_name);

        await expect(get_nth_strategy(2)).toHaveText('Answer from specific questions');
        await expect(questions_dropdown).toContainText('My User question');
        await expect(questions_dropdown).toContainText('My Assignee question');

        await expect(get_nth_strategy(3)).toHaveText('Specific actors');
        await expect(form_page.getDropdownByLabel('Select actors...', config).last()).toContainText(user_name);
    });

    test('Can create ticket using a specific question answer', async ({ page }) => {
        const config = form_page.getRegion('Approval configuration');
        const validation_dropdown = form_page.getDropdownByLabel('Select strategy...', config);
        await form_page.doSetDropdownValue(validation_dropdown, 'Answer from specific questions');
        const questions_dropdown = form_page.getDropdownByLabel('Select questions...', config);
        await form_page.doSetDropdownValue(questions_dropdown, 'My User question');

        await form_page.doSaveDestination();

        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        await form_page.doSearchAndClickDropdownValue(
            form_page.getDropdownByLabel('My Assignee question'),
            group_name,
            false
        );
        await form_page.doSearchAndClickDropdownValue(
            form_page.getDropdownByLabel('My User question'),
            user_name,
            false
        );

        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        await expect(page.getByRole('link', { name: user_name })).toBeVisible({ timeout: 15000 });
    });
});
