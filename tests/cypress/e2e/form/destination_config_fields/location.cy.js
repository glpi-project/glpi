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

describe('Location configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        cy.createFormWithAPI().as('form_id').visitFormTab('Form');

        // Create a location
        cy.get('@form_id').then((form_id) => {
            const location_name = `Test Location - ${form_id}`;
            cy.createWithAPI('Location', {
                name: location_name,
            });

            cy.findByRole('button', {'name': "Add a question"}).click();
            cy.focused().type("My Location question");
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Dropdowns');
            cy.getDropdownByLabelText("Select a dropdown type").selectDropdownValue('Locations');
            cy.get('@form_id').then((form_id) => {
                const location_name = `Test Location - ${form_id}`;
                cy.getDropdownByLabelText("Select a dropdown item").selectDropdownValue(`»${  location_name}`);
            });
            cy.findByRole('button', {'name': 'Save'}).click();
            cy.checkAndCloseAlert('Item successfully updated');

            // Go to destination tab
            cy.findByRole('tab', { 'name': "Destinations 1" }).click();
        });
    });

    it('can use all possibles configuration options', () => {
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.findByRole('region', { 'name': "Location configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('Location').as("location_dropdown");

        // Default value
        cy.get('@location_dropdown').should(
            'have.text',
            'Answer to last "Location" dropdown question'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select a location...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select a question...').should('not.exist');

        // Switch to "From template"
        cy.get('@location_dropdown').selectDropdownValue('From template');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@location_dropdown').should('have.text', 'From template');

        // Switch to "Specific location"
        cy.get('@location_dropdown').selectDropdownValue('Specific location');
        cy.get('@config').getDropdownByLabelText('Select a location...').as('specific_location_dropdown');
        cy.get('@form_id').then((form_id) => {
            const location_name = `Test Location - ${form_id}`;
            cy.get('@specific_location_dropdown').selectDropdownValue(`»${location_name}`);
        });

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@location_dropdown').should('have.text', 'Specific location');
        cy.get('@form_id').then((form_id) => {
            const location_name = `Test Location - ${form_id}`;
            cy.get('@specific_location_dropdown').should('have.text', location_name);
        });

        // Switch to "Answer from a specific question"
        cy.get('@location_dropdown').selectDropdownValue('Answer from a specific question');
        cy.get('@config').getDropdownByLabelText('Select a question...').as('specific_answer_type_dropdown');
        cy.get('@specific_answer_type_dropdown').selectDropdownValue('My Location question');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@location_dropdown').should('have.text', 'Answer from a specific question');
        cy.get('@specific_answer_type_dropdown').should('have.text', 'My Location question');
    });

    it('can create ticket using default configuration', () => {
        // Go to preview
        cy.findByRole('tab', { 'name': "Form" }).click();
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click();

        // Fill form
        cy.findByRole('button', { 'name': 'Submit' }).click();
        cy.findByRole('link', { 'name': 'My test form' }).click();

        cy.get('@form_id').then((form_id) => {
            // Check ticket values
            cy.getDropdownByLabelText('Location').should('have.text', `Test Location - ${form_id}`);
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
