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

describe('Dashboard', () => {
    beforeEach(() => {
        cy.login();
    });

    const dashboards = new Map([
        ['Asset', '/front/dashboard_assets.php'],
        ['Assistance', '/front/dashboard_helpdesk.php'],
        ['Central', '/front/central.php'],
        ['Tickets Mini', '/front/ticket.php'],
    ]);

    dashboards.forEach((value, key) => {
        it(`${key} Dashboard Loads`, () => {
            cy.visit(value);
            cy.get('.grid-stack-item .g-chart, .grid-stack-item .big-number').should('be.visible');
            // grid-stack-items should have reasonable height
            cy.get('.grid-stack-item:not(.lock-bottom)').each(($el) => {
                cy.get($el).invoke('height').should('be.gt', 30);
            });
        });
    });
});
