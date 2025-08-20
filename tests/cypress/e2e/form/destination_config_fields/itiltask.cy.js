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

describe('ITILTask configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Create form
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');
        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My question");

        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();

        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('TaskTemplate', {
                'name': `Task template 1 - ${form_id}`,
                'content': 'My Task template content',
            });
        });
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', {'name': "Tasks configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select strategy...').as("itiltask_dropdown");

        // Default value
        cy.get('@itiltask_dropdown').should(
            'have.text',
            'No Task'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select task templates...').should('not.exist');

        // Switch to "Specific Task templates"
        cy.get('@form_id').then((form_id) => {
            cy.get('@itiltask_dropdown').selectDropdownValue('Specific Task templates');
            cy.get('@config').getDropdownByLabelText('Select task templates...').as('specific_itiltask_dropdown');
            cy.get('@specific_itiltask_dropdown').selectDropdownValue(`Task template 1 - ${form_id}`);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
            cy.get('@itiltask_dropdown').should('have.text', 'Specific Task templates');
            cy.get('@specific_itiltask_dropdown').should('have.text', `×Task template 1 - ${form_id}`);
        });

        // Switch to "No Task"
        cy.get('@itiltask_dropdown').selectDropdownValue('No Task');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itiltask_dropdown').should('have.text', 'No Task');
    });

    it('can create ticket using specific task template', () => {
        cy.findByRole('region', {'name': "Tasks configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select strategy...').as("itiltask_dropdown");

        // Switch to "Specific Task templates"
        cy.get('@form_id').then((form_id) => {
            cy.get('@itiltask_dropdown').selectDropdownValue('Specific Task templates');
            cy.get('@config').getDropdownByLabelText('Select task templates...').as('specific_itiltask_dropdown');
            cy.get('@specific_itiltask_dropdown').selectDropdownValue(`Task template 1 - ${form_id}`);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
        });

        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.findByRole('button', {'name': 'Submit'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check if followup template content is displayed
        cy.contains('My Task template content');

        // Others possibles configurations are tested directly by the backend.
    });
});
