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

    // We should make sure that each unique kind of fields are tested at least
    // once here (text input, select2, dropdown, ...).
    it('can enable or disable auto configuration', () => {
        // Inputs aliases
        cy.findByRole("textbox", {'name': "Title"}).as("title_field");
        cy.findByLabelText("Content").awaitTinyMCE().as("content_field");

        // Checkbox aliases
        cy.findAllByRole('checkbox', {'name': "Auto config"}).eq(0)
            .as("title_auto_config_checkbox")
        ;
        cy.findAllByRole('checkbox', {'name': "Auto config"}).eq(1)
            .as("content_auto_config_checkbox")
        ;

        describe('Auto config must be enabled by default', () => {
            // Auto configuration should be ON by default, with all fields disabled
            cy.findAllByRole('checkbox', {'name': "Auto config"})
                .should('be.checked')
            ;
            cy.get("@title_field").should('be.disabled');
            cy.get("@content_field").parents().find("#tinymce")
                .should('have.attr', 'contenteditable', "false")
            ;

            // Ensure auto config values have been loaded for the "title" field
            cy.get("@title_field")
                .should('have.value', "Test form for the destination form suite")
            ;

            // Ensure auto config values have been loaded for the "content" field
            cy.get("@content_field")
                .should('have.text', "") // Empty because the form doesn't have any questions
            ;
        });

        describe('Disable auto config and enter manual values', () => {
            // Disable auto config for the "title" field (text input)
            cy.get("@title_auto_config_checkbox").uncheck();
            cy.get("@title_field").should('not.be.disabled');
            cy.get("@title_field").clear();
            cy.get("@title_field").type('Manual title');

            // Disable auto config for the "content" field (tinymce)
            cy.get("@content_auto_config_checkbox").uncheck();
            cy.get("@content_field").parents().find("#tinymce")
                .should('have.attr', 'contenteditable', "true")
            ;
            cy.get("@content_field").type('Manual content');

            // Save changes (page reload)
            cy.findByRole('button', {'name': "Update item"}).click();
        });

        describe('Validate manual values are kept after reload', () => {
            cy.findByLabelText("Content").awaitTinyMCE().as("content_field"); // this alias must be repeated after a page reload

            // Ensure the manual values are still there
            cy.get("@title_auto_config_checkbox").should('not.be.checked');
            cy.get("@title_field")
                .should('have.value', 'Manual title')
            ;

            cy.get("@content_auto_config_checkbox").should('not.be.checked');
            cy.get("@content_field").should('have.text', 'Manual content');
        });

        describe('Revert to auto configurated values', () => {
            // Re-enable auto config for the "title" field
            cy.get("@title_auto_config_checkbox").check();
            cy.get("@title_field").should('be.disabled');

            // Re-enable auto config for the "content" field
            cy.get("@content_auto_config_checkbox").check();
            cy.get("@content_field").parents().find("#tinymce")
                .should('have.attr', 'contenteditable', "false")
            ;

            // Save changes (page reload)
            cy.findByRole('button', {'name': "Update item"}).click();
        });

        describe('Validate manual values have been removed', () => {
            cy.findByLabelText("Content").awaitTinyMCE().as("content_field"); // this alias must be repeated after a page reload

            // Ensure the manual value has been removed for the "title" field
            cy.get("@title_auto_config_checkbox").should('be.checked');
            cy.get("@title_field")
                .should('have.value', "Test form for the destination form suite")
            ;

            // Ensure the manual value has been removed for the "content" field
            cy.get("@content_auto_config_checkbox").should('be.checked');
            cy.get("@content_field")
                .should('have.text', "")
            ;
        });
    });
});
