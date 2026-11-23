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
import { expect, test } from '../fixtures/glpi_fixture';
import { GlpiPage } from '../pages/GlpiPage';
import { Profiles } from '../utils/Profiles';
import { getWorkerEntityId } from '../utils/WorkerEntities';

test.describe('setupAjaxDropdown()', () => {
    test('sends boolean data encoded correctly so that \\Dropdown::getDropdownValue() can handle them.', async ({
        page,
        profile,
        api,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        const ticket_id = await api.createItem('Ticket', {
            name: "My ticket",
            content: "My ticket content",
            entities_id: getWorkerEntityId(),
        });

        try {
            // open Ticket view on Approval tab
            await page.goto(
                `/front/ticket.form.php?id=${ticket_id}&forcetab=TicketValidation$1`
            );

            // click on "Send an approval request" button
            await glpi_page.getButton("Send an approval request").click();

            // prepare to intercept the ajax call to getDropdownValue
            const dropdown_value_response = page.waitForResponse(
                '**/ajax/getDropdownValue.php'
            );

            // click "Approval step" dropdown to trigger the ajax call
            // The label of this required field ends with a "*", it can not be
            // matched exactly.
            await glpi_page
                .getDropdownByLabel("Approval step", undefined, false)
                .click()
            ;

            // assert that the ajax call did not return the empty choice
            // its absence means that the display_emptychoice option set to false in twig's fields.dropdownField()
            // is correctly interpreted as a boolean false by getDropdownValue()
            //
            // The cypress version asserted a single result named "Approval". That
            // does not hold here: `ValidationStep` is a global dropdown and other
            // specs add their own steps, so only the empty choice is checked.
            const body = await (await dropdown_value_response).json();
            const results: { id: number, text: string }[] = body.results;
            expect(results.filter((result) => result.id === 0)).toHaveLength(0);
            expect(results.map((result) => result.text)).toContain("Approval");
        } finally {
            // GLPI remembers the last opened tab per itemtype in the session,
            // which is shared by all the tests of the worker: restore the main
            // tab so a spec reaching a ticket through a link (and thus without
            // `forcetab`) is not sent to the approvals tab.
            await page.goto(
                `/front/ticket.form.php?id=${ticket_id}&forcetab=Ticket$main`
            );
        }
    });
});
