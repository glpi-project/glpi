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

describe("Massive actions on ITIL objects", () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });

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
        it(`can solve linked tickets (${itil_type.type})`, () => {
            // Create a ITIL item with a linked ticket.
            cy.createWithAPI(itil_type.type, {
                'name': "My ITIL object",
                'content': "My ITIL object content",
            }).as('itil_id');
            cy.createWithAPI('Ticket', {
                'name': "My ticket",
                'content': "My ticket content",
            }).as('ticket_id');
            cy.getMany(["@itil_id", "@ticket_id"]).then(([itil_id, ticket_id]) => {
                cy.createWithAPI(itil_type.link_type, {
                    [itil_type.fkey]: itil_id,
                    'tickets_id': ticket_id,
                });
            });

            // Go to the itil item on the "Tickets" tab.
            cy.get('@itil_id').then((itil_id) => {
                cy.visit(`/front/${itil_type.url}?id=${itil_id}&forcetab=${itil_type.tab}`);
            });

            // Fill resolve form through massive actions.
            cy.findByRole('checkbox', {name: "Check all"}).check();
            cy.findByRole('button', {name: "Actions"}).click();
            cy.getDropdownByLabelText("Action").selectDropdownValue("Solve tickets");
            cy.findByLabelText('Solution').awaitTinyMCE().type('My solution');

            // Submit action.
            cy.findByRole('button', {name: "Post"}).click();
            cy.findByRole('alert')
                .contains('Operation successful')
                .should('be.visible')
            ;
            cy.get('@ticket_id').then((ticket_id) => {
                cy.getWithAPI('Ticket', ticket_id).then((ticket) => {
                    expect(ticket.status).to.equal(5);
                });
            });
        });
    }

});
