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

describe('Item form question type', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the item form question type suite',
        }).as('form_id');

        cy.createWithAPI('Ticket', {
            'name': 'Test ticket',
            'content': '',
        }).as('ticket_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.findByRole("button", { name: "Add a question" }).should('exist').click();

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test item question");

            // Store the question section
            cy.findByRole("option", { name: "New question" }).should('exist').as('question');

            // Change question type
            cy.findByRole('option', {'name': 'New question'}).changeQuestionType('Item');
        });
    });

    it('test defining new ticket as default value', () => {
        // Click on the itemtype dropdown
        cy.getDropdownByLabelText("Select an itemtype").click();

        // Change the itemtype to Ticket
        cy.getDropdownByLabelText("Select an item").should('exist').then(() => {
            // Select the ticket itemtype
            cy.findByRole("option", { name: "Tickets" }).should('exist').click();
        }).should('not.exist');

        // Wait for the items_id dropdown to be loaded
        cy.waitForNetworkIdle(150);

        // Click on the items_id dropdown
        cy.getDropdownByLabelText("Select an item").click();

        cy.get('@ticket_id').then((ticket_id) => {
            // Select the new ticket item
            cy.findByRole("option", { name: `Test ticket - ${ticket_id}` }).should('exist').click();
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

        // Change the itemtype to ITIL categories
        cy.getDropdownByLabelText("Select a dropdown item").should('exist').then(() => {
            // Select the ITIL categories itemtype
            cy.findByRole("option", { name: "ITIL categories" }).should('exist').click();
        }).should('not.exist');

        // Wait for the items_id dropdown to be loaded
        cy.waitForNetworkIdle(150);

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

    describe('Item form question type default value with root entity', () => {
        before(() => {
            // We need to be in the root entity to be able to select it as default value
            // So we set the Super-Admin profile to have access to all entities
            // (we will reset it after the test)
            cy.updateWithAPI('Profile_User', 6, { entities_id: 0 }); // Super-Admin
        });

        after(() => {
            // Reset the Super-Admin profile to original value
            cy.updateWithAPI('Profile_User', 6, { entities_id: 1 }); // Super-Admin
        });

        it('can define root entity as default value', () => {
            // Change the itemtype to Entities
            cy.getDropdownByLabelText("Select an itemtype").selectDropdownValue("Entities");

            // Wait for the items_id dropdown to be loaded
            cy.waitForNetworkIdle(150);

            // Select the root entity
            cy.getDropdownByLabelText("Select an item").selectDropdownValue('Root entity');

            // Check if the default value is set correctly
            cy.findByRole("combobox", { name: "Root entity" }).should('exist');

            // Save the form
            cy.saveFormEditorAndReload();

            // Check if the default value is still set correctly after reloading the form editor
            cy.findByRole("combobox", { name: "Root entity" }).should('exist');

            // Go to preview page (remove the target="_blank" attribute to stay in the same window)
            cy.findByRole("link", { name: "Preview" })
                .invoke('attr', 'target', '_self')
                .click();

            // Check if the default values are set
            cy.findByRole("combobox", { name: "Root entity" }).should('exist');
        });
    });
});
