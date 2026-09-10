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

import '/build/vue/app.js';
import ReservationScheduler from '/js/src/vue/Reservations/ReservationScheduler.vue';
import {enableAutoUnmount, flushPromises, mount} from "@vue/test-utils";
import {startFetchMock, stopFetchMock, mockFetchIf} from '../../fetch-mock.js';

enableAutoUnmount(afterEach);

describe('Reservations/ReservationScheduler Vue Component', async () => {
    beforeEach(() => {
        $(document).off();
        document.body.innerHTML = `
            <div id="test-container"></div>
        `;

        startFetchMock();

        // Events and resources feeds
        mockFetchIf(/\/ajax\/reservations\.php\?/, () => {
            return new Response('[]', {
                headers: { 'Content-Type': 'application/json' }
            });
        });
    });

    afterEach(() => {
        stopFetchMock();
    });

    async function mountScheduler(props = {}) {
        const component = await mount(ReservationScheduler, {
            props: {
                id: 1,
                can_reserve: true,
                now: '2026-06-05 21:02:10',
                default_date: '2026-06-01',
                current_view: 'dayGridMonth',
                ...props
            },
            attachTo: document.querySelector('#test-container'),
            global: {
                mocks: {
                    __: (key) => key,
                    _x: (ctx, key) => key,
                    _n: (singular, plural, count) => count > 1 ? plural : singular,
                }
            }
        });
        await flushPromises();
        return component;
    }

    test('moving a reservation sends the new start and end dates', async () => {
        let sent_params = null;
        mockFetchIf(/\/ajax\/reservations\.php$/, async (req) => {
            if (req.method === 'POST') {
                sent_params = new URLSearchParams(await req.text());
                return new Response('{"result":true}');
            }
        });

        const component = await mountScheduler();

        const revert = vi.fn();
        component.vm.editEvent({
            event: {
                id: '12',
                start: new Date('2026-06-09T10:00:00Z'),
                end: new Date('2026-06-09T11:00:00Z'),
            },
            revert: revert,
        });
        await flushPromises();

        expect(sent_params).not.toBeNull();
        expect(sent_params.get('action')).toBe('update_event');
        expect(sent_params.get('id')).toBe('12');
        // `Reservation::updateEvent()` expects `start` and `end`
        expect(sent_params.get('start')).toBe('2026-06-09T10:00:00.000Z');
        expect(sent_params.get('end')).toBe('2026-06-09T11:00:00.000Z');
        expect(sent_params.has('begin')).toBe(false);
        expect(revert).not.toHaveBeenCalled();
    });

    test('moving a reservation is reverted when the update fails', async () => {
        mockFetchIf(/\/ajax\/reservations\.php$/, (req) => {
            if (req.method === 'POST') {
                return new Response('', { status: 500 });
            }
        });

        const component = await mountScheduler();

        const revert = vi.fn();
        component.vm.editEvent({
            event: {
                id: '12',
                start: new Date('2026-06-09T10:00:00Z'),
                end: new Date('2026-06-09T11:00:00Z'),
            },
            revert: revert,
        });
        await flushPromises();

        expect(revert).toHaveBeenCalled();
    });
});
