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
import PlanningView from '/js/src/vue/Planning/PlanningView.vue';
import {enableAutoUnmount, flushPromises, mount} from "@vue/test-utils";

enableAutoUnmount(afterEach);

describe('Planning/PlanningView Vue Component', async () => {
    beforeEach(() => {
        $(document).off();
        window.AjaxMock.end();
        document.body.innerHTML = `
            <div id="test-container"></div>
        `;
    });

    test('shallow mount', async () => {
        const component = mount(PlanningView, {
            props: {
                can_create: true,
                can_delete: true,
                full_view: true,
                now: '2026-06-05 21:02:10',
                planning_config: {
                    lastview: 'dayGridMonth',
                    filters: {
                        ChangeTask: {
                            color: '#E94A31',
                            display: true,
                            type: 'event_filter',
                        }
                    },
                    plannings: {
                        user_2: {
                            color: '#ffeec4',
                            display: true,
                            type: 'user'
                        }
                    },
                },
                active_entity: {
                    id: 0,
                    is_recursive: true,
                },
            },
            attachTo: document.querySelector('#test-container'),
            shallow: true,
        });
        await flushPromises();
        expect(component.exists()).toBe(true);
        expect(component.findComponent({name: 'PlanningFiltersPanel'}).exists()).toBe(true);
        expect(component.findComponent({name: 'PlanningScheduler'}).exists()).toBe(true);
    });
});
