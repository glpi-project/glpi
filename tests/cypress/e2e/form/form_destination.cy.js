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

describe('Form destination', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Test form for the destination form suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Destination\\FormDestination$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Create a ticket destination
            cy.findByRole('button', {name: "Add ticket"}).click();
        });
    });

    it('form destination name is loaded and name is preserved on reload', () => {
        // Check if the form destination name is loaded
        cy.findByRole("textbox", {name: "Form destination name"}).should('exist').and('have.value', 'Ticket');

        // Update the form destination name
        cy.findByRole("textbox", {name: "Form destination name"}).clear();
        cy.findByRole("textbox", {name: "Form destination name"}).type('Updated ticket destination name');

        // Save form
        cy.findByRole("button", {name: "Update item"}).click();

        // Check if the form destination name is updated
        cy.findByRole("textbox", {name: "Form destination name"}).should('exist').and('have.value', 'Updated ticket destination name');
    });
});
