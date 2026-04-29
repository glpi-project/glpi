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

test.describe('ITILFollowup configuration', () => {
    let form_page: FormPage;
    let followup_template_name: string;

    test.beforeEach(async ({ page, profile, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const info = await formImporter.importForm('destination_config_fields/simple-form-config.json');

        followup_template_name = `ITILFollowup template 1 - ${randomUUID()}`;
        await api.createItem('ITILFollowupTemplate', {
            name: followup_template_name,
            content: 'My ITILFollowup template content',
            entities_id: getWorkerEntityId(),
            is_recursive: 1,
        });

        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        const config = form_page.getRegion('Followups configuration');
        const followup_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(followup_dropdown).toHaveText('No Followup');
        await expect(form_page.getDropdownByLabel('Select followup templates...', config)).toBeHidden();

        // Switch to "Specific Followup templates"
        await form_page.doSetDropdownValue(followup_dropdown, 'Specific Followup templates');
        const specific_dropdown = form_page.getDropdownByLabel('Select followup templates...', config);
        await form_page.doSearchAndClickDropdownValue(specific_dropdown, followup_template_name, false);
        await form_page.doSaveDestination();
        await expect(followup_dropdown).toHaveText('Specific Followup templates');
        await expect(specific_dropdown).toContainText(followup_template_name);

        // Switch to "No Followup"
        await form_page.doSetDropdownValue(followup_dropdown, 'No Followup');
        await form_page.doSaveDestination();
        await expect(followup_dropdown).toHaveText('No Followup');
    });

    test('Can create ticket using specific followup template', async ({ page }) => {
        const config = form_page.getRegion('Followups configuration');
        const followup_dropdown = form_page.getStrategyDropdown(config);

        await form_page.doSetDropdownValue(followup_dropdown, 'Specific Followup templates');
        const specific_dropdown = form_page.getDropdownByLabel('Select followup templates...', config);
        await form_page.doSearchAndClickDropdownValue(specific_dropdown, followup_template_name, false);
        await form_page.doSaveDestination();

        // Go to preview
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        // Check if followup template content is displayed
        await expect(page.getByText('My ITILFollowup template content')).toBeVisible();
    });
});
