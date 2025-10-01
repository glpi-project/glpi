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

describe('setupAjaxDropdown()', () => {
    it('sends boolean data encoded correctly so that \\Dropdown::getDropdownValue() can handle them.', () => {
        cy.login();
        cy.createWithAPI('Ticket', {
            name: "My ticket",
            content: "My ticket content",
        }).then((ticket_id) => {
            // open Ticket view on Approval tab
            cy.visit(`/front/ticket.form.php?id=${ticket_id}&forcetab=TicketValidation$1`);
            // prepare to intercept the ajax call to getDropdownValue
            cy.intercept('/ajax/getDropdownValue.php').as('getDropdownValue');
            // click on "Send an approval request" button
            cy.findByRole('button', { name: "Send an approval request" }).click();
            // click "Approval step" dropdown to trigger the ajax call
            cy.getDropdownByLabelText("Approval step").click();
            // assert that the ajax call returned a single value
            // having a single value, means that the display_emptychoice option set to false in twig's fields.dropdownField()
            // is correctly interpreted as a boolean false by getDropdownValue()
            cy.wait('@getDropdownValue').then((interception) => {
                expect(interception.response.body.results.length).to.be.equal(1);
                expect(interception.response.body.results[0].text).to.equal("Approval");
            });
        });
    });
});



