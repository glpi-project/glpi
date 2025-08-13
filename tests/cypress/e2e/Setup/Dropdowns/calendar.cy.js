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

describe('Calendar', () => {
    let calendar_id;

    before(() => {
        cy.createWithAPI('Calendar', {
            name: 'Test Calendar',
            entities_id: 1
        }).then(id => {
            calendar_id = id;
        });
        cy.createWithAPI('Holiday', {
            name: 'Test Holiday',
            entities_id: 1,
            begin_date: '2025-01-13',
            end_date: '2025-01-14',
            is_perpetual: 1,
        });
    });

    beforeEach(() => {
        cy.login();
    });

    it('Time range form', () => {
        cy.visit(`/front/calendar.form.php?id=${calendar_id}`);

        cy.findByRole('tab', { name: 'Time ranges' }).click();
        cy.findByRole('tabpanel').within(() => {
            cy.getDropdownByLabelText('Day').selectDropdownValue('Tuesday');
            cy.getDropdownByLabelText('Start').selectDropdownValue('08:00');
            cy.getDropdownByLabelText('End').selectDropdownValue('18:00');
            cy.findByRole('button', { name: 'Add' }).click();

            cy.findAllByRole('row').then($rows => {
                expect($rows).to.have.length(2);
                cy.wrap($rows).eq(1).within(() => {
                    cy.findByText('Tuesday').should('exist');
                    cy.findByText('08:00:00').should('exist');
                    cy.findByText('18:00:00').should('exist');
                });
            });
        });
    });

    it('Close times form', () => {
        cy.visit(`/front/calendar.form.php?id=${calendar_id}`);

        cy.findByRole('tab', { name: 'Close times' }).click();
        cy.findByRole('tabpanel').within(() => {
            cy.getDropdownByLabelText('Add a close time').selectDropdownValue('Test Holiday');
            cy.findByRole('button', { name: 'Add' }).click();
            cy.findAllByRole('row').then($rows => {
                expect($rows).to.have.length(2);
                cy.wrap($rows).eq(1).within(() => {
                    cy.findByText('Test Holiday').should('exist');
                    cy.findByText('2025-01-13').should('exist');
                    cy.findByText('2025-01-14').should('exist');
                    cy.findByText('Yes').should('exist');
                });
            });
        });
    });
});
