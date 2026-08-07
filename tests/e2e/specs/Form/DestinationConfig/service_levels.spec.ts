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

const service_level_types = [
    {
        name: 'SLA TTO',
        item_class: 'SLA',
        type_int: 1,
        region_name: 'TTO configuration',
        label: 'TTO',
        specific_label: 'Specific SLA',
        select_label: 'Select a SLA...',
    },
    {
        name: 'SLA TTR',
        item_class: 'SLA',
        type_int: 0,
        region_name: 'TTR configuration',
        label: 'TTR',
        specific_label: 'Specific SLA',
        select_label: 'Select a SLA...',
    },
    {
        name: 'OLA TTO',
        item_class: 'OLA',
        type_int: 1,
        region_name: 'Internal TTO configuration',
        label: 'Internal TTO',
        specific_label: 'Specific OLA',
        select_label: 'Select a OLA...',
    },
    {
        name: 'OLA TTR',
        item_class: 'OLA',
        type_int: 0,
        region_name: 'Internal TTR configuration',
        label: 'Internal TTR',
        specific_label: 'Specific OLA',
        select_label: 'Select a OLA...',
    },
];

for (const sl_type of service_level_types) {
    test.describe(`${sl_type.name} configuration`, () => {
        let form_page: FormPage;
        const unique = randomUUID();
        const sl_name = `${sl_type.name} - ${unique}`;

        test.beforeEach(async ({ page, profile, api, formImporter }) => {
            await profile.set(Profiles.SuperAdmin);
            form_page = new FormPage(page);
            const info = await formImporter.importForm('destination_config_fields/simple-form-config.json');

            const slm_id = await api.createItem('SLM', {
                name: `SLM - ${unique}`,
                entities_id: getWorkerEntityId(),
                is_recursive: 1,
            });
            await api.createItem(sl_type.item_class, {
                name: sl_name,
                type: sl_type.type_int,
                number_time: 1,
                definition_time: 'hour',
                slms_id: slm_id,
            });

            await form_page.gotoDestinationTab(info.getId());
        });

        test('Can use all possible configuration options', async () => {
            await form_page.doOpenDestinationAccordionItem('Service levels');

            const config = form_page.getRegion(sl_type.region_name);
            const sl_dropdown = form_page.getStrategyDropdown(config);

            // Default value
            await expect(sl_dropdown).toHaveText('From template');

            // Switch to "From template"
            await form_page.doSetDropdownValue(sl_dropdown, 'From template');
            await form_page.doSaveDestinationAndReopenAccordion('Service levels');
            await expect(sl_dropdown).toHaveText('From template');

            // Switch to "Specific XLA"
            await form_page.doSetDropdownValue(sl_dropdown, sl_type.specific_label);
            const specific_dropdown = form_page.getDropdownByLabel(sl_type.select_label, config);
            await form_page.doSearchAndClickDropdownValue(specific_dropdown, sl_name, false);
            await form_page.doSaveDestinationAndReopenAccordion('Service levels');
            await expect(sl_dropdown).toHaveText(sl_type.specific_label);
            await expect(specific_dropdown).toContainText(sl_name);
        });

        test('Can create ticket using specific configuration', async ({ page }) => {
            await form_page.doOpenDestinationAccordionItem('Service levels');

            const config = form_page.getRegion(sl_type.region_name);
            const sl_dropdown = form_page.getStrategyDropdown(config);
            await form_page.doSetDropdownValue(sl_dropdown, sl_type.specific_label);
            const specific_dropdown = form_page.getDropdownByLabel(sl_type.select_label, config);
            await form_page.doSearchAndClickDropdownValue(specific_dropdown, sl_name, false);
            await form_page.doSaveDestination();

            // Go to preview
            await page.getByRole('tab', { name: 'Form', exact: true }).click();
            await form_page.doPreviewForm();

            // Fill form
            await page.getByRole('textbox', { name: 'My test question' }).fill('My test answer');
            await form_page.getButton('Submit').click();
            await page.getByRole('link', { name: 'My test form' }).click();

            // Check ticket values
            const service_levels = form_page.getRegion('Service levels');
            await expect(service_levels).toContainText(sl_name);
        });
    });
}
