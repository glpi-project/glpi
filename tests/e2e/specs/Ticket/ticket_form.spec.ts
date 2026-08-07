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
import { TicketPage } from '../../pages/TicketPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId, getWorkerUserId } from '../../utils/WorkerEntities';

test('TODO List', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket TODO list',
        content: 'Test ticket description',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('ITILFollowup', {
        items_id: ticket_id,
        itemtype: 'Ticket',
        content: 'Test followup',
    });
    await api.createItem('TicketTask', {
        tickets_id: ticket_id,
        content: 'Info task',
        state: 0,
    });
    await api.createItem('TicketTask', {
        tickets_id: ticket_id,
        content: 'Todo task',
        state: 1,
    });
    await api.createItem('TicketTask', {
        tickets_id: ticket_id,
        content: 'Done task',
        state: 2,
    });
    await api.createItem('ITILSolution', {
        items_id: ticket_id,
        itemtype: 'Ticket',
        content: 'Test solution',
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    const content_item = page.getByTestId('timeline-ITILContent');
    const solution_item = page.getByTestId('timeline-ITILSolution');
    const followup_item = page.getByTestId('timeline-ITILFollowup');
    const task_info = page.getByTestId('timeline-TicketTask').filter({ hasText: 'Info task' });
    const task_todo = page.getByTestId('timeline-TicketTask').filter({ hasText: 'Todo task' });
    const task_done = page.getByTestId('timeline-TicketTask').filter({ hasText: 'Done task' });

    await expect(content_item).toBeVisible();
    await expect(solution_item).toBeVisible();
    await expect(followup_item).toBeVisible();
    await expect(task_info).toBeVisible();
    await expect(task_todo).toBeVisible();
    await expect(task_done).toBeVisible();

    await page.getByTestId('view-todo-list').click();

    await expect(content_item).toBeHidden();
    await expect(solution_item).toBeHidden();
    await expect(followup_item).toBeHidden();
    await expect(task_info).toBeHidden();
    await expect(task_todo).toBeVisible();
    await expect(task_done).toBeVisible();

    await page.getByTestId('view-todo-list').click();

    await expect(content_item).toBeVisible();
    await expect(solution_item).toBeVisible();
    await expect(followup_item).toBeVisible();
    await expect(task_info).toBeVisible();
    await expect(task_todo).toBeVisible();
    await expect(task_done).toBeVisible();
});

test('Search for Solution', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test search solution',
        content: 'Test search solution',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('KnowbaseItem', {
        name: 'Test search solution',
        answer: 'Test search solution kb answer',
    });

    const ticket = new TicketPage(page);

    // Test KB search from solution form
    await ticket.goto(ticket_id);
    await ticket.getButton('View other actions').click();
    await page.getByRole('listitem', { name: 'Add a solution' }).click();
    await ticket.getButton('Search in the knowledge base').click();


    // Search for solution modal should be open with one result
    const kb_modal = page.getByRole('dialog', { name: 'Search in the knowledge base' });
    await expect(kb_modal).toBeVisible();
    await expect(kb_modal.getByLabel('Search…')).toHaveValue('Test search solution');
    await expect(kb_modal.getByRole('listitem').first()).toBeVisible();

    // View KB entry
    await kb_modal.getByTitle('Preview').first().click();
    await expect(kb_modal.getByText('Subject')).toBeVisible();
    await expect(kb_modal.getByText('Content', { exact: true })).toBeVisible();
    await expect(kb_modal.getByRole('listitem')).toHaveCount(0);

    // Go back to search and use the KB entry
    await kb_modal.getByRole('button', { name: 'Back to results' }).click();
    await expect(kb_modal.getByRole('listitem').first()).toBeVisible();
    await kb_modal.getByTitle('Use this entry').first().click();

    // Entry should be applied to solution input
    await expect(kb_modal).not.toBeAttached();
    const solution_block = page.getByTestId('new-ITILSolution-block');
    const solution_rich_text = ticket.getRichTextByLabel('Solution', solution_block);
    await expect(solution_rich_text).not.toBeEmpty();

    // Redo the same but for followups instead

    // Test KB search from followup form
    await ticket.goto(ticket_id);
    await ticket.getButton('Answer').click();
    await ticket.getButton('Search in the knowledge base').click();

    // Search for solution modal should be open with one result
    const kb_modal2 = page.getByRole('dialog', { name: 'Search in the knowledge base' });
    await expect(kb_modal2).toBeVisible();
    await expect(kb_modal2.getByLabel('Search…')).toHaveValue('Test search solution');
    await expect(kb_modal2.getByRole('listitem').first()).toBeVisible();

    // View KB entry
    await kb_modal2.getByTitle('Preview').first().click();
    await expect(kb_modal2.getByText('Subject')).toBeVisible();
    await expect(kb_modal2.getByRole('listitem')).toHaveCount(0);

    // Go back to search and use the KB entry
    await kb_modal2.getByRole('button', { name: 'Back to results' }).click();
    await expect(kb_modal2.getByRole('listitem').first()).toBeVisible();
    await kb_modal2.getByTitle('Use this entry').first().click();

    // Entry should be applied to followup input
    await expect(kb_modal2).not.toBeAttached();
    const followup_block = page.getByTestId('new-ITILFollowup-block');
    const followup_rich_text = ticket.getRichTextByLabel('Followup', followup_block);
    await expect(followup_rich_text).not.toBeEmpty();
});

test('Validation step template without validation step set', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const rand = randomUUID();
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket validation step',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    const template_id = await api.createItem('ITILValidationTemplate', {
        name: `test user 2 ${rand}`,
        content: 'test content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('ITILValidationTemplate_Target', {
        itilvalidationtemplates_id: template_id,
        itemtype: 'User',
        items_id: getWorkerUserId(),
        validationsteps_id: 0,
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);
    await ticket.getButton('View other actions').click();
    await page.getByRole('listitem', { name: 'Ask for approval' }).click();

    const validation_block = page.getByTestId('new-TicketValidation-block');
    const template_dropdown = ticket.getDropdownByLabel('Template', validation_block);
    await ticket.doSetDropdownValue(template_dropdown, `test user 2 ${rand}`);

    // User choose a template without validation step set.
    // Selected validation step should not change.
    const step_dropdown = ticket.getDropdownByLabel('Approval step *', validation_block);
    await expect(step_dropdown).toContainText('Approval');
});

test('Validation step template with validation step set', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const rand = randomUUID();
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket validation step set',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    const step_id = await api.createItem('ValidationStep', {
        name: `User Validation step ${rand}`,
        minimal_required_validation_percent: 100,
    });
    await api.createItem('ITILValidationTemplate', {
        name: `template with validation step ${rand}`,
        content: 'template with validation step',
        entities_id: getWorkerEntityId(),
        validationsteps_id: step_id,
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);
    await ticket.getButton('View other actions').click();
    await page.getByRole('listitem', { name: 'Ask for approval' }).click();

    const validation_block = page.getByTestId('new-TicketValidation-block');
    const template_dropdown = ticket.getDropdownByLabel('Template', validation_block);
    await ticket.doSetDropdownValue(template_dropdown, `template with validation step ${rand}`);

    // User choose a template with a validation step set.
    // Selected validation step should change to the template one.
    const step_dropdown = ticket.getDropdownByLabel('Approval step *', validation_block);
    await expect(step_dropdown).toContainText(`User Validation step ${rand}`);
});

test('Switch between validation templates', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const rand = randomUUID();
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket switch validation templates',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });

    const template_user_id = await api.createItem('ITILValidationTemplate', {
        name: `test user 2 ${rand}`,
        content: 'test content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('ITILValidationTemplate_Target', {
        itilvalidationtemplates_id: template_user_id,
        itemtype: 'User',
        items_id: 2,
        validationsteps_id: 0,
    });

    await api.createItem('ITILValidationTemplate', {
        name: `test no approver ${rand}`,
        content: 'no approver test content',
        entities_id: getWorkerEntityId(),
    });

    const group_id = await api.createItem('Group', {
        name: `test group ${rand}`,
        entities_id: getWorkerEntityId(),
    });
    const template_group_id = await api.createItem('ITILValidationTemplate', {
        name: `test validation template with group ${rand}`,
        content: 'test content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('ITILValidationTemplate_Target', {
        itilvalidationtemplates_id: template_group_id,
        itemtype: 'Group',
        items_id: group_id,
    });

    const group_user_id = await api.createItem('Group', {
        name: `test group user ${rand}`,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('Group_User', {
        groups_id: group_user_id,
        users_id: 2,
    });
    const template_group_user_id = await api.createItem('ITILValidationTemplate', {
        name: `test validation template with group user ${rand}`,
        content: 'test content',
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('ITILValidationTemplate_Target', {
        itilvalidationtemplates_id: template_group_user_id,
        itemtype: 'User',
        items_id: 2,
        groups_id: group_user_id,
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);
    await ticket.getButton('View other actions').click();
    await page.getByRole('listitem', { name: 'Ask for approval' }).click();

    const validation_block = page.getByTestId('new-TicketValidation-block');

    // Select user validation template
    await ticket.doSetDropdownValue(
        ticket.getDropdownByLabel('Template', validation_block),
        `test user 2 ${rand}`
    );
    await expect(ticket.getDropdownByLabel('Approver type', validation_block)).toContainText('User');
    await expect(ticket.getDropdownByLabel('Select a user', validation_block)).toContainText('glpi');
    await expect(ticket.getRichTextByLabel('Comment', validation_block)).toContainText('test content');

    // Switch to group validation template
    await ticket.doSetDropdownValue(
        ticket.getDropdownByLabel('Template', validation_block),
        `test validation template with group ${rand}`
    );
    await expect(ticket.getDropdownByLabel('Approver type', validation_block)).toContainText('Group');
    await expect(ticket.getDropdownByLabel('Select a group', validation_block)).toContainText(`test group ${rand}`);
    await expect(ticket.getRichTextByLabel('Comment', validation_block)).toContainText('test content');

    // Switch to group user validation template
    await ticket.doSetDropdownValue(
        ticket.getDropdownByLabel('Template', validation_block),
        `test validation template with group user ${rand}`
    );
    await expect(ticket.getDropdownByLabel('Approver type', validation_block)).toContainText('Group user(s)');
    await expect(ticket.getDropdownByLabel('Select a group', validation_block)).toContainText(`test group user ${rand}`);
    await expect(ticket.getDropdownByLabel('Select users', validation_block)).toContainText('glpi');
    await expect(ticket.getRichTextByLabel('Comment', validation_block)).toContainText('test content');

    // Switch to no approver template
    await ticket.doSetDropdownValue(
        ticket.getDropdownByLabel('Template', validation_block),
        `test no approver ${rand}`
    );
    await expect(ticket.getDropdownByLabel('Approver type', validation_block)).toContainText('-----');
    await expect(ticket.getDropdownByLabel('Select a user', validation_block)).toHaveCount(0);
    await expect(ticket.getDropdownByLabel('Select a group', validation_block)).toHaveCount(0);
    await expect(ticket.getRichTextByLabel('Comment', validation_block)).toContainText('no approver test content');
});

test('Enter key in requester field reloads new ticket form', async ({ profile, page }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket = new TicketPage(page);
    await ticket.gotoCreationPage();

    const response_promise = page.waitForResponse(
        (resp) => resp.url().includes('/front/ticket.form.php') && resp.request().method() === 'POST'
    );

    const requester_dropdown = ticket.getDropdownByLabel('Requester');
    // Click the inline search input directly to avoid hitting the link to the
    // requester's ongoing tickets which sits at the center of the combobox.
    await requester_dropdown.getByRole('searchbox').click();
    await page.keyboard.type('glp');
    await expect(page.getByRole('option', { name: /glpi/i }).first()).toBeVisible();
    await requester_dropdown.press('Enter');

    const response = await response_promise;
    expect(response.status()).toBe(200);
    await expect(page).toHaveURL(/\/front\/ticket\.form\.php$/);
});

test('Costs tab loads', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket costs tab',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id, 'TicketCost$1');

    await expect(ticket.getButton('Add a new cost')).toBeVisible();
    await expect(page.getByRole('tabpanel')).toContainText('No results found');
});

test('Items tab loads', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test ticket items tab',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id, 'Item_Ticket$1');

    await expect(page.getByRole('combobox').first()).toBeVisible();
    await expect(ticket.getButton('Add')).toBeVisible();
    await expect(page.getByRole('tabpanel')).toContainText('No results found');
});

test('Create/update a ticket using a template with readonly fields', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const rand = randomUUID();
    const ticket_template_name = `test template ${rand}`;

    const template_id = await api.createItem('TicketTemplate', {
        name: ticket_template_name,
        entities_id: getWorkerEntityId(),
    });
    await api.createItem('TicketTemplatePredefinedField', {
        tickettemplates_id: template_id,
        num: 10,
        value: '4',
    });
    await api.createItem('TicketTemplateReadonlyField', {
        tickettemplates_id: template_id,
        num: 10,
    });
    await api.createItem('ITILCategory', {
        name: ticket_template_name,
        tickettemplates_id: template_id,
        tickettemplates_id_incident: template_id,
        tickettemplates_id_demand: template_id,
        changetemplates_id: template_id,
        problemtemplates_id: template_id,
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.gotoCreationPage();

    const category_dropdown = ticket.getDropdownByLabel('Category');
    await ticket.doSearchAndClickDropdownValue(category_dropdown, ticket_template_name);

    // Change the urgency value directly (bypassing readonly enforcement in UI)
    await page.evaluate(() => {
        const input = document.querySelector('input[name="urgency"]') as HTMLInputElement;
        if (input) input.value = '1';
    });

    const add_response = page.waitForResponse(
        (resp) => resp.url().includes('/front/ticket.form.php') && resp.request().method() === 'POST'
    );
    await ticket.getButton('Add').click();
    await add_response;

    // Server should have ignored the readonly field change — urgency must be 4
    // eslint-disable-next-line playwright/no-raw-locators -- Input has no accessible label when disabled
    await expect(page.locator('input[name="urgency"]')).toHaveValue('4');

    // Attempt to update the readonly field
    await page.evaluate(() => {
        const input = document.querySelector('input[name="urgency"]') as HTMLInputElement;
        if (input) input.value = '1';
    });

    const save_response = page.waitForResponse(
        (resp) => resp.url().includes('/front/ticket.form.php') && resp.request().method() === 'POST'
    );
    await ticket.getButton('Save').click();
    await save_response;

    // eslint-disable-next-line playwright/no-raw-locators -- Input has no accessible label when disabled
    await expect(page.locator('input[name="urgency"]')).toHaveValue('4');
});

test('Priority recalculates when urgency or impact changes', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test priority recalculation',
        content: 'Test priority recalculation',
        urgency: 3,
        impact: 3,
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    await expect(ticket.getDropdownByLabel('Priority')).toContainText('Medium');

    await ticket.doSetDropdownValue(ticket.getDropdownByLabel('Urgency'), 'High');
    await expect(ticket.getDropdownByLabel('Priority')).toContainText('High');

    await ticket.doSetDropdownValue(ticket.getDropdownByLabel('Impact'), 'Very high');
    await expect(ticket.getDropdownByLabel('Priority')).toContainText('Very high');
});

test('Add followup', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test add followup',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);
    await ticket.getButton('Answer').click();

    const followup_block = page.getByTestId('new-ITILFollowup-block');
    const content = ticket.getRichTextByLabel('Followup', followup_block);
    await content.fill('Test followup content');

    const source_dropdown = ticket.getDropdownByLabel('Source of followup', followup_block);
    await ticket.doSetDropdownValue(source_dropdown, 'Direct');

    await followup_block.getByTestId('private-switch').click();

    await followup_block.getByRole('button', { name: 'Add', exact: true }).click();

    const last_followup = page.getByTestId('timeline-ITILFollowup').last();
    await expect(last_followup.getByText('Test followup content')).toBeVisible();
    await expect(last_followup.getByTitle('Source of followup')).toContainText('Direct');
    await expect(last_followup.getByTestId('private-followup-hint')).toBeVisible();
});

test('Add task', async ({ profile, page, api }) => {
    await profile.set(Profiles.SuperAdmin);
    const ticket_id = await api.createItem('Ticket', {
        name: 'Test add task',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);
    await ticket.getButton('View other actions').click();
    await page.getByRole('listitem', { name: 'Create a task' }).click();

    const task_block = page.getByTestId('new-TicketTask-block');
    const content = ticket.getRichTextByLabel('Task', task_block);
    await content.fill('Test task content');

    await ticket.doSetDropdownValue(
        ticket.getDropdownByLabel('Status', task_block),
        'Done',
        false,
    );

    await task_block.getByTestId('private-switch').click();

    await ticket.doSetDropdownValue(ticket.getDropdownByLabel('Duration', task_block), '1h30');

    await task_block.getByRole('button', { name: 'Add', exact: true }).click();

    const last_task = page.getByTestId('timeline-TicketTask').last();
    await expect(last_task.getByText('Test task content')).toBeVisible();
    await expect(last_task.getByRole('checkbox', { name: 'Done' })).toBeChecked();
    await expect(last_task.getByText('1 hours 30 minutes 0 seconds')).toBeVisible();
});
