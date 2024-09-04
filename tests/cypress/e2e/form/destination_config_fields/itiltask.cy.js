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

describe('ITILTask configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);

        // Create form with a single "Task template" dropdown question
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');
        cy.findByRole('button', {'name': "Add a new question"}).click();
        cy.focused().type("My Task template question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Dropdowns');

        cy.getDropdownByLabelText('Select a dropdown type').selectDropdownValue('Task templates');

        cy.findByRole('button', {'name': 'Save'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', {'name': "Items to create"}).click();
        cy.findByRole('button', {'name': "Add ticket"}).click();
        cy.checkAndCloseAlert('Item successfully added');

        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('TaskTemplate', {
                'name': 'Task template 1 - ' + form_id,
                'content': 'My Task template content',
            });
        });
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', {'name': "Tasks configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Tasks').as("itiltask_dropdown");

        // Default value
        cy.get('@itiltask_dropdown').should(
            'have.text',
            'All answers to "Task templates" questions'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select task templates...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select questions...').should('not.exist');

        // Switch to "No Task"
        cy.get('@itiltask_dropdown').selectDropdownValue('No Task');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itiltask_dropdown').should('have.text', 'No Task');

        // Switch to "Specific Task templates"
        cy.get('@form_id').then((form_id) => {
            cy.get('@itiltask_dropdown').selectDropdownValue('Specific Task templates');
            cy.get('@config').getDropdownByLabelText('Select task templates...').as('specific_itiltask_dropdown');
            cy.get('@specific_itiltask_dropdown').selectDropdownValue('Task template 1 - ' + form_id);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
            cy.get('@itiltask_dropdown').should('have.text', 'Specific Task templates');
            cy.get('@specific_itiltask_dropdown').should('have.text', '×Task template 1 - ' + form_id);
        });

        // Switch to "Answer from specific questions"
        cy.get('@itiltask_dropdown').selectDropdownValue('Answer from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_dropdown');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My Task template question');

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itiltask_dropdown').should('have.text', 'Answer from specific questions');
        cy.get('@specific_answers_dropdown').should('have.text', '×My Task template question');

        // Switch to "Answer to last "Task templates" question"
        cy.get('@itiltask_dropdown').selectDropdownValue('Answer to last "Task templates" question');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itiltask_dropdown').should('have.text', 'Answer to last "Task templates" question');

        // Switch to "All answers to "Task templates" questions"
        cy.get('@itiltask_dropdown').selectDropdownValue('All answers to "Task templates" questions');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itiltask_dropdown').should('have.text', 'All answers to "Task templates" questions');
    });

    it('can create ticket using default configuration', () => {
        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.getDropdownByLabelText("My Task template question").as('itiltask_dropdown');
        cy.get('@form_id').then((form_id) => {
            cy.get('@itiltask_dropdown').selectDropdownValue('Task template 1 - ' + form_id);
        });
        cy.findByRole('button', {'name': 'Send form'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check if followup template content is displayed
        cy.contains('My Task template content');

        // Others possibles configurations are tested directly by the backend.
    });
});
