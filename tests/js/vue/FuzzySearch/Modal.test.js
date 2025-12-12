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
        document.body.innerHTML = `
            <button class="trigger-fuzzy"></button>
            <div id="fuzzy-search-modal"></div>
        `;
    });

    beforeEach(() => {
        // clear document event listeners
        // eslint-disable-next-line no-restricted-syntax
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
        expect(document.getElementById('fuzzysearch')).not.toHaveClass('show');
        document.querySelector('.trigger-fuzzy').dispatchEvent(new MouseEvent('click', {bubbles: true}));
        await new Promise(process.nextTick);
        expect(document.getElementById('fuzzysearch')).toHaveClass('show');
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
        document.querySelector('.trigger-fuzzy').dispatchEvent(new MouseEvent('click', {bubbles: true}));
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();

        const results_items = document.querySelectorAll('#fuzzysearch .results li a');
        expect(results_items.length).toBe(2);
        expect(results_items[0]).toHaveAttribute('href', 'url1');
        expect(results_items[0]).toHaveTextContent('item1');
        expect(results_items[1]).toHaveAttribute('href', 'url2');
        expect(results_items[1]).toHaveTextContent('item2');
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
        document.querySelector('.trigger-fuzzy').dispatchEvent(new MouseEvent('click', {bubbles: true}));
        await new Promise(process.nextTick);

        let results_items = document.querySelectorAll('#fuzzysearch .results li a');
        expect(results_items.length).toBe(2);

        await wrapper.find('input').setValue('item1');
        results_items = document.querySelectorAll('#fuzzysearch .results li a');
        expect(results_items.length).toBe(1);
        expect(results_items[0]).toHaveAttribute('href', 'url1');

        await wrapper.find('input').setValue('');
        results_items = document.querySelectorAll('#fuzzysearch .results li a');
        expect(results_items.length).toBe(2);

        await wrapper.find('input').setValue('i2');
        results_items = document.querySelectorAll('#fuzzysearch .results li a');
        expect(results_items.length).toBe(1);
        expect(results_items[0]).toHaveAttribute('href', 'url2');
    });
    test('close modal', async () => {
        const wrapper = mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);
        document.querySelector('.trigger-fuzzy').dispatchEvent(new MouseEvent('click', {bubbles: true}));
        await new Promise(process.nextTick);

        // Escape key should close modal
        wrapper.trigger('keydown', {key: 'Escape'});
        await new Promise(process.nextTick);
        expect(document.getElementById('fuzzysearch')).not.toHaveClass('show');
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
        document.querySelector('.trigger-fuzzy').dispatchEvent(new MouseEvent('click', {bubbles: true}));
        await new Promise(process.nextTick);
        expect(document.getElementById('fuzzysearch')).toHaveClass('show');

        await wrapper.trigger('keyup', {key: 'ArrowDown'});
        await new Promise(process.nextTick);
        // second item should be selected
        expect(document.querySelector('#fuzzysearch .results li:nth-child(2)')).toHaveClass('active');
        await wrapper.trigger('keyup', {key: 'ArrowDown'});
        await new Promise(process.nextTick);
        // second item should still be selected as there is no third item
        expect(document.querySelector('#fuzzysearch .results li:nth-child(2)')).toHaveClass('active');
        await wrapper.trigger('keyup', {key: 'ArrowUp'});
        await new Promise(process.nextTick);
        // first item should be selected
        expect(document.querySelector('#fuzzysearch .results li:nth-child(1)')).toHaveClass('active');
        await wrapper.trigger('keyup', {key: 'ArrowUp'});
        await new Promise(process.nextTick);
        // first item should still be selected as there is no item before it
        expect(document.querySelector('#fuzzysearch .results li:nth-child(1)')).toHaveClass('active');
    });
    test('non-mac hotkeys', async () => {
        mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);

        // Pressing Ctrl + Alt + G should open the modal
        document.body.dispatchEvent(new KeyboardEvent('keydown', {ctrlKey: true, altKey: true, key: 'g', bubbles: true}));
        await new Promise(process.nextTick);
        expect(document.getElementById('fuzzysearch')).toHaveClass('show');
    });
    test('mac hotkeys', async () => {
        mount(FuzzySearchModal, {
            attachTo: '#fuzzy-search-modal'
        });
        await new Promise(process.nextTick);

        // Pressing Command + Option + G should open the modal
        document.body.dispatchEvent(new KeyboardEvent('keydown', {metaKey: true, altKey: true, key: 'g', bubbles: true}));
        await new Promise(process.nextTick);
        expect(document.getElementById('fuzzysearch')).toHaveClass('show');
    });
});
