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

describe('Clipboard module', () => {
    test('Module autoloaded', () => {
        expect(GLPI.isModuleRegistered('clipboard')).toBeTrue();
    });

    test('Get module', () => {
        expect(GLPI.getModule('clipboard').copyTextToClipboard).toBeDefined();
    });

    test('Global binds', () => {
        expect(window.copyTextToClipboard).toBeDefined();
    });

    test('Copy to clipboard', () => {
        $(document.body).append(`<div id="copy_wrapper" class="copy_to_clipboard_wrapper">
            <input type="text" value="my text to copy" /></div>`);
        const wrapper = $('#copy_wrapper');

        // Mock execCommand
        document.execCommand = jest.fn(() => true);
        // Click the wrapper
        wrapper.click();
        // Check if execCommand was called. We assume that the text was copied as we cannot read the clipboard
        expect(document.execCommand).toHaveBeenCalledWith('copy');
        // Expect the wrapper to have the class "copied"
        expect(wrapper.hasClass('copied')).toBeTrue();

        wrapper.removeClass('copied');

        // Test a failure
        document.execCommand = jest.fn(() => false);
        wrapper.click();
        expect(document.execCommand).toHaveBeenCalledWith('copy');
        expect(wrapper.hasClass('copyfail')).toBeTrue();
    });
});
