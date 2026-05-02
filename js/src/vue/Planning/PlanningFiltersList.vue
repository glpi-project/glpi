<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import PlanningFilter from "./PlanningFilter.vue";
    import {ref} from "vue";

    const props = defineProps({
        filters: {
            type: Object,
            default: () => ({}),
        },
        can_delete: {
            type: Boolean,
            default: false,
        },
    });

    const emits = defineEmits(['filtersUpdated']);

    const filters = ref(props.filters);

    function deleteFilter(filter_key, event_type) {
        if (!props.can_delete) {
            return;
        }
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
                delete(filters.value[filter_key]);
                emits('filtersUpdated');
            }
        });
    }

    function toggleFilter(filter_key, event_type, displayed, parent_filter_key = '') {
        fetch(`${CFG_GLPI.root_doc}/ajax/planning.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'toggle_filter',
                name: filter_key,
                type: event_type,
                parent: parent_filter_key,
                display: displayed,
            }),
        }).then(response => {
            const filter = filters.value[filter_key];
            if (response.ok) {
                emits('filtersUpdated');
                if (filter.child_filters && Object.keys(filter.child_filters).length > 0) {
                    for (const child_filter_key in filter.child_filters) {
                        if (filter.child_filters.hasOwnProperty(child_filter_key)) {
                            const child_filter_data = filter.child_filters[child_filter_key];
                            child_filter_data.filter_data.display = displayed;
                        }
                    }
                }
            } else {
                filter.filter_data.display = !displayed;
            }
        });
    }
</script>

<template>
    <ul class="border-0 list-unstyled m-0 p-0">
        <PlanningFilter v-for="(filter_data, filter_key) in filters" :key="filter_key"
                        :filter_key="filter_key" :filter_data="filter_data"
                        @deleteFilter="deleteFilter" @toggleFilter="toggleFilter"/>
    </ul>
</template>

<style scoped>

</style>
