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

import '/build/vue/app.js';
import FuzzySearchModal from '/js/src/vue/FuzzySearch/Modal.vue';
import '/lib/fuzzy.js';
import {enableAutoUnmount, mount} from "@vue/test-utils";

enableAutoUnmount(afterEach);

describe('FuzzySearch/Modal Vue Component', () => {
    beforeAll(() => {
        $('body').append(`
            <button class="trigger-fuzzy"></button>
            <div id="fuzzy-search-modal"></div>
        `);
    });

    beforeEach(() => {
        // clear document event listeners
        $(document).off();
        // clear ajax mock
        window.AjaxMock.end();
    });

    test('component in global components list', async () => {
        /**
         * This tests how the component would be accessed in a real environment.
         * Not used like this in tests because the async loading of components doesn't work (or I don't know how to make it work).
         */
        expect(window.Vue.components['FuzzySearch/Modal']).toBeDefined();
        expect(window.Vue.components['FuzzySearch/Modal'].component).toHaveProperty('name', 'AsyncComponentWrapper');
    });
    test('get menus after display', async () => {
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/fuzzysearch.php', 'GET', {}, () => {
            return [];
        }));

        mount(FuzzySearchModal, {
            props: {
                button_id: 'trigger-fuzzy'
            },
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);

        // request should not be made before display
        expect(window.AjaxMock.isResponseStackEmpty()).toBeFalse();
        expect($('#fuzzysearch').hasClass('show')).toBeFalse();
        $('.trigger-fuzzy').click();
        await new Promise(process.nextTick);
        expect($('#fuzzysearch').hasClass('show')).toBeTrue();
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });
    test('list populated', async () => {
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/fuzzysearch.php', 'GET', {}, () => {
            return [
                {
                    url: 'url1',
                    title: 'item1'
                },
                {
                    url: 'url2',
                    title: 'item2'
                }
            ];
        }));

        mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);
        $('.trigger-fuzzy').click();
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();

        const results_items = $('#fuzzysearch .results li');
        expect(results_items.length).toBe(2);
        expect(results_items.eq(0).find('a').attr('href')).toBe('url1');
        expect(results_items.eq(0).find('a').text()).toBe('item1');
        expect(results_items.eq(1).find('a').attr('href')).toBe('url2');
        expect(results_items.eq(1).find('a').text()).toBe('item2');
    });
    test('fuzzy filter', async () => {
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/fuzzysearch.php', 'GET', {}, () => {
            return [
                {
                    url: 'url1',
                    title: 'item1'
                },
                {
                    url: 'url2',
                    title: 'item2'
                }
            ];
        }));

        const wrapper = mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);
        $('.trigger-fuzzy').click();
        await new Promise(process.nextTick);

        expect($('#fuzzysearch .results li').length).toBe(2);
        await wrapper.find('input').setValue('item1');
        expect($('#fuzzysearch .results li').length).toBe(1);
        expect($('#fuzzysearch .results li a').attr('href')).toBe('url1');
        await wrapper.find('input').setValue('');
        expect($('#fuzzysearch .results li').length).toBe(2);
        await wrapper.find('input').setValue('i2');
        expect($('#fuzzysearch .results li').length).toBe(1);
        expect($('#fuzzysearch .results li a').attr('href')).toBe('url2');
    });
    test('close modal', async () => {
        const wrapper = mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);
        $('.trigger-fuzzy').click();
        await new Promise(process.nextTick);

        // Escape key should close modal
        wrapper.trigger('keydown', {key: 'Escape'});
        await new Promise(process.nextTick);
        expect($('#fuzzysearch').hasClass('show')).toBeFalse();
    });
    test('arrow keys navigation', async () => {
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/fuzzysearch.php', 'GET', {}, () => {
            return [
                {
                    url: 'url1',
                    title: 'item1'
                },
                {
                    url: 'url2',
                    title: 'item2'
                }
            ];
        }));
        const wrapper = mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);
        $('.trigger-fuzzy').click();
        await new Promise(process.nextTick);
        expect($('#fuzzysearch').hasClass('show')).toBeTrue();

        wrapper.trigger('keyup', {key: 'ArrowDown'});
        await new Promise(process.nextTick);
        // second item should be selected
        expect($('#fuzzysearch .results li').eq(1).hasClass('active')).toBeTrue();
        wrapper.trigger('keyup', {key: 'ArrowDown'});
        await new Promise(process.nextTick);
        // second item should still be selected as there is no third item
        expect($('#fuzzysearch .results li').eq(1).hasClass('active')).toBeTrue();
        wrapper.trigger('keyup', {key: 'ArrowUp'});
        await new Promise(process.nextTick);
        // first item should be selected
        expect($('#fuzzysearch .results li').eq(0).hasClass('active')).toBeTrue();
        wrapper.trigger('keyup', {key: 'ArrowUp'});
        await new Promise(process.nextTick);
        // first item should still be selected as there is no item before it
        expect($('#fuzzysearch .results li').eq(0).hasClass('active')).toBeTrue();
    });
    test('non-mac hotkeys', async () => {
        mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);

        // Pressing Ctrl + Alt + G should open the modal
        $('body').trigger($.Event('keydown', {ctrlKey: true, altKey: true, key: 'g'}));
        await new Promise(process.nextTick);
        expect($('#fuzzysearch').hasClass('show')).toBeTrue();
    });
    test('mac hotkeys', async () => {
        mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);

        // Pressing Command + Option + G should open the modal
        $('body').trigger($.Event('keydown', {metaKey: true, altKey: true, key: 'g'}));
        await new Promise(process.nextTick);
        expect($('#fuzzysearch').hasClass('show')).toBeTrue();
    });
});
