/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

import { test, expect } from '../../fixtures/glpi_fixture';
import { Api } from '../../utils/Api';
import { Profiles } from '../../utils/Profiles';
import { ReservationPage } from '../../pages/ReservationPage';
import { getWorkerEntityId, getWorkerUserId } from '../../utils/WorkerEntities';

// A fixed month in the future, so the reservation is never in the past.
const MONTH = '2044-06';
const BEGIN_DAY = 10;
const TARGET_DAY = 11;
const TARGET_DAY_LABEL = '11 June 2044';

async function createReservation(api: Api, name: string): Promise<{
    reservationitems_id: number,
    reservations_id: number,
}> {
    const computer_id = await api.createItem('Computer', {
        name: name,
        entities_id: getWorkerEntityId(),
    });
    const reservationitems_id = await api.createItem('ReservationItem', {
        itemtype: 'Computer',
        items_id: computer_id,
        entities_id: getWorkerEntityId(),
        is_active: 1,
    });
    const reservations_id = await api.createItem('Reservation', {
        reservationitems_id: reservationitems_id,
        users_id: getWorkerUserId(),
        begin: `${MONTH}-${BEGIN_DAY} 10:00:00`,
        end: `${MONTH}-${BEGIN_DAY} 11:00:00`,
    });

    return { reservationitems_id, reservations_id };
}

test('Moving a reservation in the calendar updates its dates', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    // Arrange: create and go to a computer with a reservation
    const name = `Reservable computer (move) ${getWorkerEntityId()}`;
    const { reservationitems_id, reservations_id } = await createReservation(api, name);

    const reservation_page = new ReservationPage(page);
    await reservation_page.goto(reservationitems_id, `${MONTH}-${BEGIN_DAY}`);
    await reservation_page.assertCalendarIsLoaded();
    await reservation_page.gotoView('month');

    const event = reservation_page.getEvent(name);
    await expect(event).toBeVisible();

    // Act: move event
    const update_response = page.waitForResponse(
        (response) => response.url().includes('/ajax/reservations.php')
            && response.request().method() === 'POST'
    );
    await reservation_page.doDragToDay(
        event,
        reservation_page.getDayCell(TARGET_DAY_LABEL),
    );
    expect((await update_response).status()).toBe(200);

    // Assert: validate the dates were updated
    const reservation = await api.getItem('Reservation', reservations_id);
    expect(reservation.begin).toBe(`${MONTH}-${TARGET_DAY} 10:00:00`);
    expect(reservation.end).toBe(`${MONTH}-${TARGET_DAY} 11:00:00`);
});

test('Resizing a reservation in the calendar updates its end date', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    // Arrange: create and go to a computer with a reservation
    const name = `Reservable computer (resize) ${getWorkerEntityId()}`;
    const { reservationitems_id, reservations_id } = await createReservation(api, name);

    const reservation_page = new ReservationPage(page);
    await reservation_page.goto(reservationitems_id, `${MONTH}-${BEGIN_DAY}`);
    await reservation_page.assertCalendarIsLoaded();
    await reservation_page.gotoView('week');

    const event = reservation_page.getEvent(name);
    await expect(event).toBeVisible();

    // The reservation lasts one hour, so its own height is worth one hour.
    const one_hour = (await event.boundingBox())!.height;

    // Act: resize event
    const update_response = page.waitForResponse(
        (response) => response.url().includes('/ajax/reservations.php')
            && response.request().method() === 'POST'
    );
    await reservation_page.doResizeEventBy(event, one_hour);
    expect((await update_response).status()).toBe(200);

    // Assert: validate the dates were updated
    const reservation = await api.getItem('Reservation', reservations_id);
    expect(reservation.begin).toBe(`${MONTH}-${BEGIN_DAY} 10:00:00`);
    expect(reservation.end).toBe(`${MONTH}-${BEGIN_DAY} 12:00:00`);
});
