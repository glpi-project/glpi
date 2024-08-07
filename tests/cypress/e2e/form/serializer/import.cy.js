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


describe ('Import forms', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin', true);
    });

    it('can import forms', () => {
        // Step 1: file selection
        cy.visit('/front/form/form.php');
        cy.findByRole('button', {'name': "Import forms"}).click();
        cy.findByLabelText("Select your file").selectFile("fixtures/export-of-2-forms.json");

        // Step 2: preview
        cy.findByRole('button', {'name': "Preview import"}).click();
        cy.findAllByRole('row').as('preview');
        cy.get("@preview").eq(1).within(() => {
            cy.findByText("My valid form").should('exist');
            cy.findByText("Ready to be imported").should('exist');
        });
        cy.get("@preview").eq(2).within(() => {
            cy.findByText("My invalid form").should('exist');
            cy.findByText("Can't be imported").should('exist');
        });

        // Step 3: import
        cy.findByRole('button', {'name': "Import"}).click();
        cy.findAllByRole('row').as('preview');
        cy.get("@preview").eq(1).within(() => {
            cy.findByRole("link", {'name': "My valid form"}).should('exist');
            cy.findByText("Imported").should('exist');
        });
        cy.get("@preview").eq(2).within(() => {
            cy.findByText("My invalid form").should('exist');
            cy.findByText("Not imported").should('exist');
        });

        // Go back to first step
        cy.findByRole('link', {'name': "Import another file"}).click();
        cy.findByLabelText("Select your file").should('exist');
    });
});
