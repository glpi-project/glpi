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

test('Can open the knowledge base modal after adding a followup', async ({
    profile,
    page,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);

    // Arrange: create a ticket
    const ticket_id = await api.createItem('Ticket', {
        name: 'Ticket with a followup to save into the knowledge base',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    // Act: add a followup with the "Save and add to the knowledge base" option on.
    await ticket.getButton('Answer').click();
    const followup_block = page.getByTestId('new-ITILFollowup-block');

    const content = await ticket.initRichTextByLabel('Followup', followup_block);
    await content.click();
    await page.keyboard.type('This followup should be saved as an article.');

    await followup_block.getByRole('checkbox', {
        name: 'Save and add to the knowledge base',
        exact: true,
    }).check();
    await ticket.getButton('Add').click();

    // Assert: the knowledge base form is displayed in an iframe modal.
    const modal = page.getByRole('dialog').filter({
        has: page.getByRole('heading', {
            name: 'Save and add to the knowledge base',
        }),
    });
    await expect(modal).toBeVisible();

    // Validate iframe size.
    const iframe = modal.getByTestId('modal-iframe');
    await expect(async () => {
        const box = await iframe.boundingBox();
        expect(box?.height ?? 0).toBeGreaterThan(300);
    }).toPass();

    // Validate that the article is initialized from the ticket and its followup.
    const modal_frame = iframe.contentFrame();
    await expect(modal_frame.getByTestId('subject')).toHaveText(
        'Ticket with a followup to save into the knowledge base'
    );
    const editor = modal_frame.getByTestId('content').getByRole('textbox');
    await expect(editor).toContainText(
        'This followup should be saved as an article.'
    );
    await expect(editor).toBeVisible();

    // Validate that the article form is usable from within the modal.
    const subject = modal_frame.getByTestId('subject');
    await subject.click();
    await subject.fill('');
    await page.keyboard.type('Article created from a followup');

    await editor.click();
    await page.keyboard.type('This is the content of the new article.');

    // Submit the form and confirm the results.
    await modal_frame.getByRole('button', { name: 'Add article' }).click();
    await expect(
        modal_frame
            .getByRole('alert')
            .filter({ hasText: 'Item successfully added' })
    ).toBeVisible();

    await expect(modal_frame.getByTestId('subject')).toHaveText(
        'Article created from a followup'
    );
    await expect(modal_frame.getByTestId('content')).toContainText(
        'This is the content of the new article.'
    );
});

test('Can open the knowledge base modal after adding a task', async ({
    profile,
    page,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);

    // Arrange: create a ticket
    const ticket_id = await api.createItem('Ticket', {
        name: 'Ticket with a task to save into the knowledge base',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    // Act: add a task with the "Save and add to the knowledge base" option on.
    await ticket.getButton('View other actions').click();
    await page.getByRole('listitem', { name: 'Create a task' }).click();
    const task_block = page.getByTestId('new-TicketTask-block');

    const content = await ticket.initRichTextByLabel('Task', task_block);
    await content.click();
    await page.keyboard.type('This task should be saved as an article.');

    await task_block.getByRole('checkbox', {
        name: 'Save and add to the knowledge base',
        exact: true,
    }).check();
    await task_block.getByRole('button', { name: 'Add', exact: true }).click();

    // Assert: the knowledge base form is displayed in an iframe modal.
    const modal = page.getByRole('dialog').filter({
        has: page.getByRole('heading', {
            name: 'Save and add to the knowledge base',
        }),
    });
    await expect(modal).toBeVisible();

    // Validate iframe size.
    const iframe = modal.getByTestId('modal-iframe');
    await expect(async () => {
        const box = await iframe.boundingBox();
        expect(box?.height ?? 0).toBeGreaterThan(300);
    }).toPass();

    // Validate that the article is initialized from the ticket and its task.
    const modal_frame = iframe.contentFrame();
    await expect(modal_frame.getByTestId('subject')).toHaveText(
        'Ticket with a task to save into the knowledge base'
    );
    const editor = modal_frame.getByTestId('content').getByRole('textbox');
    await expect(editor).toContainText(
        'This task should be saved as an article.'
    );
    await expect(editor).toBeVisible();

    // Validate that the article form is usable from within the modal.
    const subject = modal_frame.getByTestId('subject');
    await subject.click();
    await subject.fill('');
    await page.keyboard.type('Article created from a task');

    await editor.click();
    await page.keyboard.type('This is the content of the new article.');

    // Submit the form and confirm the results.
    await modal_frame.getByRole('button', { name: 'Add article' }).click();
    await expect(
        modal_frame
            .getByRole('alert')
            .filter({ hasText: 'Item successfully added' })
    ).toBeVisible();

    await expect(modal_frame.getByTestId('subject')).toHaveText(
        'Article created from a task'
    );
    await expect(modal_frame.getByTestId('content')).toContainText(
        'This is the content of the new article.'
    );
});

test('Can open the knowledge base modal after adding a solution', async ({
    profile,
    page,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);

    // Arrange: create a ticket
    const ticket_id = await api.createItem('Ticket', {
        name: 'Ticket with a solution to save into the knowledge base',
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    // Act: add a solution with the "Save and add to the knowledge base" option on.
    await ticket.getButton('View other actions').click();
    await page.getByRole('listitem', { name: 'Add a solution' }).click();
    const solution_block = page.getByTestId('new-ITILSolution-block');

    const content = await ticket.initRichTextByLabel('Solution', solution_block);
    await content.click();
    await page.keyboard.type('This solution should be saved as an article.');

    await solution_block.getByRole('checkbox', {
        name: 'Save and add to the knowledge base',
        exact: true,
    }).check();
    await solution_block.getByRole('button', { name: 'Add', exact: true }).click();

    // Assert: the knowledge base form is displayed in an iframe modal.
    const modal = page.getByRole('dialog').filter({
        has: page.getByRole('heading', {
            name: 'Save and add to the knowledge base',
        }),
    });
    await expect(modal).toBeVisible();

    // Validate iframe size.
    const iframe = modal.getByTestId('modal-iframe');
    await expect(async () => {
        const box = await iframe.boundingBox();
        expect(box?.height ?? 0).toBeGreaterThan(300);
    }).toPass();

    // Validate that the article is initialized from the ticket and its solution.
    const modal_frame = iframe.contentFrame();
    await expect(modal_frame.getByTestId('subject')).toHaveText(
        'Ticket with a solution to save into the knowledge base'
    );
    const editor = modal_frame.getByTestId('content').getByRole('textbox');
    await expect(editor).toContainText(
        'This solution should be saved as an article.'
    );
    await expect(editor).toBeVisible();

    // Validate that the article form is usable from within the modal.
    const subject = modal_frame.getByTestId('subject');
    await subject.click();
    await subject.fill('');
    await page.keyboard.type('Article created from a solution');

    await editor.click();
    await page.keyboard.type('This is the content of the new article.');

    // Submit the form and confirm the results.
    await modal_frame.getByRole('button', { name: 'Add article' }).click();
    await expect(
        modal_frame
            .getByRole('alert')
            .filter({ hasText: 'Item successfully added' })
    ).toBeVisible();

    await expect(modal_frame.getByTestId('subject')).toHaveText(
        'Article created from a solution'
    );
    await expect(modal_frame.getByTestId('content')).toContainText(
        'This is the content of the new article.'
    );
});
