/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/* global {GLPI} GLPI */
import {jest} from '@jest/globals';

describe('GLPI Core Module', () => {
    test('Global object is defined', () => {
        expect(GLPI).toBeDefined();
    });

    test('Core modules auto-registered', async () => {
        expect(GLPI.modules).toBeObject();
        expect(GLPI.modules).toHaveProperty('clipboard');
    });

    test('Event target', () => {
        expect(GLPI.getEventTarget()).toBeInstanceOf(EventTarget);

        const event_listener = jest.fn();
        GLPI.getEventTarget().addEventListener('test', event_listener);
        GLPI.getEventTarget().dispatchEvent(new Event('test'));
        expect(event_listener).toHaveBeenCalled();
    });

    test('Redefine Alert', () => {
        expect(window.old_alert).toBeDefined();
        GLPI.getModule('dialogs').showAlert = jest.fn();
        window.alert('test', 'test caption');
        expect(GLPI.getModule('dialogs').showAlert).toHaveBeenCalledWith({
            message: 'test',
            title: 'test caption',
        });
    });

    test('Redefine Confirm', () => {
        expect(window.nativeConfirm).toBeDefined();
        GLPI.getModule('dialogs').showConfirm = jest.fn();
        window.confirm('test', 'test caption');
        expect(GLPI.getModule('dialogs').showConfirm).toHaveBeenCalledWith(
            expect.objectContaining({
                message: 'test',
                title: 'test caption',
                confirm_callback: expect.any(Function),
            })
        );
    });
});
