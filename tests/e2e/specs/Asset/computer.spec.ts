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
import {GlpiPage} from "../../pages/GlpiPage";

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

test('Create ITIL objects from computer', async ({ page, profile, api, context }) => {
    await profile.set(Profiles.SuperAdmin);
    const computer_id = await api.createItem('Computer', {
        name: 'Test computer for ITIL',
        entities_id: getWorkerEntityId(),
    });

    const computer_page = new ComputerPage(page);
    await computer_page.goto(computer_id, 'Item_Problem$1');
    const tabpanel = page.getByRole('tabpanel');
    const new_problem_page_event = context.waitForEvent('page');
    await tabpanel.getByRole('button', { name: 'New Problem for this item' }).click();
    const new_problem_page = new GlpiPage(await new_problem_page_event);
    await new_problem_page.page.getByLabel('Title').fill('Test problem');
    await new_problem_page.getRichTextByLabel('Content').fill('Problem content');
    const problem_page_event = context.waitForEvent('page');
    await new_problem_page.page.getByRole('button', { name: 'Add' }).click();
    const problem_page = new GlpiPage(await problem_page_event);
    await expect(problem_page.page.getByRole('heading', { name: 'Test problem' })).toBeVisible();
    await expect(problem_page.page.getByRole('link', { name: 'Test computer for ITIL' })).toBeVisible();

    await computer_page.goto(computer_id, 'Change_Item$1');
    const new_change_page_event = context.waitForEvent('page');
    await tabpanel.getByRole('button', { name: 'New Change for this item' }).click();
    const new_change_page = new GlpiPage(await new_change_page_event);
    await new_change_page.page.getByLabel('Title').fill('Test change');
    await new_change_page.getRichTextByLabel('Content').fill('Change content');
    const change_page_event = context.waitForEvent('page');
    await new_change_page.page.getByRole('button', { name: 'Add' }).click();
    const change_page = new GlpiPage(await change_page_event);
    await expect(change_page.page.getByRole('heading', { name: 'Test change' })).toBeVisible();
    await expect(change_page.page.getByRole('link', { name: 'Test computer for ITIL' })).toBeVisible();

    await computer_page.goto(computer_id, 'Item_Ticket$1');
    const new_ticket_page_event = context.waitForEvent('page');
    await tabpanel.getByRole('button', { name: 'New Ticket for this item' }).click();
    const new_ticket_page = new GlpiPage(await new_ticket_page_event);
    await new_ticket_page.page.getByLabel('Title').fill('Test ticket');
    await new_ticket_page.getRichTextByLabel('Content').fill('Ticket content');
    const ticket_page_event = context.waitForEvent('page');
    await new_ticket_page.page.getByRole('button', { name: 'Add' }).click();
    const ticket_page = new GlpiPage(await ticket_page_event);
    await expect(ticket_page.page.getByRole('heading', { name: 'Test ticket' })).toBeVisible();
    await expect(ticket_page.page.getByRole('link', { name: 'Test computer for ITIL' })).toBeVisible();
});
