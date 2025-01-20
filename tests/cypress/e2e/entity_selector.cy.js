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

describe('Entity Selector', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('Loads', () => {
        cy.visit('/');
        cy.get('.user-menu-dropdown-toggle:visible').click();
        cy.get('.entity-dropdown-toggle:visible').click();
        cy.get('.entity-dropdown-toggle:visible').next().should('have.class', 'dropdown-menu').and('be.visible');

        cy.get('.entity-dropdown-toggle:visible').next().within(() => {
            cy.get('h3').should('have.text', 'Select the desired entity');
            cy.get('.alert').should('have.length', 2);
            cy.get('input[name="entsearchtext"]').should('be.visible').and('have.attr', 'placeholder', 'Search entity');
            cy.get('.data_tree table tr').should('have.length.gte', 1);
            cy.get('.data_tree table a').contains('Root entity').should('exist');
        });
    });
});
