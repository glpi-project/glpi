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
import {flushPromises, mount} from "@vue/test-utils";
import Kanban from '/js/src/vue/Kanban/KanbanApp.vue';

describe('Kanban', () => {
    beforeEach(() => {
        document.body.innerHTML = '<div id="kanban-app"></div>';
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('/ajax/kanban.php', 'GET', {
            action: 'load_column_state'
        }, () => {
            return {"state":{"0":{"column":"0","folded": "false"}, "1":{"column":"1","folded": "false"}},"timestamp":"2026-06-17 08:59:14"};
        }));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('/ajax/kanban.php', 'GET', {
            action: 'get_kanbans'
        }, () => {
            return {"-1": "Global", "1": "Project 1"};
        }));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('/ajax/kanban.php', 'GET', {
            action: 'refresh'
        }, () => {
            return {"0":{"name":"No status","_protected":true}};
        }));

        window.sortable = vi.fn().mockImplementation(() => {
            return null;
        });
    });

    async function mountKanban() {
        const wrapper = mount(Kanban, {
            appendTo: document.getElementById('kanban-app'),
            props: {
                rights: {
                    create_card_limited_columns: [],
                    create_column: true,
                    create_item: true,
                    delete_item: true,
                    modify_view: true,
                    order_card: true,
                },
                supported_itemtypes: {
                    "Project":{
                        "name":"Project",
                        "icon":"ti ti-layout-kanban",
                        "fields":{
                            "projects_id":{"type":"hidden","value":0},
                            "name":{"placeholder":"Name"},
                            "content":{"placeholder":"Content","type":"textarea"},
                            "users_id":{"type":"hidden","value":2},
                            "entities_id":{"type":"hidden","value":0},
                            "is_recursive":{"type":"hidden","value":0}
                        },
                        "team_itemtypes":["User","Group","Supplier","Contact"],
                        "team_roles":{"7":"Member"},
                        "allow_create":true
                    },
                    "ProjectTask":{
                        "name":"Project task",
                        "icon":"ti ti-list-check",
                        "fields":{
                            "projects_id":{"type":"raw","value":"<select name=\"projects_id\"><option value=\"1\">Project 1</option><option value=\"2\">Project 2</option></select>"},
                            "name":{"placeholder":"Name"},
                            "content":{"placeholder":"Content","type":"textarea"},
                            "projecttasktemplates_id":{"type":"hidden","value":0},
                            "projecttasks_id":{"type":"hidden","value":0},
                            "entities_id":{"type":"hidden","value":0},
                            "is_recursive":{"type":"hidden","value":0}
                        },
                        "team_itemtypes":["User","Group","Supplier","Contact"],
                        "team_roles":{"7":"Member"},
                        "allow_create":true,
                        "allow_bulk_add":false
                    }
                },
                column_field: {id: 'projectstates_id', extra_fields: {color: {type: 'color'}}},
                item: {itemtype: 'Project', items_id: 0},
                supported_filters: {
                    contact: {
                        autocomplete_values: [],
                        description: 'Contact',
                        supported_prefixes: ['!'],
                    },
                    content: {
                        autocomplete_values: [],
                        description: 'Content',
                        supported_prefixes: ['!', '#'],
                    },
                    deleted: {
                        autocomplete_values: [],
                        description: 'Is deleted',
                        supported_prefixes: ['!'],
                    },
                    group: {
                        autocomplete_values: [],
                        description: 'Group',
                        supported_prefixes: ['!'],
                    },
                    milestone: {
                        autocomplete_values: [],
                        description: 'Milestone',
                        supported_prefixes: ['!'],
                    },
                    supplier: {
                        autocomplete_values: [],
                        description: 'Supplier',
                        supported_prefixes: ['!'],
                    },
                    team: {
                        autocomplete_values: [],
                        description: 'Team',
                        supported_prefixes: ['!'],
                    },
                    title: {
                        autocomplete_values: [],
                        description: 'Title',
                        supported_prefixes: ['!', '#'],
                    },
                    type: {
                        autocomplete_values: [],
                        description: 'Type',
                        supported_prefixes: ['!'],
                    },
                    user: {
                        autocomplete_values: [],
                        description: 'User',
                        supported_prefixes: ['!'],
                    },
                }
            },
            global: {
                mocks: {
                    __: (msg) => msg
                }
            }
        });
        // A loading state should be present before the kanban is loaded
        expect(wrapper.find('.spinner-border').exists()).toBe(true);
        // wait for the kanban to load and the loading state to disappear
        await flushPromises();
        // The loading spinner should disappear when the kanban is loaded
        expect(wrapper.find('.spinner-border').exists()).toBe(false);
        return wrapper;
    }

    it('Global project kanban loads', async () => {
        let popoverOptions = null;
        $.fn.popover = vi.fn().mockImplementation((options) => {
            if (typeof options === 'string') {
                if (options === 'show') {
                    toolbar.element.insertAdjacentHTML('beforeend', `<div class="search-input-popover">${popoverOptions.content()}</div>`);
                } else if (options === 'hide' || options === 'dispose') {
                    toolbar.element.querySelector('.search-input-popover').remove();
                }
            } else {
                popoverOptions = options;
            }
        });
        const wrapper = await mountKanban();

        const toolbar = wrapper.find('.kanban-toolbar');
        // console.log(wrapper.html());
        expect(toolbar.exists()).toBe(true);
        expect(toolbar.find('select[name="kanban-board-switcher"]').element.value).toBe('-1');
        expect(toolbar.find('div.search-input').exists()).toBe(true);
        expect(toolbar.findAll('button').some((btn) => btn.text() === 'Add column')).toBe(true);
        const moreActionsButton = toolbar.find('button[aria-label="More actions"]');
        expect(moreActionsButton.exists()).toBe(true);

        expect(toolbar.find('.search-input-tag-input').exists()).toBe(true);
        await toolbar.find('div.search-input').trigger('click');
        const popover = wrapper.find('.search-input-popover');
        expect(popover.exists()).toBe(true);
        const expected_tags = [
            'title', 'type', 'milestone', 'content', 'deleted', 'team', 'user', 'group', 'supplier', 'contact'
        ];
        expected_tags.forEach((tag) => {
            const tag_element = popover.find(`li[data-tag="${tag}"]`);
            expect(tag_element.exists()).toBe(true);
            expect(tag_element.find('b').text()).toBe(tag);
            expect(tag_element.find('button[title="Exclude"]').exists()).toBe(true);
            if (['title', 'content'].includes(tag)) {
                expect(tag_element.find('button[title="Regex"]').exists()).toBe(true);
            } else {
                expect(tag_element.find('button[title="Regex"]').exists()).toBe(false);
            }
            expect(tag_element.find('.text-muted').text().length).toBeGreaterThan(0);
        });
    });
});
