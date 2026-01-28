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

import { expect, test } from "../../fixtures/glpi_fixture";
import { KnowbaseItemPage } from "../../pages/KnowbaseItemPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

test('Can configure service catalog settings', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    // Create a KB and go to its page
    const [kb_id] = await Promise.all([
        api.createItem('KnowbaseItem', {
            name: 'My kb entry for service catalog config test',
            entities_id: getWorkerEntityId(),
            answer: "My answer",
        }),
        api.createItem('Glpi\\Form\\Category', {
            'name': "My category",
        }),
    ]);
    await kb.goto(kb_id);

    // Open service catalog panel
    await page.getByTitle('More actions').click();
    await kb.getButton('Service catalog').click();

    // Confirm default state
    await expect(kb.getHeading('Service catalog')).toBeVisible();
    await expect(kb.getCheckbox('Show in service catalog')).not.toBeChecked();
    await expect(kb.getCheckbox('Pin to top of the service catalog')).not.toBeChecked();
    await expect(kb.getRichTextByLabel('Description')).toHaveText("");
    await expect(kb.getDropdownByLabel('Category')).toHaveText("-----");

    // Set values
    await page.getByText('Show in service catalog').click();
    await page.getByText('Pin to top of the service catalog').click();
    await kb.getRichTextByLabel('Description').fill("My description");
    await kb.doSetDropdownValue(kb.getDropdownByLabel('Category'), "My category", false);

    // Save and reload
    await kb.getButton('Save').click();
    await expect(page.getByText('Service catalog settings saved')).toBeVisible();
    await page.reload();

    // Validate values
    await page.getByTitle('More actions').click();
    await kb.getButton('Service catalog').click();

    await expect(kb.getCheckbox('Show in service catalog')).toBeChecked();
    await expect(kb.getCheckbox('Pin to top of the service catalog')).toBeChecked();
    await expect(kb.getRichTextByLabel('Description')).toHaveText("My description");
    await expect(kb.getDropdownByLabel('Category')).toHaveText("My category");
});
