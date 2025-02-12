/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import { test, expect } from '../../../fixtures/authenticated';
import { HomePage } from '../../../pages/helpdesk/HomePage';
import { Constants } from '../../../utils/Constants';
import { GlpiApi } from '../../../utils/GlpiApi';
import { randomUUID } from 'crypto';
import { SessionManager } from '../../../utils/SessionManager';

let home_page: HomePage;
const uuid = randomUUID();

test.beforeEach(async ({ page, request }) => {
    // Load helpdesk profile
    const session = new SessionManager(request);
    await session.changeProfile("Self-Service");

    // Load POM object
    home_page = new HomePage(page);
});

test('can search for forms and faq entries', async () => {
    // Arrange some KB entries
    const glpi_api = new GlpiApi();
    [`My faq entry ${uuid}`, `My other faq entry ${uuid}`].forEach(async (entry) => {
        const id = await glpi_api.createItem('KnowbaseItem', {
            name: entry,
            answer: 'my answer',
            is_faq: true,
        });
        await glpi_api.createItem('KnowbaseItem_User', {
            knowbaseitems_id: id,
            users_id: await glpi_api.getCurrentUserId(),
        });
    });

    await home_page.goto();

    // Search for a form
    await home_page.search("Issue");
    await expect(home_page.getSearchResult("Report an issue")).toBeVisible();
    await expect(home_page.getSearchResult("Request a service")).not.toBeAttached();
    await expect(home_page.getSearchResult(`My faq entry ${uuid}`)).not.toBeAttached();
    await expect(home_page.getSearchResult(`My other faq entry ${uuid}`)).not.toBeAttached();

    // Search for a KB entry
    await home_page.search("Other");
    await expect(home_page.getSearchResult(`Report an issue`)).not.toBeAttached();
    await expect(home_page.getSearchResult(`Request a service`)).not.toBeAttached();
    await expect(home_page.getSearchResult(`My faq entry ${uuid}`)).not.toBeAttached();
    await expect(home_page.getSearchResult(`My other faq entry ${uuid}`)).toBeAttached();
});

test('can use tiles', async ( {request} ) => {
    await home_page.goto();

    // Make sure each tiles must lead to a valid page (status code 200)
    for (const tile of await home_page.getTiles().all()) {
        const href = await tile.getAttribute('href');
        await request.get(href, {failOnStatusCode: true});
    }

    // Avoid "Test has no assertions" warning.
    expect(true).toBeTruthy();
});

test('can use tabs', async () => {
    // Create some tickets
    const glpi_api = new GlpiApi();
    [
        {name: `Open ticket 1 ${uuid}`, status: Constants.TICKET_STATUS_NEW},
        {name: `Open ticket 2 ${uuid}`, status: Constants.TICKET_STATUS_NEW},
        {name: `Closed ticket 1 ${uuid}`, status: Constants.TICKET_STATUS_RESOLVED},
    ].forEach(async (ticket: {name: string, status: number}) => {
        await glpi_api.createItem('Ticket', {
            users_id: await glpi_api.getCurrentUserId(),
            name    : ticket.name,
            content : ticket.name,
            status  : ticket.status,
        });
    });

    // Create a reminder
    const next_year = (new Date().getFullYear() + 1);
    const reminder_id = await glpi_api.createItem('Reminder', {
        users_id: await glpi_api.getCurrentUserId(),
        name    : `Public reminder 1 ${uuid}`,
        content : `Public reminder 1 ${uuid}`,
        begin   : '2023-10-01 16:45:11',
        end     : `${next_year}-10-01 16:45:11`,
    });
    await glpi_api.createItem('Reminder_User', {
        users_id    : await glpi_api.getCurrentUserId(),
        reminders_id: reminder_id,
    });

    // Go to page, the default tab should be "opened tickets"
    await home_page.goto();
    await expect(home_page.getLinkToTicket(`Open ticket 1 ${uuid}`)).toBeVisible();
    await expect(home_page.getLinkToTicket(`Open ticket 2 ${uuid}`)).toBeVisible();
    await expect(home_page.getLinkToTicket(`Closed ticket 1 ${uuid}`)).not.toBeAttached();
    await expect(home_page.getLinkToReminder(`Public reminder 1 ${uuid}`)).not.toBeAttached();

    // Before changing tab, validate the columns that are displayed
    await expect(home_page.getTicketListColumnHeaders()).toHaveCount(5);
    await expect(home_page.getTicketListColumnHeader('ID')).toBeVisible();
    await expect(home_page.getTicketListColumnHeader('Title')).toBeVisible();
    await expect(home_page.getTicketListColumnHeader('Status')).toBeVisible();
    await expect(home_page.getTicketListColumnHeader('Last update')).toBeVisible();
    await expect(home_page.getTicketListColumnHeader('Opening date')).toBeVisible();

    // Go to the "closed tickets" tab
    await home_page.goToTab('Solved tickets');
    await expect(home_page.getLinkToTicket(`Open ticket 1 ${uuid}`)).not.toBeAttached();
    await expect(home_page.getLinkToTicket(`Open ticket 2 ${uuid}`)).not.toBeAttached();
    await expect(home_page.getLinkToTicket(`Closed ticket 1 ${uuid}`)).toBeVisible();
    await expect(home_page.getLinkToReminder(`Public reminder 1 ${uuid}`)).not.toBeAttached();

    // Before changing tab, validate the columns that are displayed
    await expect(home_page.getTicketListColumnHeaders()).toHaveCount(5);
    await expect(home_page.getTicketListColumnHeader('ID')).toBeVisible();
    await expect(home_page.getTicketListColumnHeader('Title')).toBeVisible();
    await expect(home_page.getTicketListColumnHeader('Status')).toBeVisible();
    await expect(home_page.getTicketListColumnHeader('Last update')).toBeVisible();
    await expect(home_page.getTicketListColumnHeader('Opening date')).toBeVisible();

    // Go to the "reminder feed" tab
    await home_page.goToTab('Reminders');
    await expect(home_page.getLinkToTicket(`Open ticket 1 ${uuid}`)).not.toBeAttached();
    await expect(home_page.getLinkToTicket(`Open ticket 2 ${uuid}`)).not.toBeAttached();
    await expect(home_page.getLinkToTicket(`Closed ticket 1 ${uuid}`)).not.toBeAttached();
    await expect(home_page.getLinkToReminder(`Public reminder 1 ${uuid}`)).toBeVisible();

    // RSS feeds are not tested as they are only displayed if a real feed
    // is configurated. Since the query to the feed is done on the backend,
    // we can't mock it here.
    // Could be added if we don't mind relying on a real outside feeds for
    // ours tests or if we setup a dedicated container for this.
});

