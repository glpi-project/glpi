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

describe('Assignee configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.createFormWithAPI().as('form_id').visitFormTab('Form');

        // Create a Assignee
        cy.get('@form_id').then((form_id) => {
            const assignee_name = `Test Assignee - ${form_id}`;
            cy.createWithAPI('User', {
                name: assignee_name,
                _profiles_id: 6, // Technician
            }).as('assignee_id');

            // Create a Group
            cy.createWithAPI('Group', {
                name: `Test Group - ${form_id}`,
            }).as('group_id');

            // Create a Computer with users_id, users_id_tech, groups_id and groups_id_tech
            cy.get('@group_id').then((group_id) => {
                cy.createWithAPI('Computer', {
                    name: `Test Computer - ${form_id}`,
                    users_id: 7, // E2E Tests user
                    users_id_tech: 7, // E2E Tests user
                    groups_id: group_id,
                    groups_id_tech: group_id,
                }).as('computer_id');
            });

            cy.findByRole('button', {'name': "Add a question"}).click();
            cy.focused().type("My Assignee question");
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Actors');
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Assignees');
            cy.getDropdownByLabelText("Select an actor...").selectDropdownValue(assignee_name);

            cy.findByRole('button', {'name': "Add a question"}).click();
            cy.focused().type("My Email question");
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Short answer');
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Emails');

            cy.findByRole('button', {'name': "Add a question"}).click();
            cy.focused().type("My Computer question");
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue('GLPI Objects');
            cy.getDropdownByLabelText("Select an itemtype").selectDropdownValue('Computers');

            // Save form
            cy.findByRole('button', {'name': 'Save'}).click();

            // Check alert
            cy.checkAndCloseAlert('Item successfully updated');

            // Go to destination tab
            cy.findByRole('tab', { 'name': "Destinations 1" }).click();
        });
    });

    it('can use all possibles configuration options', () => {
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.findByRole('region', { 'name': "Assignees configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('Assignees').as("assignees_dropdown");

        // Default value
        cy.get('@assignees_dropdown').should(
            'have.text',
            'From template'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select actors...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select questions...').should('not.exist');

        // Switch to "From template"
        cy.get('@assignees_dropdown').selectDropdownValue('From template');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'From template');

        // Switch to "User who filled the form"
        cy.get('@assignees_dropdown').selectDropdownValue('User who filled the form');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'User who filled the form');

        // Switch to "Specific actors"
        cy.get('@assignees_dropdown').selectDropdownValue('Specific actors');
        cy.get('@config').getDropdownByLabelText('Select actors...').as('specific_assignees_dropdown');
        cy.get('@form_id').then((form_id) => {
            const assignee_name = `Test Assignee - ${form_id}`;
            cy.get('@specific_assignees_dropdown').selectDropdownValue(assignee_name);
        });

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'Specific actors');
        cy.get('@form_id').then((form_id) => {
            const assignee_name = `Test Assignee - ${form_id}`;
            cy.get('@specific_assignees_dropdown').should('have.text', `×${assignee_name}`);
        });

        // Switch to "Answer from specific questions"
        cy.get('@assignees_dropdown').selectDropdownValue('Answer from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_type_dropdown');
        cy.get('@specific_answers_type_dropdown').selectDropdownValue('My Assignee question');
        cy.get('@specific_answers_type_dropdown').selectDropdownValue('My Email question');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'Answer from specific questions');
        cy.get('@specific_answers_type_dropdown').should('have.text', '×My Assignee question×My Email question');

        // Switch to "Answer to last "Assignees" or "Email" question"
        cy.get('@assignees_dropdown').selectDropdownValue('Answer to last "Assignees" or "Email" question');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'Answer to last "Assignees" or "Email" question');

        // Switch to "Supervisor of the user who filled the form"
        cy.get('@assignees_dropdown').selectDropdownValue('Supervisor of the user who filled the form');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'Supervisor of the user who filled the form');

        // Switch to "User from GLPI object answer"
        cy.get('@assignees_dropdown').selectDropdownValue('User from GLPI object answer');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('user_object_answer_dropdown');
        cy.get('@user_object_answer_dropdown').selectDropdownValue('My Computer question');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'User from GLPI object answer');
        cy.get('@user_object_answer_dropdown').should('have.text', '×My Computer question');

        // Switch to "Tech user from GLPI object answer"
        cy.get('@assignees_dropdown').selectDropdownValue('Tech user from GLPI object answer');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('tech_user_object_answer_dropdown');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'Tech user from GLPI object answer');
        cy.get('@tech_user_object_answer_dropdown').should('have.text', '×My Computer question');

        // Switch to "Group from GLPI object answer"
        cy.get('@assignees_dropdown').selectDropdownValue('Group from GLPI object answer');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('group_object_answer_dropdown');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'Group from GLPI object answer');
        cy.get('@group_object_answer_dropdown').should('have.text', '×My Computer question');

        // Switch to "Tech group from GLPI object answer"
        cy.get('@assignees_dropdown').selectDropdownValue('Tech group from GLPI object answer');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('tech_group_object_answer_dropdown');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.get('@assignees_dropdown').should('have.text', 'Tech group from GLPI object answer');
        cy.get('@tech_group_object_answer_dropdown').should('have.text', '×My Computer question');
    });

    it('can create ticket using default configuration', () => {
        cy.openAccordionItem('Destination fields accordion', 'Actors');
        cy.findByRole('region', { 'name': "Assignees configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('Assignees').as("assignees_dropdown");

        // Switch to "User who filled the form"
        cy.get('@assignees_dropdown').selectDropdownValue('User who filled the form');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to preview
        cy.findByRole('tab', { 'name': "Form" }).click();
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click();

        // Fill form
        cy.findByRole('button', { 'name': 'Submit' }).click();
        cy.findByRole('link', { 'name': 'My test form' }).click();

        // Check ticket values
        cy.findByRole('region', { 'name': "Actors" }).within(() => {
            cy.get('select[data-actor-type="assign"]').contains('E2E Tests');
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
