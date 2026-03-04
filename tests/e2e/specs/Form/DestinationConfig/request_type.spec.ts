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

import { test, expect } from '../../../fixtures/glpi_fixture';
import { Profiles } from "../../../utils/Profiles";
import { FormPage } from "../../../pages/FormPage";

test.describe('Request type configuration', () => {
    let form_page: FormPage;

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const info = await formImporter.importForm('destination_config_fields/request-type-config.json');
        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        await form_page.doOpenDestinationAccordionItem('Properties');

        const config = form_page.getRegion('Request type configuration');
        const request_type_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(request_type_dropdown).toHaveText('Answer to last "Request type" question');
        await expect(form_page.getDropdownByLabel('Select a request type...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select a question...', config)).toBeHidden();

        // Switch to "From template"
        await form_page.doSetDropdownValue(request_type_dropdown, 'From template');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(request_type_dropdown).toHaveText('From template');

        // Switch to "Specific request type"
        await form_page.doSetDropdownValue(request_type_dropdown, 'Specific request type');
        const specific_dropdown = form_page.getDropdownByLabel('Select a request type...', config);
        await form_page.doSetDropdownValue(specific_dropdown, 'Request');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(request_type_dropdown).toHaveText('Specific request type');
        await expect(specific_dropdown).toHaveText('Request');

        // Switch to "Answer from a specific question"
        await form_page.doSetDropdownValue(request_type_dropdown, 'Answer from a specific question');
        const question_dropdown = form_page.getDropdownByLabel('Select a question...', config);
        await form_page.doSetDropdownValue(question_dropdown, 'My request type question');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(request_type_dropdown).toHaveText('Answer from a specific question');
        await expect(question_dropdown).toHaveText('My request type question');
    });

    test('Can create ticket using default configuration', async ({ page }) => {
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        await expect(form_page.getDropdownByLabel('My request type question')).toHaveText('-----');
        await form_page.doSetDropdownValue(
            form_page.getDropdownByLabel('My request type question'),
            'Request'
        );
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        await expect(form_page.getDropdownByLabel('Type')).toHaveText('Request');
    });
});
