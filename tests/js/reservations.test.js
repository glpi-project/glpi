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

require("@jest/globals");
const Reservations = require('/js/reservations.js').Reservations;

describe('Reservations', () => {
    beforeAll(() => {
        document.body.innerHTML = '<div id="reservations_planning_1234"></div>';
    });
    beforeEach(() => {
        jest.clearAllMocks();
        window.AjaxMock.end();
    });
    it('init', () => {
        const r = new Reservations();

        // No config
        r.init({});
        expect(r).toSatisfy((instance) => {
            return instance.id === 0
                && instance.is_all === true
                && instance.rand === true
                && instance.is_tab === false
                && instance.dom_id === 'reservations_planning_true'
                && instance.currentv === 'dayGridMonth'
                && instance.defaultDate.toDateString() === new Date().toDateString()
                && instance.defaultPDate.toDateString() === new Date().toDateString()
                && instance.can_reserve === true
                && instance.now === null;
        });

        const r2 = new Reservations();
        r2.init({
            id: 5,
            rand: '1234',
            is_tab: true,
            defaultDate: '2024-06-15',
            can_reserve: false,
            now: '2024-06-10T12:00:00',
        });
        expect(r2).toSatisfy((instance) => {
            return instance.id === 5
                && instance.rand === '1234'
                && instance.is_tab === true
                && instance.dom_id === 'reservations_planning_1234'
                && instance.currentv === 'dayGridMonth'
                && instance.defaultDate === '2024-06-15'
                && instance.defaultPDate.toDateString() === new Date('2024-06-15').toDateString()
                && instance.can_reserve === false
                && instance.now === '2024-06-10T12:00:00';
        });
    });
    it('displayPlanning', () => {
        const r = new Reservations();
        r.init({
            id: 5,
            rand: '1234',
            is_tab: true,
            defaultDate: '2024-06-15',
            can_reserve: false,
            now: '2024-06-10T12:00:00',
        });
        window.CFG_GLPI.planning_begin = '08:00:00';
        window.CFG_GLPI.planning_end = '18:00:00';
        window.FullCalendarLocales = {
            'en-gb': {
                code: "en-gb",
            }
        };
        window.FullCalendar = {
            Calendar: jest.fn().mockImplementation((dom, config) => {
                expect(dom).toHaveProperty('id', 'reservations_planning_1234');
                expect(config.now).toBe(r.now);
                expect(config.defaultDate).toBe(r.defaultDate);
                expect(config.defaultView).toBe(r.currentv);
                expect(config.minTime).toBe('08:00:00');
                expect(config.maxTime).toBe('18:00:00');
                expect(config.plugins).toIncludeAllMembers(['dayGrid', 'interaction', 'list', 'timeGrid', 'resourceTimeline']);
                expect(config.header).toBeObject();
                expect(config.views).toContainAllKeys(['listFull', 'resourceWeek']);
                return {
                    setOption: jest.fn().mockImplementation((option, value) => {
                        if (option === 'locale') {
                            expect(value).toBe('en-gb');
                        }
                    }),
                    render: jest.fn(),
                };
            })
        };
        r.displayPlanning();
        expect(window.FullCalendar.Calendar).toHaveBeenCalledTimes(1);
        expect(r.calendar).toBeDefined();
        expect(r.calendar.render).toHaveBeenCalledTimes(1);
        expect(r.calendar.setOption).toHaveBeenCalled();

        jest.spyOn(Storage.prototype, 'getItem').mockImplementation((key) => {
            if (key === 'fcDefaultViewReservation') {
                return 'test';
            }
            return null;
        });
        window.FullCalendar = {
            Calendar: jest.fn().mockImplementation((dom, config) => {
                expect(config.defaultView).toBe('test');
                return {
                    setOption: jest.fn().mockImplementation((option, value) => {
                        if (option === 'locale') {
                            expect(value).toBe('en-gb');
                        }
                    }),
                    render: jest.fn(),
                };
            })
        };
        r.displayPlanning();
    });
    it('dateAreSameDay', () => {
        const r = new Reservations();
        expect(r.dateAreSameDay(new Date('2024-06-10'), new Date('2024-06-10'))).toBe(true);
        expect(r.dateAreSameDay(new Date('2024-06-10'), new Date('2024-06-11'))).toBe(false);
        expect(r.dateAreSameDay(new Date('2024-06-10T12:00:00'), new Date('2024-06-10T23:59:59'))).toBe(true);
    });
    it('editEvent', () => {
        const revert_fn = jest.fn();

        window.AjaxMock.start();

        // success
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/reservations.php', 'POST', {
            action: 'update_event',
            start: '2024-06-15T10:00:00.000Z',
            end: '2024-06-15T12:00:00.000Z',
            id: '42',
        }, () => {
            return '<div>Event Form HTML</div>';
        }));

        // success but missing HTML response
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/reservations.php', 'POST', {
            action: 'update_event',
            start: '2024-06-15T10:00:00.000Z',
            end: '2024-06-15T12:00:00.000Z',
            id: '43',
        }, () => {
            return '';
        }));

        // failure
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/reservations.php', 'POST', {
            action: 'update_event',
            start: '2024-06-15T10:00:00.000Z',
            end: '2024-06-15T12:00:00.000Z',
            id: '44',
        }, () => {
            return '';
        }, false, 'error'));

        const r = new Reservations();

        // First call - success
        r.editEvent({
            event: {
                start: new Date('2024-06-15T10:00:00.000Z'),
                end: new Date('2024-06-15T12:00:00.000Z'),
                id: 42,
            },
            revert: revert_fn
        });
        return new Promise(process.nextTick).then(() => {
            expect(window.AjaxMock.isResponseStackEmpty()).toBeFalse();
            expect(revert_fn).not.toHaveBeenCalled();

            // Second call - success but missing HTML
            r.editEvent({
                event: {
                    start: new Date('2024-06-15T10:00:00.000Z'),
                    end: new Date('2024-06-15T12:00:00.000Z'),
                    id: 43,
                },
                revert: revert_fn
            });
            return new Promise(process.nextTick);
        }).then(() => {
            expect(window.AjaxMock.isResponseStackEmpty()).toBeFalse();
            expect(revert_fn).toHaveBeenCalledTimes(1);

            // Third call - failure
            r.editEvent({
                event: {
                    start: new Date('2024-06-15T10:00:00.000Z'),
                    end: new Date('2024-06-15T12:00:00.000Z'),
                    id: 44,
                },
                revert: revert_fn
            });
            return new Promise(process.nextTick);
        }).then(() => {
            expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
            expect(revert_fn).toHaveBeenCalledTimes(2);
            window.AjaxMock.end();
        });
    });
});
