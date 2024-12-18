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

describe('Observer configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);

        cy.createFormWithAPI().as('form_id').visitFormTab('Form');

        // Create a Observer
        cy.get('@form_id').then((form_id) => {
            const observer_name = `Test Observer - ${form_id}`;
            cy.createWithAPI('User', {
                name: observer_name,
            }).as('observer_id');

            cy.findByRole('button', {'name': "Add a new question"}).click();
            cy.focused().type("My Observer question");
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Actors');
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Observers');
            cy.getDropdownByLabelText("Select an actor...").selectDropdownValue(observer_name);
            cy.findByRole('button', {'name': 'Save'}).click();
            cy.checkAndCloseAlert('Item successfully updated');

            // Go to destination tab
            cy.findByRole('tab', { 'name': "Items to create" }).click();
            cy.findByRole('button', { 'name': "Add ticket" }).click();
            cy.checkAndCloseAlert('Item successfully added');
        });
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', { 'name': "Observers configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('Observers').as("observers_dropdown");

        // Default value
        cy.get('@observers_dropdown').should(
            'have.text',
            'From template'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select actors...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select questions...').should('not.exist');

        // Switch to "From template"
        cy.get('@observers_dropdown').selectDropdownValue('From template');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@observers_dropdown').should('have.text', 'From template');

        // Switch to "User who filled the form"
        cy.get('@observers_dropdown').selectDropdownValue('User who filled the form');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@observers_dropdown').should('have.text', 'User who filled the form');

        // Switch to "Specific actors"
        cy.get('@observers_dropdown').selectDropdownValue('Specific actors');
        cy.get('@config').getDropdownByLabelText('Select actors...').as('specific_observers_dropdown');
        cy.get('@form_id').then((form_id) => {
            const observer_name = `Test Observer - ${form_id}`;
            cy.get('@specific_observers_dropdown').selectDropdownValue(observer_name);
        });

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@observers_dropdown').should('have.text', 'Specific actors');
        cy.get('@form_id').then((form_id) => {
            const observer_name = `Test Observer - ${form_id}`;
            cy.get('@specific_observers_dropdown').should('have.text', `×${observer_name}`);
        });

        // Switch to "Answer from specific questions"
        cy.get('@observers_dropdown').selectDropdownValue('Answer from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_type_dropdown');
        cy.get('@specific_answers_type_dropdown').selectDropdownValue('My Observer question');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@observers_dropdown').should('have.text', 'Answer from specific questions');
        cy.get('@specific_answers_type_dropdown').should('have.text', '×My Observer question');

        // Switch to "Answer to last "Observers" question"
        cy.get('@observers_dropdown').selectDropdownValue('Answer to last "Observers" question');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.get('@observers_dropdown').should('have.text', 'Answer to last "Observers" question');
    });

    it('can create ticket using default configuration', () => {
        cy.findByRole('region', { 'name': "Observers configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('Observers').as("observers_dropdown");

        // Switch to "User who filled the form"
        cy.get('@observers_dropdown').selectDropdownValue('User who filled the form');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to preview
        cy.findByRole('tab', { 'name': "Form" }).click();
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click();

        // Fill form
        cy.findByRole('button', { 'name': 'Send form' }).click();
        cy.findByRole('link', { 'name': 'My test form' }).click();

        // Check ticket values
        cy.findByRole('region', { 'name': "Actors" }).within(() => {
            cy.get('select[data-actor-type="observer"]').contains('E2E Tests');
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
