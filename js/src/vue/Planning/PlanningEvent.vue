<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {computed, inject, onMounted, onUnmounted, useTemplateRef} from "vue";

    const props = defineProps({
        event_info: {
            type: Object,
            required: true,
        },
    });

    const { current_view, event_context_menu_el: context_menu } = inject('scheduler');
    const event_content = useTemplateRef('event_content');
    /** Non-ref reference to the FC event element as refs get cleaned before unmounting but we need to reference it */
    let fc_event_el = null;
    const event = props.event_info.event;
    const show_content = computed(() => {
        return current_view.value !== 'dayGridMonth' && !current_view.value.includes('list') && event._def.ui.display !== 'background' && !event.allDay;
    });
    const type_color = event.extendedProps.typeColor;
    const time_hour = props.event_info.timeText.split(':')[0].padStart(2, '0');
    const icon_class = event.extendedProps?.icon || '';
    const icon_alt = event.extendedProps?.icon_alt || '';
    let popover = null;

    onMounted(() => {
        event_content.value.closest('.fc-event').addEventListener('contextmenu', handleContextMenu);

        popover = new bootstrap.Popover(event_content.value.closest('.fc-event'), {
            trigger: 'hover focus',
            html: true,
            content: event.extendedProps.tooltip
        });

        fc_event_el = event_content.value.closest('.fc-event');
    });

    onUnmounted(() => {
        if (fc_event_el) {
            fc_event_el.removeEventListener('contextmenu', handleContextMenu);
        }
        if (popover) {
            popover.dispose();
        }
    });

    function handleContextMenu(e) {
        if (!context_menu.value) {
            return;
        }
        e.preventDefault();
        context_menu.value.classList.remove('d-none');
        context_menu.value.style.left = `${e.clientX}px`;
        context_menu.value.style.top = `${e.clientY}px`;
        context_menu.value.dataset.event_defid = event._def.defId;
    }
</script>

<template>
    <div ref="event_content" class="fc-content px-1 overflow-hidden fw-bold">
        <span class="fc-time me-1 text-nowrap">{{ time_hour }}</span>
        <span class="fc-title">
            {{ event_info.event.title }}
            <i v-if="icon_class" :class="icon_class" :title="icon_alt" :aria-label="icon_alt" class="ms-1"></i>
        </span>
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
