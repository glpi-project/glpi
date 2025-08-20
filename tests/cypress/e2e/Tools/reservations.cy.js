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
    let computer_id;
    let computer_reservationitem_id;

    before(() => {
        cy.createWithAPI('Computer', { name: 'Reservable computer', entities_id: 1 }).then((computers_id) => {
            computer_id = computers_id;
            cy.createWithAPI('ReservationItem', { itemtype: 'Computer', items_id: computers_id }).then(id => computer_reservationitem_id = id);
        });
        cy.createWithAPI('Monitor', { name: 'Reservable monitor', entities_id: 1 }).then((monitors_id) => {
            cy.createWithAPI('ReservationItem', { itemtype: 'Monitor', items_id: monitors_id });
        });
    });

    beforeEach(() => {
        cy.login();
    });

    it('Create a reservation and view', () => {
        cy.visit(`/front/reservation.php?reservationitems_id=${computer_reservationitem_id}`);
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

    it('Find available reservation items', () => {
        cy.visit('/front/reservationitem.php');
        cy.findByRole('tabpanel').within(() => {
            cy.findByRole('button', { name: /Find a free item in a specific period/ }).click();
            cy.findByLabelText('Item type').select('Monitor', { force: true });
            cy.findByRole('button', { name: 'Search' }).click();
            cy.findAllByRole('cell').contains('Computers - ').should('not.exist');
            cy.findAllByRole('cell').contains('Monitors - ').should('exist');
            cy.findByLabelText('Item type').select('Computer', { force: true });
            cy.findByRole('button', { name: 'Search' }).click();
            cy.findAllByRole('cell').contains('Monitors - ').should('not.exist');
            cy.findAllByRole('cell').contains('Computers - ').should('exist');
        });
    });

    it('Month and Year context preserved', () => {
        /**
         * Ensure the month and year context is preserved when adding, updating or deleting a reservation.
         * For example, if you add a reservation in October 2024, after saving, the calendar should still display October 2025 when the page reloads or the user is redirected back.
         * We will test with a past date to ensure it's not the real current month.
         *
         * We will also test the user isn't redirected to a different page.
         * For example, if the user adds a reservation from the asset form, after saving, they should still be in the asset form, not redirected to the reservations page.
         */

        function gotoOctober2024() {
            cy.findByRole('button', { name: 'month' }).click();
            cy.get('.fc-header-toolbar .fc-center').contains('November 2024').should('exist');
            // Change month to ensure the back URL computes correctly
            cy.get('.fc-header-toolbar').findByLabelText('prev').click();
            cy.get('.fc-header-toolbar .fc-center').contains('October 2024').should('exist');
        }

        function checkOctober2024() {
            cy.get('.fc-header-toolbar .fc-center').contains('October 2024').should('exist');
            cy.url().should('match', /(month=10(&|$))|(tab_params%5Bmonth%5D=10(&|$))/);
        }

        function addReservation() {
            cy.get('.fc-day-grid .fc-day:not(.fc-other-month)').first().click();
            cy.findByRole('dialog', {name: 'Add reservation'}).within(() => {
                cy.contains('Reservable computer');
                cy.getDropdownByLabelText('By').selectDropdownValue('E2E Tests');
                cy.findByRole('button', {name: 'Add'}).click();
            });
        }

        function updateReservation() {
            cy.get('.fc-day-grid-event').first().click();
            cy.findByRole('dialog', {name: 'Edit reservation'}).within(() => {
                cy.contains('Reservable computer');
                cy.findByRole('button', { name: 'Save' }).click();
            });
        }

        function deleteReservation() {
            cy.get('.fc-day-grid-event').first().click();
            cy.findByRole('dialog', {name: 'Edit reservation'}).within(() => {
                cy.contains('Reservable computer');
                cy.findByRole('button', { name: 'Delete permanently' }).click();
            });
        }

        // Test from Reservations tab in asset form
        cy.visit(`/front/computer.form.php?id=${computer_id}&forcetab=Reservation$1&tab_params[month]=11&tab_params[year]=2024`);
        gotoOctober2024();
        addReservation();
        checkOctober2024();
        // Check we are still in the asset form
        cy.url().should('include', `computer.form.php?id=${computer_id}`);

        // Update the reservation and check we are still in October 2024
        cy.visit(`/front/computer.form.php?id=${computer_id}&forcetab=Reservation$1&tab_params[month]=11&tab_params[year]=2024`);
        gotoOctober2024();
        updateReservation();
        checkOctober2024();
        // Check we are still in the asset form
        cy.url().should('include', `computer.form.php?id=${computer_id}`);

        // Delete the reservation and check we are still in October 2024
        cy.visit(`/front/computer.form.php?id=${computer_id}&forcetab=Reservation$1&tab_params[month]=11&tab_params[year]=2024`);
        gotoOctober2024();
        deleteReservation();
        checkOctober2024();
        // Check we are still in the asset form
        cy.url().should('include', `computer.form.php?id=${computer_id}`);

        // Test from reservations page
        cy.visit(`/front/reservation.php?reservationitems_id=${computer_reservationitem_id}&month=11&year=2024`);
        gotoOctober2024();
        addReservation();
        checkOctober2024();
        // Check we are still in the reservations page
        cy.url().should('include', `reservationitems_id=${computer_reservationitem_id}`);

        cy.visit(`/front/reservation.php?reservationitems_id=${computer_reservationitem_id}&month=11&year=2024`);
        gotoOctober2024();
        updateReservation();
        checkOctober2024();
        // Check we are still in the reservations page
        cy.url().should('include', `reservationitems_id=${computer_reservationitem_id}`);

        // Delete the reservation and check we are still in October 2024
        cy.visit(`/front/reservation.php?reservationitems_id=${computer_reservationitem_id}&month=11&year=2024`);
        gotoOctober2024();
        deleteReservation();
        checkOctober2024();
        // Check we are still in the reservations page
        cy.url().should('include', `reservationitems_id=${computer_reservationitem_id}`);
    });
});
