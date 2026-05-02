<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import PlanningFilters from "./PlanningFilters.vue";
    import PlanningScheduler from "./PlanningScheduler.vue";

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
            type: Object,
            default: () => ({}),
        },
        fullcalendar_options: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        planning_config: {
            type: Object,
        },
    });
</script>

<template>
    <div class="d-flex flex-wrap flex-sm-nowrap gap-2">
        <PlanningFilters v-if="full_view" :planning_config="planning_config" :filters="filters"></PlanningFilters>
        <PlanningScheduler
            :can_create="can_create"
            :can_delete="can_delete"
            :full_view="full_view"
            :now="now"
            :header="header"
            :height="height"
            :resources="resources"
            :fullcalendar_options="fullcalendar_options"
        ></PlanningScheduler>
    </div>
</template>

<style scoped>

</style>
