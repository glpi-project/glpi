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
import { GlpiPage } from '../../pages/GlpiPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test.describe("Massive actions on ITIL objects", () => {
    // List ITIL objects that have a "Ticket" tab from which they can resolve
    // linked tickets.
    const itil_types_than_can_solve_tickets = [
        {
            type: "Change",
            link_type: "Change_Ticket",
            fkey: "changes_id",
            tab: "Change_Ticket$1",
            url: "change.form.php",
        },
        {
            type: "Problem",
            link_type: "Problem_Ticket",
            fkey: "problems_id",
            tab: "Problem_Ticket$1",
            url: "problem.form.php",
        },
    ];
    for (const itil_type of itil_types_than_can_solve_tickets) {
        test(`can solve linked tickets (${itil_type.type})`, async ({
            page,
            profile,
            api,
        }) => {
            await profile.set(Profiles.SuperAdmin);
            const glpi_page = new GlpiPage(page);

            // Create a ITIL item with a linked ticket.
            const itil_id = await api.createItem(itil_type.type, {
                'name': "My ITIL object",
                'content': "My ITIL object content",
                'entities_id': getWorkerEntityId(),
            });
            const ticket_id = await api.createItem('Ticket', {
                'name': "My ticket",
                'content': "My ticket content",
                'entities_id': getWorkerEntityId(),
            });
            await api.createItem(itil_type.link_type, {
                [itil_type.fkey]: itil_id,
                'tickets_id': ticket_id,
            });

            // Go to the itil item on the "Tickets" tab.
            await page.goto(
                `/front/${itil_type.url}?id=${itil_id}&forcetab=${itil_type.tab}`
            );

            // Fill resolve form through massive actions.
            await glpi_page.getCheckbox("Check all").check();

            await page.getByRole('button', { name: "Actions" }).click();
            await glpi_page.doSetDropdownValue(
                glpi_page.getDropdownByLabel("Action"),
                "Solve tickets"
            );
            const solution = await glpi_page.initRichTextByLabel('Solution');
            await solution.fill('My solution');

            // Submit action.
            await glpi_page.getButton("Post").click();
            await expect(glpi_page.getAlert('Operation successful'))
                .toBeVisible()
            ;

            const ticket = await api.getItem('Ticket', ticket_id);
            expect(ticket.status).toEqual(5);
        });
    }
});
