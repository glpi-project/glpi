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

import { WebIconSelector } from '/js/modules/Form/WebIconSelector.js';

describe('Web Icon Selector', () => {
    beforeEach(() => {
        $('body').empty();
        // Mock the existance of the fontawesome and tabler css by manually crafting fake css rules and adding them to the document
        const style = document.createElement('style');
        style.type = 'text/css';
        style.innerHTML = `
            .ti-home::before{content: "\\e600";}
            .fa-home::before{content: "\\f015";}
            .fa-arrow-turn-right::before,.fa-mail-forward::before,.fa-share::before{content: "\\f064";}
        `;
        $('head').empty();
        document.getElementsByTagName('head')[0].appendChild(style);
    });
    test('Class exists', () => {
        expect(WebIconSelector).toBeDefined();
    });
    test('Constructor', () => {
        const selectElement = document.createElement('select');
        const webIconSelector = new WebIconSelector(selectElement);
        expect(webIconSelector.selectElement).toBe(selectElement);

        const webIconSelector2 = new WebIconSelector(selectElement);
        expect(webIconSelector2.selectElement).toBe(selectElement);

        const webIconSelector3 = new WebIconSelector(selectElement);
        expect(webIconSelector3.selectElement).toBe(selectElement);
    });
    test('Init select2', () => {
        $('body').append('<select id="test"></select>');
        const webIconSelector = new WebIconSelector(document.getElementById('test'));
        webIconSelector.init();

        expect($(webIconSelector.selectElement).data('select2')).toBeDefined();
    });
    test('Select2 data', async () => {
        $('body')
            .append('<select id="test"></select>')
            .append('<select id="test2"></select>')
            .append('<select id="test3"></select>');
        const webIconSelector = new WebIconSelector(document.getElementById('test'));
        webIconSelector.init();
        await new Promise(process.nextTick);

        $('#test').data('select2').results.data.query({
            term: '',
        }, (results) => {
            // One page/category returned by default
            expect(results.results.length).toBe(1);
            expect(results.results[0].text).toBe('Animals');
            expect(results.results[0].children.length).toBeGreaterThan(1);
            // More pages available
            expect(results.pagination.more).toBeTrue();
            // each option id and text should match and contain 'ti-'
            results.results[0].children.forEach((option) => {
                expect(option.id).toBe(`ti-${option.text}`);
                expect(option.id).toContain('ti-');
            });
        });
    });
});
