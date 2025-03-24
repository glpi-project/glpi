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

describe('File upload', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
        cy.visit('/front/document.form.php');
    });

    it('Can upload file', () => {
        // Upload file
        cy.get("input[type=file]").selectFile("fixtures/uploads/bar.txt");
        cy.findByText('Upload successful').should('exist');
        cy.findByRole("button", {'name': "Add"}).click();
        cy.findByRole('textbox', {'name': "Name"}).should('have.value', 'bar.txt');

        // Download file
        cy.get('#main-form')
            .findByRole('link', {'name': "bar.txt"})
            .invoke('attr', 'target', '_self') // Cypress don't like new tabs
            .click();
        cy.readFile('cypress/downloads/bar.txt').then(content => {
            cy.wrap('bar').should('eq', content);
        });
    });
});
