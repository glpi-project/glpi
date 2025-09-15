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

import { initObjectLock } from '/js/modules/ObjectLock.js';
import {jest} from '@jest/globals';

describe('Object Lock', () => {
    let window_reload_spy;
    let glpi_confirm_spy;
    let glpi_alert_spy;

    beforeEach(() => {
        // Mock reload function
        delete window.location;
        Object.defineProperty(window, 'location', {
            value: {
                href: '',
                reload: jest.fn().mockImplementation(() => {})
            },
            writable: true,
            configurable: true,
        });
        window_reload_spy = jest.spyOn(window.location, 'reload');

        window.glpi_confirm = jest.fn((opts) => {
            if (opts.confirm_callback) {
                opts.confirm_callback();
            }
        });
        window.glpi_alert = jest.fn((opts) => {
            if (opts.ok_callback) {
                opts.ok_callback();
            }
        });
        glpi_confirm_spy = jest.spyOn(window, 'glpi_confirm');
        glpi_alert_spy = jest.spyOn(window, 'glpi_alert');

        $('body').empty();
    });
    afterEach(() => {
        jest.clearAllMocks();
        // clear timer mock
        jest.useRealTimers();
        window.AjaxMock.end();
        $(window).off('beforeunload');
    });

    test('Exports', () => {
        expect(initObjectLock).toBeFunction();
    });
    test('Alert me', async () => {
        $('body').append('<input type="checkbox" id="alertMe" />');
        // Legacy fake timers needed to support setInterval
        jest.useFakeTimers({
            doNotFake: ['nextTick'],
        });

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/unlockobject.php', 'GET', {
            lockstatus: 1,
            id: 3450
        }, () => {
            return 1; // Not unlocked yet
        }));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/unlockobject.php', 'GET', {
            lockstatus: 1,
            id: 3450
        }, () => {
            return 0; // Unlocked
        }));

        initObjectLock({
            id: 3450,
            itemtype: 'Ticket',
            itemtype_name: 'Ticket',
            items_id: 24
        }, {
            name: 'John Doe'
        }, false);

        // Checkbox not checked, so no timer and no AJAX
        jest.advanceTimersByTime(30000);
        expect(window.AjaxMock.response_stack).toHaveLength(2);

        $('#alertMe').prop('checked', true).trigger('change');
        await new Promise(process.nextTick);
        jest.advanceTimersByTime(15000);
        expect(window.AjaxMock.response_stack).toHaveLength(1);
        jest.advanceTimersByTime(15000);
        expect(window.AjaxMock.response_stack).toHaveLength(0);
        await new Promise(process.nextTick);
        expect(glpi_confirm_spy).toHaveBeenCalled();
        expect(glpi_confirm_spy).toHaveBeenCalledWith(
            expect.objectContaining({
                title: 'Item unlocked!',
                message: 'Reload page?',
                confirm_callback: expect.toBeFunction()
            })
        );
        expect(window_reload_spy).toHaveBeenCalled();
        jest.advanceTimersByTime(15000);
        // AjaxMock should not throw an error here. If it does, it means the timer is still running
    });
    test('Ask unlock item', async () => {
        $('body').append('<button class="ask-unlock-item"></button>');
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/unlockobject.php', 'POST', {
            requestunlock: 1,
            id: 3450
        }, () => {
            return Promise.resolve();
        }));

        initObjectLock({
            id: 3450,
            itemtype: 'Ticket',
            itemtype_name: 'Ticket',
            items_id: 24
        }, {
            name: 'John Doe'
        }, false);

        $('.ask-unlock-item').trigger('click');
        await new Promise(process.nextTick);
        expect(glpi_confirm_spy).toHaveBeenCalled();
        expect(glpi_confirm_spy).toHaveBeenCalledWith(
            expect.objectContaining({
                title: 'Ticket #24',
                message: 'Ask for unlock this item?',
                confirm_callback: expect.toBeFunction()
            })
        );
        await new Promise(process.nextTick);
        expect(glpi_alert_spy).toHaveBeenCalledWith(
            expect.objectContaining({
                title: 'Unlock request sent!',
                message: 'Request sent to John Doe',
            })
        );
        expect(window.AjaxMock.response_stack).toHaveLength(0);
    });
    test('Unlock on beforeunload - fetch', async () => {
        window.fetch = jest.fn(() => {
            return {
                catch: () => {}
            };
        });
        const fetch_spy = jest.spyOn(window, 'fetch');
        window.getAjaxCsrfToken = jest.fn(() => {
            return 'token';
        });

        initObjectLock({
            id: 3450,
            itemtype: 'Ticket',
            itemtype_name: 'Ticket',
            items_id: 24
        }, {
            name: 'John Doe'
        }, true);

        window.dispatchEvent(new Event('beforeunload'));
        await new Promise(process.nextTick);
        expect(fetch_spy).toHaveBeenCalledWith('//ajax/unlockobject.php', expect.objectContaining({
            method: 'POST',
            cache: 'no-cache',
            headers: expect.objectContaining({
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;',
                'X-Glpi-Csrf-Token': 'token'
            })
        }));
    });
    test('Unlock on beforeunload - ajax fallback', async () => {
        window.fetch = undefined;
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/unlockobject.php', 'POST', {
            unlock: 1,
            id: 3450
        }, () => {
            return true;
        }));

        initObjectLock({
            id: 3450,
            itemtype: 'Ticket',
            itemtype_name: 'Ticket',
            items_id: 24
        }, {
            name: 'John Doe'
        }, true);

        window.dispatchEvent(new Event('beforeunload'));
        await new Promise(process.nextTick);
        expect(window.AjaxMock.response_stack).toHaveLength(0);
    });
});
