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
import { PrinterPage } from '../../pages/PrinterPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Can view pages counter', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const printer_id = await api.createItem('Printer', {
        name: 'Test printer',
        entities_id: getWorkerEntityId(),
    });

    const printer_page = new PrinterPage(page);
    await printer_page.goto(printer_id, 'PrinterLog$0');
    // eslint-disable-next-line playwright/no-raw-locators
    await expect(page.getByTestId('pages_barchart').locator('canvas')).toBeAttached();
});
