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

test("preserves user's pending reason when applying template without pending reason", async ({
    profile,
    page,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);

    const unique_id = Date.now();
    const ticket_id = await api.createItem('Ticket', {
        name: `Test ticket for followup templates ${unique_id}`,
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    const empty_template_name = `Empty Template ${unique_id}`;
    await api.createItem('ITILFollowupTemplate', {
        name: empty_template_name,
        content: '',
        entities_id: getWorkerEntityId(),
        is_recursive: 1,
    });
    const pending_reason_name = `Test Pending Reason ${unique_id}`;
    await api.createItem('PendingReason', {
        name: pending_reason_name,
        comment: 'For e2e testing',
        entities_id: getWorkerEntityId(),
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    await ticket.getButton('Answer').click();

    const followup_block = page.getByTestId('new-ITILFollowup-block');
    const pending_checkbox = followup_block.getByTestId('pending-checkbox');

    await pending_checkbox.click();

    const pending_dropdown = ticket.getDropdownByLabel('Pending reason', followup_block);
    await expect(pending_dropdown).toBeVisible();

    await ticket.doSearchAndClickDropdownValue(pending_dropdown, pending_reason_name);
    await expect(pending_dropdown).toContainText(pending_reason_name);

    const template_dropdown = ticket.getDropdownByLabel('Followup template', followup_block);
    const template_response = page.waitForResponse('**/ajax/itilfollowup.php');
    await ticket.doSearchAndClickDropdownValue(template_dropdown, empty_template_name);
    await template_response;

    await expect(pending_dropdown).toContainText(pending_reason_name);
    await expect(pending_checkbox).toBeChecked();
});

test("preserves user's content when applying template without content", async ({
    profile,
    page,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);

    const unique_id = Date.now();
    const ticket_id = await api.createItem('Ticket', {
        name: `Test ticket for followup templates ${unique_id}`,
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    const empty_template_name = `Empty Template ${unique_id}`;
    await api.createItem('ITILFollowupTemplate', {
        name: empty_template_name,
        content: '',
        entities_id: getWorkerEntityId(),
        is_recursive: 1,
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    await ticket.getButton('Answer').click();

    const followup_block = page.getByTestId('new-ITILFollowup-block');
    const rich_text = ticket.getRichTextByLabel('Followup', followup_block);

    const user_content = 'User typed content';
    await rich_text.fill(user_content);
    await expect(rich_text).toContainText(user_content);

    const template_dropdown = ticket.getDropdownByLabel('Followup template', followup_block);
    const template_response = page.waitForResponse('**/ajax/itilfollowup.php');
    await ticket.doSearchAndClickDropdownValue(template_dropdown, empty_template_name);
    await template_response;

    await expect(rich_text).toContainText(user_content);
});

test("replaces user's content when applying template with content", async ({
    profile,
    page,
    api,
}) => {
    await profile.set(Profiles.SuperAdmin);

    const unique_id = Date.now();
    const ticket_id = await api.createItem('Ticket', {
        name: `Test ticket for followup templates ${unique_id}`,
        content: 'Test ticket',
        entities_id: getWorkerEntityId(),
    });
    const with_content_template_name = `Template with Content ${unique_id}`;
    await api.createItem('ITILFollowupTemplate', {
        name: with_content_template_name,
        content: '<p>Template test content</p>',
        entities_id: getWorkerEntityId(),
        is_recursive: 1,
    });

    const ticket = new TicketPage(page);
    await ticket.goto(ticket_id);

    await ticket.getButton('Answer').click();

    const followup_block = page.getByTestId('new-ITILFollowup-block');
    const rich_text = ticket.getRichTextByLabel('Followup', followup_block);

    const user_content = 'User initial content';
    await rich_text.fill(user_content);
    await expect(rich_text).toContainText(user_content);

    const template_dropdown = ticket.getDropdownByLabel('Followup template', followup_block);
    const template_response = page.waitForResponse('**/ajax/itilfollowup.php');
    await ticket.doSearchAndClickDropdownValue(template_dropdown, with_content_template_name);
    await template_response;

    const template_content = 'Template test content';
    await expect(rich_text).toContainText(template_content);
    await expect(rich_text).not.toContainText(user_content);
});
