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
import { ComputerPage } from '../../pages/ComputerPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Main form loads', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const computer_id = await api.createItem('Computer', {
        name: 'Test computer',
        entities_id: getWorkerEntityId(),
    });

    const computer_page = new ComputerPage(page);
    await computer_page.goto(computer_id);

    const tabpanel = page.getByRole('tabpanel');
    await expect(tabpanel.getByRole('textbox', {
        name: 'Name',
        exact: true,
    })).toHaveValue('Test computer');
    await expect(tabpanel.getByRole('button', { name: 'Put in trashbin' })).toBeVisible();
    await expect(tabpanel.getByRole('button', { name: 'Save' })).toBeVisible();
});

test('Virtual machine tab loads', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const computer_id = await api.createItem('Computer', {
        name: 'Test computer VM',
        entities_id: getWorkerEntityId(),
    });

    const computer_page = new ComputerPage(page);
    await computer_page.goto(computer_id, 'ItemVirtualMachine$1');

    const tabpanel = page.getByRole('tabpanel');
    await expect(tabpanel.getByRole('cell')).toContainText('No results found');

    await tabpanel.getByRole('button', { name: 'Add a virtual machine' }).click();
    await tabpanel.getByRole('textbox', { name: 'Name', exact: true }).fill('Test VM');
    await tabpanel.getByRole('button', { name: 'Add', exact: true }).click();
    await expect(tabpanel.getByRole('cell', { name: 'Test VM' })).toBeVisible();
});

test('Antivirus tab loads', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const computer_id = await api.createItem('Computer', {
        name: 'Test computer AV',
        entities_id: getWorkerEntityId(),
    });

    const computer_page = new ComputerPage(page);
    await computer_page.goto(computer_id, 'ItemAntivirus$1');

    const tabpanel = page.getByRole('tabpanel');
    await expect(tabpanel.getByRole('cell')).toContainText('No results found');

    await tabpanel.getByRole('button', { name: 'Add an antivirus' }).click();
    await tabpanel.getByRole('textbox', { name: 'Name', exact: true }).fill('Test AV');
    await tabpanel.getByRole('button', { name: 'Add', exact: true }).click();
    await expect(tabpanel.getByRole('cell', { name: 'Test AV' })).toBeVisible();
});
