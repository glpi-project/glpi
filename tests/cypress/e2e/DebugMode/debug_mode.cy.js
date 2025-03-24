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

describe("Debug Mode", () => {
    beforeEach(() => {
        cy.login();
    });
    after(() => {
        cy.disableDebugMode();
    });
    it('No debug mode for non super-admin', () => {
        cy.changeProfile('Admin');
        cy.visit('/front/computer.form.php');
        cy.get('#debug-toolbar-applet').should('not.exist', { timeout: 200 });
        cy.get('header a.user-menu-dropdown-toggle').click();
        cy.get('.dropdown-item[title="Change mode"]').should('not.exist', { timeout: 200 });
    });
    it('Debug mode for super-admin', () => {
        cy.changeProfile('Super-Admin');
        cy.visit('/front/computer.form.php');
        cy.get('#debug-toolbar-applet').should('not.exist');
        cy.get('header a.user-menu-dropdown-toggle').click();
        cy.get('.dropdown-item[title="Change mode"]').should('exist').invoke('attr', 'href').should('include', '/ajax/switchdebug.php');
    });
});
