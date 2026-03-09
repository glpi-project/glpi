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
import { getWorkerEntityId, getWorkerUserId } from '../../../utils/WorkerEntities';
import { FormPage } from "../../../pages/FormPage";

test.describe('Associated items configuration', () => {
    let form_page: FormPage;

    test.beforeEach(async ({ page, profile, formImporter }) => {
        await profile.set(Profiles.SuperAdmin);
        form_page = new FormPage(page);

        const info = await formImporter.importForm('destination_config_fields/associated-items-config.json');
        await form_page.gotoDestinationTab(info.getId());
    });

    // Helper to open the "Associated items" accordion section
    const openAssociatedItemsAccordion = async () => {
        const accordion = form_page.getRegion('Destination fields accordion');
        await accordion.getByRole('button', { name: 'Associated items' }).click();
        await expect(form_page.getRegion('Associated items configuration')).toBeVisible();
    };

    // Helper to save destination and re-open the accordion section
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
        await api.createItem('Computer', {
            name: `My computer - ${unique}`,
            entities_id: entity_id,
        });

        await openAssociatedItemsAccordion();

        const config = form_page.getRegion('Associated items configuration');
        const items_dropdown = form_page.getStrategyDropdown(config);

        // Default value
        await expect(items_dropdown).toHaveText('Answer to last assets item question');

        // Hidden dropdowns
        await expect(form_page.getDropdownByLabel('Select the itemtype of the item to associate...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select the item to associate...', config)).toBeHidden();
        await expect(form_page.getDropdownByLabel('Select questions...', config)).toBeHidden();

        // Specific items
        await form_page.doSetDropdownValue(items_dropdown, 'Specific items');
        const itemtype_dropdown = form_page.getDropdownByLabel('Select the itemtype of the item to associate...', config);
        await form_page.doSetDropdownValue(itemtype_dropdown, 'Computers');
        const item_dropdown = form_page.getDropdownByLabel('Select the item to associate...', config);
        await form_page.doSearchAndClickDropdownValue(item_dropdown, `My computer - ${unique}`, false);
        await saveAndReopenAccordion();
        await expect(items_dropdown).toHaveText('Specific items');
        await expect(form_page.getDropdownByLabel('Select the itemtype of the item to associate...', config).first()).toHaveText('Computers');
        await expect(item_dropdown).toContainText(`My computer - ${unique}`);

        // Answer from specific questions
        await form_page.doSetDropdownValue(items_dropdown, 'Answer from specific questions');
        const questions_dropdown = form_page.getDropdownByLabel('Select questions...', config);
        await form_page.doSetDropdownValue(questions_dropdown, 'My item question');
        await form_page.doSetDropdownValue(questions_dropdown, 'My user device question');
        await form_page.doSetDropdownValue(questions_dropdown, 'My multiple user device question');
        await saveAndReopenAccordion();
        await expect(items_dropdown).toHaveText('Answer from specific questions');
        await expect(questions_dropdown).toContainText('My item question');
        await expect(questions_dropdown).toContainText('My user device question');
        await expect(questions_dropdown).toContainText('My multiple user device question');

        // All valid "Item" answers
        await form_page.doSetDropdownValue(items_dropdown, 'All valid "Item" answers');
        await saveAndReopenAccordion();
        await expect(items_dropdown).toHaveText('All valid "Item" answers');
    });

    test('Can create ticket using default configuration', async ({ page, api }) => {
        const unique = randomUUID();
        const entity_id = getWorkerEntityId();
        const worker_user_id = getWorkerUserId();
        await api.createItem('Computer', {
            name: `My computer - ${unique}`,
            entities_id: entity_id,
        });
        await api.createItem('Computer', {
            name: `My Assigned computer - ${unique}`,
            users_id: worker_user_id,
            entities_id: entity_id,
        });
        await api.createItem('Monitor', {
            name: `My Assigned monitor - ${unique}`,
            users_id: worker_user_id,
            entities_id: entity_id,
        });

        await page.getByRole('tab', { name: 'Form', exact: true }).click();
        await form_page.doPreviewForm();

        await form_page.doSearchAndClickDropdownValue(
            form_page.getDropdownByLabel('My item question'),
            `My computer - ${unique}`,
            false
        );
        await form_page.doSearchAndClickDropdownValue(
            form_page.getDropdownByLabel('My user device question'),
            `Computers - My Assigned computer - ${unique}`,
            false
        );
        await form_page.doSearchAndClickDropdownValue(
            form_page.getDropdownByLabel('My multiple user device question'),
            `Monitors - My Assigned monitor - ${unique}`,
            false
        );

        await form_page.getButton('Submit').click();
        await page.getByRole('link', { name: 'My test form' }).click();

        const items_region = form_page.getRegion('Items');
        await expect(items_region.getByRole('link', { name: `My Assigned monitor - ${unique}` })).toBeVisible();
    });

    test('Can define multiple specific items', async ({ api }) => {
        const unique = randomUUID();
        const entity_id = getWorkerEntityId();
        await api.createItem('Computer', {
            name: `My computer - ${unique}`,
            entities_id: entity_id,
        });
        await api.createItem('Computer', {
            name: `My second computer - ${unique}`,
            entities_id: entity_id,
        });
        await api.createItem('Monitor', {
            name: `My monitor - ${unique}`,
            entities_id: entity_id,
        });

        await openAssociatedItemsAccordion();

        const config = form_page.getRegion('Associated items configuration');
        const items_dropdown = form_page.getStrategyDropdown(config);
        await form_page.doSetDropdownValue(items_dropdown, 'Specific items');

        // eslint-disable-next-line playwright/no-raw-locators
        const item_rows = config.locator('[data-glpi-associated-items-specific-values-extra-field-item]');

        // First computer
        const first_itemtype = form_page.getDropdownByLabel('Select the itemtype of the item to associate...', item_rows.nth(0));
        await form_page.doSetDropdownValue(first_itemtype, 'Computers');
        const first_item = form_page.getDropdownByLabel('Select the item to associate...', item_rows.nth(0));
        await form_page.doSearchAndClickDropdownValue(first_item, `My computer - ${unique}`, false);

        // Second computer
        const second_itemtype = form_page.getDropdownByLabel('Select the itemtype of the item to associate...', item_rows.nth(1));
        await form_page.doSetDropdownValue(second_itemtype, 'Computers');
        const second_item = form_page.getDropdownByLabel('Select the item to associate...', item_rows.nth(1));
        await form_page.doSearchAndClickDropdownValue(second_item, `My second computer - ${unique}`, false);

        // Monitor
        const third_itemtype = form_page.getDropdownByLabel('Select the itemtype of the item to associate...', item_rows.nth(2));
        await form_page.doSetDropdownValue(third_itemtype, 'Monitors');
        const third_item = form_page.getDropdownByLabel('Select the item to associate...', item_rows.nth(2));
        await form_page.doSearchAndClickDropdownValue(third_item, `My monitor - ${unique}`, false);

        // Save and verify
        await saveAndReopenAccordion();
        await expect(items_dropdown).toHaveText('Specific items');

        await expect(form_page.getDropdownByLabel('Select the itemtype of the item to associate...', item_rows.nth(0))).toHaveText('Computers');
        await expect(form_page.getDropdownByLabel('Select the item to associate...', item_rows.nth(0))).toContainText(`My computer - ${unique}`);
        await expect(form_page.getDropdownByLabel('Select the itemtype of the item to associate...', item_rows.nth(1))).toHaveText('Computers');
        await expect(form_page.getDropdownByLabel('Select the item to associate...', item_rows.nth(1))).toContainText(`My second computer - ${unique}`);
        await expect(form_page.getDropdownByLabel('Select the itemtype of the item to associate...', item_rows.nth(2))).toHaveText('Monitors');
        await expect(form_page.getDropdownByLabel('Select the item to associate...', item_rows.nth(2))).toContainText(`My monitor - ${unique}`);
    });

    test('Can add specific item, combine strategy, then add another specific item', async ({ api }) => {
        const unique = randomUUID();
        const entity_id = getWorkerEntityId();
        const worker_user_id = getWorkerUserId();
        await api.createItem('Computer', {
            name: `My computer - ${unique}`,
            entities_id: entity_id,
        });
        await api.createItem('Monitor', {
            name: `My Assigned monitor - ${unique}`,
            users_id: worker_user_id,
            entities_id: entity_id,
        });

        await openAssociatedItemsAccordion();

        const config = form_page.getRegion('Associated items configuration');
        const items_dropdown = form_page.getStrategyDropdown(config);
        await form_page.doSetDropdownValue(items_dropdown, 'Specific items');

        // eslint-disable-next-line playwright/no-raw-locators
        const item_rows = config.locator('[data-glpi-associated-items-specific-values-extra-field-item]');

        // Associate first computer
        const first_itemtype = form_page.getDropdownByLabel('Select the itemtype of the item to associate...', item_rows.nth(0));
        await form_page.doSetDropdownValue(first_itemtype, 'Computers');
        const first_item = form_page.getDropdownByLabel('Select the item to associate...', item_rows.nth(0));
        await form_page.doSearchAndClickDropdownValue(first_item, `My computer - ${unique}`, false);

        // Add another strategy
        await config.getByRole('button', { name: 'Combine with another option' }).click();
        // eslint-disable-next-line playwright/no-raw-locators
        const strategy_configs = config.locator('[data-glpi-itildestination-field-config]');
        const second_strategy_dropdown = form_page.getDropdownByLabel('Select strategy...', strategy_configs.nth(1));
        await form_page.doSetDropdownValue(second_strategy_dropdown, 'Answer from specific questions');

        // Associate monitor in new specific item row
        const second_itemtype = form_page.getDropdownByLabel('Select the itemtype of the item to associate...', item_rows.nth(1));
        await form_page.doSetDropdownValue(second_itemtype, 'Monitors');
        const second_item = form_page.getDropdownByLabel('Select the item to associate...', item_rows.nth(1));
        await form_page.doSearchAndClickDropdownValue(second_item, `My Assigned monitor - ${unique}`, false);

        await saveAndReopenAccordion();
        await expect(items_dropdown).toHaveText('Specific items');
    });
});
