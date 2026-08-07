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

test.describe('Linked ITIL Objects configuration', () => {
    let form_page: FormPage;

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);

        const info = await formImporter.importForm('destination_config_fields/linked-itilobjects-config.json');
        await form_page.gotoDestinationTab(info.getId());

        // With 2 destinations and no active_destination, none is expanded.
        // Expand the first destination and wait for AJAX content to load.
        const destinations = form_page.getRegion('Form destinations');
        await destinations.getByRole('button', { name: 'First ticket destination' }).click();
        await expect(form_page.getRegion('Title configuration')).toBeVisible();
    });

    // Helper to open the "Associated items" accordion section in the inner accordion
    const openAssociatedItemsAccordion = async () => {
        const accordion = form_page.getRegion('Destination fields accordion');
        await accordion.getByRole('button', { name: 'Associated items' }).click();
        await expect(form_page.getRegion('Link to assistance objects configuration')).toBeVisible();
    };

    // Helper to save destination and re-open the accordion (page reloads with active_destination)
    const saveAndReopenAccordion = async () => {
        await form_page.getButton('Update item').click();
        await expect(
            form_page.getRegion('Destination fields accordion')
                .getByRole('button', { name: 'Associated items' })
        ).toBeVisible();
        await openAssociatedItemsAccordion();
    };

    test('Can use all possible configuration options', async ({ api }) => {
        const unique = randomUUID();
        const entity_id = getWorkerEntityId();

        const ticket_name = `Test ticket - ${unique}`;
        await api.createItem('Ticket', {
            name: ticket_name,
            content: `Content for ticket - ${unique}`,
            entities_id: entity_id,
        });

        await openAssociatedItemsAccordion();

        const config = form_page.getRegion('Link to assistance objects configuration');
        const strategy_dropdown = form_page.getDropdownByLabel('Select the strategy...', config);

        // Default value
        await expect(strategy_dropdown).toHaveText('-----');

        // Link type dropdown should be visible
        await expect(form_page.getDropdownByLabel('Select the link type...', config)).toBeVisible();

        // Hidden dropdowns
        await expect(form_page.getDropdownByLabel('Select assistance object...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select destination...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select questions...', config)).toBeHidden();

        // An other destination of this form
        await form_page.doSetDropdownValue(strategy_dropdown, 'An other destination of this form');
        const destination_dropdown = form_page.getDropdownByLabel('Select destination...', config);
        await form_page.doSetDropdownValue(destination_dropdown, 'Second ticket destination');
        await saveAndReopenAccordion();
        await expect(destination_dropdown).toContainText('Second ticket destination');

        // An existing assistance object
        await form_page.doSetDropdownValue(strategy_dropdown, 'An existing assistance object');
        const type_dropdown = form_page.getDropdownByLabel('Select assistance object type...', config);
        await form_page.doSetDropdownValue(type_dropdown, 'Tickets');
        const object_dropdown = form_page.getDropdownByLabel('Select assistance object...', config);
        await form_page.doSearchAndClickDropdownValue(object_dropdown, ticket_name, false);
        await saveAndReopenAccordion();
        await expect(strategy_dropdown).toHaveText('An existing assistance object');
        await expect(type_dropdown).toHaveText('Tickets');
        await expect(object_dropdown).toContainText(ticket_name);

        // Assistance object from specific questions
        await form_page.doSetDropdownValue(strategy_dropdown, 'Assistance object from specific questions');
        const questions_dropdown = form_page.getDropdownByLabel('Select questions...', config);
        await form_page.doSetDropdownValue(questions_dropdown, 'My Ticket question');
        await saveAndReopenAccordion();
        await expect(strategy_dropdown).toHaveText('Assistance object from specific questions');
        await expect(questions_dropdown).toContainText('My Ticket question');
    });

    test('Can define multiple strategies at once', async ({ api }) => {
        const unique = randomUUID();
        const entity_id = getWorkerEntityId();

        const ticket_name = `Test ticket - ${unique}`;
        await api.createItem('Ticket', {
            name: ticket_name,
            content: `Content for ticket - ${unique}`,
            entities_id: entity_id,
        });

        await openAssociatedItemsAccordion();

        const config = form_page.getRegion('Link to assistance objects configuration');

        // Helper to get the nth strategy dropdown (no data-testid in this field)
        const get_nth_strategy = (n: number) => {
            // eslint-disable-next-line playwright/no-raw-locators
            return config.getByLabel('Select the strategy...').nth(n).locator('+ span').getByRole('combobox');
        };

        // First strategy: An other destination
        const first_strategy = get_nth_strategy(0);
        await form_page.doSetDropdownValue(first_strategy, 'An other destination of this form');
        const destination_dropdown = form_page.getDropdownByLabel('Select destination...', config);
        await form_page.doSetDropdownValue(destination_dropdown, 'Second ticket destination');

        // Add second strategy: An existing assistance object (Ticket)
        await config.getByRole('button', { name: 'Combine with another option' }).click();
        const second_strategy = get_nth_strategy(1);
        await form_page.doSetDropdownValue(second_strategy, 'An existing assistance object');
        const type_dropdown = form_page.getDropdownByLabel('Select assistance object type...', config);
        await form_page.doSetDropdownValue(type_dropdown, 'Tickets');
        const object_dropdown = form_page.getDropdownByLabel('Select assistance object...', config);
        await form_page.doSearchAndClickDropdownValue(object_dropdown, ticket_name, false);

        // Add third strategy: Assistance object from specific questions
        await config.getByRole('button', { name: 'Combine with another option' }).click();
        const third_strategy = get_nth_strategy(2);
        await form_page.doSetDropdownValue(third_strategy, 'Assistance object from specific questions');
        const questions_dropdown = form_page.getDropdownByLabel('Select questions...', config);
        await form_page.doSetDropdownValue(questions_dropdown, 'My Ticket question');

        // Save
        await saveAndReopenAccordion();

        // Verify all 3 strategies
        await expect(get_nth_strategy(0)).toHaveText('An other destination of this form');
        await expect(form_page.getDropdownByLabel('Select destination...', config)).toContainText('Second ticket destination');

        await expect(get_nth_strategy(1)).toHaveText('An existing assistance object');
        await expect(form_page.getDropdownByLabel('Select assistance object type...', config)).toHaveText('Tickets');
        await expect(form_page.getDropdownByLabel('Select assistance object...', config)).toContainText(ticket_name);

        await expect(get_nth_strategy(2)).toHaveText('Assistance object from specific questions');
        await expect(form_page.getDropdownByLabel('Select questions...', config)).toContainText('My Ticket question');
    });
});
