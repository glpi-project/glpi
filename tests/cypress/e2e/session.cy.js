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

describe("Session", () => {
    it('can login', () => {
        cy.blockGLPIDashboards();

        // Go to login page
        cy.visit('/', {
            headers: {
                'Accept-Language': 'en-GB,en;q=0.9',
            }
        });
        cy.title().should('eq', 'Authentication - GLPI');

        // Fill form
        cy.findByRole('textbox', {'name': "Login"}).type('e2e_tests');
        cy.findByLabelText("Password").type('glpi');
        cy.findByRole('checkbox', {name: "Remember me"}).check();
        cy.getDropdownByLabelText("Login source").selectDropdownValue('GLPI internal database');

        // After logging in, the url should contain /front/central.php or /Helpdesk
        cy.findByRole('button', {name: "Sign in"}).click();
        cy.url().should('match', /(\/front\/central.php|\/Helpdesk)/);

        // Validate cookies
        cy.getCookies().should('have.length.gte', 2).then((cookies) => {
            // Should be two cookies starting with 'glpi_' and one of them should end with '_rememberme'
            expect(cookies.filter((cookie) => cookie.name.startsWith('glpi_'))).to.have.length(2);
            expect(cookies.filter((cookie) => cookie.name.startsWith('glpi_') && cookie.name.endsWith('_rememberme'))).to.have.length(1);
        });
    });

    it('can logout', () => {
        // Login and go to any page
        cy.login();
        cy.visit('/front/computer.form.php');

        // Logout
        cy.findByRole('link', {name: 'User menu'}).click();
        cy.findByRole('link', {name: 'Logout'}).click();

        // Should be redirected to login page
        cy.visit('/front/computer.form.php');
        cy.findByText('Your session has expired. Please log in again.').should('exist');
    });

    it("redirect to requested page after login", () => {
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

    it("can change profile", () => {
        // Login and go to any page
        cy.login();
        cy.visit('/front/computer.form.php');
        cy.findByRole('link', {'name': 'User menu'}).should('contain.text', 'Super-Admin');
        cy.findByRole('listitem', {'name': 'Administration'}).should('exist');

        // Change profile
        cy.findByRole('link', {'name': 'User menu'}).click();
        cy.findByRole('button', {'name': 'Change profile'}).click();
        cy.findByRole('button', {'name': 'Self-Service'}).click();
        cy.findByRole('link', {'name': 'User menu'}).should('contain.text', 'Self-Service');
        cy.findByRole('listitem', {'name': 'Administration'}).should('not.exist');
    });

    // Note: testing that the current entity can be changed is done in the
    // dedicated entities_selector.cy.js file
});

