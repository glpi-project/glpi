<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {onMounted, onUnmounted, useTemplateRef} from "vue";

    const props = defineProps({
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
    const event = props.event_info.event;
    const type_color = event.extendedProps.typeColor;
    const time_hour = props.event_info.timeText.split(':')[0].padStart(2, '0');

    onMounted(() => {
        if (!event_content.value) {
            return;
        }
        event_content.value.closest('.fc-event').addEventListener('contextmenu', handleContextMenu);
    });

    onUnmounted(() => {
        if (!event_content.value) {
            return;
        }
        event_content.value.closest('.fc-event').removeEventListener('contextmenu', handleContextMenu);
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
        <span class="fc-time me-1">{{ time_hour }}</span>
        <span class="fc-title">{{ event_info.event.title }}</span>
    </div>
    <span class="event_type"></span>
</template>

<style scoped>
    .fc-content {
        color: v-bind(event_info.textColor);
        margin-inline-end: 8px;
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
