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

test.describe('Request source configuration', () => {
    let form_page: FormPage;
    const unique = randomUUID();

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const info = await formImporter.importForm('destination_config_fields/simple-form-config.json');
        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        await form_page.doOpenDestinationAccordionItem('Properties');

        const config = form_page.getRegion('Request source configuration');
        const source_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(source_dropdown).toHaveText('From template');
        await expect(form_page.getDropdownByLabel('Select a request source...', config)).toBeHidden();

        // Switch to "Specific request source"
        await form_page.doSetDropdownValue(source_dropdown, 'Specific request source');
        const specific_dropdown = form_page.getDropdownByLabel('Select a request source...', config);
        await form_page.doSetDropdownValue(specific_dropdown, 'E-Mail');

        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(source_dropdown).toHaveText('Specific request source');
        await expect(specific_dropdown).toHaveText('E-Mail');
    });

    test('Can create ticket using default configuration', async ({ page, api }) => {
        // Create a ticket template with predefined request source
        const ticket_template_id = await api.createItem('TicketTemplate', {
            name: `Test template for request source - ${unique}`,
            entities_id: getWorkerEntityId(),
            is_recursive: 1,
        });
        await api.createItem('TicketTemplatePredefinedField', {
            tickettemplates_id: ticket_template_id,
            num: 9,
            value: 3,
        });

        // Reload page so the newly created template appears in the dropdown
        await page.reload();
        await form_page.doOpenDestinationAccordionItem('Properties');
        const template_config = form_page.getRegion('Template configuration');
        const template_dropdown = form_page.getStrategyDropdown(template_config);
        await form_page.doSetDropdownValue(template_dropdown, 'Specific template');
        const specific_template = form_page.getDropdownByLabel('Select a template...', template_config);
        await form_page.doSetDropdownValue(specific_template, `Test template for request source - ${unique}`, false);
        await form_page.doSaveDestination();

        // Go to preview
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        // Check ticket values
        await expect(form_page.getDropdownByLabel('Request source')).toHaveText('Phone');
    });

    test('Only assignable request sources are displayed', async ({ api }) => {
        await api.createItem('RequestType', {
            name: `Assignable request source ${unique}`,
            is_active: 1,
            is_ticketheader: 1,
        });
        await api.createItem('RequestType', {
            name: `Non assignable request source ${unique}`,
            is_active: 1,
            is_ticketheader: 0,
        });
        await api.createItem('RequestType', {
            name: `Disabled request source ${unique}`,
            is_active: 0,
            is_ticketheader: 1,
        });

        await form_page.doOpenDestinationAccordionItem('Properties');
        const config = form_page.getRegion('Request source configuration');
        const source_dropdown = form_page.getStrategyDropdown(config);
        await form_page.doSetDropdownValue(source_dropdown, 'Specific request source');
        const specific_dropdown = form_page.getDropdownByLabel('Select a request source...', config);

        // Check that only assignable request sources are displayed
        await form_page.doSearchAndClickDropdownValue(specific_dropdown, `Assignable request source ${unique}`, false);
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(specific_dropdown).toContainText(`Assignable request source ${unique}`);
    });
});
