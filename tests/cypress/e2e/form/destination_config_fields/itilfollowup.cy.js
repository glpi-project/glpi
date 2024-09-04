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

describe('ITILFollowup configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);

        // Create form with a single "ITILFollowup template" dropdown question
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');
        cy.findByRole('button', {'name': "Add a new question"}).click();
        cy.focused().type("My ITILFollowup template question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Dropdowns');

        cy.getDropdownByLabelText('Select a dropdown type').selectDropdownValue('Followup templates');

        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', {'name': "Items to create"}).click();
        cy.findByRole('button', {'name': "Add ticket"}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully added');
        cy.checkAndCloseAlert('Item successfully added');

        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('ITILFollowupTemplate', {
                'name': 'ITILFollowup template 1 - ' + form_id,
                'content': 'My ITILFollowup template content',
            });
        });
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', {'name': "Followups configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Followups').as("itilfollowup_dropdown");

        // Default value
        cy.get('@itilfollowup_dropdown').should(
            'have.text',
            'All answers to "Followup templates" questions'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select followup templates...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select questions...').should('not.exist');

        // Switch to "No Followup"
        cy.get('@itilfollowup_dropdown').selectDropdownValue('No Followup');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itilfollowup_dropdown').should('have.text', 'No Followup');

        // Switch to "Specific Followup templates"
        cy.get('@form_id').then((form_id) => {
            cy.get('@itilfollowup_dropdown').selectDropdownValue('Specific Followup templates');
            cy.get('@config').getDropdownByLabelText('Select followup templates...').as('specific_itilfollowup_dropdown');
            cy.get('@specific_itilfollowup_dropdown').selectDropdownValue('ITILFollowup template 1 - ' + form_id);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
            cy.get('@itilfollowup_dropdown').should('have.text', 'Specific Followup templates');
            cy.get('@specific_itilfollowup_dropdown').should('have.text', '×ITILFollowup template 1 - ' + form_id);
        });

        // Switch to "Answer from specific questions"
        cy.get('@itilfollowup_dropdown').selectDropdownValue('Answer from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_dropdown');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My ITILFollowup template question');

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itilfollowup_dropdown').should('have.text', 'Answer from specific questions');
        cy.get('@specific_answers_dropdown').should('have.text', '×My ITILFollowup template question');

        // Switch to "Answer to last "Followup templates" question"
        cy.get('@itilfollowup_dropdown').selectDropdownValue('Answer to last "Followup templates" question');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itilfollowup_dropdown').should('have.text', 'Answer to last "Followup templates" question');

        // Switch to "All answers to "Followup templates" questions"
        cy.get('@itilfollowup_dropdown').selectDropdownValue('All answers to "Followup templates" questions');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itilfollowup_dropdown').should('have.text', 'All answers to "Followup templates" questions');
    });

    it('can create ticket using default configuration', () => {
        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.getDropdownByLabelText("My ITILFollowup template question").as('itilfollowup_dropdown');
        cy.get('@form_id').then((form_id) => {
            cy.get('@itilfollowup_dropdown').selectDropdownValue('ITILFollowup template 1 - ' + form_id);
        });
        cy.findByRole('button', {'name': 'Send form'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check if followup template content is displayed
        cy.contains('My ITILFollowup template content');

        // Others possibles configurations are tested directly by the backend.
    });
});
