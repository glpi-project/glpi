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
import { Profiles } from '../../utils/Profiles';
import {
    getWorkerEntityId,
    getWorkerUserId,
} from '../../utils/WorkerEntities';

test.describe('Helpdesk home page', () => {
    test('can search for forms and faq entries', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SelfService);

        const unique_id = randomUUID();
        const knowbaseitems_id = await api.createItem('KnowbaseItem', {
            name: `FAQ: ${unique_id}`,
            answer: 'my answer',
            is_faq: true,
            entities_id: getWorkerEntityId(),
        });
        await api.createItem('KnowbaseItem_User', {
            knowbaseitems_id: knowbaseitems_id,
            users_id: getWorkerUserId(),
        });

        await page.goto('/Helpdesk');

        const search_input = page.getByPlaceholder(
            "Search for knowledge base entries or forms"
        );
        const search_results = page.getByRole('region', {
            name: "Search results",
        });

        // Search for a form
        await search_input.fill("Issue");
        await expect(
            search_results.getByRole('link', { name: "Report an issue" })
        ).toBeAttached();
        await expect(
            search_results.getByRole('link', { name: `FAQ: ${unique_id}` })
        ).not.toBeAttached();

        // Search for a faq entry
        await search_input.fill(unique_id);
        await expect(
            search_results.getByRole('link', { name: `FAQ: ${unique_id}` })
        ).toBeAttached();
        await expect(
            search_results.getByRole('link', { name: "Report an issue" })
        ).not.toBeAttached();
    });

    test('can use tiles', async ({ page, profile }) => {
        await profile.set(Profiles.SelfService);

        await page.goto('/Helpdesk');
        const tiles = page.getByRole('region', { name: 'Quick Access' })
            .getByRole('link')
        ;

        // Asserting that some tiles exist first prevents the loop below from
        // silently doing nothing.
        await expect(tiles).not.toHaveCount(0);

        // Each links must lead to a valid page (status code 200)
        for (const tile of await tiles.all()) {
            const href = await tile.getAttribute('href');
            expect(href).not.toBeNull();
            const response = await page.request.get(href as string);
            expect(response.ok()).toBe(true);
        }
    });

    test('can use tabs', async ({ page, profile, api }) => {
        await profile.set(Profiles.SelfService);

        const next_year = (new Date().getFullYear() + 1);
        const unique_id = randomUUID();
        const open_ticket_1 = `Open ticket 1 ${unique_id}`;
        const open_ticket_2 = `Open ticket 2 ${unique_id}`;
        const closed_ticket_1 = `Closed ticket 1 ${unique_id}`;
        const reminder_1 = `Public reminder 1 ${unique_id}`;

        // Create test data set
        await api.createItem('Ticket', {
            '_users_id_requester': getWorkerUserId(),
            'name': open_ticket_1,
            'content': open_ticket_1,
            'entities_id': getWorkerEntityId(),
        });
        await api.createItem('Ticket', {
            '_users_id_requester': getWorkerUserId(),
            'name': open_ticket_2,
            'content': open_ticket_2,
            'entities_id': getWorkerEntityId(),
        });
        await api.createItem('Ticket', {
            '_users_id_requester': getWorkerUserId(),
            'name': closed_ticket_1,
            'content': closed_ticket_1,
            'entities_id': getWorkerEntityId(),
            'status': 5,
        });
        const reminders_id = await api.createItem('Reminder', {
            'name': reminder_1,
            'content': reminder_1,
            'begin': '2023-10-01 16:45:11',
            'end': `${next_year}-10-01 16:45:11`,
        });
        await api.createItem('Reminder_User', {
            'users_id': getWorkerUserId(),
            'reminders_id': reminders_id,
        });

        await page.goto('/Helpdesk');

        // TODO: the cypress version expected 6 columns, including "Entity".
        // `SearchOption::getDefaultToView()` only adds that column when more
        // than one entity is active in the session; the cypress user browsed a
        // whole entity tree while a playwright worker is pinned to its single
        // (childless) worker entity, so only 5 columns are displayed here.
        // TODO: the exact number of columns also comes from the global
        // DisplayPreference rows with `users_id = 0`. It only holds as long as
        // the specs that rewrite them stay out of the parallel suite.
        const assertDefaultColumns = async (): Promise<void> => {
            const tabpanel = page.getByRole('tabpanel');
            const headers = tabpanel.getByRole('columnheader');
            await expect(headers).toHaveCount(5);
            for (const name of [
                'ID',
                'Title',
                'Status',
                'Last update',
                'Opening date',
            ]) {
                await expect(
                    tabpanel.getByRole('columnheader', { name: name })
                ).toBeVisible();
            }
        };

        // Default tab should be opened tickets
        await expect(page.getByText(open_ticket_1).first()).toBeVisible();
        await expect(page.getByText(open_ticket_2).first()).toBeVisible();
        await expect(page.getByText(closed_ticket_1)).toHaveCount(0);
        await assertDefaultColumns();

        // Got to closed tickets tab
        await page.getByRole('tab', { name: 'Solved tickets' }).click();
        await expect(page.getByText(open_ticket_1).first()).toBeHidden();
        await expect(page.getByText(open_ticket_2).first()).toBeHidden();
        await expect(page.getByText(closed_ticket_1).first()).toBeVisible();
        await assertDefaultColumns();

        // Got to Reminder Feed tab
        await page.getByRole('tab', { name: 'Reminders' }).click();
        await expect(
            page.getByRole('link', { name: reminder_1 }).first()
        ).toBeVisible();

        // Return to main tab, make it easier to re-run the test as the last tab
        // is kept in the session
        await page.getByRole('tab', { name: 'Ongoing tickets' }).click();

        // RSS feeds are not tested as they are only displayed if a real feed
        // is configurated. Since the query to the feed is done on the backend,
        // we can't mock it here.
        // Could be added if we don't mind relying on a real outside feeds for
        // ours tests or if we setup a dedicated container for this.
    });
});
