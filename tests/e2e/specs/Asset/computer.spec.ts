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
import { GlpiPage } from '../../pages/GlpiPage';

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

    const add_button = tabpanel.getByRole('button', { name: 'Add a virtual machine' });
    await add_button.click();
    await tabpanel.getByRole('textbox', { name: 'Name', exact: true }).fill('Test VM');
    await expect(add_button).toBeHidden();
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

    const add_button = tabpanel.getByRole('button', { name: 'Add an antivirus' });
    await add_button.click();
    await tabpanel.getByRole('textbox', { name: 'Name', exact: true }).fill('Test AV');
    await expect(add_button).toBeHidden();
    await tabpanel.getByRole('button', { name: 'Add', exact: true }).click();
    await expect(tabpanel.getByRole('cell', { name: 'Test AV' })).toBeVisible();
});

test('Create ITIL objects from computer', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const computer_id = await api.createItem('Computer', {
        name: 'Test computer for ITIL',
        entities_id: getWorkerEntityId(),
    });

    const computer_page = new ComputerPage(page);
    const glpi_page = new GlpiPage(page);

    // Create a Problem from the computer's Problem tab
    await computer_page.goto(computer_id, 'Item_Problem$1');
    await page.getByRole('tabpanel').getByText('New Problem for this item').click();
    await page.getByLabel('Title').fill('Test problem');
    await glpi_page.getRichTextByLabel('Description').fill('Problem content');
    await page.getByRole('button', { name: 'Add', exact: true }).click();
    await page.waitForLoadState('load');
    await computer_page.goto(computer_id, 'Item_Problem$1');
    await expect(page.getByRole('tabpanel').getByRole('link', { name: 'Test problem' })).toBeVisible();

    // Create a Change from the computer's Change tab
    await computer_page.goto(computer_id, 'Change_Item$1');
    await page.getByRole('tabpanel').getByText('New Change for this item').click();
    await page.getByLabel('Title').fill('Test change');
    await glpi_page.getRichTextByLabel('Description').fill('Change content');
    await page.getByRole('button', { name: 'Add', exact: true }).click();
    await page.waitForLoadState('load');
    await computer_page.goto(computer_id, 'Change_Item$1');
    await expect(page.getByRole('tabpanel').getByRole('link', { name: 'Test change' })).toBeVisible();

    // Create a Ticket from the computer's Ticket tab
    await computer_page.goto(computer_id, 'Item_Ticket$1');
    await page.getByRole('tabpanel').getByText('New Ticket for this item').click();
    await page.getByLabel('Title').fill('Test ticket');
    await glpi_page.getRichTextByLabel('Description').fill('Ticket content');
    await page.getByRole('button', { name: 'Add', exact: true }).click();
    await page.waitForLoadState('load');
    await computer_page.goto(computer_id, 'Item_Ticket$1');
    await expect(page.getByRole('tabpanel').getByRole('link', { name: 'Test ticket' })).toBeVisible();
});
