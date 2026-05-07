<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {computed, onMounted, onUnmounted, ref, useTemplateRef, watch} from 'vue';
    import BaseFullCalendar from '../FullCalendar/BaseFullCalendar.vue';
    import dayGridPlugin from '@fullcalendar/daygrid';
    import interactionPlugin from '@fullcalendar/interaction';
    import listPlugin from '@fullcalendar/list';
    import timeGridPlugin from '@fullcalendar/timegrid';
    import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
    import rrulePlugin from '@fullcalendar/rrule';
    import bootstrapPlugin from '@fullcalendar/bootstrap';
    import PlanningEvent from "./PlanningEvent.vue";

    const props = defineProps({
        can_create: {
            type: Boolean,
            default: false,
        },
        can_delete: {
            type: Boolean,
            default: false,
        },
        full_view: {
            type: Boolean,
            default: true,
        },
        now: {
            type: String,
        },
        header: {
            type: Object,
            default: () => {
                return {
                    start: 'prev,next today',
                    center: 'title',
                    end: 'dayGridMonth,timeGridWeek,timeGridDay,listFull,resourceWeek',
                }
            },
        },
        height: {
            type: Number,
            default: () => {
                const min_height = 300;
                return Math.max(window.innerHeight - 272, min_height);
            }
        },
        resources: {
            type: [Array, Object],
            default: () => ([]),
        },
        fullcalendar_options: {
            type: Object,
            default: () => ({}),
        },
    });

    defineExpose({
        refresh: refresh,
    });

    const all_days = [0, 1, 2, 3, 4, 5, 6];
    const enabled_days = CFG_GLPI.planning_work_days;
    const hidden_days = all_days.filter(day => !enabled_days.some(n => n == day));

    const loading = ref(true);
    const calendar = useTemplateRef('calendar');
    const date_picker = useTemplateRef('date_picker');
    const event_context_menu = useTemplateRef('event_context_menu');
    let date_picker_flatpickr = null;
    let calendar_api = null;
    let disable_edit = false;
    let disable_qtip = false;
    const current_view = ref('timeGridWeek');
    const current_view_data = ref(null);
    const list_full_year_range = props.full_view ? 5 : 1; // +/- number of years to display in list full view
    const all_resources = ref(!Array.isArray(props.resources) ? Object.values(props.resources) : props.resources);
    const visible_res = computed(() => {
        return Object.keys(all_resources).filter(index => all_resources[index].is_visible);
    });
    const dateNavVisibility = computed(() => {
        return current_view.value === 'listFull' ? 'hidden' : 'visible';
    });

    const calendar_options = Object.assign({
        plugins: [
            dayGridPlugin,
            interactionPlugin,
            listPlugin,
            timeGridPlugin,
            resourceTimelinePlugin,
            rrulePlugin,
            bootstrapPlugin,
        ],
        weekNumbers: props.full_view,
        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
        },
        dayMaxEvents: true,
        droppable: false, // we cant drop external items by default
        now: props.now,// as we set the calendar as UTC, we need to reprecise the current datetime
        listDaySideFormat: false,
        headerToolbar: props.full_view ? props.header : false,
        hiddenDays: hidden_days,
        initialView: 'timeGridWeek',
        views: {
            listFull: {
                type: 'list',
                titleFormat: function() {
                    return __('List');
                },
                visibleRange: function(currentDate) {
                    const current_year = currentDate.getFullYear();
                    return {
                        start: (new Date(currentDate.getTime())).setFullYear(current_year - list_full_year_range),
                        end: (new Date(currentDate.getTime())).setFullYear(current_year + list_full_year_range)
                    };
                }
            },
            resourceWeek: {
                type: 'resourceTimeline',
                buttonText: 'Timeline Week',
                duration: { weeks: 1 },
                groupByDateAndResource: true,
                slotLabelFormat: [
                    { week: 'short' },
                    { weekday: 'short', day: 'numeric', month: 'numeric', omitCommas: true },
                    (date) => {
                        return date.date.hour;
                    }
                ]
            },
        },
        resources: (fetchInfo, successCallback, failureCallback) => {
            // Filter resources by whether their id is in visible_res.
            successCallback(all_resources.value.filter((elem, index) => {
                return visible_res.value.indexOf(index.toString()) !== -1;
            }));
        },
        eventResizeStart: () => {
            disable_edit = true;
            disable_qtip = true;
        },
        eventResizeStop: () => {
            setTimeout(() => {
                disable_edit = false;
                disable_qtip = false;
            }, 300);
        },
        eventDragStart: () => {
            disable_qtip = true;
        },
        eventResize: (info) => {
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
                            click: () => {
                                editEventTimes(info);
                            }
                        }, {
                            label: _n("Instance", "Instances", 1),
                            click: () => {
                                editEventTimes(info, true);
                            }
                        }
                    ]
                });
            } else {
                editEventTimes(info);
            }
        },
        eventDrop: (info) =>{
            disable_qtip = false;

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
                            click: () => {
                                editEventTimes(info);
                            }
                        }, {
                            label: _n("Instance", "Instances", 1),
                            click: () => {
                                editEventTimes(info, true);
                            }
                        }
                    ]
                });
            } else {
                editEventTimes(info);
            }
        },
        eventClick: (info) => {
            info.jsEvent.preventDefault();
            hideContextMenu();
            const event = info.event;
            const editable = event.extendedProps._editable;
            const ajax_url = event.extendedProps.ajaxurl;

            if (ajax_url && editable && !disable_edit) {
                const start = event.start;
                info.jsEvent.preventDefault();

                glpi_ajax_dialog({
                    url: `${ajax_url}&start=${start.toISOString()}`,
                    close: refresh,
                    dialogclass: 'modal-lg',
                    title: __('Edit an event'),
                    bs_focus: false
                });
            }
        },
        selectable: true,
        select: (info) => {
            if (!props.can_create) {
                calendar_api.unselect();
                return false;
            }
            hideContextMenu();

            const itemtype = info.resource?._resource?.extendedProps?.itemtype || '';
            const items_id = info.resource?._resource?.extendedProps?.items_id || 0;

            // prevent adding events on group users
            if (itemtype === 'Group_User') {
                calendar_api.unselect();
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
                    done: () => {
                        refresh();
                    }
                });
            }

            calendar_api.unselect();
        }
    }, props.fullcalendar_options);

    onMounted(() => {
        calendar_api = calendar.value.getApi();

        if (props.full_view) {
            date_picker_flatpickr = new flatpickr(date_picker.value, {
                onChange: function (selected_date) {
                    // convert to UTC to avoid timezone issues
                    const date = new Date(
                        Date.UTC(
                            selected_date[0].getFullYear(),
                            selected_date[0].getMonth(),
                            selected_date[0].getDate()
                        )
                    );
                    calendar_api.gotoDate(date);
                }
            });
        }

        window.addEventListener('click', hideContextMenu);
    });

    onUnmounted(() => {
        if (date_picker_flatpickr) {
            date_picker_flatpickr.destroy();
        }

        window.removeEventListener('click', hideContextMenu);
    });

    function hideContextMenu() {
        if (event_context_menu.value) {
            event_context_menu.value.classList.add('d-none');
        }
    }

    function getEvents(info, success_callback, failure_callback) {
        if (loading.value) {
            return [];
        }
        const view_name = current_view.value;
        const params = {
            start: info.startStr,
            end: info.endStr,
            action: 'get_events',
            view_name: view_name,
        };
        if (props.full_view) {
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

    watch(current_view, (new_view) => {
        if (props.full_view) {
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
        loading.value = false;
        refresh();
    }, { deep: true });

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
        document.querySelectorAll('.fc-timeline-header-row:nth-of-type(2) .fc-day').forEach((day_elem, index) => {
            day_elem.classList.add('end-of-day');
        });
        // Add EOD to hours
        document.querySelectorAll(`.fc-timeline-header-row:nth-of-type(3) .fc-day:nth-child(${number_of_slots}n)`).forEach((day_elem, index) => {
            day_elem.classList.add('end-of-day');
        });
        // Add EOD to content slots
        document.querySelectorAll(`.fc-timeline-slots .fc-timeline-slot:nth-child(${number_of_slots}n)`).forEach((day_elem, index) => {
            day_elem.classList.add('end-of-day');
        });
    }

    function refresh() {
        // Wait for component loading to stop and only then add the debounced event source to avoid multiple calls during loading
        if (!loading.value && calendar_api.getEventSources().length === 0) {
            const debounced_source = _.debounce(getEvents, 200);
            calendar_api.addEventSource(debounced_source);
        }
        if (typeof calendar_api.refetchResources === 'function') {
            calendar_api.refetchResources();
        }
        calendar_api.refetchEvents();
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
        }).catch(error => {
            revert_func();
        });
    }

    function getEventByDefId(defId) {
        return calendar_api.getEvents().find(event => event._def.defId === defId);
    }

    function onCloneEventClick() {
        const event_defid = event_context_menu.value.dataset.event_defid;
        const event = getEventByDefId(event_defid);
        if (!event) {
            return;
        }

        //FIXME Only a single "null" resource is returned. May be missing something related to this from the legacy code?
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

    function onDeleteEventClick() {
        const event_defid = event_context_menu.value.dataset.event_defid;
        const event = getEventByDefId(event_defid);
        if (!event) {
            return;
        }
        //TODO

        const doDelete = (instance = false) => {
            fetch(`${CFG_GLPI.root_doc}/ajax/planning.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({
                    action: 'delete_event',
                    itemtype: event.extendedProps.itemtype,
                    items_id: event.extendedProps.items_id,
                    day: event.start.toISOString().substring(0, 10),
                    instance: instance ? 1 : 0,
                }),
            }).then(response => {
                if (response.ok) {
                    refresh();
                }
            });
        }

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
</script>

<template>
    <BaseFullCalendar ref="calendar" class="flex-grow-1" :calendar_options="calendar_options" v-model:currentView="current_view" @currentViewDataChanged="current_view_data = $event">
        <template #datesRender="">
            <button>test</button>
        </template>
        <template #eventContent="event_info">
            <PlanningEvent :event_info="event_info" :context_menu="event_context_menu"/>
        </template>
    </BaseFullCalendar>
    <Teleport v-if="full_view" defer to=".fc-toolbar-title">
        <button v-show="current_view !== 'listFull'" ref="date_picker" class="btn btn-sm btn-ghost-secondary"
                :title="_n('Calendar', 'Calendars', 1)"
                :aria-label="_n('Calendar', 'Calendars', 1)">
            <i class="ti ti-calendar"></i>
        </button>
    </Teleport>
    <Teleport v-if="full_view" defer to=".fc-toolbar-title">
        <button class="btn btn-sm btn-ghost-secondary" :title="__('Refresh')" :aria-label="__('Refresh')" @click="refresh">
            <i class="ti ti-refresh"></i>
        </button>
    </Teleport>
    <Teleport v-if="full_view && (can_create || can_delete)" to="body">
        <div ref="event_context_menu" class="d-none planning-context-menu position-fixed card">
            <ul class="list-group list-group-flush list-group-hoverable">
                <li v-if="can_create" class="list-group-item p-0">
                    <button class="btn btn-ghost-secondary p-2 w-100 border-radius-0" @click="onCloneEventClick">
                        <i class="ti ti-copy"></i>
                        {{ __('Clone') }}
                    </button>
                </li>
                <li v-if="can_delete" class="list-group-item p-0">
                    <button class="btn btn-ghost-secondary p-2 w-100 border-radius-0" @click="onDeleteEventClick">
                        <i class="ti ti-trash"></i>
                        {{ __('Delete') }}
                    </button>
                </li>
            </ul>
        </div>
    </Teleport>
</template>

<style scoped>
    :global(.planning_on_central .fc-scroller) {
        height: auto !important;
        max-height: 400px !important;
    }

    :deep(.fc-header-toolbar .fc-toolbar-chunk:first-child) {
        visibility: v-bind(dateNavVisibility);
    }

    :deep(.end-of-day) {
        border-right: 1px solid var(--tblr-body-color) !important;
    }

    :deep(.event_past .event_type) {
        opacity: 0.5;
    }

    @media screen and (max-width: 767px) {
        :deep(.fc-toolbar.fc-header-toolbar) {
          flex-direction:column;
       }

    :deep(.fc-toolbar-chunk) {
          display: table-row;
          text-align:center;
          padding:5px 0;
       }
    }

    .planning-context-menu {
        z-index:20000;
    }
</style>
