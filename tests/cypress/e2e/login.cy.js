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
describe('Login tests', () => {
    it('can login from the local database', () => {
        cy.visit('/');
        cy.title().should('eq', 'Authentication - GLPI');
        cy.findByRole('textbox', {'name': "Login"}).type('e2e_tests');
        cy.findByLabelText("Password").type('glpi');
        cy.findByRole('checkbox', {name: "Remember me"}).check();
        // Select 'local' from the 'auth' dropdown
        cy.findByLabelText("Login source").select('local', { force: true });

        cy.findByRole('button', {name: "Sign in"}).click();
        // After logging in, the url should contain /front/central.php or /front/helpdesk.public.php
        cy.url().should('match', /\/front\/(central|helpdesk.public).php/);

        cy.getCookies().should('have.length.gte', 2).then((cookies) => {
            // Should be two cookies starting with 'glpi_' and one of them should end with '_rememberme'
            expect(cookies.filter((cookie) => cookie.name.startsWith('glpi_'))).to.have.length(2);
            expect(cookies.filter((cookie) => cookie.name.startsWith('glpi_') && cookie.name.endsWith('_rememberme'))).to.have.length(1);
        });
    });
});
