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
import { Locator } from '@playwright/test';
import { randomUUID } from 'crypto';
import { expect, test } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';
import {
    getWorkerEntityId,
    getWorkerEntityName,
} from '../../utils/WorkerEntities';

test.describe('Budget', () => {
    const getWorkerEntityCompleteName = (): string => {
        return `Root entity > E2E tests entity > ${getWorkerEntityName()}`;
    };

    const assertRowCells = async (
        table: Locator,
        row_index: number,
        expected: Record<string, string>,
    ): Promise<void> => {
        const header_cells = table.getByRole('columnheader');

        // The table is loaded asynchronously, wait for it before reading its
        // headers (`allTextContents()` does not auto retry).
        await expect(header_cells.first()).toBeAttached();

        // `allTextContents()` and not `allInnerTexts()`: the headers are
        // uppercased through CSS, which the rendered text would reflect.
        const headers = (await header_cells.allTextContents())
            .map((header) => header.trim())
        ;
        const cells = table.getByRole('row').nth(row_index).getByRole('cell');

        for (const [header, value] of Object.entries(expected)) {
            const index = headers.indexOf(header);
            expect(index, `Column "${header}" not found`)
                .toBeGreaterThanOrEqual(0)
            ;
            await expect(cells.nth(index)).toContainText(value);
        }
    };

    test('Item list display', async ({ page, profile, api }) => {
        await profile.set(Profiles.SuperAdmin);

        const uuid = randomUUID();
        const computer_name = `Computer for budget ${uuid}`;
        const gpu_name = `Graphic card for budget ${uuid}`;

        // Set `entities_id` explicitly.
        const budget = await api.createItem('Budget', {
            name: `Budget for E2E test ${uuid}`,
            entities_id: getWorkerEntityId(),
        });
        const computer = await api.createItem('Computer', {
            name: computer_name,
            entities_id: getWorkerEntityId(),
        });
        const gpu = await api.createItem('DeviceGraphicCard', {
            designation: gpu_name,
            entities_id: getWorkerEntityId(),
        });

        const gpu_item = await api.createItem('Item_DeviceGraphicCard', {
            itemtype: 'Computer',
            items_id: computer,
            devicegraphiccards_id: gpu,
        });

        await api.createItem('Infocom', {
            itemtype: 'Computer',
            items_id: computer,
            budgets_id: budget,
        });
        await api.createItem('Infocom', {
            itemtype: 'Item_DeviceGraphicCard',
            items_id: gpu_item,
            budgets_id: budget,
        });

        await page.goto(`/front/budget.form.php?id=${budget}`);
        await page.getByRole('tab', { name: /Items/i }).click();

        const table = page.getByRole('table').filter({
            has: page.getByRole('columnheader', { name: /Type/i }),
        });

        await assertRowCells(table, 1, {
            'Type': 'Computer',
            'Entity': getWorkerEntityCompleteName(),
            'Name': computer_name,
            'Serial number': '-',
            'Inventory number': '-',
            'Value': '0.00',
        });
        await assertRowCells(table, 2, {
            'Type': 'Graphics card item',
            'Entity': getWorkerEntityCompleteName(),
            'Name': gpu_name,
            'Serial number': '-',
            'Inventory number': '-',
            'Value': '0.00',
        });
    });
});
