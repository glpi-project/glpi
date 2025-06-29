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

describe('Log viewer', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('List Accessibility', () => {
        cy.visit('/front/logs.php');
        cy.get('#page > .container').within(() => {
            cy.get('.list-group .list-group-item').should('have.length.greaterThan', 0);
            cy.injectAndCheckA11y();
        });
    });
    it('Viewer Accessibility', () => {
        cy.visit('/front/logviewer.php?filepath=php-errors.log');
        cy.get('.log_entry').should('have.length.greaterThan', 0);
        cy.get('.log-entries').injectAndCheckA11y();
    });
});
