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

describe('Item form question type', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the item form question type suite',
        }).as('form_id');

        cy.createWithAPI('Ticket', {
            'name': 'Test ticket',
        }).as('ticket_id');

        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a new question
            cy.findByRole("button", { name: "Add a new question" }).should('exist').click();

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test item question");

            // Store the question section
            cy.findByRole("option", { name: "New question" }).should('exist').as('question');

            // Change question type
            cy.findByRole("combobox", { name: "Short answer" }).should('exist').select("Item");
        });
    });

    it('test defining new ticket as default value', () => {
        // Click on the itemtype dropdown
        cy.getDropdownByLabelText("Select an itemtype").click();

        // Select the ticket itemtype
        cy.findByRole("option", { name: "Tickets" }).should('exist').click();

        // Click on the items_id dropdown
        cy.getDropdownByLabelText("Select an item").click();

        cy.get('@ticket_id').then((ticket_id) => {
            // Select the new ticket item
            cy.findByRole("option", { name: "Test ticket - " + ticket_id }).should('exist').click();
        });

        // Save the form (force is required because the button is hidden by a toast message)
        cy.findByRole("button", { name: "Save" }).click({ force: true });

        // Go to preview page (remove the target="_blank" attribute to stay in the same window)
        cy.findByRole("link", { name: "Preview" })
            .invoke('attr', 'target', '_self')
            .click();

        // Check if the default values are set
        cy.findByRole("combobox", { name: "Test ticket" }).should('exist');
    });

    it('test defining new itil category as default value', () => {
        // Create a new ITIL category
        cy.createWithAPI('ITILCategory', {
            'name': 'Test ITIL category',
        }).as('itil_category_id');

        // Select the Dropdowns question type
        cy.findByRole("combobox", { name: "GLPI Objects" }).should('exist').select("Dropdowns");

        // Click on the itemtype dropdown
        cy.getDropdownByLabelText("Select a dropdown type").click();

        // Select the ITIL category itemtype
        cy.findByRole("option", { name: "ITIL categories" }).should('exist').click();

        // Click on the items_id dropdown
        cy.getDropdownByLabelText("Select a dropdown item").click();

        // Select the new ITIL category item
        cy.findAllByRole("option", { name: "»Test ITIL category" }).should('exist').eq(0).click();

        // Save the form (force is required because the button is hidden by a toast message)
        cy.findByRole("button", { name: "Save" }).click({ force: true });

        // Go to preview page (remove the target="_blank" attribute to stay in the same window)
        cy.findByRole("link", { name: "Preview" })
            .invoke('attr', 'target', '_self')
            .click();

        // Check if the default values are set
        cy.findByRole("combobox", { name: "Test ITIL category" }).should('exist');
    });
});
