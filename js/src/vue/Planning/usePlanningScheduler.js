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

/* global _, glpi_html_dialog, glpi_ajax_dialog, flatpickr */

import useScheduler from "../FullCalendar/useScheduler.js";
import { watch, ref, onMounted, onUnmounted } from "vue";

export default function usePlanningScheduler(calendar_el, current_view, full_view, date_picker_el, event_context_menu_el, now) {
    const calendar_api = ref(null);
    let date_picker_flatpickr = null;

    onMounted(() => {
        calendar_api.value = calendar_el.value.getApi();
        window.focus();

        if (full_view) {
            date_picker_flatpickr = new flatpickr(date_picker_el.value, {
                // keep the date picker in sync with the calendar's own notion of "now"
                now: now,
                onChange: function (selected_date) {
                    // convert to UTC to avoid timezone issues
                    const date = new Date(
                        Date.UTC(
                            selected_date[0].getFullYear(),
                            selected_date[0].getMonth(),
                            selected_date[0].getDate()
                        )
                    );
                    calendar_api.value.gotoDate(date);
                }
            });
        }
        refresh();

        window.addEventListener('click', hideContextMenu);
    });

    onUnmounted(() => {
        if (date_picker_flatpickr) {
            date_picker_flatpickr.destroy();
        }
        window.removeEventListener('click', hideContextMenu);
    });

    function hideContextMenu() {
        if (event_context_menu_el.value) {
            event_context_menu_el.value.classList.add('d-none');
        }
    }

    function getCalendarApi() {
        return calendar_api.value;
    }

    function clearSelection() {
        getCalendarApi().unselect();
    }

    function refresh() {
        const api = getCalendarApi();
        if (api.getEventSources().length === 0) {
            const debounced_source = _.debounce(getEvents, 200);
            api.addEventSource(debounced_source);
        }
        if (typeof api.refetchResources === 'function') {
            api.refetchResources();
        }
        api.refetchEvents();
        window.displayAjaxMessageAfterRedirect();
    }

    function editEventTimes(info, move_instance = false) {
        const event = info.event;
        const revert_func = info.revert;
        const ext_props = event.extendedProps;
        const recurring_def = event._def.recurringDef;

        let old_itemtype = null;
        let old_items_id = null;
        let new_itemtype = null;
        let new_items_id = null;

        if (info?.newResource) {
            new_itemtype = info.newResource._resource.extendedProps.itemtype;
            new_items_id = info.newResource._resource.extendedProps.items_id;
        }

        if (info?.oldResource) {
            old_itemtype = info.oldResource._resource.extendedProps.itemtype;
            old_items_id = info.oldResource._resource.extendedProps.items_id;
        }

        let start = event.start;
        let end   = event.end;

        if (!move_instance && recurring_def && recurring_def?.typeData?.origOptions?.dtstart !== start) {
            const startDate = new Date(start);
            const dtstart = recurring_def.typeData._dtstart || recurring_def.typeData.origOptions.dtstart;
            const originDate = new Date(dtstart);

            const hours = startDate.getHours();
            const minutes = startDate.getMinutes();

            originDate.setHours(hours, minutes);

            start = originDate;

            const duration = end - event.start;
            end = new Date(start.getTime() + duration);
        }

        if (typeof end === 'undefined' || end === null) {
            end = new Date(start.getTime());
            if (event.allDay) {
                end.setDate(end.getDate() + 1);
            } else {
                end.setHours(end.getHours() + 2);
            }
        }

        const old_event = info.oldEvent || {};
        const old_start = old_event.start || start;

        fetch(`${CFG_GLPI.root_doc}/ajax/planning.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                action:        'update_event_times',
                start:         start.toISOString(),
                end:           end.toISOString(),
                itemtype:      ext_props.itemtype,
                items_id:      ext_props.items_id,
                move_instance: move_instance,
                old_start:     old_start.toISOString(),
                new_actor_itemtype: new_itemtype,
                new_actor_items_id: new_items_id,
                old_actor_itemtype: old_itemtype,
                old_actor_items_id: old_items_id,
            }),
        }).then(response => {
            if (!response.ok) {
                revert_func();
                return false;
            } else {
                return response.json();
            }
        }).then(data => {
            if (data) {
                refresh();
            } else {
                revert_func();
            }
        }).catch(() => {
            revert_func();
        });
    }

    function getEventByDefId(defId) {
        return getCalendarApi()
            .getEvents()
            .find(event => event._def.defId === defId);
    }

    function getEvents(info, success_callback, failure_callback) {
        const view_name = current_view.value;
        const params = {
            start: info.startStr,
            end: info.endStr,
            action: 'get_events',
            view_name: view_name,
        };
        if (!full_view) {
            params.state_done = false;
        }
        const url = `${CFG_GLPI.root_doc}/ajax/planning.php?${new URLSearchParams(params).toString()}`;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(response => response.json())
            .then(data => {
                success_callback(data);
            }).catch(error => {
            failure_callback(error);
        });
    }

    function cloneEvent(event_defid) {
        const event = getEventByDefId(event_defid);
        if (!event) {
            return;
        }

        let actor = {};
        const resources = event.getResources();

        // manage resource changes
        if (resources.length === 1) {
            actor = {
                itemtype: resources[0].extendedProps.itemtype || null,
                items_id: resources[0].extendedProps.items_id || null,
            };
        }

        fetch(`${CFG_GLPI.root_doc}/ajax/planning.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                action: 'clone_event',
                old_itemtype: event.extendedProps.itemtype,
                old_items_id: event.extendedProps.items_id,
                actor_itemtype: actor.itemtype,
                actor_items_id: actor.items_id,
                start: event.start.toISOString(),
                end: event.end.toISOString(),
            }),
        }).then(response => {
            if (response.ok) {
                refresh();
            }
        });
    }

    function deleteEvent(event_defid) {
        const event = getEventByDefId(event_defid);
        if (!event) {
            return;
        }

        const doDelete = (instance = false) => {
            fetch(`${CFG_GLPI.root_doc}/ajax/planning.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({
                    action: 'delete_event',
                    'event[itemtype]': event.extendedProps.itemtype,
                    'event[items_id]': event.extendedProps.items_id,
                    'event[day]': event.start.toISOString().substring(0, 10),
                    'event[instance]': instance ? 1 : 0,
                }),
            }).then(response => {
                if (response.ok) {
                    refresh();
                }
            });
        };

        if (!('is_recurrent' in event.extendedProps) || !event.extendedProps.is_recurrent) {
            doDelete();
        } else {
            glpi_html_dialog({
                title: __("Make a choice"),
                body: `${__("Delete the whole series of the recurrent event")}<br>${
                    __("or just add an exception by deleting this instance?")}`,
                buttons: [
                    {
                        label: __("Series"),
                        click: () => {
                            doDelete(false);
                        }
                    }, {
                        label: _n("Instance", "Instances", 1),
                        click: () => {
                            doDelete(true);
                        }
                    }
                ]
            });
        }
    }

    function createEventFromSelect(info) {
        const itemtype = info.resource?._resource?.extendedProps?.itemtype || '';
        const items_id = info.resource?._resource?.extendedProps?.items_id || 0;

        // prevent adding events on group users
        if (itemtype === 'Group_User') {
            clearSelection();
            return false;
        }

        const start = info.start;
        const end = info.end;

        if (document.querySelector('div.modal.planning-modal') === null) {
            glpi_ajax_dialog({
                url: `${CFG_GLPI.root_doc}/ajax/planning.php`,
                params: {
                    action: 'add_event_fromselect',
                    begin: start.toISOString(),
                    end: end.toISOString(),
                    res_itemtype: itemtype,
                    res_items_id: items_id
                },
                dialogclass: 'modal-lg planning-modal',
                title: __('Add an event'),
                bs_focus: false,
                close: () => refresh()
            });
        }

        clearSelection();
    }

    /**
     * Set end of day markers on timeline view
     */
    function setEndOfDays() {
        if (current_view.value !== 'resourceWeek') {
            return;
        }

        // Compute number of hour slots displayed
        const time_bgein = CFG_GLPI.planning_begin.split(':');
        const time_end = CFG_GLPI.planning_end.split(':');
        const begin_in_minutes = parseInt(time_bgein[0]) * 60 + parseInt(time_bgein[1]);
        const end_in_minutes = parseInt(time_end[0]) * 60 + parseInt(time_end[1]);
        const number_of_slots = Math.ceil((end_in_minutes - begin_in_minutes) / 60);

        // Add EOD to days
        document.querySelectorAll('.fc-timeline-header-row:nth-of-type(2) .fc-day').forEach((day_elem) => {
            day_elem.classList.add('end-of-day');
        });
        // Add EOD to hours
        document.querySelectorAll(`.fc-timeline-header-row:nth-of-type(3) .fc-day:nth-child(${number_of_slots}n)`).forEach((day_elem) => {
            day_elem.classList.add('end-of-day');
        });
        // Add EOD to content slots
        document.querySelectorAll(`.fc-timeline-slots .fc-timeline-slot:nth-child(${number_of_slots}n)`).forEach((day_elem) => {
            day_elem.classList.add('end-of-day');
        });
    }

    function onEventResize(info) {
        const event        = info.event;
        const exprops      = event.extendedProps;
        const is_recurrent = exprops.is_recurrent || false;

        if (is_recurrent) {
            glpi_html_dialog({
                title: __("Recurring event resized"),
                body: __("The resized event is a recurring event. Do you want to change the series or instance ?"),
                buttons: [
                    {
                        label: __("Series"),
                        click: () => editEventTimes(info)
                    }, {
                        label: _n("Instance", "Instances", 1),
                        click: () => editEventTimes(info, true)
                    }
                ]
            });
        } else {
            editEventTimes(info);
        }
    }

    function onEventDrop(info) {
        const event        = info.event;
        const exprops      = event.extendedProps;
        const is_recurrent = exprops.is_recurrent || false;

        if (is_recurrent) {
            glpi_html_dialog({
                title: __("Recurring event dragged"),
                body: __("The dragged event is a recurring event. Do you want to move the series or instance?"),
                buttons: [
                    {
                        label: __("Series"),
                        click: () => editEventTimes(info)
                    }, {
                        label: _n("Instance", "Instances", 1),
                        click: () => editEventTimes(info, true)
                    }
                ]
            });
        } else {
            editEventTimes(info);
        }
    }

    watch(current_view, (new_view) => {
        if (full_view) {
            fetch(`${CFG_GLPI.root_doc}/ajax/planning.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({
                    action: 'view_changed',
                    view: new_view,
                }),
            });
        } else {
            // Observe changes in the DOM before triggering
            const observer = new MutationObserver((mutations, obs) => {
                if (document.readyState === 'complete') {
                    obs.disconnect(); // Stop observation once the DOM is stable
                    setTimeout(() => {
                        document.dispatchEvent(new Event('masonry_grid:layout'));
                    }, 100);
                }
            });

            observer.observe(document.body, {childList: true, subtree: true});
        }

        setEndOfDays();
        refresh();
    });

    return {
        ...useScheduler(),
        editEventTimes,
        refresh,
        clearSelection,
        getEventByDefId,
        cloneEvent,
        deleteEvent,
        createEventFromSelect,
        setEndOfDays,
        onEventResize,
        onEventDrop,
        hideContextMenu,
        current_view,
        event_context_menu_el,
    };
};
