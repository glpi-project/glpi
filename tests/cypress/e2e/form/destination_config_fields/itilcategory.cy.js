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

describe('ITILCategory configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Create form with a single "request type" question
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');

        // Create an ITIL category
        cy.get('@form_id').then((form_id) => {
            const itilcategory_name = `Test ITIL Category for the ITILCategory configuration suite - ${form_id}`;

            cy.createWithAPI('ITILCategory', {
                'name': itilcategory_name,
            }).as('itilcategory_id');
        });

        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My ITILCategory question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Dropdowns');
        cy.getDropdownByLabelText("Select a dropdown type").selectDropdownValue('ITIL categories');
        cy.get('@form_id').then((form_id) => {
            const itilcategory_name = `Test ITIL Category for the ITILCategory configuration suite - ${form_id}`;

            // Wait for the items_id dropdown to be loaded
            cy.intercept('/ajax/dropdownAllItems.php').as('dropdownAllItems');

            // eslint-disable-next-line cypress/no-unnecessary-waiting
            cy.wait(200);

            cy.getDropdownByLabelText("Select a dropdown item").selectDropdownValue(`»${itilcategory_name}`);
        });
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();
    });

    it('can use all possibles configuration options', () => {
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.findByRole('region', {'name': "ITIL category configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('ITIL category').as("itilcategory_dropdown");

        // Default value
        cy.get('@itilcategory_dropdown').should(
            'have.text',
            'Answer to last "ITIL Category" dropdown question'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select an ITIL category...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select a question...').should('not.exist');

        // Switch to "Specific ITIL category"
        cy.get('@itilcategory_dropdown').selectDropdownValue('Specific ITIL category');
        cy.get('@config').getDropdownByLabelText('Select an ITIL category...').as('specific_itilcategory_dropdown');
        cy.get('@form_id').then((form_id) => {
            const itilcategory_name = `Test ITIL Category for the ITILCategory configuration suite - ${form_id}`;
            cy.get('@specific_itilcategory_dropdown').selectDropdownValue(`»${itilcategory_name}`);
        });

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@itilcategory_dropdown').should('have.text', 'Specific ITIL category');
        cy.get('@form_id').then((form_id) => {
            const itilcategory_name = `Test ITIL Category for the ITILCategory configuration suite - ${form_id}`;
            cy.get('@specific_itilcategory_dropdown').should('have.text', itilcategory_name);
        });

        // Switch to "Answer from a specific question"
        cy.get('@itilcategory_dropdown').selectDropdownValue('Answer from a specific question');
        cy.get('@config').getDropdownByLabelText('Select a question...').as('specific_answer_type_dropdown');
        cy.get('@specific_answer_type_dropdown').selectDropdownValue('My ITILCategory question');

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Properties');
        cy.get('@itilcategory_dropdown').should('have.text', 'Answer from a specific question');
        cy.get('@specific_answer_type_dropdown').should('have.text', 'My ITILCategory question');
    });

    it('can create ticket using default configuration', () => {
        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill the form
        cy.findByRole('button', {'name': 'Submit'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check ticket values
        cy.get('@form_id').then((form_id) => {
            const itilcategory_name = `Test ITIL Category for the ITILCategory configuration suite - ${form_id}`;
            cy.getDropdownByLabelText('Category').should('have.text', itilcategory_name);
        });

        // Others possibles configurations are tested directly by the backend.
    });
});
