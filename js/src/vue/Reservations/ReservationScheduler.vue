<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import dayGridPlugin from "@fullcalendar/daygrid";
    import interactionPlugin from "@fullcalendar/interaction";
    import listPlugin from "@fullcalendar/list";
    import timeGridPlugin from "@fullcalendar/timegrid";
    import resourceTimelinePlugin from "@fullcalendar/resource-timeline";
    import BaseFullCalendar from "../FullCalendar/BaseFullCalendar.vue";
    import {onMounted, ref, useTemplateRef, watch} from "vue";
    import ReservationEvent from "./ReservationEvent.vue";
    import useScheduler from "../FullCalendar/useScheduler.js";

    const props = defineProps({
        id: {
            type: [String, Number],
            required: true,
        },
        can_reserve: {
            type: Boolean,
            default: false,
        },
        now: {
            type: String,
            default: null,
        },
        default_date: {
            type: [String, Date],
            default: new Date(),
        },
        current_view: {
            type: String
        },
    });

    const { getListFullView, getResourceWeekView } = useScheduler();
    const id = Number(props.id);
    const default_date = new Date(props.default_date);
    const calendar = useTemplateRef('calendar');
    let calendar_api = null;

    const calendar_options = {
        initialView: localStorage.getItem("fcDefaultViewReservation") !== null
            ? localStorage.getItem("fcDefaultViewReservation")
            : (props.current_view || 'dayGridMonth'),
        now: props.now,
        plugins: [
            dayGridPlugin,
            interactionPlugin,
            listPlugin,
            timeGridPlugin,
            resourceTimelinePlugin,
        ],
        initialDate: props.default_date,
        views: {
            listFull: getListFullView(id > 0 ? 10 : 1),
            resourceWeek: getResourceWeekView(),
        },
        events: {
            url:  `${CFG_GLPI.root_doc}/ajax/reservations.php`,
            extraParams: {
                'action': 'get_events',
                'reservationitems_id': id,
            },
            failure: (error) => {
                console.error('there was an error while fetching events!', error);
            }
        },
        resources: {
            url:  `${CFG_GLPI.root_doc}/ajax/reservations.php`,
            method: 'GET',
            extraParams: {
                'action': 'get_resources',
            }
        },
        dayCellClassNames: (arg) => {
            if (datesAreSameDay(arg.date, default_date)) {
                return ['defaultDate'];
            }
            return [];
        },
        selectable: props.can_reserve,
        select: (info) => {
            if (props.can_reserve) {
                glpi_ajax_dialog({
                    title: __("Add reservation"),
                    url: `${CFG_GLPI.root_doc}/ajax/reservations.php`,
                    params: {
                        action: 'add_edit_reservation_fromselect',
                        id: 0,
                        item: [id],
                        begin: info.start.toISOString(),
                        end: info.end.toISOString(),
                    },
                    dialogclass: 'modal-xl',
                });
            }
            calendar_api.unselect();
        },
        eventResize: (info) => {
            editEvent(info);
        },
        eventDrop: (info) => {
            editEvent(info);
        },
        eventClick: (info) => {
            const event = info.event;
            const ajax_url = event.extendedProps.ajaxurl;
            const editable = event.extendedProps._editable;

            info.jsEvent.preventDefault();

            if (!editable || !ajax_url) {
                return;
            }

            glpi_ajax_dialog({
                title: __("Edit reservation"),
                url: ajax_url,
                dialogclass: 'modal-xl',
            });
        }
    };

    const current_view = ref(calendar_options.initialView);

    onMounted(() => {
        calendar_api = calendar.value.getApi();
    });

    function editEvent(info) {
        const event = info.event;
        const revert_fn = info.revert;
        const start = event.start;
        const end = event.end;

        fetch(`${CFG_GLPI.root_doc}/ajax/reservations.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({
                action: 'update_event',
                id: event.id,
                begin: start.toISOString(),
                end: end.toISOString(),
            }),
        }).then(response => {
            if (!response.ok) {
                revert_fn();
            }
        }).catch(() => {
            revert_fn();
        });
    }

    function datesAreSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
            date1.getMonth() === date2.getMonth() &&
            date1.getDate() === date2.getDate();
    }

    watch(current_view, (new_view) => {
        localStorage.setItem("fcDefaultViewReservation", new_view);
    });
</script>

<template>
    <BaseFullCalendar ref="calendar" class="flex-grow-1" :calendar_options="calendar_options" v-model:currentView="current_view">
        <template #eventContent="event_info">
            <ReservationEvent :view_type="current_view" :event_info="event_info"/>
        </template>
    </BaseFullCalendar>
</template>

<style scoped>
    :deep(.defaultDate) {
        background: #e3fce8;
    }
</style>
