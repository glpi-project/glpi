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

import '../../../../js/RichText/UserMention.js';

describe('User Mentions', () => {
    beforeEach(() => {
        $('body').html(`<textarea id="richtext"></textarea>`);
    });
    test('Global variable', () => {
        expect(window.GLPI.RichText.UserMention).toBeDefined();
    });
    test('fetchItems', async () => {
        const mentions = new window.GLPI.RichText.UserMention(null, 5, 'test', {
            full: true
        });
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/getDropdownUsers.php', 'POST', {
            entity_restrict: 5,
            right: 'all',
            _idor_token: 'test'
        }, () => {
            return {
                results: [
                    {id: 2, text: 'John Doe'},
                    {id: 3, text: 'Jane Doe'},
                ]
            };
        }));
        await expect(mentions.fetchItems('')).resolves.toEqual([
            {
                type: 'autocompleteitem',
                value: "{\"id\":2,\"name\":\"John Doe\"}",
                text: 'John Doe',
            },
            {
                type: 'autocompleteitem',
                value: "{\"id\":3,\"name\":\"Jane Doe\"}",
                text: 'Jane Doe',
            }
        ]);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });
    test('fetchItems Restricted', async () => {
        const mentions = new window.GLPI.RichText.UserMention(null, 5, 'test', {
            full: false,
            users: [2, 4]
        });
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/getDropdownUsers.php', 'POST', {
            entity_restrict: 5,
            right: 'all',
            _idor_token: 'test'
        }, () => {
            return {
                results: [
                    {id: 2, text: 'John Doe'},
                    {id: 3, text: 'Jane Doe'},
                    {id: 4, text: 'John Smith'},
                ]
            };
        }));
        await expect(mentions.fetchItems('')).resolves.toEqual([
            {
                type: 'autocompleteitem',
                value: "{\"id\":2,\"name\":\"John Doe\"}",
                text: 'John Doe',
            },
            {
                type: 'autocompleteitem',
                value: "{\"id\":4,\"name\":\"John Smith\"}",
                text: 'John Smith',
            }
        ]);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });
});
