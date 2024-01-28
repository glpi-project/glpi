/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

import { WebIconSelector } from '../../../../js/modules/Form/WebIconSelector.js';

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
        expect(webIconSelector.icon_sets).toEqual(['ti']);

        const webIconSelector2 = new WebIconSelector(selectElement, ['ti', 'fa']);
        expect(webIconSelector2.selectElement).toBe(selectElement);
        expect(webIconSelector2.icon_sets).toEqual(['ti', 'fa']);

        const webIconSelector3 = new WebIconSelector(selectElement, ['fa']);
        expect(webIconSelector3.selectElement).toBe(selectElement);
        expect(webIconSelector3.icon_sets).toEqual(['fa']);
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

        const select2_options = $('#test').data('select2').results.data._dataToConvert;
        expect(select2_options).toBeDefined();
        expect(select2_options.length).toBe(1);
        // each option id and text should match and contain 'ti-'
        select2_options.forEach((option) => {
            expect(option.id).toBe(option.text);
            expect(option.id).toContain('ti-');
        });

        // Test with FontAwesome
        const webIconSelector2 = new WebIconSelector(document.getElementById('test2'), ['fa']);
        webIconSelector2.init();

        const select2_options2 = $('#test2').data('select2').results.data._dataToConvert;
        expect(select2_options2).toBeDefined();
        expect(select2_options2.length).toBe(4);
        // each option id and text should match and contain 'fa-'
        select2_options2.forEach((option) => {
            expect(option.id).toBe(option.text);
            expect(option.id).toContain('fa-');
        });

        // Test with both icon sets
        const webIconSelector3 = new WebIconSelector(document.getElementById('test3'), ['ti', 'fa']);
        webIconSelector3.init();

        let has_ti = false;
        let has_fa = false;
        const select2_options3 = $('#test3').data('select2').results.data._dataToConvert;
        expect(select2_options3).toBeDefined();
        expect(select2_options3.length).toBe(5);
        // each option id and text should match and contain 'fa-' or 'ti-' and both types of icons should be present
        select2_options3.forEach((option) => {
            expect(option.id).toBe(option.text);
            if (option.id.includes('fa-')) {
                has_fa = true;
            } else if (option.id.includes('ti-')) {
                has_ti = true;
            }
        });
        expect(has_ti).toBeTrue();
        expect(has_fa).toBeTrue();
    });
});
