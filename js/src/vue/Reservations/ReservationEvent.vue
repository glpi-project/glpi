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
    });

    const event_content = useTemplateRef('event_content');
    const event = props.event_info.event;
    const icon_class = event.extendedProps?.icon || '';
    const icon_alt = event.extendedProps?.icon_alt || '';
    let popover = null;

    onMounted(() => {
        if (!event_content.value) {
            return;
        }

        popover = new bootstrap.Popover(event_content.value.closest('.fc-event'), {
            trigger: 'hover focus',
            html: true,
            content: event.extendedProps.comment
        });
    });

    onUnmounted(() => {
        if (!event_content.value) {
            return;
        }
        if (popover) {
            popover.dispose();
        }
    });
</script>

<template>
    <div ref="event_content" class="fc-content px-1 overflow-hidden fw-bold">
        <span class="fc-time me-1 text-nowrap">{{ event_info.timeText.split(':')[0].padStart(2, 0) }}</span>
        <span class="fc-title">
            {{ event_info.event.title }}
            <i v-if="icon_class" :class="icon_class" :title="icon_alt" :aria-label="icon_alt" class="ms-1 align-text-bottom"></i>
        </span>
    </div>
</template>

<style scoped>
    .fc-content {
        color: initial;
    }
</style>
