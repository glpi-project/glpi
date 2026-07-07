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

import '/build/vue/app.js';
import EntitySelector from '/js/src/vue/EntitySelector/EntitySelector.vue';
import '/lib/fuzzy.js';
import {enableAutoUnmount, flushPromises, mount} from "@vue/test-utils";

enableAutoUnmount(afterEach);

describe('EntitySelector Vue Component', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        window.location.reload = vi.fn();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.resetAllMocks();
    });

    test('component in global components list', async () => {
        expect(window.Vue.components['EntitySelector/EntitySelector']).toBeDefined();
        expect(window.Vue.components['EntitySelector/EntitySelector'].component).toHaveProperty('name', 'AsyncComponentWrapper');
    });

    const mockEntityTreeData = [
        {
            "key": 0,
            "label": "Root entity",
            "children": [
                {
                    "key": 101117,
                    "label": "HQ",
                    "children": []
                },
                {
                    "key": 101118,
                    "label": "Remote Office A",
                    "children": [
                        {
                            "key": 101120,
                            "label": "HR",
                            "children": []
                        },
                        {
                            "key": 101119,
                            "label": "IT",
                            "children": []
                        },
                        {
                            "key": 101121,
                            "label": "Maintenance",
                            "children": []
                        }
                    ]
                },
                {
                    "key": 101122,
                    "label": "Remote Office B",
                    "children": [
                        {
                            "key": 101124,
                            "label": "HR",
                            "children": []
                        },
                        {
                            "key": 101123,
                            "label": "IT",
                            "children": []
                        }
                    ]
                }
            ],
            "expanded": true,
            "selected": true
        }
    ];

    async function mountEntitySelector() {
        const wrapper = mount(EntitySelector, {
            attachTo: document.body,//'#entity-tree-dropdown',
            props: {
                current_entity: 'Root entity',
                current_entity_short: 'Root',
            },
            global: {
                mocks: {
                    __: (msg) => msg,
                }
            }
        });
        await flushPromises();
        return wrapper;
    }

    test('mounts', async () => {
        const wrapper = await mountEntitySelector();

        expect(wrapper.find('a').attributes()).toHaveProperty('title', 'Root entity');
        expect(wrapper.find('a').text()).toBe('Root');
        expect(wrapper.find('input[name="entsearchtext"]').exists()).toBe(true);
    });

    test('hotkeys', async () => {
        const wrapper = await mountEntitySelector();
        const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
            ok: true,
            json: async () => mockEntityTreeData,
        });

        const event = new KeyboardEvent('keydown', {ctrlKey: true, altKey: true, key: 'e'});
        document.dispatchEvent(event);
        await flushPromises();
        vi.advanceTimersByTime(1500);
        await flushPromises();
        expect(fetchMock).toHaveBeenCalledTimes(1);
        wrapper.unmount();

        await mountEntitySelector();
        const macEvent = new KeyboardEvent('keydown', {metaKey: true, altKey: true, key: 'e'});
        document.dispatchEvent(macEvent);
        await flushPromises();
        vi.advanceTimersByTime(1500);
        await flushPromises();
        expect(fetchMock).toHaveBeenCalledTimes(2);
    });

    test('keyboard navigation', async () => {
        const wrapper = await mountEntitySelector();
        const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue({
            ok: true,
            json: async () => mockEntityTreeData,
        });

        const event = new KeyboardEvent('keydown', {ctrlKey: true, altKey: true, key: 'e'});
        document.dispatchEvent(event);
        await flushPromises();
        vi.advanceTimersByTime(1500);
        await flushPromises();

        // JSDOM does not support tabbing so we will manually focus the first list item before starting keyboard navigation tests
        const firstListItem = wrapper.find('li');
        firstListItem.element.focus();
        // Should be the Root entity
        expect(document.activeElement).toBe(firstListItem.element);
        expect(firstListItem.text()).toBe('Root entity');

        // Press ArrowDown to navigate to the next item
        firstListItem.element.dispatchEvent(new KeyboardEvent('keyup', {key: 'ArrowDown', bubbles: true}));
        await flushPromises();
        expect(document.activeElement.textContent.trim()).toBe('HQ');

        // Press ArrowDown again to navigate to the next item
        document.activeElement.dispatchEvent(new KeyboardEvent('keyup', {key: 'ArrowDown', bubbles: true}));
        await flushPromises();
        expect(document.activeElement.textContent.trim()).toBe('Remote Office A');

        // ArrowRight should expand Remote Office A and focus on the first child (HR)
        document.activeElement.dispatchEvent(new KeyboardEvent('keyup', {key: 'ArrowRight', bubbles: true}));
        await flushPromises();
        expect(document.activeElement.textContent.trim()).toBe('HR');

        // ArrowDown should navigate to the next sibling (IT)
        document.activeElement.dispatchEvent(new KeyboardEvent('keyup', {key: 'ArrowDown', bubbles: true}));
        await flushPromises();
        expect(document.activeElement.textContent.trim()).toBe('IT');

        // ArrowLeft should focus Remote Office A without collapsing it
        document.activeElement.dispatchEvent(new KeyboardEvent('keyup', {key: 'ArrowLeft', bubbles: true}));
        await flushPromises();
        expect(document.activeElement.textContent.trim()).toBe('Remote Office A');
        // Check "HR" is still visible to ensure it did not collapse
        expect(wrapper.find('li[data-key="101120"]').exists()).toBe(true);

        // ArrowLeft again should collapse Remote Office A and keep it focused
        document.activeElement.dispatchEvent(new KeyboardEvent('keyup', {key: 'ArrowLeft', bubbles: true}));
        await flushPromises();
        expect(document.activeElement.textContent.trim()).toBe('Remote Office A');
        // Check "HR" is not visible to ensure it collapsed
        expect(wrapper.find('li[data-key="101120"]').exists()).toBe(false);

        // Arrow up should navigate back to HQ
        document.activeElement.dispatchEvent(new KeyboardEvent('keyup', {key: 'ArrowUp', bubbles: true}));
        await flushPromises();
        expect(document.activeElement.textContent.trim()).toBe('HQ');

        // Enter key should trigger the change to HQ entity without selecting children
        fetchMock.mockClear();
        fetchMock.mockResolvedValue({
            ok: true,
            json: async () => ({}),
        });
        document.activeElement.dispatchEvent(new KeyboardEvent('keyup', {key: 'Enter', bubbles: true}));
        await flushPromises();
        let request_body = new URLSearchParams(fetchMock.mock.calls[0][1].body);
        expect(request_body.get('id')).toBe('101117');
        expect(request_body.get('is_recursive')).toBe('false');
        expect(fetchMock).toHaveBeenCalledWith(expect.stringContaining('/Session/ChangeEntity'), expect.objectContaining({
            method: 'POST',
            headers: expect.objectContaining({
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            })
        }));

        // Ctrl+Enter should trigger the change to HQ entity with selecting children
        fetchMock.mockClear();
        document.activeElement.dispatchEvent(new KeyboardEvent('keyup', {key: 'Enter', ctrlKey: true, bubbles: true}));
        await flushPromises();
        request_body = new URLSearchParams(fetchMock.mock.calls[0][1].body);
        expect(request_body.get('id')).toBe('101117');
        expect(request_body.get('is_recursive')).toBe('true');
        expect(fetchMock).toHaveBeenCalledWith(expect.stringContaining('/Session/ChangeEntity'), expect.objectContaining({
            method: 'POST',
            headers: expect.objectContaining({
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            })
        }));
    });
});
