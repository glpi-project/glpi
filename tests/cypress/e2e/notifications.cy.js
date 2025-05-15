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
    it('View Templates for a Notification', () => {
        // New Ticket notification
        cy.visit('/front/notification.form.php?id=2');

        cy.get('#tabspanel .nav-item').contains('Templates').within((nav_link) => {
            cy.get('.glpi-badge').should('exist').invoke('text').should((t) => {
                expect(parseInt(t)).to.be.gte(1);
            });
            cy.wrap(nav_link).click();
        });

        cy.get('.tab-content .tab-pane.active[id^="tab"]').within(() => {
            cy.get('.btn').contains('Add a template').should('exist');
            cy.get('table.table[id^="datatable"]').should('exist').within(() => {
                cy.get('thead th:nth-of-type(1) input[type="checkbox"].massive_action_checkbox').should('exist');
                cy.get('thead th').contains('ID').should('exist');
                cy.get('thead th').contains('Template').should('exist');
                cy.get('thead th').contains('Mode').should('exist');

                // All cells in 1st column should be checkboxes
                cy.get('tbody td:nth-of-type(1)').each((cell) => {
                    cy.wrap(cell).find('input[type="checkbox"]').should('exist');
                });
                // All cells in 2nd column should be links to notification_notificationtemplate forms
                cy.get('tbody td:nth-of-type(2)').each((cell) => {
                    cy.wrap(cell).find('a').invoke('attr', 'href').should('contain', '/front/notification_notificationtemplate.form.php');
                });
                // All cells in 3rd column should be links to notificationtemplate forms
                cy.get('tbody td:nth-of-type(3)').each((cell) => {
                    cy.wrap(cell).find('a').invoke('attr', 'href').should('contain', '/front/notificationtemplate.form.php');
                });
            });
        });

        cy.findByRole('tabpanel').within(() => {
            cy.findByRole('button', { name: /Add a template/ }).click();
        });

        // Should be redirected to notification_notificationtemplate form
        cy.url().should('include', '/front/notification_notificationtemplate.form.php').and('include', 'notifications_id=2');
        cy.get('label').contains('Notification').next().find('a').invoke('attr', 'href').should('contain', '/front/notification.form.php?id=2');

        // Go back to the notification form
        cy.go('back');
        cy.findByRole('tab', { name: /Templates/ }).click();

        cy.findByRole('table').findByRole('link', {name: "Tickets"}).click();
        cy.findByRole('tab', { name: /Template translations/ }).click();
        // Click the default template translation link
        cy.findByRole('link', {name: "Default translation"}).click();
        cy.findByRole('tab', { name: /Template translation/ }).click();
        cy.findByRole('tabpanel').within(() => {
            cy.get('select[name=language]').should('have.value', '');
            cy.get('select[name=language] option:selected').should('have.text', 'Default translation');
        });
    });
});
