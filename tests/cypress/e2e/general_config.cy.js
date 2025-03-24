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

describe('Notifications', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });

    it('Change devices in menu', () => {
        cy.visit('/front/config.form.php');
        cy.get('#tabspanel .nav-item').contains('Assets').within((nav_link) => {
            cy.wrap(nav_link).click();
        });

        cy.get('.tab-content .tab-pane.active[id^="tab"]').within(() => {
            cy.get('label').contains('Devices displayed in menu').next().within(() => {
                cy.get('select').select(['Simcard items', 'Case items'], {force: true});
            });
            cy.get('button').contains('Save').click();
        });

        cy.get('#navbar-menu').within(() => {
            cy.get('.nav-item.dropdown').contains('Assets').click();
            cy.get('.dropdown-item').contains('Simcard items').should('exist');
            cy.get('.dropdown-item').contains('Case items').should('exist');
        });

        cy.get('.tab-content .tab-pane.active[id^="tab"]').within(() => {
            cy.get('label').contains('Devices displayed in menu').next().within(() => {
                cy.get('select').select(['Case items'], {force: true});
            });
            cy.get('button').contains('Save').click();
        });

        cy.get('#navbar-menu').within(() => {
            cy.get('.nav-item.dropdown').contains('Assets').click();
            cy.get('.dropdown-item').contains('Simcard items').should('not.exist');
            cy.get('.dropdown-item').contains('Case items').should('exist');
        });

        cy.get('.tab-content .tab-pane.active[id^="tab"]').within(() => {
            cy.get('label').contains('Devices displayed in menu').next().within(() => {
                cy.get('select').select([], {force: true});
            });
            cy.get('button').contains('Save').click();
        });

        cy.get('#navbar-menu').within(() => {
            cy.get('.nav-item.dropdown').contains('Assets').click();
            cy.get('.dropdown-item').contains('Simcard items').should('not.exist');
            cy.get('.dropdown-item').contains('Case items').should('not.exist');
        });
    });
});
