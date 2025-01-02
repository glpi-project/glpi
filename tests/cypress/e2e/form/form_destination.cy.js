/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Destination\\FormDestination$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Create a ticket destination
            cy.findByRole('button', {name: "Add ticket"}).click();
            cy.checkAndCloseAlert('Item successfully added');
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
        cy.checkAndCloseAlert('Item successfully updated');

        // Check if the form destination name is updated
        cy.findByRole("textbox", {name: "Form destination name"}).should('exist').and('have.value', 'Updated ticket destination name');
    });

    it('can enable or disable auto configuration on supported fields', () => {
        // Inputs aliases
        cy.findByLabelText("Title").awaitTinyMCE().as("title_field");
        cy.findByLabelText("Content").awaitTinyMCE().as("content_field");

        // Checkbox aliases
        cy.findAllByRole('checkbox', {'name': "Auto config"}).eq(0)
            .as("content_auto_config_checkbox")
        ;

        describe('Field that does not support auto config are not impacted', () => {
            cy.get("@title_field").should('be.not.disabled');
        });

        describe('Auto config must be enabled by default on field that support it', () => {
            // Auto configuration should be ON by default, with all fields disabled
            cy.findAllByRole('checkbox', {'name': "Auto config"})
                .should('be.checked')
            ;
            cy.get("@content_field").parents().find("#tinymce")
                .type('This field is not writable')
            ;
            cy.get("@content_field").should('have.text', ''); // Nothing was written.

            // Ensure auto config values have been loaded for the "content" field
            cy.get("@content_field")
                .should('have.text', "") // Empty because the form doesn't have any questions
            ;
        });

        describe('Disable auto config and enter manual values', () => {
            // Disable auto config for the "content" field (tinymce)
            cy.get("@content_auto_config_checkbox").uncheck();
            cy.get("@content_field").parents().find("#tinymce")
                .type('This field is writable')
            ;

            // Save changes (page reload)
            cy.findByRole('button', {'name': "Update item"}).click();
            cy.checkAndCloseAlert('Item successfully updated');
        });

        describe('Validate manual values are kept after reload', () => {
            cy.findByLabelText("Content").awaitTinyMCE().as("content_field"); // this alias must be repeated after a page reload

            // Ensure the manual values are still there
            cy.get("@content_auto_config_checkbox").should('not.be.checked');
            cy.get("@content_field").should('have.text', 'This field is writable');
        });

        describe('Revert to auto configurated values', () => {
            // Re-enable auto config for the "content" field
            cy.get("@content_auto_config_checkbox").check();
            cy.get("@content_field").parents().find("#tinymce")
                .type('This field is not writable')
            ;
            cy.get("@content_field").should('have.text', 'This field is writable'); // Previous content, no chane=ges

            // Save changes (page reload)
            cy.findByRole('button', {'name': "Update item"}).click();
            cy.checkAndCloseAlert('Item successfully updated');
        });

        describe('Validate manual values have been removed', () => {
            cy.findByLabelText("Content").awaitTinyMCE().as("content_field"); // this alias must be repeated after a page reload

            // Ensure the manual value has been removed for the "content" field
            cy.get("@content_auto_config_checkbox").should('be.checked');
            cy.get("@content_field")
                .should('have.text', "")
            ;
        });
    });

    it('check form destination title default value', () => {
        cy.findByLabelText("Title").awaitTinyMCE().as("title_field");
        cy.get('@title_field').contains('Form name: Test form for the destination form suite');
    });

    it('can define multiple strategies for the same field', () => {
        cy.findByRole('region', {name: 'Requesters configuration'}).as('requesters_config');
        cy.get('@requesters_config').findByRole('button', {name: 'Combine with another option'}).should('exist').as('add_strategy_button');
        cy.getDropdownByLabelText('Requesters').as('first_strategy_dropdown');

        // Define first strategy
        cy.get('@first_strategy_dropdown').selectDropdownValue('From template');

        // Add a second strategy
        cy.get('@add_strategy_button').click();
        cy.findByRole('combobox', {name: '-----'}).as('second_strategy_dropdown');
        cy.get('@second_strategy_dropdown').selectDropdownValue('Specific actors');
        cy.get('@requesters_config').getDropdownByLabelText('Select actors...').as('second_strategy_actors_dropdown');
        cy.get('@second_strategy_actors_dropdown').selectDropdownValue('glpi');

        // Add a third strategy
        cy.get('@add_strategy_button').click();
        cy.findByRole('combobox', {name: '-----'}).as('third_strategy_dropdown');
        cy.get('@third_strategy_dropdown').selectDropdownValue('Answer to last "Requesters" question');

        // Save changes
        cy.findByRole('button', {name: 'Update item'}).click();

        // Check if the strategies are saved
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@requesters_config').within(() => {
            cy.findByRole('combobox', {name: 'From template'}).should('exist');
            cy.findByRole('combobox', {name: 'Specific actors'}).should('exist');
            cy.findByRole('listitem', {name: 'glpi'}).should('exist');
            cy.findByRole('combobox', {name: 'Answer to last "Requesters" question'}).should('exist');
        });

        // Add a fourth strategy
        cy.get('@requesters_config').findByRole('button', {name: 'Combine with another option'}).click();
        cy.findByRole('combobox', {name: '-----'}).as('fourth_strategy_dropdown');
        cy.get('@fourth_strategy_dropdown').selectDropdownValue('User who filled the form');

        // Save changes
        cy.findByRole('button', {name: 'Update item'}).click();

        // Check if the strategies are saved
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@requesters_config').within(() => {
            cy.findByRole('combobox', {name: 'From template'}).should('exist');
            cy.findByRole('combobox', {name: 'Specific actors'}).should('exist');
            cy.findByRole('listitem', {name: 'glpi'}).should('exist');
            cy.findByRole('combobox', {name: 'Answer to last "Requesters" question'}).should('exist');
            cy.findByRole('combobox', {name: 'User who filled the form'}).should('exist');
        });
    });
});
