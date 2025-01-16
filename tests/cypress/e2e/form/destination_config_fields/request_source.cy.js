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

describe('Request source configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Create form
        cy.createFormWithAPI().visitFormTab('Form');

        // Add a default question
        cy.findByRole('button', { 'name': "Add a new question" }).click();
        cy.findByRole('button', { 'name': 'Save' }).click();

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Items to create" }).click();
        cy.findByRole('button', { 'name': "Add ticket" }).click();
        cy.checkAndCloseAlert('Item successfully added');
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', { 'name': "Request source configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('Request source').as("source_dropdown");

        // Default value
        cy.get('@source_dropdown').should(
            'have.text',
            'From template'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select a request source...').should('not.exist');

        // Switch to "Specific request source"
        cy.get('@source_dropdown').selectDropdownValue('Specific request source');
        cy.get('@config').getDropdownByLabelText('Select a request source...').as('specific_request_source_id_dropdown');
        cy.get('@specific_request_source_id_dropdown').selectDropdownValue('E-Mail');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@source_dropdown').should('have.text', 'Specific request source');
        cy.get('@specific_request_source_id_dropdown').should('have.text', 'E-Mail');
    });

    it('can create ticket using default configuration', () => {
        cy.createWithAPI('TicketTemplatePredefinedField', {
            'tickettemplates_id': 1, // Default template
            'num': 9, // Request source
            'value': 3, // Phone
        });
        // Go to preview
        cy.findByRole('tab', { 'name': "Form" }).click();
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click();

        cy.findByRole('button', { 'name': 'Send form' }).click();
        cy.findByRole('link', { 'name': 'My test form' }).click();

        // Check ticket values
        cy.getDropdownByLabelText('Request source').should('have.text', 'Phone');

        // Others possibles configurations are tested directly by the backend.
    });
});
