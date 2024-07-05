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

describe('Request type configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);

        // Create form with a single "request type" question
        cy.createFormWithAPI().visitFormTab('Form');
        cy.findByRole('button', {'name': "Add a new question"}).click();
        cy.focused().type("My request type question");
        cy.getSelect2DropdownByValue('Short answer').setSelect2Value('Request type');
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', {'name': "Items to create"}).click();
        cy.findByRole('button', {'name': "Add ticket"}).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully added');
    });

    it('can use all possibles configuration options', () => {
        cy.findByRole('region', {'name': "Request type configuration"}).as('config');

        // Default value
        cy.get("@config").select2ValueShouldBeSelected("Last valid answer");

        // Make sure hidden dropdowns are not displayed
        cy.get("@config").select2ValueShouldNotBeSelected('Select a question...');
        cy.get("@config").select2ValueShouldNotBeSelected('Select a request type...');

        // Switch to "From template"
        cy.get("@config")
            .getSelect2DropdownByValue('Last valid answer')
            .setSelect2Value('From template')
        ;
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.get("@config").select2ValueShouldBeSelected("From template");

        // Switch to "Specific value"
        cy.get("@config")
            .getSelect2DropdownByValue('From template')
            .setSelect2Value('Specific value')
        ;
        cy.get("@config")
            .getSelect2DropdownByValue('Select a request type...')
            .setSelect2Value('Request')
        ;
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.get("@config").select2ValueShouldBeSelected("Specific value");
        cy.get("@config").select2ValueShouldBeSelected("Request");

        // Switch to "Answer from a specific question"
        cy.get("@config")
            .getSelect2DropdownByValue("Specific value")
            .setSelect2Value("Answer from a specific question")
        ;
        cy.get("@config")
            .getSelect2DropdownByValue('Select a question...')
            .setSelect2Value('My request type question')
        ;
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.get("@config").select2ValueShouldBeSelected("Answer from a specific question");
        cy.get("@config").select2ValueShouldBeSelected("My request type question");
    });

    it('can create ticket using default configuration', () => {
        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.findByRole("region", {"name": "My request type question"})
            .getSelect2DropdownByValue('-----')
            .setSelect2Value('Request')
        ;
        cy.findByRole('button', {'name': 'Send form'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check ticket values
        cy.findByRole('region', {'name': 'Ticket'})
            .select2ValueShouldBeSelected('Request')
        ;

        // Others possibles configurations are tested directly by the backend.
    });
});
