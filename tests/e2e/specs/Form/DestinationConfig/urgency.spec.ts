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

test.describe('Urgency configuration', () => {
    let form_page: FormPage;

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const info = await formImporter.importForm('destination_config_fields/urgency-config.json');
        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        await form_page.doOpenDestinationAccordionItem('Properties');

        const config = form_page.getRegion('Urgency configuration');
        const urgency_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(urgency_dropdown).toHaveText('Answer to last "Urgency" question');
        await expect(form_page.getDropdownByLabel('Select an urgency level...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select a question...', config)).toBeHidden();

        // Switch to "From template"
        await form_page.doSetDropdownValue(urgency_dropdown, 'From template');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(urgency_dropdown).toHaveText('From template');

        // Switch to "Specific urgency"
        await form_page.doSetDropdownValue(urgency_dropdown, 'Specific urgency');
        const specific_dropdown = form_page.getDropdownByLabel('Select an urgency level...', config);
        await form_page.doSetDropdownValue(specific_dropdown, 'High');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(urgency_dropdown).toHaveText('Specific urgency');
        await expect(specific_dropdown).toHaveText('High');

        // Switch to "Answer from a specific question"
        await form_page.doSetDropdownValue(urgency_dropdown, 'Answer from a specific question');
        const question_dropdown = form_page.getDropdownByLabel('Select a question...', config);
        await form_page.doSetDropdownValue(question_dropdown, 'My urgency question');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(urgency_dropdown).toHaveText('Answer from a specific question');
        await expect(question_dropdown).toHaveText('My urgency question');
    });

    test('Can create ticket using default configuration', async ({ page }) => {
        // Go to preview
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        // Fill form
        await form_page.doSetDropdownValue(
            form_page.getDropdownByLabel('My urgency question'),
            'Very high'
        );
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        // Check ticket values
        await expect(form_page.getDropdownByLabel('Urgency')).toHaveText('Very high');
    });
});
