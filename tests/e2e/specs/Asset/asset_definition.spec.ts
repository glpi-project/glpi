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

test('Show translations form', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const asset_page = new AssetDefinitionPage(page);
    await asset_page.gotoCreationForm();

    // Add an asset type
    const asset_name = AssetDefinitionPage.generateAssetName();
    await asset_page.system_name_input.fill(asset_name);
    await asset_page.doSetDropdownValue(asset_page.active_dropdown, 'Yes');
    await asset_page.add_button.click();
    await expect(page).toHaveURL(/\/front\/asset\/assetdefinition\.form\.php\?id=/);

    // Go to translation tab, there should be no translations
    await asset_page.getTab('Translations').click();
    const tabpanel = page.getByRole('tabpanel');
    await expect(tabpanel).toContainText('No translation has been added yet');

    // Add a translation
    await tabpanel.getByRole('button', { name: "New translation" }).click();
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('heading')).toContainText('Add translation');
    await expect(dialog.getByLabel('Asset name / Field')).toBeVisible();
    await expect(dialog.getByLabel('Language')).toBeVisible();
    await expect(dialog.getByLabel('one')).not.toBeAttached();
    const language_dropdown = asset_page.getDropdownByLabel('Language', dialog);
    await asset_page.doSetDropdownValue(language_dropdown, 'Français');
    await expect(dialog.getByLabel('one')).toBeVisible();
});

test('Profiles tab', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    const system_name = `test${Math.random().toString(36).replace(/[^a-z0-9_]+/g, '')}`;
    const definition_id = await api.createItem('Glpi\\Asset\\AssetDefinition', {
        system_name: system_name,
        is_active: true,
    });

    const asset_page = new AssetDefinitionPage(page);
    await asset_page.goto(definition_id, 'Glpi\\Asset\\AssetDefinition$3');
    await expect(
        page.getByLabel(`Profiles that can associate ${system_name} with tickets, problems or changes`)
    ).toBeVisible();
});
