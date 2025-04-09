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

describe('Number form question type', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the number form question type suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.findByRole("button", { name: "Add a question" }).should('exist').click();

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test number question");

            // Change question type
            cy.getDropdownByLabelText('Question type').selectDropdownValue('Short answer');

            // Change question sub type
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue('Number');
        });
    });

    const testDefaultValue = (value) => {
        // Define default value
        cy.findByRole('spinbutton', { name: 'Default value' }).should('exist').type(value);

        // Save
        cy.findByRole('button', { name: 'Save' }).click();

        // Reload the page
        cy.reload();

        // Check the default value
        cy.findByRole('spinbutton', { name: 'Default value' }).should('have.value', value);

        // Go to preview page (remove the target="_blank" attribute to stay in the same window)
        cy.findByRole("link", { name: "Preview" })
            .invoke('attr', 'target', '_self')
            .click();

        // Check the default value in the preview page
        cy.findByRole('spinbutton', { name: 'Test number question' }).should('have.value', value);

        // Submit
        cy.findByRole('button', { name: 'Submit' }).click();

        // Check the form was submitted
        cy.checkAndCloseAlert('Item successfully created');
    };

    it('should be able to define an integer as default value', () => {
        testDefaultValue('42');
    });

    it('should be able to define a float as default value', () => {
        testDefaultValue('3.14');
    });
});
