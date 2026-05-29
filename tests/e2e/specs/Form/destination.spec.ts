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
import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from '../../utils/WorkerEntities';
import { FormPage } from "../../pages/FormPage";

test('Form destination name is loaded and name is preserved on reload', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test destination name - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.gotoDestinationTab(form_id);

    const name_input = page.getByRole('textbox', { name: 'Form destination name' });
    await expect(name_input).toHaveValue('Ticket');

    await name_input.fill('Updated ticket destination name');

    await form.doSaveDestination();

    await expect(name_input).toHaveValue('Updated ticket destination name');
});

test('Can enable or disable auto configuration on supported fields', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test auto config - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.gotoDestinationTab(form_id);

    const title_region = form.getRegion('Title configuration');
    const content_region = form.getRegion('Content configuration');
    const title_field = form.getRichTextByLabel('Title', title_region);
    const content_field = form.getRichTextByLabel('Content', content_region);
    const auto_config_checkbox = page.getByRole('checkbox', { name: 'Auto config' }).first();

    // Title field should not be disabled (does not support auto config)
    await expect(title_field).toBeEditable();

    // Auto config should be checked by default
    await expect(auto_config_checkbox).toBeChecked();

    // Content field should be empty (no questions in form)
    await expect(content_field).toHaveText('');

    // Disable auto config and enter manual content
    await auto_config_checkbox.uncheck();
    const content_field_after = form.getRichTextByLabel('Content', content_region);
    await content_field_after.click();
    await content_field_after.pressSequentially('This field is writable');

    await form.doSaveDestination();

    // Verify manual values are kept after reload
    const content_field_reloaded = form.getRichTextByLabel('Content', content_region);
    await expect(auto_config_checkbox).not.toBeChecked();
    await expect(content_field_reloaded).toHaveText('This field is writable');

    // Re-enable auto config
    await auto_config_checkbox.check();

    await form.doSaveDestination();

    // Verify manual values have been removed
    const content_field_final = form.getRichTextByLabel('Content', content_region);
    await expect(auto_config_checkbox).toBeChecked();
    await expect(content_field_final).toHaveText('');
});

test('Check form destination title default value', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_name = `Test dest title - ${randomUUID()}`;
    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: form_name,
        entities_id: getWorkerEntityId(),
    });
    await form.gotoDestinationTab(form_id);

    const title_region = form.getRegion('Title configuration');
    const title_field = form.getRichTextByLabel('Title', title_region);
    await expect(title_field).toContainText(`Form name: ${form_name}`);
});

test('Can define multiple strategies for the same field', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test multi strategy - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.gotoDestinationTab(form_id);

    await form.doOpenDestinationAccordionItem('Actors');

    const requesters_config = form.getRegion('Requesters configuration');
    const add_strategy_button = requesters_config.getByRole('button', { name: 'Combine with another option' });
    const first_strategy_dropdown = form.getStrategyDropdown(requesters_config);

    // Define first strategy
    await form.doSetDropdownValue(first_strategy_dropdown, 'From template');

    // Add a second strategy
    await add_strategy_button.click();
    const second_strategy_dropdown = page.getByRole('combobox', { name: '-----' });
    await form.doSetDropdownValue(second_strategy_dropdown, 'Specific actors', true, false);
    const actors_dropdown = form.getDropdownByLabel('Select actors...', requesters_config);
    await form.doSetDropdownValue(actors_dropdown, 'glpi', false);

    // Add a third strategy
    await add_strategy_button.click();
    const third_strategy_dropdown = page.getByRole('combobox', { name: '-----' });
    await form.doSetDropdownValue(third_strategy_dropdown, 'Answer to last "Requesters" or "Email" question', true, false);

    // Save
    await form.doSaveDestinationAndReopenAccordion('Actors');

    // Verify strategies are saved
    await expect(requesters_config.getByRole('combobox', { name: 'From template' })).toBeVisible();
    await expect(requesters_config.getByRole('combobox', { name: 'Specific actors' })).toBeVisible();
    await expect(requesters_config.getByRole('listitem', { name: 'glpi' })).toBeVisible();
    await expect(requesters_config.getByRole('combobox', { name: 'Answer to last "Requesters" or "Email" question' })).toBeVisible();

    // Add a fourth strategy
    await requesters_config.getByRole('button', { name: 'Combine with another option' }).click();
    const fourth_strategy_dropdown = page.getByRole('combobox', { name: '-----' });
    await form.doSetDropdownValue(fourth_strategy_dropdown, 'User who filled the form', true, false);

    // Save
    await form.doSaveDestinationAndReopenAccordion('Actors');

    // Verify all strategies are saved
    await expect(requesters_config.getByRole('combobox', { name: 'From template' })).toBeVisible();
    await expect(requesters_config.getByRole('combobox', { name: 'Specific actors' })).toBeVisible();
    await expect(requesters_config.getByRole('listitem', { name: 'glpi' })).toBeVisible();
    await expect(requesters_config.getByRole('combobox', { name: 'Answer to last "Requesters" or "Email" question' })).toBeVisible();
    await expect(requesters_config.getByRole('combobox', { name: 'User who filled the form' })).toBeVisible();
});

test('Can duplicate a form destination', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const form = new FormPage(page);

    const form_id = await api.createItem('Glpi\\Form\\Form', {
        name: `Test dup destination - ${randomUUID()}`,
        entities_id: getWorkerEntityId(),
    });
    await form.gotoDestinationTab(form_id);

    const name_input = page.getByRole('textbox', { name: 'Form destination name' });
    await name_input.fill('Original destination');

    const title_region = form.getRegion('Title configuration');
    const title_field = form.getRichTextByLabel('Title', title_region);
    await title_field.click();
    await title_field.clear();
    await title_field.pressSequentially('Custom title for duplication test');

    await form.doSaveDestination();

    // Duplicate
    await page.getByRole('button', { name: 'Duplicate' }).click();

    await expect(page.getByRole('region', { name: 'Original destination' })).toHaveCount(2);

    await expect(name_input).toHaveValue('Original destination');

    const duplicated_title = form.getRichTextByLabel('Title', form.getRegion('Title configuration'));
    await expect(duplicated_title).toContainText('Custom title for duplication test');

    // Rename the duplicate
    await name_input.fill('Duplicated destination');
    await form.doSaveDestination();

    await expect(name_input).toHaveValue('Duplicated destination');
    await expect(page.getByRole('region', { name: 'Duplicated destination' })).toHaveCount(1);

    // Verify original still exists
    await expect(page.getByRole('region', { name: 'Original destination' })).toHaveCount(1);
    await page.getByRole('region', { name: 'Original destination' }).click();

    await expect(name_input).toHaveValue('Original destination');
    const original_title = form.getRegion('Title configuration');
    await expect(original_title).toContainText('Custom title for duplication test');
});
