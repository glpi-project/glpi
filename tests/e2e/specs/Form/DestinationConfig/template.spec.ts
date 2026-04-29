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

test.describe('Template configuration', () => {
    let form_page: FormPage;
    const unique = randomUUID();
    const ticket_template_name = `Test ticket template - ${unique}`;

    test.beforeEach(async ({ page, profile, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const info = await formImporter.importForm('destination_config_fields/simple-form-config.json');

        const ticket_template_id = await api.createItem('TicketTemplate', {
            name: ticket_template_name,
        });
        await api.createItem('TicketTemplateHiddenField', {
            tickettemplates_id: ticket_template_id,
            num: 12,
        });

        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        await form_page.doOpenDestinationAccordionItem('Properties');

        const config = form_page.getRegion('Template configuration');
        const template_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(template_dropdown).toHaveText('Default template');
        await expect(form_page.getDropdownByLabel('Select a template...', config)).toBeHidden();

        // Switch to "Specific template"
        await form_page.doSetDropdownValue(template_dropdown, 'Specific template');
        const specific_template_dropdown = form_page.getDropdownByLabel('Select a template...', config);
        await form_page.doSetDropdownValue(specific_template_dropdown, ticket_template_name);

        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(template_dropdown).toHaveText('Specific template');
        await expect(specific_template_dropdown).toHaveText(ticket_template_name);
    });

    test('Can create ticket using default configuration', async ({ page }) => {
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        await page.getByRole('textbox', { name: 'My test question' }).fill('My test answer');
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        await expect(form_page.getDropdownByLabel('Status')).toBeHidden();
    });
});
