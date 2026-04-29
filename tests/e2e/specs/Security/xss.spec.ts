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
import { expect, test } from '../../fixtures/glpi_fixture';
import { EntityPage } from '../../pages/EntityPage';
import { Profiles } from '../../utils/Profiles';

const xss_payload = '<script>throw new Error("XSS");</script>';

test.describe('XSS tests for CRUD and search operations', () => {
    test("Can't inject XSS into an item name", async ({ profile, page }) => {
        await profile.set(Profiles.SuperAdmin);
        const entity_page = new EntityPage(page);
        const name = randomUUID() + xss_payload;

        // Create an entity with an XSS payload in the name
        await entity_page.gotoCreationPage();
        await entity_page.name_input.fill(name);
        await entity_page.add_button.click();

        // Verify the success alert contains the name
        const alert = page.getByRole('alert').filter({ hasText: 'Item successfully added' });
        await expect(alert).toBeAttached();

        // Go to the created entity via the link in the alert
        await alert.getByRole('link').click();

        // Check name is displayed correctly (XSS not executed)
        await expect(entity_page.name_input).toHaveValue(name);

        // Search for the entity and verify the XSS payload is shown as plain text
        await page.goto(
            `/front/entity.php?criteria[0][link]=AND&criteria[0][field]=14&criteria[0][searchtype]=contains&criteria[0][value]=${encodeURIComponent(name)}`
        );
        await expect(page.getByText(name)).toHaveCount(2);
    });
});
