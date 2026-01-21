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
const common = require('/js/common.js');

describe('Common', () => {
    beforeEach(() => {
        document.body.innerHTML = ``;
    });

    afterEach(() => {
        window.AjaxMock.end();
    });

    it('markCheckboxes', async () => {
        document.body.innerHTML = `
            <div id="containerA">
                <input type="checkbox" name="check1" />
                <input type="checkbox" name="check2" />
                <input type="checkbox" name="check3" />
            </div>
            <div id="containerB">
                <input type="checkbox" name="check4" />
                <input type="checkbox" name="check5" />
            </div>
        `;
        common.markCheckboxes('containerA');
        const check1 = document.querySelector('input[name="check1"]');
        const check2 = document.querySelector('input[name="check2"]');
        const check3 = document.querySelector('input[name="check3"]');
        const check4 = document.querySelector('input[name="check4"]');
        const check5 = document.querySelector('input[name="check5"]');
        expect(check1.checked).toBe(true);
        expect(check2.checked).toBe(true);
        expect(check3.checked).toBe(true);
        expect(check4.checked).toBe(false);
        expect(check5.checked).toBe(false);
    });

    it('unMarkCheckboxes', () => {
        document.body.innerHTML = `
            <div id="containerA">
                <input type="checkbox" name="check1" checked />
                <input type="checkbox" name="check2" checked />
                <input type="checkbox" name="check3" checked />
            </div>
            <div id="containerB">
                <input type="checkbox" name="check4" checked />
                <input type="checkbox" name="check5" checked />
            </div>
        `;
        common.unMarkCheckboxes('containerA');
        const check1 = document.querySelector('input[name="check1"]');
        const check2 = document.querySelector('input[name="check2"]');
        const check3 = document.querySelector('input[name="check3"]');
        const check4 = document.querySelector('input[name="check4"]');
        const check5 = document.querySelector('input[name="check5"]');
        expect(check1.checked).toBe(false);
        expect(check2.checked).toBe(false);
        expect(check3.checked).toBe(false);
        expect(check4.checked).toBe(true);
        expect(check5.checked).toBe(true);
    });

    it('checkAsCheckboxes', () => {
        document.body.innerHTML = `
            <div id="containerA">
                <input type="checkbox" name="check1" checked />
                <input type="checkbox" name="check2" />
                <input type="checkbox" name="check3" checked />
            </div>
            <input type="checkbox" id="check4" name="check4" checked />
        `;
        common.checkAsCheckboxes('check4', 'containerA');
        const check1 = document.querySelector('input[name="check1"]');
        const check2 = document.querySelector('input[name="check2"]');
        const check3 = document.querySelector('input[name="check3"]');
        const check4 = document.getElementById('check4');
        expect(check1.checked).toBe(true);
        expect(check2.checked).toBe(true);
        expect(check3.checked).toBe(true);
        expect(check4.checked).toBe(true);

        check4.checked = false;
        common.checkAsCheckboxes('check4', 'containerA');
        expect(check1.checked).toBe(false);
        expect(check2.checked).toBe(false);
        expect(check3.checked).toBe(false);
        expect(check4.checked).toBe(false);

        check4.checked = true;
        common.checkAsCheckboxes(check4, 'containerA');
        expect(check1.checked).toBe(true);
        expect(check2.checked).toBe(true);
        expect(check3.checked).toBe(true);
        expect(check4.checked).toBe(true);

        check4.checked = false;
        common.checkAsCheckboxes(check4, 'containerB');
        expect(check1.checked).toBe(true);
        expect(check2.checked).toBe(true);
        expect(check3.checked).toBe(true);
        expect(check4.checked).toBe(false);
    });

    it('selectAll', () => {
        document.body.innerHTML = `
            <select id="mySelect" multiple>
                <option value="1">Option 1</option>
                <option value="2">Option 2</option>
                <option value="3">Option 3</option>
            </select>
        `;
        common.selectAll('mySelect');
        const selectElement = document.getElementById('mySelect');
        for (const option of selectElement.options) {
            expect(option.selected).toBe(true);
        }
    });

    it('deselectAll', () => {
        document.body.innerHTML = `
            <select id="mySelect" multiple>
                <option value="1" selected>Option 1</option>
                <option value="2" selected>Option 2</option>
                <option value="3" selected>Option 3</option>
            </select>
        `;
        common.deselectAll('mySelect');
        const selectElement = document.getElementById('mySelect');
        for (const option of selectElement.options) {
            expect(option.selected).toBe(false);
        }
    });

    it('isImage', () => {
        expect(common.isImage({ type: 'image/gif'})).toBe(true);
        expect(common.isImage({ type: 'image/jpeg'})).toBe(true);
        expect(common.isImage({ type: 'image/jpg'})).toBe(true);
        expect(common.isImage({ type: 'image/png'})).toBe(true);
        expect(common.isImage({ type: 'image/notreally'})).toBe(false);
        expect(common.isImage({ type: 'application/json'})).toBe(false);
        expect(common.isImage({})).toBe(false);
    });

    it('getExtIcon', () => {
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//pics/icones/png-dist.png', 'HEAD', {}, () => ''));
        expect(common.getExtIcon('png')).toBe(`<img src="${window.CFG_GLPI.root_doc}/pics/icones/png-dist.png" title="png">`);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//pics/icones/fake-dist.png', 'HEAD', {}, () => '', false, 'error'));
        expect(common.getExtIcon('fake')).toBe(`<img src="${window.CFG_GLPI.root_doc}/pics/icones/defaut-dist.png" title="fake">`);
    });

    it('getSize (Memory size formatting)', () => {
        expect(common.getSize(500)).toBe('500B');
        expect(common.getSize(2048)).toBe('2KiB');
        expect(common.getSize(1048576)).toBe('1024KiB');
        expect(common.getSize(1073741824)).toBe('1024MiB');
        expect(common.getSize(1099511627776)).toBe('1024GiB');

        expect(common.getSize(452345)).toBe('441.74KiB');
        expect(common.getSize(3456789)).toBe('3.3MiB');
        expect(common.getSize(7890123456)).toBe('7.35GiB');
        expect(common.getSize(1234567890123)).toBe('1.12TiB');
    });

    it('getBijectiveIndex', () => {
        expect(common.getBijectiveIndex(1)).toBe('A');
        expect(common.getBijectiveIndex(26)).toBe('Z');
        expect(common.getBijectiveIndex(27)).toBe('AA');
        expect(common.getBijectiveIndex(52)).toBe('AZ');
        expect(common.getBijectiveIndex(53)).toBe('BA');
        expect(common.getBijectiveIndex(702)).toBe('ZZ');
        expect(common.getBijectiveIndex(703)).toBe('AAA');
    });

    it('getUuidV4', () => {
        const uuidSet = new Set();
        const uuidV4Regex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
        for (let i = 0; i < 10; i++) {
            const uuid = common.getUuidV4();
            expect(uuidV4Regex.test(uuid)).toBe(true);
            expect(uuidSet.has(uuid)).toBe(false);
            uuidSet.add(uuid);
        }
    });

    it('setHasUnsavedChanges / hasUnsavedChanges', () => {
        expect(common.hasUnsavedChanges()).toBe(false);
        common.setHasUnsavedChanges(true);
        expect(common.hasUnsavedChanges()).toBe(true);
        common.setHasUnsavedChanges(false);
        expect(common.hasUnsavedChanges()).toBe(false);
    });

    it('getFlatPickerLocale', () => {
        expect(common.getFlatPickerLocale('en', 'GB')).toStrictEqual({
            firstDayOfWeek: 1
        });
        expect(common.getFlatPickerLocale('en', 'US')).toBe('en');
        expect(common.getFlatPickerLocale('fr', 'FR')).toBe('fr');
        expect(common.getFlatPickerLocale('es', 'ES')).toBe('es');
        expect(common.getFlatPickerLocale('de', 'DE')).toBe('de');
    });

    it('getAjaxCsrfToken', () => {
        const csrf_meta = document.createElement('meta');
        csrf_meta.setAttribute('property', 'glpi:csrf_token');
        csrf_meta.setAttribute('content', 'dummy_csrf_token_value');
        document.head.append(csrf_meta);
        expect(common.getAjaxCsrfToken()).toBe('dummy_csrf_token_value');
    });

    it('tableToDetails', () => {
        document.body.innerHTML = `
            <div id="sysinfo">
                <div class="accordion-item">
                    <div class="accordion-header section-header">Section 1</div>
                    <div class="accordion-body section-content">Section 1 Content</div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header section-header">Section 2</div>
                    <div class="accordion-body section-content">Section 2 Content</div>
                </div>
            </div>
        `;
        expect(common.tableToDetails('#sysinfo')).toBe("<details><summary>Section 1</summary><pre>\nSection 1 Content\n</pre></details><details><summary>Section 2</summary><pre>\nSection 2 Content\n</pre></details>");
    });
});
