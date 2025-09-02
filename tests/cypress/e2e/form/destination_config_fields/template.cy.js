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

describe('Template configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Create form
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');

        // Add a default question
        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My test question");
        cy.findByRole('button', {'name': 'Save'}).click();

        // Check alert
        cy.checkAndCloseAlert('Item successfully updated');

        // Create a ticket template
        cy.get('@form_id').then((form_id) => {
            const ticket_template_name = `Test ticket template for the template configuration suite - ${form_id}`;

            cy.createWithAPI('TicketTemplate', {
                'name': ticket_template_name,
            }).as('ticket_template_id');

            cy.get('@ticket_template_id').then((ticket_template_id) => {
                cy.createWithAPI('TicketTemplateHiddenField', {
                    'tickettemplates_id': ticket_template_id,
                    'num': 12,
                });
            });
        });

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();
    });

    it('can use all possibles configuration options', () => {
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.findByRole('region', {'name': "Template configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Template').as("template_dropdown");

        // Default value
        cy.get('@template_dropdown').should(
            'have.text',
            'Default template'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select a template...').should('not.exist');

        cy.get('@form_id').then((form_id) => {
            const ticket_template_name = `Test ticket template for the template configuration suite - ${form_id}`;

            // Switch to "Specific template"
            cy.get('@template_dropdown').selectDropdownValue('Specific template');
            cy.get('@config').getDropdownByLabelText('Select a template...').as('specific_template_id_dropdown');
            cy.get('@specific_template_id_dropdown').selectDropdownValue(ticket_template_name);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
            cy.openAccordionItem('Destination fields accordion', 'Properties');
            cy.get('@template_dropdown').should('have.text', 'Specific template');
            cy.get('@specific_template_id_dropdown').should('have.text', ticket_template_name);
        });
    });

    it('can create ticket using default configuration', () => {
        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.findByRole('textbox', { 'name': 'My test question' }).type('My test answer');

        // Submit form
        cy.findByRole('button', {'name': 'Submit'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check ticket values
        cy.getDropdownByLabelText('Status').should('not.exist');

        // Others possibles configurations are tested directly by the backend.
    });
});
