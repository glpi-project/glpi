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
import 'flatpickr';
import PlanningScheduler from '/js/src/vue/Planning/PlanningScheduler.vue';
import {enableAutoUnmount, flushPromises, mount} from "@vue/test-utils";
import {startFetchMock, stopFetchMock, mockFetchIf} from '../../fetch-mock.js';

enableAutoUnmount(afterEach);

describe('Planning/PlanningScheduler Vue Component', async () => {
    beforeAll(() => {
        window.displayAjaxMessageAfterRedirect = vi.fn();
    });

    beforeEach(() => {
        $(document).off();
        document.body.innerHTML = `
            <div id="test-container"></div>
        `;

        window.bootstrap = {
            Popover: vi.fn(class {
                dispose = vi.fn();
            }),
        };

        CFG_GLPI.planning_begin = '08:00:00';
        CFG_GLPI.planning_end = '18:00:00';
        CFG_GLPI.planning_work_days = [1, 2, 3, 4, 5]; // no weekends

        startFetchMock();

        mockFetchIf(/\/ajax\/planning.php$/, (req) => {
            if (req.method === 'POST') {
                return new Response('{}');
            }
        });

        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-06-05T21:02:10'));
    });

    afterEach(() => {
        stopFetchMock();
        vi.useRealTimers();
    });

    async function mountScheduler(props = {}) {
        mockFetchIf(/\/ajax\/planning.php/, (req) => {
            const query = new URLSearchParams(req.url.split('?')[1]);
            if (req.method === 'POST' && query.get('action') === 'get_events') {
                return new Response(JSON.stringify([
                    {
                        title: 'Test event',
                        content: '<p><b>Test event content</b></p>',
                        tooltip: '<p><b>Test event tooltip</b></p>',
                        start: '2026-06-03 10:30:00',
                        end: '2026-06-03 11:30:00',
                        duration: 3600000,
                        itemtype: 'PlanningExternalEvent',
                        resourceId: 'user_2',
                        display: '',
                        _editable: true,
                    }
                ]), {
                    headers: { 'Content-Type': 'application/json' }
                });
            }
        });

        const component = await mount(PlanningScheduler, {
            props: {
                now: '2026-06-05 21:02:10',
                header: {
                    start: 'prev,next today',
                    center: 'title',
                    end: 'dayGridMonth,timeGridWeek,timeGridDay,listFull,resourceWeek',
                },
                resources: [
                    {
                        group_id: false,
                        id: 'user_2',
                        is_visible: true,
                        items_id: 2,
                        itemtype: 'user',
                        title: 'glpi',
                    }
                ],
                fullcalendar_options: {
                    initialView: 'dayGridMonth',
                },
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
        vi.advanceTimersByTime(500); // wait for any debounced functions or timeouts
        return component;
    }

    test('mount', async () => {
        let fetched_events = false;
        mockFetchIf(/\/ajax\/planning.php/, (req) => {
            const query = new URLSearchParams(req.url.split('?')[1]);
            if (req.method === 'POST' && query.get('action') === 'get_events') {
                fetched_events = true;
                return new Response('[]');
            }
        });

        const component = await mountScheduler();

        expect(fetched_events).toBe(true);

        const headerTexts = component.findAll('th').map(header => header.text().trim());
        expect(headerTexts).toContain('Mon');
        expect(headerTexts).not.toContain('Sat');
        expect(headerTexts).not.toContain('Sun');

        expect(component.get('h2').text()).toBe('June 2026');
        const toolbar_buttons = component.findAll('button');
        expect(toolbar_buttons.some(button => button.text().trim() === 'month' && button.attributes('aria-pressed') === 'true')).toBe(true);
        const other_view_buttons = ['week', 'day', 'list', 'Timeline Week'];
        other_view_buttons.forEach(view => {
            expect(toolbar_buttons.some(button => button.text().trim() === view && button.attributes('aria-pressed') === 'false')).toBe(true);
        });
        expect(toolbar_buttons.some(button => button.attributes('title') === 'Calendar')).toBe(true);
        expect(toolbar_buttons.some(button => button.attributes('title') === 'Refresh')).toBe(true);
    });

    test('initial view', async () => {
        const component = await mountScheduler({
            fullcalendar_options: {
                initialView: 'timeGridWeek',
            }
        });

        const toolbar_buttons = component.findAll('button');
        expect(toolbar_buttons.some(button => button.text().trim() === 'week' && button.attributes('aria-pressed') === 'true')).toBe(true);
        expect(component.get('h2').text()).toBe('1 – 5 June 2026');
    });

    test('switch views', async () => {
        const component = await mountScheduler();

        const buttons = component.findAll('button');

        const switchView = async (view) => {
            const button = buttons.find(btn => btn.text().trim() === view);
            expect(button).toBeTruthy();
            await button.trigger('click');
            await flushPromises();
        };

        await switchView('week');
        expect(component.get('h2').text()).toBe('1 – 5 June 2026');
        await switchView('day');
        expect(component.get('h2').text()).toBe('5 June 2026');
        await switchView('list');
        expect(component.get('h2').text()).toBe('List');
        await switchView('Timeline Week');
        expect(component.get('h2').text()).toBe('1 – 5 June 2026');
    });

    test('date picker', async () => {
        const component = await mountScheduler();

        const calendar_button = component.find('button[title="Calendar"]');
        expect(calendar_button.exists()).toBe(true);
        await calendar_button.trigger('click');
        await flushPromises();

        const flatpickr_container = document.querySelector('.flatpickr-calendar');
        expect(flatpickr_container).toBeTruthy();

        const day_31 = flatpickr_container.querySelector('.flatpickr-day[aria-label="May 31, 2026"]');
        expect(day_31).toBeTruthy();
        await day_31.click();
        await flushPromises();

        expect(component.get('h2').text()).toBe('May 2026');
    });

    test('refresh button', async () => {
        let fetched_events = false;

        const component = await mountScheduler();

        stopFetchMock();
        startFetchMock();
        mockFetchIf(/\/ajax\/planning.php/, (req) => {
            const query = new URLSearchParams(req.url.split('?')[1]);
            if (req.method === 'POST' && query.get('action') === 'get_events') {
                fetched_events = true;
                return new Response('[]');
            }
        });

        const refresh_button = component.find('button[title="Refresh"]');
        expect(refresh_button.exists()).toBe(true);
        await refresh_button.trigger('click');
        await flushPromises();
        vi.advanceTimersByTime(500);

        expect(fetched_events).toBe(true);
    });

    test('today button', async () => {
        const component = await mountScheduler();

        await component.get('button[title="Previous month"]').trigger('click');
        await flushPromises();

        const real_today = new Date();
        const today_button = component.findAll('button').find(btn => btn.text().trim() === 'today');
        expect(today_button).toBeTruthy();
        await today_button.trigger('click');
        await flushPromises();

        const expected_title = real_today.toLocaleString('en-GB', { month: 'long', year: 'numeric' });
        expect(component.get('h2').text()).toBe(expected_title);
    });
});
