/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

describe("Login", () => {
    it("redirect to requested page after login", () => {
        // Must visit twice because glpi doens't support redirect on the first
        // ever visit due to some cookies checks...
        cy.visit('/front/ticket.form.php');
        cy.visit('/front/ticket.form.php', {
            failOnStatusCode: false
        });
        cy.findByRole('link', {'name': "Log in again"}).click();

        // Login as e2e_tests
        cy.findByRole('textbox', {'name': "Login"}).type('e2e_tests');
        cy.findByLabelText("Password").type('glpi');
        cy.findByRole('button', {name: "Sign in"}).click();

        // Should be redirected to requested page
        cy.url().should('contains', "/front/ticket.form.php");
    });
});

