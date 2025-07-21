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

describe('Tabs', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('can use the "forcetab" URL parameter to land on a specific tab', () => {
        cy.visit("/front/user.form.php?id=2&forcetab=Change$1");
        cy.findByRole('tab', { name: 'Created changes' })
            .should('have.attr', 'aria-selected', 'true');
        cy.findByRole('tab', { name: 'Created problems' })
            .should('not.have.attr', 'aria-selected', 'true');

        cy.visit("/front/user.form.php?id=2&forcetab=Problem$1");
        cy.findByRole('tab', { name: 'Created problems' })
            .should('have.attr', 'aria-selected', 'true');
        cy.findByRole('tab', { name: 'Created changes' })
            .should('not.have.attr', 'aria-selected', 'true');
    });
});
