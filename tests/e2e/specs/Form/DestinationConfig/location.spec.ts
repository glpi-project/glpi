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

test.describe('Location configuration', () => {
    let form_page: FormPage;
    const unique = randomUUID();
    const location_name = `Test Location - ${unique}`;

    test.beforeEach(async ({ page, profile, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const entity_id = getWorkerEntityId();
        const info = await formImporter.importForm('destination_config_fields/location-config.json');

        await api.createItem('Location', {
            name: location_name,
            entities_id: entity_id,
            is_recursive: 1,
        });

        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        await form_page.doOpenDestinationAccordionItem('Properties');

        const config = form_page.getRegion('Location configuration');
        const location_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(location_dropdown).toHaveText('Answer to last "Location" dropdown question');
        await expect(form_page.getDropdownByLabel('Select a location...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select a question...', config)).toBeHidden();

        // Switch to "From template"
        await form_page.doSetDropdownValue(location_dropdown, 'From template');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(location_dropdown).toHaveText('From template');

        // Switch to "Specific location"
        await form_page.doSetDropdownValue(location_dropdown, 'Specific location');
        const specific_dropdown = form_page.getDropdownByLabel('Select a location...', config);
        await form_page.doSearchAndClickDropdownValue(specific_dropdown, location_name, false);
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(location_dropdown).toHaveText('Specific location');
        await expect(specific_dropdown).toHaveText(location_name);

        // Switch to "Answer from a specific question"
        await form_page.doSetDropdownValue(location_dropdown, 'Answer from a specific question');
        const question_dropdown = form_page.getDropdownByLabel('Select a question...', config);
        await form_page.doSetDropdownValue(question_dropdown, 'My Location question');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(location_dropdown).toHaveText('Answer from a specific question');
        await expect(question_dropdown).toHaveText('My Location question');
    });

    test('Can create ticket using default configuration', async ({ page }) => {
        // Go to preview
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        // Fill form
        await form_page.doSearchAndClickDropdownValue(
            form_page.getDropdownByLabel('My Location question'),
            location_name,
            false
        );
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        // Check ticket values
        await expect(form_page.getDropdownByLabel('Location')).toHaveText(location_name);
    });
});
