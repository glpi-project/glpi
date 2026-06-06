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
import PlanningEvent from '/js/src/vue/Planning/PlanningEvent.vue';
import {enableAutoUnmount, flushPromises, mount} from "@vue/test-utils";
import { ref } from 'vue';

enableAutoUnmount(afterEach);

describe('Planning/PlanningEvent Vue Component', () => {
    beforeEach(() => {
        // clear document event listeners
        $(document).off();
        // Reset body content
        document.body.innerHTML = `
            <div id="test-container">
                <a class="fc-event" href="#">
                    <div class="fc-event-main"></div>
                </a>
            </div>
            <div id="event-context-menu" class="d-none planning-context-menu position-fixed card">
                <ul>
                    <li>
                        <button><i class="ti ti-copy"></i>Clone</button>
                    </li>
                    <li>
                        <button><i class="ti ti-trash"></i>Delete</button>
                    </li>
                </ul>
            </div>
        `;
    });

    const event_info_basic = {
        timeText: '10:30',
        event: {
            title: 'Test event',
            extendedProps: {
                typeColor: '#ff0000',
                icon: 'ti ti-calendar-event',
                icon_alt: 'Event icon',
                tooltip: '<p><b>Test event content</b></p>',
            },
            _def: {
                defId: 'event1',
                ui: {
                    display: null
                }
            }
        }
    };

    const event_info_noicon = {
        timeText: '10:30',
        event: {
            title: 'Test event',
            extendedProps: {
                typeColor: '#ff0000',
                tooltip: '<p><b>Test event content</b></p>',
            },
            _def: {
                defId: 'event1',
                ui: {
                    display: null
                }
            }
        }
    };

    async function mountEvent(event_info) {
        window.bootstrap = {
            Popover: vi.fn(class {
                dispose = vi.fn();
            }),
        };
        const component = mount(PlanningEvent, {
            props: {
                event_info: event_info,
            },
            attachTo: document.querySelector('#test-container .fc-event-main'),
            global: {
                provide: {
                    scheduler: {
                        current_view: ref('dayGridMonth'),
                        event_context_menu_el: $('#event-context-menu'),
                    }
                }
            }
        });
        await flushPromises();
        return component;
    }

    test('mount basic event and cleanup', async () => {
        const component = await mountEvent(event_info_basic);

        // popover should be initialized
        vi.spyOn(window.bootstrap, 'Popover');
        expect(window.bootstrap.Popover).toHaveBeenCalledWith(expect.anything(), expect.objectContaining({
            trigger: 'hover focus',
            html: true,
            content: '<p><b>Test event content</b></p>',
        }));
        const popoverInstance = window.bootstrap.Popover.mock.results[0]?.value;

        expect(component.find('.fc-time').text()).toBe('10');
        expect(component.find('.fc-title').text()).toBe('Test event');
        expect(component.find('i.ti-calendar-event').exists()).toBe(true);
        expect(component.find('i.ti-calendar-event').attributes('title')).toBe('Event icon');
        expect(component.find('i.ti-calendar-event').attributes('aria-label')).toBe('Event icon');

        // Unmount cleanup check
        component.unmount();
        await flushPromises();
        expect(popoverInstance.dispose).toHaveBeenCalled();
    });

    test('mount event with no icon', async () => {
        const component = await mountEvent(event_info_noicon);
        expect(component.find('i').exists()).toBe(false);
    });
});
