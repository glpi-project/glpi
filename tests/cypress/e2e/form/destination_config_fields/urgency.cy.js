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

describe('Urgency configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Create form with a single "urgency" question
        cy.createFormWithAPI().visitFormTab('Form');
        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My urgency question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Urgency');
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();
    });

    it('can use all possibles configuration options', () => {
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.findByRole('region', {'name': "Urgency configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Urgency').as("urgency_dropdown");

        // Default value
        cy.get('@urgency_dropdown').should(
            'have.text',
            'Answer to last "Urgency" question'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select an urgency level...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select a question...').should('not.exist');

        // Switch to "From template"
        cy.get('@urgency_dropdown').selectDropdownValue('From template');
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@urgency_dropdown').should('have.text', 'From template');

        // Switch to "Specific type"
        cy.get('@urgency_dropdown').selectDropdownValue('Specific urgency');
        cy.get('@config').getDropdownByLabelText('Select an urgency level...').as('specific_urgency_dropdown');
        cy.get('@specific_urgency_dropdown').selectDropdownValue('High');

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@urgency_dropdown').should('have.text', 'Specific urgency');
        cy.get('@specific_urgency_dropdown').should('have.text', 'High');

        // Switch to "Answer from a specific question"
        cy.get('@urgency_dropdown').selectDropdownValue('Answer from a specific question');
        cy.get('@config').getDropdownByLabelText('Select a question...').as('specific_answer_type_dropdown');
        cy.get('@specific_answer_type_dropdown').selectDropdownValue('My urgency question');

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@urgency_dropdown').should('have.text', 'Answer from a specific question');
        cy.get('@specific_answer_type_dropdown').should('have.text', 'My urgency question');
    });

    it('can create ticket using default configuration', () => {
        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.getDropdownByLabelText("My urgency question").selectDropdownValue('Very high');
        cy.findByRole('button', {'name': 'Submit'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check ticket values
        cy.getDropdownByLabelText('Urgency').should('have.text', 'Very high');

        // Others possibles configurations are tested directly by the backend.
    });
});
