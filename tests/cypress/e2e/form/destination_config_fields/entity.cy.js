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

describe('Entity configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Create form with a single "entity" question
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');
        cy.findByRole('button', { 'name': "Add a question" }).click();
        cy.focused().type("My entity question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('GLPI Objects');

        cy.getDropdownByLabelText('Select an itemtype').selectDropdownValue('Entities');

        cy.findByRole('button', { 'name': 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();
    });

    it('can use all possibles configuration options', () => {
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.findByRole('region', { 'name': "Entity configuration" }).as("config");
        cy.get('@config').getDropdownByLabelText('Entity').as("entity_dropdown");

        // Default value
        cy.get('@entity_dropdown').should(
            'have.text',
            'Answer to last "Entity" item question'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select an entity...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select a question...').should('not.exist');

        // Switch to "Form filler"
        cy.get('@entity_dropdown').selectDropdownValue('Active entity of the form filler');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@entity_dropdown').should('have.text', 'Active entity of the form filler');

        // Switch to "From form"
        cy.get('@entity_dropdown').selectDropdownValue('From form');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@entity_dropdown').should('have.text', 'From form');

        // Switch to "Specific entity"
        cy.get('@entity_dropdown').selectDropdownValue('Specific entity');
        cy.get('@config').getDropdownByLabelText('Select an entity...').as('specific_entity_dropdown');
        cy.get('@specific_entity_dropdown').selectDropdownValue('»E2ETestEntity');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@entity_dropdown').should('have.text', 'Specific entity');
        cy.get('@specific_entity_dropdown').should('have.text', 'Root entity > E2ETestEntity');

        // Switch to "Answer from a specific question"
        cy.get('@entity_dropdown').selectDropdownValue('Answer from a specific question');
        cy.get('@config').getDropdownByLabelText('Select a question...').as('specific_answer_type_dropdown');
        cy.get('@specific_answer_type_dropdown').selectDropdownValue('My entity question');

        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@entity_dropdown').should('have.text', 'Answer from a specific question');
        cy.get('@specific_answer_type_dropdown').should('have.text', 'My entity question');

        // Switch to "Answer to last "Entity" item question"
        cy.get('@entity_dropdown').selectDropdownValue('Answer to last "Entity" item question');
        cy.findByRole('button', { 'name': 'Update item' }).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@entity_dropdown').should('have.text', 'Answer to last "Entity" item question');
    });

    it('can create ticket using default configuration', () => {
        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('Entity', {
                name: `E2ETestEntityForFormDestinationField-${form_id}`,
                entities_id: 1, // subentity of E2ETestEntity
            });
        });

        cy.openEntitySelector();
        cy.findByRole('gridcell', {'name': "Root entity > E2ETestEntity"})
            .findByTitle('+ sub-entities')
            .click();

        // Go to preview
        cy.findByRole('tab', { 'name': "Form" }).click();
        cy.findByRole('link', { 'name': "Preview" })
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click();

        // Fill form
        cy.get('@form_id').then((form_id) => {
            cy.getDropdownByLabelText("My entity question").selectDropdownValue(`»E2ETestEntityForFormDestinationField-${form_id}`);
        });
        cy.findByRole('button', { 'name': 'Submit' }).click();
        cy.findByRole('link', { 'name': 'My test form' }).click();

        // Check ticket values
        cy.get('@form_id').then((form_id) => {
            cy.findAllByRole('region', { 'name': 'Ticket' }).eq(0).findAllByRole('link', `Root entity > E2ETestEntity > E2ETestEntityForFormDestinationField-${form_id}`).should('exist');
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
