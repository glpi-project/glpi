<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {computed, onMounted, onUnmounted, useTemplateRef} from "vue";

    const props = defineProps({
        view_type: {
            type: String,
            required: true,
        },
        event_info: {
            type: Object,
            required: true,
        },
        context_menu: {
            type: Object,
            required: false,
        },
    });

    const event_content = useTemplateRef('event_content');
    const show_content = computed(() => {
        return props.view_type !== 'dayGridMonth' && !props.view_type.includes('list') && event.rendering !== 'background' && !event.allDay;
    });
    const event = props.event_info.event;
    const type_color = event.extendedProps.typeColor;
    const time_hour = props.event_info.timeText.split(':')[0].padStart(2, '0');
    let popover = null;

    onMounted(() => {
        if (!event_content.value) {
            return;
        }
        event_content.value.closest('.fc-event').addEventListener('contextmenu', handleContextMenu);

        popover = new bootstrap.Popover(event_content.value.closest('.fc-event'), {
            trigger: 'hover focus',
            html: true,
            content: () => {
                return event.extendedProps.tooltip;
            }
        });

        if (event.extendedProps.icon) {
            const icon_alt = event.extendedProps?.icon_alt || '';
            event_content.value.querySelector('.fc-title .fc-list-item-title').append(`&nbsp;<i class='${_.escape(event.extendedProps.icon)}' title='${_.escape(icon_alt)}'></i>`);
        }
    });

    onUnmounted(() => {
        if (!event_content.value) {
            return;
        }
        event_content.value.closest('.fc-event').removeEventListener('contextmenu', handleContextMenu);
        if (popover) {
            popover.dispose();
        }
    });

    function handleContextMenu(e) {
        if (!props.context_menu) {
            return;
        }
        e.preventDefault();
        props.context_menu.style.position = 'fixed';
        props.context_menu.classList.remove('d-none');
        props.context_menu.style.left = `${e.clientX}px`;
        props.context_menu.style.top = `${e.clientY}px`;
        props.context_menu.dataset.event_defid = event._def.defId;
    }
</script>

<template>
    <div ref="event_content" class="fc-content px-1 overflow-hidden fw-bold">
        <span class="fc-time me-1 text-nowrap">{{ time_hour }}</span>
        <span class="fc-title">{{ event_info.event.title }}</span>
    </div>
    <span class="event_type"></span>
    <div v-if="show_content" class="content" v-html="event.extendedProps.content"></div>
</template>

<style scoped>
    .fc-content {
        color: v-bind(event_info.textColor);
        margin-inline-end: 8px;
    }

    .fc-title {
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .content {
        font-weight: normal;
        padding: 0 7px 0 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        margin: 0;
        display: block;

        .event-description {
            border-top: 1px solid rgba(0, 0, 0, 0.2);
            margin-top: 2px;
            padding-top: 2px;

            p {
                margin: 0;
            }
        }
    }

    .event_type {
        background-color: v-bind(type_color);
        position: absolute;
        width: 7px;
        bottom: 0;
        top: 0;
        right: 0;
        margin-block: -1px;
    }
</style>
