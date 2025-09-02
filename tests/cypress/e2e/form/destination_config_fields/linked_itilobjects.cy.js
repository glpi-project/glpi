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

describe('Linked ITIL Objects configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        cy.createFormWithAPI().as('form_id').visitFormTab('Form');

        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('Ticket', {
                name   : `Test ticket for linked itil objects - ${form_id}`,
                content: `Content for ticket linked to form - ${form_id}`
            }).as('ticket_id');

            cy.createWithAPI('Change', {
                name   : `Test change for linked itil objects - ${form_id}`,
                content: `Content for change linked to form - ${form_id}`
            }).as('change_id');

            cy.createWithAPI('Problem', {
                name   : `Test problem for linked itil objects - ${form_id}`,
                content: `Content for problem linked to form - ${form_id}`
            }).as('problem_id');
        });

        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My Ticket question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('GLPI Objects');
        cy.getDropdownByLabelText('Select an itemtype').selectDropdownValue('Tickets');

        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();

        // Rename default destination
        cy.findByRole('region', { 'name': "Ticket" }).findByRole('textbox', { 'name': "Form destination name" }).as("destination_name_input");
        cy.get('@destination_name_input').clear();
        cy.get('@destination_name_input').type('First ticket destination');

        // Update destination
        cy.findByRole('button', { 'name': "Update item" }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Add a new destination
        cy.findByRole('button', { 'name': "Add Ticket" }).click();
        cy.checkAndCloseAlert('Item successfully added');

        // Rename new destination
        cy.findByRole('region', { 'name': "Ticket" }).findByRole('textbox', { 'name': "Form destination name" }).as("second_destination_name_input");
        cy.get('@second_destination_name_input').clear();
        cy.get('@second_destination_name_input').type('Second ticket destination');

        // Update destination
        cy.findByRole('button', { 'name': "Update item" }).click();
        cy.checkAndCloseAlert('Item successfully updated');
    });

    it('can use all possibles configuration options', () => {
        // Retrieve configuration section
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.findByRole('region', {'name': "Link to assistance objects configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select the strategy...').as("linked_itilobjects_dropdown");

        // Default value
        cy.get('@linked_itilobjects_dropdown').should(
            'have.text',
            '-----'
        );

        // Make sure link dropdown is displayed
        cy.get('@config').getDropdownByLabelText('Select the link type...').should('exist');

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select assistance object...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select destination...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select questions...').should('not.exist');

        // Switch to "An other destination of this form"
        cy.get('@linked_itilobjects_dropdown').selectDropdownValue('An other destination of this form');
        cy.get('@config').getDropdownByLabelText('Select destination...').as('specific_destination_dropdown');
        cy.get('@specific_destination_dropdown').selectDropdownValue('First ticket destination');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.get('@specific_destination_dropdown').hasDropdownValue('First ticket destination');

        // Switch to "An existing assistance object"
        cy.get('@linked_itilobjects_dropdown').selectDropdownValue('An existing assistance object');
        cy.get('@config').getDropdownByLabelText('Select assistance object type...').as('specific_assistance_object_type_dropdown');
        cy.get('@form_id').then((form_id) => {
            cy.get('@ticket_id').then((ticket_id) => {
                cy.get('@specific_assistance_object_type_dropdown').selectDropdownValue('Tickets');
                cy.get('@config').getDropdownByLabelText('Select assistance object...').as('specific_assistance_object_dropdown');
                cy.get('@specific_assistance_object_dropdown').selectDropdownValue(`Test ticket for linked itil objects - ${form_id} - ${ticket_id}`);

                cy.findByRole('button', {'name': 'Update item'}).click();
                cy.checkAndCloseAlert('Item successfully updated');
                cy.openAccordionItem('Destination fields accordion', 'Associated items');
                cy.get('@linked_itilobjects_dropdown').should('have.text', 'An existing assistance object');
                cy.get('@specific_assistance_object_type_dropdown').should('have.text', 'Tickets');
                cy.get('@specific_assistance_object_dropdown').should(
                    'have.text',
                    `Test ticket for linked itil objects - ${form_id}`
                );
            });
        });

        // Switch to "Assistance object from specific questions"
        cy.get('@linked_itilobjects_dropdown').selectDropdownValue('Assistance object from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_dropdown');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My Ticket question');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.get('@linked_itilobjects_dropdown').should('have.text', 'Assistance object from specific questions');
        cy.get('@specific_answers_dropdown').should(
            'have.text',
            '×My Ticket question'
        );
    });

    it('can define multiple strategies at once', () => {
        // Retrieve configuration section
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.findByRole('region', {'name': "Link to assistance objects configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select the strategy...').as("linked_itilobjects_dropdown");

        // Add first strategy
        cy.get('@linked_itilobjects_dropdown').selectDropdownValue('An other destination of this form');
        cy.get('@config').getDropdownByLabelText('Select destination...').as('specific_destination_dropdown');
        cy.get('@specific_destination_dropdown').selectDropdownValue('First ticket destination');

        // Add second strategy
        cy.get('@config').findByRole('button', {'name': 'Combine with another option'}).click();
        cy.get('@linked_itilobjects_dropdown').eq(-1).selectDropdownValue('An existing assistance object');
        cy.get('@config').getDropdownByLabelText('Select assistance object type...').as('specific_assistance_object_type_dropdown');
        cy.get('@form_id').then((form_id) => {
            cy.get('@ticket_id').then((ticket_id) => {
                cy.get('@specific_assistance_object_type_dropdown').selectDropdownValue('Tickets');
                cy.get('@config').getDropdownByLabelText('Select assistance object...').as('specific_assistance_object_dropdown');
                cy.get('@specific_assistance_object_dropdown').selectDropdownValue(`Test ticket for linked itil objects - ${form_id} - ${ticket_id}`);
            });
        });

        // Add third strategy
        cy.get('@config').findByRole('button', {'name': 'Combine with another option'}).click();
        cy.get('@linked_itilobjects_dropdown').eq(-1).selectDropdownValue('Assistance object from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_dropdown');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My Ticket question');

        // Add fourth strategy
        cy.get('@config').findByRole('button', {'name': 'Combine with another option'}).click();
        cy.get('@linked_itilobjects_dropdown').eq(-1).selectDropdownValue('An existing assistance object');
        cy.get('@config').getDropdownByLabelText('Select assistance object type...').as('specific_assistance_object_type_dropdown_2');
        cy.get('@form_id').then((form_id) => {
            cy.get('@change_id').then((change_id) => {
                cy.get('@specific_assistance_object_type_dropdown_2').eq(-1).selectDropdownValue('Changes');
                cy.get('@specific_assistance_object_dropdown').should('have.length', 2)
                    .eq(-1).selectDropdownValue(`Test change for linked itil objects - ${form_id} - ${change_id}`);
            });
        });

        // Save
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Check values
        cy.get('@form_id').then((form_id) => {
            cy.openAccordionItem('Destination fields accordion', 'Associated items');
            cy.get('@linked_itilobjects_dropdown').should('have.length', 4);

            cy.get('@linked_itilobjects_dropdown').eq(0).should('have.text', 'An other destination of this form');
            cy.get('@specific_destination_dropdown').should('have.text', '×First ticket destination');

            cy.get('@linked_itilobjects_dropdown').eq(1).should('have.text', 'An existing assistance object');
            cy.get('@specific_assistance_object_type_dropdown').eq(0).should('have.text', 'Tickets');
            cy.get('@specific_assistance_object_dropdown').eq(0).should(
                'have.text',
                `Test ticket for linked itil objects - ${form_id}`
            );

            cy.get('@linked_itilobjects_dropdown').eq(2).should('have.text', 'Assistance object from specific questions');
            cy.get('@specific_answers_dropdown').should(
                'have.text',
                '×My Ticket question'
            );

            cy.get('@linked_itilobjects_dropdown').eq(3).should('have.text', 'An existing assistance object');
            cy.get('@specific_assistance_object_type_dropdown').eq(1).should('have.text', 'Changes');
            cy.get('@specific_assistance_object_dropdown').eq(1).should(
                'have.text',
                `Test change for linked itil objects - ${form_id}`
            );
        });
    });
});
