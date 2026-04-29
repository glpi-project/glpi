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

import { test, expect } from '../../fixtures/glpi_fixture';
import { AssetDefinitionPage } from '../../pages/AssetDefinitionPage';
import { Profiles } from '../../utils/Profiles';

const ALLSTANDARDRIGHT = 31;

test('Reordering fields', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    const system_name = AssetDefinitionPage.generateAssetName();
    const definition_id = await api.createItem('Glpi\\Asset\\AssetDefinition', {
        system_name: system_name,
        is_active: true,
        profiles: { [Profiles.SuperAdmin]: ALLSTANDARDRIGHT },
    });

    const asset_page = new AssetDefinitionPage(page);
    await asset_page.goto(definition_id, 'Glpi\\Asset\\AssetDefinition$2');

    // Swap name and status
    const name_field = page.getByTestId('sortable-field-name');
    const states_field = page.getByTestId('sortable-field-states_id');
    await expect(name_field).toBeVisible();
    await expect(states_field).toBeVisible();
    await name_field.dragTo(states_field);
    await asset_page.save_button.click();

    const name_index = await name_field.evaluate(
        (el) => Array.from(el.parentElement!.children).indexOf(el)
    );
    const states_index = await states_field.evaluate(
        (el) => Array.from(el.parentElement!.children).indexOf(el)
    );
    expect(name_index).toBe(2);
    expect(states_index).toBe(1);
});

test('Create custom fields', async ({ page, profile, api }) => {
    test.slow();
    await profile.set(Profiles.SuperAdmin);

    const system_name = AssetDefinitionPage.generateAssetName();
    const definition_id = await api.createItem('Glpi\\Asset\\AssetDefinition', {
        system_name: system_name,
        is_active: true,
    });

    const asset_page = new AssetDefinitionPage(page);
    await asset_page.goto(definition_id, 'Glpi\\Asset\\AssetDefinition$2');

    await asset_page.doCreateField('Test String', 'String');
    await asset_page.doCreateField('Test Text', 'Text');
    await asset_page.doCreateField('Test Number', 'Number', new Map([['min', '10'], ['max', '20'], ['step', '2']]));
    await asset_page.doCreateField('Test Date', 'Date');
    await asset_page.doCreateField('Test Datetime', 'Date and time');
    await asset_page.doCreateField('Test Dropdown', 'Dropdown', new Map([['item_type', 'Monitor']]));
    await asset_page.doCreateField('Test MultiDropdown', 'Dropdown', new Map<string, string | boolean>([['item_type', 'Monitor'], ['multiple_values', true]]));
    await asset_page.doCreateField('Test URL', 'URL');
    await asset_page.doCreateField('Test YesNo', 'Yes/No');
    await asset_page.doCreateField('Test RichText', 'Text', new Map<string, string | boolean>([['enable_richtext', true], ['enable_images', true]]));

    const [response] = await Promise.all([
        page.waitForResponse(
            (resp) => resp.url().includes('/front/asset/assetdefinition.form.php') && resp.request().method() === 'POST'
        ),
        asset_page.save_button.click(),
    ]);
    const request = response.request();
    const post_data = request.postData() ?? '';

    expect(post_data).toContain('_update_fields_display');
    expect(post_data).toContain('custom_test_string');
    expect(post_data).toContain('custom_test_text');
    expect(post_data).toContain('custom_test_number');
    expect(post_data).toContain('custom_test_date');
    expect(post_data).toContain('custom_test_datetime');
    expect(post_data).toContain('custom_test_dropdown');
    expect(post_data).toContain('custom_test_multidropdown');
    expect(post_data).toContain('custom_test_url');
    expect(post_data).toContain('custom_test_yesno');
});

test('Edit core fields', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    // Create an asset definition
    const system_name = AssetDefinitionPage.generateAssetName();
    const definition_id = await api.createItem('Glpi\\Asset\\AssetDefinition', {
        system_name: system_name,
        is_active: true,
    });

    // Go to asset definition
    const asset_page = new AssetDefinitionPage(page);
    await page.route('**/ajax/asset/assetdefinition.php?action=get_all_fields*', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify({ results: [] }),
        });
    }, { times: 1 });
    await asset_page.goto(definition_id, 'Glpi\\Asset\\AssetDefinition$2');

    // Edit a field
    await page.getByTestId('sortable-field-name').hover();
    await page.getByTestId('sortable-field-name').getByTitle('Edit').click();
    const editor = page.getByRole('dialog');
    await expect(editor).toBeVisible();

    await editor.getByLabel('Full width').check();

    const readonly_dropdown = asset_page.getDropdownByLabel('Readonly for these profiles', editor);
    await asset_page.doSetDropdownValue(readonly_dropdown, 'Super-Admin');
    await asset_page.doSetDropdownValue(readonly_dropdown, 'Technician');

    await editor.getByLabel('Mandatory').check();
    await editor.getByRole('button', { name: 'Save' }).click();

    await page.getByTestId('sortable-field-name').hover();
    await page.getByTestId('sortable-field-name').getByTitle('Edit').click();
    await expect(editor).toBeVisible();
    await expect(editor.getByLabel('Full width')).toBeChecked();
    await expect(editor.getByLabel('Mandatory')).toBeChecked();
    const readonly_values = asset_page.getDropdownByLabel('Readonly for these profiles', editor);
    await expect(readonly_values).toContainText('Super-Admin');
    await expect(readonly_values).toContainText('Technician');
});
