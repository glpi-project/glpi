<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import PlanningFilter from "./PlanningFilter.vue";
    import PlanningFiltersList from "./PlanningFiltersList.vue";
    import {ref} from "vue";

    const props = defineProps({
        filters: {
            type: Object,
            default: () => ({}),
        },
        planning_config: {
            type: Object,
            required: true,
        }
    });

    const emits = defineEmits(['filtersUpdated']);

    const filters = ref(props.filters);
    const filters_collapsed = ref(false);

    function showAddCalendar() {
        const url = `${CFG_GLPI.root_doc}/ajax/planning.php?action=add_planning_form`;
        glpi_ajax_dialog({
            url: url,
            title: __('Add a calendar'),
        });
    }

    function toggleFilters() {
        filters_collapsed.value = !filters_collapsed.value;
    }

    function deleteFilter(filter_key, event_type) {
        fetch(`${CFG_GLPI.root_doc}/ajax/planning.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'delete_filter',
                filter: filter_key,
                type: event_type,
            }),
        }).then(response => {
            if (response.ok) {
                // Remove the deleted filter from the filters object
                delete(filters.value['plannings'][filter_key]);
                emits('filtersUpdated');
            }
        });
    }
</script>

<template>
    <div id="planning_filter" :style="filters_collapsed ? '' : 'min-width: 300px;'">
        <div id="planning_filter_content">
            <div v-if="Object.keys(planning_config).includes('filters')">
                <h3 class="d-flex justify-content-between fw-normal" :style="filters_collapsed ? 'background: none' : ''">
                    <span v-show="!filters_collapsed">{{ __('Filters') }}</span>
                    <button class="btn btn-sm btn-icon btn-ghost-secondary p-1" @click="toggleFilters"
                            :title="__('Toggle filters')">
                        <i :class="filters_collapsed ? 'ti ti-caret-right-filled' : 'ti ti-caret-left-filled'" role="presentation"></i>
                    </button>
                </h3>
                <PlanningFiltersList v-show="!filters_collapsed" :filters="filters.filters" :can_delete="false" @filtersUpdated="emits('filtersUpdated')"/>
            </div>
            <div v-show="!filters_collapsed" v-if="Object.keys(planning_config).includes('plannings')">
                <h3 class="d-flex justify-content-between fw-normal">
                    {{ __('Plannings') }}
                    <button class="btn btn-sm btn-icon btn-ghost-secondary me-1"
                            @click="showAddCalendar"
                            :title="__('Add a calendar')" :aria-label="__('Add a calendar')">
                        <i class="ti ti-circle-plus"></i>
                    </button>
                </h3>
                <PlanningFiltersList v-show="!filters_collapsed" :filters="filters.plannings" :can_delete="true" @filtersUpdated="emits('filtersUpdated')"/>
            </div>
        </div>
    </div>
</template>

<style scoped>
    h3 {
        background: var(--glpi-form-header-bg);
        color: var(--glpi-form-header-fg);
        margin: 2px 0 0 0;
        padding: .5em .5em .5em .7em;
        line-height: 1.3;
        font-size: 13px;
    }
</style>
