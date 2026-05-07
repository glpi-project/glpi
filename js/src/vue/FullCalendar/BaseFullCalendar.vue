<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */
    import FullCalendar from "@fullcalendar/vue3";
    import {useTemplateRef, watch, ref} from 'vue';
    import allLocales from "@fullcalendar/core/locales-all";

    const props = defineProps({
        calendar_options: {
            type: Object,
            required: true,
        },
    });

    defineExpose({
        getApi: () => calendar.value.getApi(),
    });

    const emit = defineEmits(['currentViewDataChanged']);

    const calendar = useTemplateRef('calendar');

    const document_lang = document.documentElement.lang;
    let matching_locales = allLocales.filter(locale => locale.code === document_lang);
    if (matching_locales.length === 0) {
        // try to match only the language part of the locale
        const document_lang_short = document_lang.split('-')[0];
        matching_locales = allLocales.filter(locale => locale.code.startsWith(document_lang_short));
    }

    const default_fullcalendar_options = {
        height: Math.max(window.innerHeight - 272, 300),
        timeZone: 'UTC',
        themeSystem: 'bootstrap5',
        weekNumbers: true,
        editable: true,
        nowIndicator: true,
        schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
        resourceAreaWidth: '15%',
        slotMinTime: CFG_GLPI.planning_begin,
        slotMaxTime: CFG_GLPI.planning_end,
        eventDisplay: 'block',
        locale: matching_locales.length > 0 ? matching_locales[0] : 'en',
    };
    const calendar_options = {...default_fullcalendar_options, ...props.calendar_options};
    const current_view = defineModel('currentView', 'timeGridWeek');
    const current_view_data = ref(null);

    calendar_options.datesSet = (info) => {
        if (props.calendar_options.datesSet) {
            props.calendar_options.datesSet(info);
        }
        current_view.value = info.view.type;
        current_view_data.value = info.view.getCurrentData();
    };

    watch(current_view, (new_view, old_view) => {
        calendar.value.getApi().changeView(new_view);
    });

    watch(current_view_data, (new_data) => {
        if (!new_data) {
            return;
        }
        emit('currentViewDataChanged', new_data);
    }, { immediate: true });
</script>

<template>
    <FullCalendar ref="calendar" :options="calendar_options">
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps || {}" />
        </template>
    </FullCalendar>
</template>

<style scoped>
   :deep(.fc-button-primary) {
      background-color: var(--tblr-secondary) !important;
      border-color: var(--tblr-secondary) !important;
      color: var(--tblr-secondary-fg, #fff) !important;

      &:not(:disabled):active,
      &:not(:disabled).fc-button-active {
         background-color: var(--tblr-primary) !important;
         border-color: var(--tblr-primary) !important;
         color: var(--tblr-primary-fg, #fff) !important;
      }
   }

   :deep(.fc-toolbar-title) {
       font-size: 1.25em;
   }

   :deep(.fc-toolbar-title button) {
       vertical-align: baseline;
       margin-inline-start: 0.25em;
   }

   :deep(.fc-toolbar-title .ti) {
       font-size: 1.5em;
   }
</style>
