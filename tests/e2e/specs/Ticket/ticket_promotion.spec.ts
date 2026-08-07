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

import { expect, test } from '../../fixtures/glpi_fixture';
import { TicketPage } from '../../pages/TicketPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

const content = 'Ticket content';
const requester_id = 2;
const tech_id = 4;
const project_task_name = 'Project Name';
const category_name = 'CatName';

test('promote followup and change category', async ({ page, api, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_id = getWorkerEntityId();

    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket',
        content: '',
        entities_id: entity_id,
    });
    const followup_id = await api.createItem('ITILFollowup', {
        content,
        items_id: ticket_id,
        itemtype: 'Ticket',
        users_id: requester_id,
    });
    await api.createItem('ITILCategory', {
        name: `${category_name}-${ticket_id}`,
        entities_id: entity_id,
        is_recursive: 1,
    });

    const ticket = new TicketPage(page);
    await page.goto(`/front/ticket.form.php?_promoted_fup_id=${followup_id}`);

    await expect(ticket.getRichTextByLabel('Description')).toContainText(content);
    await expect(page.getByTestId(`actor-entry-requester-User-${requester_id}`)).toBeVisible();

    const category_dropdown = ticket.getDropdownByLabel('Category');
    await ticket.doSearchAndClickDropdownValue(category_dropdown, `${category_name}-${ticket_id}`, false);

    await expect(ticket.getRichTextByLabel('Description')).toContainText(content);
    await expect(page.getByTestId(`actor-entry-requester-User-${requester_id}`)).toBeVisible();
});

test('promote task and change category', async ({ page, api, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_id = getWorkerEntityId();

    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket',
        content: '',
        entities_id: entity_id,
    });
    const group_id = await api.createItem('Group', {
        name: 'Group1',
        entities_id: entity_id,
    });
    const task_id = await api.createItem('TicketTask', {
        content,
        tickets_id: ticket_id,
        users_id: requester_id,
        users_id_tech: tech_id,
        groups_id_tech: group_id,
    });
    await api.createItem('ITILCategory', {
        name: `${category_name}-${ticket_id}`,
        entities_id: entity_id,
        is_recursive: 1,
    });

    const ticket = new TicketPage(page);
    await page.goto(`/front/ticket.form.php?_promoted_task_id=${task_id}`);

    await expect(page.getByTestId(`actor-entry-assign-User-${tech_id}`)).toBeVisible();
    await expect(ticket.getRichTextByLabel('Description')).toContainText(content);
    await expect(page.getByTestId(`actor-entry-requester-User-${requester_id}`)).toBeVisible();
    await expect(page.getByTestId(`actor-entry-assign-Group-${group_id}`)).toBeVisible();

    const category_dropdown = ticket.getDropdownByLabel('Category');
    await ticket.doSearchAndClickDropdownValue(category_dropdown, `${category_name}-${ticket_id}`, false);

    await expect(page.getByTestId(`actor-entry-assign-User-${tech_id}`)).toBeVisible();
    await expect(ticket.getRichTextByLabel('Description')).toContainText(content);
    await expect(page.getByTestId(`actor-entry-requester-User-${requester_id}`)).toBeVisible();
    await expect(page.getByTestId(`actor-entry-assign-Group-${group_id}`)).toBeVisible();
});

test('promote project task and change category', async ({ page, api, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const entity_id = getWorkerEntityId();

    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket',
        content: '',
        entities_id: entity_id,
    });
    await api.createItem('ITILCategory', {
        name: `${category_name}-${ticket_id}`,
        entities_id: entity_id,
        is_recursive: 1,
    });
    const project_id = await api.createItem('Project', {
        content: 'Project',
        entities_id: entity_id,
    });
    const projecttask_id = await api.createItem('ProjectTask', {
        name: project_task_name,
        content,
        projects_id: project_id,
    });

    const ticket = new TicketPage(page);
    await page.goto(`/front/ticket.form.php?_projecttasks_id=${projecttask_id}`);

    await expect(ticket.getTextbox('Title')).toHaveValue(project_task_name);
    await expect(ticket.getRichTextByLabel('Description')).toContainText(content);

    const category_dropdown = ticket.getDropdownByLabel('Category');
    await ticket.doSearchAndClickDropdownValue(category_dropdown, `${category_name}-${ticket_id}`, false);

    await expect(ticket.getTextbox('Title')).toHaveValue(project_task_name);
    await expect(ticket.getRichTextByLabel('Description')).toContainText(content);
});
