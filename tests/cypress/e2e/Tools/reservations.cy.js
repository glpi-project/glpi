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

describe('Reservations', () => {
    before(() => {
        // Create a computer that can be reserved
        cy.createWithAPI('Computer', { name: 'Reservable computer', entities_id: 1 }).then((computers_id) => {
            cy.createWithAPI('ReservationItem', { itemtype: 'Computer', items_id: computers_id }).as('reservation_items_id');
        });
    });
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('Create a reservation and view', () => {
        cy.get('@reservation_items_id').then((reservation_items_id) => {
            cy.visit(`/front/reservation.php?reservationitems_id=${reservation_items_id}`);
            //FIXME Work around known issue with sector-based JS loading not always loading the TinyMCE library in time
            cy.on('uncaught:exception', (err) => {
                if (err.message.includes('tinyMCE is not defined')) {
                    return false;
                }
            });
            cy.get('.fc-week .fc-day').first().click();
            cy.findByRole('dialog', {name: 'Add reservation'}).within(() => {
                cy.contains('Reservable computer');
                cy.getDropdownByLabelText('By').selectDropdownValue('E2E Tests');
                cy.findByRole('button', { name: 'Add' }).click();
            });

            cy.get('.fc-day-grid-event').first().click();
            cy.findByRole('dialog', {name: 'Edit reservation'}).within(() => {
                cy.contains('Reservable computer');
                cy.findByRole('button', { name: 'Close' }).click();
            });
        });
    });
});
