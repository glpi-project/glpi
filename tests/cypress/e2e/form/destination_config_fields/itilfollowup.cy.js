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

describe('ITILFollowup configuration', () => {
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
            cy.createWithAPI('ITILFollowupTemplate', {
                'name': `ITILFollowup template 1 - ${form_id}`,
                'content': 'My ITILFollowup template content',
            });
        });
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', {'name': "Followups configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select strategy...').as("itilfollowup_dropdown");

        // Default value
        cy.get('@itilfollowup_dropdown').should(
            'have.text',
            'No Followup'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select followup templates...').should('not.exist');

        // Switch to "Specific Followup templates"
        cy.get('@form_id').then((form_id) => {
            cy.get('@itilfollowup_dropdown').selectDropdownValue('Specific Followup templates');
            cy.get('@config').getDropdownByLabelText('Select followup templates...').as('specific_itilfollowup_dropdown');
            cy.get('@specific_itilfollowup_dropdown').selectDropdownValue(`ITILFollowup template 1 - ${form_id}`);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
            cy.get('@itilfollowup_dropdown').should('have.text', 'Specific Followup templates');
            cy.get('@specific_itilfollowup_dropdown').should('have.text', `×ITILFollowup template 1 - ${form_id}`);
        });

        // Switch to "No Followup"
        cy.get('@itilfollowup_dropdown').selectDropdownValue('No Followup');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@itilfollowup_dropdown').should('have.text', 'No Followup');
    });

    it('can create ticket using specific followup template', () => {
        cy.findByRole('region', {'name': "Followups configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Select strategy...').as("itilfollowup_dropdown");

        // Switch to "Specific Followup templates"
        cy.get('@form_id').then((form_id) => {
            cy.get('@itilfollowup_dropdown').selectDropdownValue('Specific Followup templates');
            cy.get('@config').getDropdownByLabelText('Select followup templates...').as('specific_itilfollowup_dropdown');
            cy.get('@specific_itilfollowup_dropdown').selectDropdownValue(`ITILFollowup template 1 - ${form_id}`);

            cy.findByRole('button', {'name': 'Update item'}).click();
            cy.checkAndCloseAlert('Item successfully updated');
            cy.get('@itilfollowup_dropdown').should('have.text', 'Specific Followup templates');
            cy.get('@specific_itilfollowup_dropdown').should('have.text', `×ITILFollowup template 1 - ${form_id}`);
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
        cy.contains('My ITILFollowup template content');

        // Others possibles configurations are tested directly by the backend.
    });
});
