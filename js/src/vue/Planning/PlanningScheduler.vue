<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {computed, provide, ref, useTemplateRef} from 'vue';
    import BaseFullCalendar from '../FullCalendar/BaseFullCalendar.vue';
    import dayGridPlugin from '@fullcalendar/daygrid';
    import interactionPlugin from '@fullcalendar/interaction';
    import listPlugin from '@fullcalendar/list';
    import timeGridPlugin from '@fullcalendar/timegrid';
    import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
    import rrulePlugin from '@fullcalendar/rrule';
    import PlanningEvent from "./PlanningEvent.vue";
    import usePlanningScheduler from "./usePlanningScheduler.js";

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

    const all_days = [0, 1, 2, 3, 4, 5, 6];
    const hidden_days = all_days.filter(day => !CFG_GLPI.planning_work_days.some(n => n == day));

    const event_context_menu = useTemplateRef('event_context_menu');
    const current_view = ref(props.fullcalendar_options.initialView || 'timeGridWeek');
    const current_view_data = ref(null);
    const list_full_year_range = props.full_view ? 5 : 1; // +/- number of years to display in list full view
    const all_resources = ref(!Array.isArray(props.resources) ? Object.values(props.resources) : props.resources);
    const visible_res = computed(() => {
        return Object.keys(all_resources.value).filter(index => {
            return all_resources.value[index].is_visible
        });
    });
    const dateNavVisibility = computed(() => {
        return current_view.value === 'listFull' ? 'hidden' : 'visible';
    });
    const scheduler = usePlanningScheduler(
        useTemplateRef('calendar'),
        current_view,
        props.full_view,
        useTemplateRef('date_picker'),
        event_context_menu,
        props.now
    );
    provide('scheduler', scheduler);
    const {
        getListFullView, getResourceWeekView, defaultHeaderToolbar, refresh, clearSelection,
        cloneEvent, deleteEvent, onEventResize, onEventDrop, createEventFromSelect, hideContextMenu
    } = scheduler;

    defineExpose({
        refresh: refresh,
    });

    const calendar_options = Object.assign({
        plugins: [
            dayGridPlugin,
            interactionPlugin,
            listPlugin,
            timeGridPlugin,
            resourceTimelinePlugin,
            rrulePlugin,
        ],
        height: props.height,
        weekNumbers: props.full_view,
        dayMaxEvents: true,
        now: props.now,// as we set the calendar as UTC, we need to reprecise the current datetime
        headerToolbar: props.full_view ? (props.header ?? defaultHeaderToolbar) : false,
        hiddenDays: hidden_days,
        initialView: current_view.value,
        views: {
            listFull: getListFullView(list_full_year_range),
            resourceWeek: getResourceWeekView(),
        },
        resources: (_fetchInfo, successCallback) => {
            // Filter resources by whether their id is in visible_res.
            successCallback(all_resources.value.filter((_elem, index) => {
                return visible_res.value.indexOf(index.toString()) !== -1;
            }));
        },
        eventResize: onEventResize,
        eventDrop: onEventDrop,
        eventClick: (info) => {
            info.jsEvent.preventDefault();
            hideContextMenu();
            const event = info.event;
            const ajax_url = event.extendedProps.ajaxurl;

            if (ajax_url && event.extendedProps._editable) {
                const start = event.start;

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
                clearSelection();
                return false;
            }
            hideContextMenu();
            createEventFromSelect(info);
        }
    }, props.fullcalendar_options);

    function getResourceIcon(resource) {
        let icon = '';
        switch (resource.extendedProps.itemtype.toLowerCase()) {
            case "group":
            case "group_user":
                icon = "users";
                break;
            case "user":
                icon = "user";
        }
        return icon;
    }
</script>

<template>
    <BaseFullCalendar ref="calendar" class="flex-grow-1" :calendar_options="calendar_options" v-model:currentView="current_view" @currentViewDataChanged="current_view_data = $event">
        <template #eventContent="event_info">
            <PlanningEvent :event_info="event_info"/>
        </template>
        <template #resourceLabelContent="{ resource }">
            <span>
                <i :class="`ti ti-${getResourceIcon(resource)}`" role="presentation"></i>
                {{ resource.title }}
            </span>
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
                    <button class="btn btn-ghost-secondary p-2 w-100 border-radius-0" @click="() => cloneEvent(event_context_menu.dataset.event_defid)">
                        <i class="ti ti-copy"></i>
                        {{ __('Clone') }}
                    </button>
                </li>
                <li v-if="can_delete" class="list-group-item p-0">
                    <button class="btn btn-ghost-secondary p-2 w-100 border-radius-0" @click="() => deleteEvent(event_context_menu.dataset.event_defid)">
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

    :deep(.fc-event-past .event_type) {
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

    :deep(.fc-timegrid-event) {
        overflow: hidden;
    }

    :deep(.fc-timegrid-slot) {
        height: 2.5em;
    }

    :deep(.fc-timeline .fc-event .content) {
        max-height: 25px;
    }

    .planning-context-menu {
        z-index:20000;
    }
</style>
