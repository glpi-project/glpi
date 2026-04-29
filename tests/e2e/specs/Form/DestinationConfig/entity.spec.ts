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

test.describe('Entity configuration', () => {
    let form_page: FormPage;
    const unique = randomUUID();
    const entity_name = `E2ETestEntity - ${unique}`;

    test.beforeEach(async ({ page, profile, api, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);
        const entity_id = getWorkerEntityId();

        await api.createItem('Entity', {
            name: entity_name,
            entities_id: entity_id,
        });

        const info = await formImporter.importForm('destination_config_fields/entity-config.json');
        await form_page.gotoDestinationTab(info.getId());
    });

    test('Can use all possible configuration options', async () => {
        await form_page.doOpenDestinationAccordionItem('Properties');

        const config = form_page.getRegion('Entity configuration');
        const entity_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(entity_dropdown).toHaveText('Answer to last "Entity" item question');
        await expect(form_page.getDropdownByLabel('Select an entity...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select a question...', config)).toBeHidden();

        // Switch to "Form filler"
        await form_page.doSetDropdownValue(entity_dropdown, 'Active entity of the form filler');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(entity_dropdown).toHaveText('Active entity of the form filler');

        // Switch to "From form"
        await form_page.doSetDropdownValue(entity_dropdown, 'From form');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(entity_dropdown).toHaveText('From form');

        // Switch to "Specific entity"
        await form_page.doSetDropdownValue(entity_dropdown, 'Specific entity');
        const specific_entity = form_page.getDropdownByLabel('Select an entity...', config);
        await form_page.doSearchAndClickDropdownValue(specific_entity, entity_name, false);
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(entity_dropdown).toHaveText('Specific entity');
        await expect(specific_entity).toContainText(entity_name);

        // Switch to "Answer from a specific question"
        await form_page.doSetDropdownValue(entity_dropdown, 'Answer from a specific question');
        const question_dropdown = form_page.getDropdownByLabel('Select a question...', config);
        await form_page.doSetDropdownValue(question_dropdown, 'My entity question');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(entity_dropdown).toHaveText('Answer from a specific question');
        await expect(question_dropdown).toHaveText('My entity question');

        // Switch to "Answer to last "Entity" item question"
        await form_page.doSetDropdownValue(entity_dropdown, 'Answer to last "Entity" item question');
        await form_page.doSaveDestinationAndReopenAccordion('Properties');
        await expect(entity_dropdown).toHaveText('Answer to last "Entity" item question');
    });

    test('Can create ticket using default configuration', async ({ page, api }) => {
        const entity_id = getWorkerEntityId();
        const sub_entity_name = `E2ETestEntityForForm-${unique}`;
        await api.createItem('Entity', {
            name: sub_entity_name,
            entities_id: entity_id,
        });

        // Go to preview
        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        // Fill form
        await form_page.doSearchAndClickDropdownValue(
            form_page.getDropdownByLabel('My entity question'),
            sub_entity_name,
            false
        );
        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        // Check ticket values
        await expect(page.getByRole('region', { name: 'Ticket' }).first().getByRole('link', { name: sub_entity_name })).toBeVisible();
    });
});
