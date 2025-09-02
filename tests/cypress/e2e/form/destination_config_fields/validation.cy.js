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

describe('Validation configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        cy.createFormWithAPI().as('form_id').visitFormTab('Form');

        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My Assignee question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Actors');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Assignees');

        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My User question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('GLPI Objects');
        cy.getDropdownByLabelText('Select an itemtype').selectDropdownValue('Users');

        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();

        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('User', {
                'name': `Validation configuration test user - ${form_id}`,
            });

            cy.createWithAPI('Group', {
                'name': `Validation configuration test group - ${form_id}`,
            });

            cy.createWithAPI('ITILValidationTemplate', {
                'name': `Validation configuration test validation template - ${form_id}`
            });

            cy.createWithAPI('ValidationStep', {
                'name': `Validation configuration test validation step - ${form_id}`,
                'minimal_required_validation_percent': 100,
            });
        });
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', {'name': "Approval configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select strategy...').as("validation_dropdown");

        // Default value
        cy.get('@validation_dropdown').should(
            'have.text',
            'No approval'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select a request type...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select a question...').should('not.exist');

        // Switch to "Specific values"
        cy.get('@validation_dropdown').selectDropdownValue('Specific Approval templates');
        cy.get('@config').getDropdownByLabelText('Select approval templates...').as('specific_templates_dropdown');
        cy.get('@form_id').then((form_id) => {
            cy.get('@specific_templates_dropdown').selectDropdownValue(`Validation configuration test validation template - ${form_id}`);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
            cy.get('@validation_dropdown').hasDropdownValue('Specific Approval templates');
            cy.get('@specific_templates_dropdown').hasDropdownValue(`Validation configuration test validation template - ${form_id}`);
        });

        // Switch to "Specific actors"
        cy.get('@validation_dropdown').selectDropdownValue('Specific actors');
        cy.get('@config').getDropdownByLabelText('Select validation step...').as('validation_step_dropdown');
        cy.get('@config').getDropdownByLabelText('Select actors...').as('specific_actors_dropdown');
        cy.get('@form_id').then((form_id) => {
            cy.get('@validation_step_dropdown').selectDropdownValue(`Validation configuration test validation step - ${form_id}`);

            cy.get('@specific_actors_dropdown').selectDropdownValue(`Validation configuration test user - ${form_id}`);
            cy.get('@specific_actors_dropdown').selectDropdownValue(`Validation configuration test group - ${form_id}`);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
            cy.get('@validation_dropdown').should('have.text', 'Specific actors');
            cy.get('@validation_step_dropdown').should('have.text', `Validation configuration test validation step - ${form_id}`);
            cy.get('@specific_actors_dropdown').should(
                'have.text',
                `×Validation configuration test user - ${form_id}×Validation configuration test group - ${form_id}`
            );
        });

        // Switch to "Answer from specific questions"
        cy.get('@validation_dropdown').selectDropdownValue('Answer from specific questions');
        cy.get('@config').getDropdownByLabelText('Select validation step...').as('validation_step_dropdown');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_dropdown');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My User question');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My Assignee question');

        cy.get('@form_id').then((form_id) => {
            cy.get('@validation_step_dropdown').selectDropdownValue(`Validation configuration test validation step - ${form_id}`);
        });

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@validation_dropdown').should('have.text', 'Answer from specific questions');
        cy.get('@form_id').then((form_id) => {
            cy.get('@validation_step_dropdown')
                .should(
                    'have.text',
                    `Validation configuration test validation step - ${form_id}`
                );
        });
        cy.get('@specific_answers_dropdown').should(
            'have.text',
            '×My User question×My Assignee question'
        );
    });

    it('can define multiple strategies at once', () => {
        cy.findByRole('region', {'name': "Approval configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select strategy...').as("validation_dropdown");

        // Add first strategy
        cy.get('@validation_dropdown').selectDropdownValue('Specific actors');
        cy.get('@config').getDropdownByLabelText('Select actors...').eq(0).as('specific_actors_dropdown');
        cy.get('@form_id').then((form_id) => {
            cy.get('@specific_actors_dropdown').selectDropdownValue(`Validation configuration test user - ${form_id}`);
            cy.get('@specific_actors_dropdown').selectDropdownValue(`Validation configuration test group - ${form_id}`);
        });

        // Add second strategy
        cy.get('@config').findByRole('button', {'name': 'Combine with another option'}).click();
        cy.get('@validation_dropdown').eq(-1).selectDropdownValue('Specific Approval templates');
        cy.get('@config').getDropdownByLabelText('Select approval templates...').as('specific_templates_dropdown');
        cy.get('@form_id').then((form_id) => {
            cy.get('@specific_templates_dropdown').selectDropdownValue(`Validation configuration test validation template - ${form_id}`);
        });

        // Add third strategy
        cy.get('@config').findByRole('button', {'name': 'Combine with another option'}).click();
        cy.get('@validation_dropdown').eq(-1).selectDropdownValue('Answer from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_dropdown');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My User question');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My Assignee question');

        // Add fourth strategy
        cy.get('@config').findByRole('button', {'name': 'Combine with another option'}).click();
        cy.get('@validation_dropdown').eq(-1).selectDropdownValue('Specific actors');
        cy.get('@config').getDropdownByLabelText('Select actors...').eq(-1).as('specific_actors_dropdown_2');
        cy.get('@form_id').then((form_id) => {
            cy.get('@specific_actors_dropdown_2').selectDropdownValue(`Validation configuration test user - ${form_id}`);
        });

        // Save
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Check values
        cy.get('@form_id').then((form_id) => {
            cy.get('@validation_dropdown').should('have.length', 4);

            cy.get('@validation_dropdown').eq(0).should('have.text', 'Specific actors');
            cy.get('@specific_actors_dropdown').should(
                'have.text',
                `×Validation configuration test user - ${form_id}×Validation configuration test group - ${form_id}`
            );

            cy.get('@validation_dropdown').eq(1).should('have.text', 'Specific Approval templates');
            cy.get('@specific_templates_dropdown').should(
                'have.text',
                `×Validation configuration test validation template - ${form_id}`
            );

            cy.get('@validation_dropdown').eq(2).should('have.text', 'Answer from specific questions');
            cy.get('@specific_answers_dropdown').should(
                'have.text',
                '×My User question×My Assignee question'
            );

            cy.get('@validation_dropdown').eq(3).should('have.text', 'Specific actors');
            cy.get('@specific_actors_dropdown_2').should(
                'have.text',
                `×Validation configuration test user - ${form_id}`
            );
        });
    });

    it('can create ticket using a specific question answer', () => {
        // Switch to "Answer from specific questions"
        cy.findByRole('region', {'name': "Approval configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select strategy...').selectDropdownValue('Answer from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_dropdown');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My User question');

        // Save
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.get('@form_id').then((form_id) => {
            cy.getDropdownByLabelText("My Assignee question").selectDropdownValue(`Validation configuration test group - ${form_id}`);
            cy.getDropdownByLabelText("My User question").selectDropdownValue(`Validation configuration test user - ${form_id}`);
        });
        cy.findByRole('button', {'name': 'Submit'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check ticket values
        cy.findAllByRole('region', {'name': "Ticket"}).eq(1).contains('Waiting for approval');
        cy.get('@form_id').then((form_id) => {
            cy.findByRole('link', {'name': `Validation configuration test user - ${form_id}`}).should('exist');
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
