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
        cy.findByRole('button', {name: "Add a new question"}).click();
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
        cy.findByRole('button', {name: 'Send form'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check value was submited
        cy.findByTestId('content').should(
            'contain.text',
            '1) My range question: 50'
        );
    });
});
