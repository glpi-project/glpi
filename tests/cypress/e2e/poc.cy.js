/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

describe('POC Tests', () => {
    beforeEach(() => {
        cy.login();
    });
    it('TinyMCE set content', () => {
        cy.visit('/front/ticket.form.php');
        // Sometimes Cypress is too quick and is ready to go before certain libraries are done replacing/modifying inputs
        //cy.waitForInputs();
        cy.get('textarea[name="content"]').type('this is a test');
    });
    it('TinyMCE type', () => {
        cy.visit('/front/ticket.form.php');
        //cy.waitForInputs();
        cy.get('textarea[name="content"]').type('this is a test', {
            interactive: true
        });
    });
//     it('Flatpickr interactive', () => {
//         cy.visit('/front/ticket.form.php');
//         cy.waitForInputs();
//         // Try interacting with the 'date' input field by using the flatpickr calendar.
//         // This is how most users would interact with the field, so it is good to emulate actual user behavior.
//         cy.get('input[name="date"]').selectDate('2021-08-24');
//     });
//     it('Flatpickr typing', () => {
//         cy.visit('/front/ticket.form.php');
//         cy.waitForInputs();
//         // Try interacting with the 'date' input field by typing
//         // While less common, this is a quicker way to enter a date.
//         // Although, Cypress is so quick already that it probably doesn't matter.
//         cy.get('input[name="date"]').selectDate('2021-08-24', false);
//     });
//
//     it('GLPI Search', () => {
//         // Create one ticket so this test doesn't fail if there are no tickets
//         cy.visit('/front/ticket.form.php');
//         cy.waitForInputs();
//         cy.get('input[name="name"]').type('Test ticket');
//         cy.get('textarea[name="content"]').type('This is a test ticket');
//         cy.get('#itil-object-container button[type="submit"]').click();
//
//         // Note: Most of this can be replaced with a custom cypress command that takes the search parameters as an argument
//         cy.visit('/front/ticket.php');
//         // Open the search controls panel
//         cy.get('.search-controls button:has(i.ti-list-search)').click();
//         // Reset the search
//         cy.get('.search-controls a.search-reset').click();
//         // Wait for page to reload
//         cy.url().should('include', '?reset=reset');
//         // Open the search controls panel
//         cy.get('.search-controls button:has(i.ti-list-search)').click();
//         // Change the value from "Not solved" to "New"
//         cy.get('div.search-form div[data-fieldname="criteria"][data-num="0"] select').select('Processing (assigned)', { force: true });
//
//         // Spy on the AJAX request and save the request to the alias 'search'
//         cy.intercept('GET', '/ajax/search*').as('search');
//         // Click the search button
//         cy.get('div.search-form button').contains('Search').click();
//         // Wait for the AJAX request to complete and verify the URL has changed
//         cy.wait('@search');
//         cy.url().should('not.include', 'reset=reset');
//         // Wait for the spinner to disappear
//         cy.get('table.search-results div.spinner-overlay').should('not.exist');
//
//         // No results in the table should have text other than "Processing (assigned)" in the status column
//         cy.get('table.search-results thead tr th').contains('Status').invoke('index').then((index) => {
//             cy.get('table.search-results tbody tr td:nth-child(' + (index + 1) + ')').each((cell) => {
//                 cy.wrap(cell).contains('Processing (assigned)');
//             });
//         });
//     });
});
