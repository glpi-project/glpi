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
import { randomUUID } from "crypto";

test('Can link an item to a knowledge base article', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const kb = new KnowbaseItemPage(page);

    const computer_name = `Computer - ${randomUUID()}`;
    await api.createItem('Computer', {
        name: computer_name,
        entities_id: getWorkerEntityId(),
    });

    const kb_id = await api.createItem('KnowbaseItem', {
        name: 'KB article for link test',
        entities_id: getWorkerEntityId(),
        answer: 'Test content',
    });

    await kb.goto(kb_id);

    // Counter should start at 0
    const related_items_tab = page.getByRole('tab', { name: /Related items/ });
    await expect(related_items_tab).toContainText('0');

    // Open the Related items tab and click link button
    await related_items_tab.click();
    await page.getByRole('button', { name: /Link to another item/ }).click();

    // Modal should open with proper content
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible();
    await expect(modal.getByText('Link to another item')).toBeVisible();

    // Select "Computers" in the itemtype dropdown
    const itemtype_dropdown = modal.getByRole('combobox').first();
    await kb.doSetDropdownValue(itemtype_dropdown, 'Computers');

    // Select the computer in the items dropdown
    const items_dropdown = modal.getByRole('combobox').nth(1);
    await kb.doSetDropdownValue(items_dropdown, computer_name, false);

    // Submit the form
    await modal.getByRole('button', { name: 'Add' }).click();

    // Counter should be updated to 1
    const updated_tab = page.getByRole('tab', { name: /Related items/ });
    await expect(updated_tab).toContainText('1');

    // Switch to the Related items tab and check the chip is visible
    await updated_tab.click();
    await expect(page.getByTestId('related-item-chip').filter({ hasText: computer_name })).toBeVisible();
});
