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
import PlanningFiltersPanel from '/js/src/vue/Planning/PlanningFiltersPanel.vue';
import {enableAutoUnmount, flushPromises, mount} from "@vue/test-utils";

enableAutoUnmount(afterEach);

describe('Planning/PlanningFiltersPanel Vue Component', () => {
    beforeEach(() => {
        // clear document event listeners
        $(document).off();
        // Reset body content
        document.body.innerHTML = `<div id="test-container"></div>`;
    });

    async function mountFiltersPanel() {
        const component = mount(PlanningFiltersPanel, {
            props: {
                filters: {
                    "filters":{
                        "ChangeTask":{"filter_key":"ChangeTask","filter_data":{"color":"#E94A31","display":true,"type":"event_filter"},"expanded":"","title":"Change tasks","params":{"show_delete":true,"filter_color_index":0},"color":"#E94A31","show_export_buttons":false,"uID":0,"gID":0,"webcal_base_url":null,"caldav_url":null,"child_filters":[]},
                        "PlanningExternalEvent":{"filter_key":"PlanningExternalEvent","filter_data":{"color":"#364959","display":true,"type":"event_filter"},"expanded":"","title":"External events","params":{"show_delete":true,"filter_color_index":0},"color":"#364959","show_export_buttons":false,"uID":0,"gID":0,"webcal_base_url":null,"caldav_url":null,"child_filters":[]},
                        "NotPlanned":{"filter_key":"NotPlanned","filter_data":{"color":"#8C5344","display":false,"type":"event_filter"},"expanded":"","title":"Not planned tasks","params":{"show_delete":true,"filter_color_index":0},"color":"#8C5344","show_export_buttons":false,"uID":0,"gID":0,"webcal_base_url":null,"caldav_url":null,"child_filters":[]},
                        "OnlyBgEvents":{"filter_key":"OnlyBgEvents","filter_data":{"color":"#FF8100","display":false,"type":"event_filter"},"expanded":"","title":"Only background events","params":{"show_delete":true,"filter_color_index":0},"color":"#FF8100","show_export_buttons":false,"uID":0,"gID":0,"webcal_base_url":null,"caldav_url":null,"child_filters":[]},
                        "StateDone":{"filter_key":"StateDone","filter_data":{"color":"#F600C4","display":true,"type":"event_filter"},"expanded":"","title":"Done elements","params":{"show_delete":true,"filter_color_index":0},"color":"#F600C4","show_export_buttons":false,"uID":0,"gID":0,"webcal_base_url":null,"caldav_url":null,"child_filters":[]}
                    },
                    "plannings":{
                        "user_2":{"filter_key":"user_2","filter_data":{"color":"#ffeec4","display":true,"type":"user"},"expanded":"","title":"glpi","params":{"show_delete":true,"filter_color_index":0},"color":"#ffeec4","show_export_buttons":true,"uID":2,"gID":0,"webcal_base_url":"webcal://glpi.localhost","caldav_url":"http://glpi.localhost/caldav.php/calendars/users/glpi/calendar","child_filters":[]},
                        "group_1_users":{
                            "filter_key":"group_1_users",
                            "filter_data":{"display":false,"type":"group_users","users":{"user_2":{"color":"#D4EDFB","display":true,"type":"user"},"user_4":{"color":"#E1D0E1","display":true,"type":"user"}}},"expanded":"","title":"Techs","params":{"show_delete":true,"filter_color_index":1},"color":"#D4EDFB","show_export_buttons":false,"uID":0,"gID":0,"webcal_base_url":null,"caldav_url":"http://glpi.localhost/caldav.php/calendars/groups/1/calendar",
                            "child_filters":{"user_2":{"filter_key":"user_2","filter_data":{"color":"#D4EDFB","display":true,"type":"user"},"expanded":"","title":"glpi","params":{"show_delete":false,"filter_color_index":0},"color":"#D4EDFB","show_export_buttons":true,"uID":2,"gID":0,"webcal_base_url":"webcal://glpi.localhost","caldav_url":"http://glpi.localhost/caldav.php/calendars/users/glpi/calendar","child_filters":[]},"user_4":{"filter_key":"user_4","filter_data":{"color":"#E1D0E1","display":true,"type":"user"},"expanded":"","title":"tech","params":{"show_delete":false,"filter_color_index":0},"color":"#E1D0E1","show_export_buttons":true,"uID":4,"gID":0,"webcal_base_url":"webcal://glpi.localhost","caldav_url":"http://glpi.localhost/caldav.php/calendars/users/tech/calendar","child_filters":[]}}
                        },
                    }
                },
                active_entity: {
                    id: 0,
                    is_recursive: true,
                }
            },
            attachTo: '#test-container',
            global: {
                mocks: {
                    __: (key) => key,
                    _x: (ctx, key) => key,
                    _n: (singular, plural, count) => count > 1 ? plural : singular,
                }
            }
        });
        await flushPromises();
        return component;
    }

    test('mount', async () => {
        const component = await mountFiltersPanel();

        // Check that checkboxes align with the display property of filters
        expect(component.find('input[type="checkbox"][value="ChangeTask"]').element).toBeChecked();
        expect(component.find('input[type="checkbox"][value="PlanningExternalEvent"]').element).toBeChecked();
        expect(component.find('input[type="checkbox"][value="NotPlanned"]').element).not.toBeChecked();
        expect(component.find('input[type="checkbox"][value="OnlyBgEvents"]').element).not.toBeChecked();
        expect(component.find('input[type="checkbox"][value="StateDone"]').element).toBeChecked();
        expect(component.find('input[type="checkbox"][value="user_2"]').element).toBeChecked();
        expect(component.find('input[type="checkbox"][value="group_1_users"]').element).not.toBeChecked();
        expect(component.find('input[type="checkbox"][value="user_4"]').element).toBeChecked();

        // Check that filters with children have an expand button
        const group_filter_parent = component.find('input[type="checkbox"][value="group_1_users"]').element.parentElement;
        expect(group_filter_parent.querySelector('button[title="Toggle filters"] i').classList.contains('ti-caret-down-filled')).toBe(true);
        // child filters should be hidden by default
        expect(component.find('input[type="checkbox"][value="user_4"]').element.closest('ul').classList.contains('d-none')).toBe(true);
        group_filter_parent.querySelector('button[title="Toggle filters"]').click();
        await flushPromises();
        expect(group_filter_parent.querySelector('button[title="Toggle filters"] i').classList.contains('ti-caret-up-filled')).toBe(true);
        expect(component.find('input[type="checkbox"][value="user_4"]').element.closest('ul').classList.contains('d-none')).toBe(false);
    });

    test('toggle filter', async () => {
        const component = await mountFiltersPanel();

        window.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({success: true})
        });

        component.find('input[type="checkbox"][value="user_2"]').element.click();
        await flushPromises();
        expect(component.emitted()['filtersUpdated']).toHaveLength(1);
        expect(window.fetch).toHaveBeenCalledWith(expect.stringContaining('/ajax/planning.php'), expect.toSatisfy((options) => {
            return options.method === 'POST'
                && options.body.get('action') === 'toggle_filter'
                && options.body.get('name') === 'user_2'
                && options.body.get('type') === 'user'
                && options.body.get('display') === 'false';
        }));
    });

    test('change color', async () => {
        const component = await mountFiltersPanel();

        window.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({success: true})
        });

        component.find('input[type="checkbox"][value="ChangeTask"]').element.parentElement.querySelector('input[type="color"]').value = '#123456';
        component.find('input[type="checkbox"][value="ChangeTask"]').element.parentElement.querySelector('input[type="color"]').dispatchEvent(new Event('change'));
        await flushPromises();
        expect(component.emitted()['filtersUpdated']).toHaveLength(1);
        expect(window.fetch).toHaveBeenCalledWith(expect.stringContaining('/ajax/planning.php'), expect.toSatisfy((options) => {
            return options.method === 'POST'
                && options.body.get('action') === 'color_filter'
                && options.body.get('name') === 'ChangeTask'
                && options.body.get('type') === 'event_filter'
                && options.body.get('color') === '#123456';
        }));
    });

    test('delete filter', async () => {
        const component = await mountFiltersPanel();

        component.find('input[type="checkbox"][value="group_1_users"]').element.parentElement.querySelector('button[title="Actions"]').click();
        await flushPromises();
        const delete_button = component.findAll('.dropdown-menu.show button').find(btn => btn.text() === 'Delete');
        expect(delete_button).toBeTruthy();
        window.fetch = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({success: true})
        });
        delete_button.element.click();
        await flushPromises();
        expect(component.emitted()['filtersUpdated']).toHaveLength(1);
        expect(window.fetch).toHaveBeenCalledWith(expect.stringContaining('/ajax/planning.php'), expect.toSatisfy((options) => {
            return options.method === 'POST'
                && options.body.get('action') === 'delete_filter'
                && options.body.get('filter') === 'group_1_users'
                && options.body.get('type') === 'group_users';
        }));
    });
});
