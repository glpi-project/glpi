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

require('@jest/globals');
require('/js/notifications_ajax.js');

describe('Ajax notifications', () => {
    beforeEach(() => {
        jest.useFakeTimers();
        localStorage.clear();

        Object.defineProperty(window, 'Notification', {
            configurable: true,
            value: class {},
        });
    });

    afterEach(() => {
        jest.clearAllTimers();
        jest.useRealTimers();
        jest.restoreAllMocks();
        localStorage.clear();
    });

    test('Waits for the current request before scheduling the next one', () => {
        const interval = 1000;
        const first_request = $.Deferred();
        const second_request = $.Deferred();
        const ajax_spy = jest.spyOn($, 'ajax')
            .mockReturnValueOnce(first_request.promise())
            .mockReturnValueOnce(second_request.promise());
        const notifications = new window.GLPINotificationsAjax({
            interval: interval,
            user_id: 1,
        });

        notifications.startMonitoring();

        expect(ajax_spy).toHaveBeenCalledTimes(1);
        expect(ajax_spy).toHaveBeenCalledWith({
            url: '//ajax/notifications_ajax.php',
            dataType: 'json',
            timeout: interval * 2,
        });

        jest.advanceTimersByTime(interval * 2);
        expect(ajax_spy).toHaveBeenCalledTimes(1);

        first_request.resolve(false);
        jest.advanceTimersByTime(interval - 1);
        expect(ajax_spy).toHaveBeenCalledTimes(1);

        jest.advanceTimersByTime(1);
        expect(ajax_spy).toHaveBeenCalledTimes(2);
    });

    test('Schedules the next check after a stalled request times out', () => {
        const interval = 1000;
        const request_timeout = interval * 2;
        const xhr = {
            open: jest.fn(),
            setRequestHeader: jest.fn(),
            send: jest.fn(),
            abort: jest.fn(),
            getAllResponseHeaders: jest.fn(),
        };
        jest.spyOn($.ajaxSettings, 'xhr').mockReturnValue(xhr);
        const ajax_spy = jest.spyOn($, 'ajax');
        const notifications = new window.GLPINotificationsAjax({
            interval: interval,
            user_id: 1,
        });

        notifications.startMonitoring();

        expect(ajax_spy).toHaveBeenCalledTimes(1);

        jest.advanceTimersByTime(request_timeout - 1);
        expect(xhr.abort).not.toHaveBeenCalled();

        jest.advanceTimersByTime(1);
        expect(xhr.abort).toHaveBeenCalledTimes(1);
        expect(ajax_spy).toHaveBeenCalledTimes(1);

        jest.advanceTimersByTime(1);
        jest.advanceTimersByTime(interval - 1);
        expect(ajax_spy).toHaveBeenCalledTimes(1);

        jest.advanceTimersByTime(1);
        expect(ajax_spy).toHaveBeenCalledTimes(2);
    });
});
