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

import { randomUUID } from 'crypto';
import { test, expect } from '../../fixtures/glpi_fixture';
import { Select2Page } from '../../pages/Select2Page';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe('AJAX dropdown with fewer results than the search field threshold', () => {
    test.afterEach(async ({ entity, api }) => {
        await entity.resetToDefaultWorkerEntity();
        api.refreshSession();
    });

    test('options are announced while navigating with the keyboard', async ({ page, profile, entity, api }) => {
        await profile.set(Profiles.SuperAdmin);

        // Use a dedicated entity so that the number of locations does not depend on other tests
        const entity_id = await api.createItem('Entity', {
            name: `Entity ${randomUUID()}`,
            entities_id: getWorkerEntityId(),
        });
        api.refreshSession();
        for (const name of ['Location A', 'Location B']) {
            await api.createItem('Location', { name: name, entities_id: entity_id, is_recursive: 0 });
        }
        await entity.switchToWithoutRecursion(entity_id);
        api.refreshSession();

        const select2 = new Select2Page(page);
        await page.goto('/front/computer.form.php');

        const dropdown = select2.getDropdownByLabel('Location');
        await select2.doOpenDropdownWithKeyboard(dropdown);
        await expect(select2.options).toHaveText(['-----', /Location A/, /Location B/]);

        // The search field gets hidden: the focus must be given back to the selection
        await expect(select2.dropdown_search_field).toBeHidden();
        await expect(dropdown).toBeFocused();

        const first_id = await select2.assertHighlightedOptionIsAnnounced();
        await select2.doHighlightNextOption();
        await select2.assertHighlightedOptionIsAnnounced(first_id);
    });
});

test('options of an AJAX dropdown are announced while searching with the keyboard', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const select2 = new Select2Page(page);
    await page.goto('/front/computer.form.php');

    const dropdown = select2.getDropdownByLabel('Technician in charge');
    await select2.doOpenDropdownWithKeyboard(dropdown);
    await page.keyboard.type('E2E worker');
    await expect(page.getByRole('option', { name: 'E2E worker account 02' })).toBeVisible();
    await expect(select2.dropdown_search_field).toBeFocused();

    const first_id = await select2.assertHighlightedOptionIsAnnounced();
    await select2.doHighlightNextOption();
    await select2.assertHighlightedOptionIsAnnounced(first_id);
});
