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

describe('Self-Service Tickets', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Self-Service');
    });
    it('Create a ticket', () => {
        cy.visit('/');

        cy.get('aside.sidebar').contains('Create a ticket').click();
        cy.url().should('include', '/front/helpdesk.public.php?create_ticket=1');

        cy.get('form.new-itil-object').within(() => {
            cy.get('input[name="name"]').type('My first ticket');
            cy.get('textarea[name="content"]').type('This is my first ticket in GLPI. I sure hope it works.', { interactive: true });
            cy.get('button[type="submit"]').contains('Submit message').click();
        });

        cy.url().should('include', '/front/tracking.injector.php');
        cy.get('main#page img').should('have.attr', 'src', '/pics/ok.png');
    });
});
