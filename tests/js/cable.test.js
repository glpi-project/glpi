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
const cable = require('/js/cable.js');

describe('Cable', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div>
                <span id="show_a_asset_breadcrumb">Default A</span>
                <span id="show_b_asset_breadcrumb">Default B</span>
                <span id="networkport_dropdown">Default Dropdown</span>
                <div><div><select name="socket_select"></select></div></div>
            </div>
        `;
    });
    afterEach(() => {
        window.AjaxMock.end();
    });
    it('refreshAssetBreadcrumb', async () => {
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/cable.php', 'GET', {
            action: 'get_item_breadcrum',
            itemtype: 'Computer',
            items_id: '123',
        }, () => {
            return '<div>Breadcrumb HTML</div>';
        }));
        cable.refreshAssetBreadcrumb('Computer', 123, 'show_a_asset_breadcrumb');
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(document.getElementById('show_a_asset_breadcrumb').innerHTML).toBe('<div>Breadcrumb HTML</div>');
        expect(document.getElementById('show_b_asset_breadcrumb').innerHTML).toBe('Default B');
    });
    it('refreshNetworkPortDropdown', async () => {
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/cable.php', 'GET', {
            action: 'get_networkport_dropdown',
            itemtype: 'Computer',
            items_id: '123',
        }, () => {
            return '<select><option>Port 1</option><option>Port 2</option></select>';
        }));
        cable.refreshNetworkPortDropdown('Computer', 123, 'networkport_dropdown');
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(document.getElementById('networkport_dropdown').innerHTML).toBe('<select><option>Port 1</option><option>Port 2</option></select>');
    });
    it('refreshSocketDropdown', async () => {
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/cable.php', 'GET', {
            action: 'get_socket_dropdown',
            itemtype: 'Computer',
            items_id: '123',
            socketmodels_id: '456',
            dom_name: 'socket_select'
        }, () => {
            return '<div><div><select name="socket_select"><option>Socket A</option><option>Socket B</option></select></div></div>';
        }));
        cable.refreshSocketDropdown('Computer', 123, 456, 'socket_select');
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(document.querySelector('select[name="socket_select"]').innerHTML).toBe('<option>Socket A</option><option>Socket B</option>');
        // make sure other elements are untouched
        expect(document.getElementById('show_b_asset_breadcrumb').innerHTML).toBe('Default B');
    });
});
