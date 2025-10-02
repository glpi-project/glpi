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

describe('Form plugins', () => {
    beforeEach(() => {
        cy.login();
    });

    it('can use plugin questions types', () => {
        // Create and go to form
        cy.createFormWithAPI().visitFormTab('Form');

        // Create range question
        cy.findByRole('button', {name: "Add a question"}).click();
        cy.focused().type('My range question');
        cy.getDropdownByLabelText('Question type').selectDropdownValue("Tester plugin");
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue("Range");

        // Save and preview form
        cy.findByRole('button', {name: "Save"}).click();
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully updated')
        ;
        cy.findByRole('link', { name: "Preview" })
            .invoke('removeAttr', 'target')
            .click()
        ;

        // Sumbmit form with the default value and go to ticket
        cy.findByRole('button', {name: 'Submit'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check value was submited
        cy.findByTestId('content').should(
            'contain.text',
            '1) My range question: 50'
        );
    });

    it('can configure access policies from plugins', () => {
        // Create and go to form
        cy.createFormWithAPI().visitFormTab('Policies');

        // Config day of the week
        cy.findByRole('region', {
            name: 'Restrict access to a specific day of the week'
        }).within(() => {
            // Validate default values
            cy.getDropdownByLabelText('Day').should('have.text', "Monday");
            cy.findByRole('checkbox', {name: 'Active'}).should('not.be.checked');

            // Apply config
            cy.getDropdownByLabelText('Day').selectDropdownValue('Thursday');
            cy.findByRole('checkbox', {name: 'Active'}).should('be.checked');
        });

        // Save and reload
        cy.findByRole('button', {name: 'Save changes'}).click();
        cy.reload();

        // Validate that the value was applied and that the policy is active.
        cy.findByRole('region', {
            name: 'Restrict access to a specific day of the week'
        }).within(() => {
            cy.findByRole('checkbox', {name: 'Active'}).should('be.checked');
            cy.getDropdownByLabelText('Day').should('have.text', "Thursday");
        });
    });

    it('can configure destinations from plugins', () => {
        // Create and go to form
        cy.createFormWithAPI().visitFormTab('Destinations');

        // Add computer destination
        cy.findByRole('button', {name: "Add Computer"}).click();

        // Set name and save
        cy.findByRole('textbox', {name: 'Name'}).should(
            'not.have.value',
            "My computer name"
        );
        cy.findByRole('textbox', {name: 'Name'}).type("My computer name");
        cy.findByRole('button', {name: 'Update item'}).click();

        // Validate value was saved
        cy.findByRole('textbox', {name: 'Name'}).should(
            'have.value',
            "My computer name"
        );
    });

    it('can configure destination config field from plugins', () => {
        // Create and go to form
        cy.createFormWithAPI().visitFormTab('Destinations');

        // Add external ID field
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.findByRole('region', { 'name': 'External ID configuration' }).as("config");
        cy.get('@config').getDropdownByLabelText('External ID').selectDropdownValue('Specific external ID');
        cy.get('@config').findAllByRole('textbox', {name: 'Specific external ID'}).eq(-1).type("MY-EXTERNAL-ID");

        // Save
        cy.findByRole('button', {name: 'Update item'}).click();

        // Validate value was saved
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@config').getDropdownByLabelText('External ID').should('have.text', 'Specific external ID');
        cy.get('@config').findAllByRole('textbox', {name: 'Specific external ID'}).eq(-1).should('have.value', "MY-EXTERNAL-ID");
    });
});
