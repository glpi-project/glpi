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
                <ul v-show="!filters_collapsed" class="filters">
                    <PlanningFilter v-for="(filter_data, filter_key) in filters.filters" :key="filter_key"
                                    :filter_key="filter_key" :filter_data="filter_data"/>
                </ul>
            </div>
            <div v-show="!filters_collapsed" v-if="Object.keys(planning_config).includes('plannings')">
                <h3 class="fw-normal">
                    {{ __('Plannings') }}
                    <button class="btn btn-sm btn-icon btn-ghost-secondary planning_link planning_add_filter"
                            @click="showAddCalendar"
                            :title="__('Add a calendar')" :aria-label="__('Add a calendar')">
                        <i class="ti ti-circle-plus"></i>
                    </button>
                </h3>
                <ul class="filters">
                    <PlanningFilter v-for="(filter_data, filter_key) in filters.plannings" :key="filter_key"
                                    :filter_key="filter_key" :filter_data="filter_data" @deleteFilter="deleteFilter"/>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped>
    #planning_filter {
        h3 {
            background: var(--glpi-form-header-bg);
            color: var(--glpi-form-header-fg);
            margin: 2px 0 0 0;
            padding: .5em .5em .5em .7em;
            line-height: 1.3;
            font-size: 13px;
        }

        :deep(ul.filters) {
            border: 0;
            list-style: none;
            margin: 0;
            padding: 0;

            > li {
                padding: 5px 0 5px 5px;
                display: flex;
                flex-wrap: wrap;
                align-items: center;

                label {
                    padding-left: 5px;
                    line-height: 16px;
                    width: 185px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: inline-block;
                    white-space: nowrap;

                    > i {
                        color: var(--tblr-warning);
                        float: right;
                    }
                }

                .filter-icon {
                    float: right;
                    padding: 0;
                    border: none;
                }

                .sp-replacer {
                    float: right;
                    padding: 0;
                    border: none;

                    .sp-preview {
                        margin-right: 0;
                        border: none;
                    }

                    .sp-dd {
                        display: none;
                    }
                }

                &.group_users {
                    .toggle {
                        width: 14px;
                        height: 14px;
                        margin: 0 4px 2px 0;
                        vertical-align: middle;

                        &::before {
                            font: var(--fa-font-solid);
                            content: "\f0fe";
                        }
                    }

                    &.expanded .toggle {
                        &::before {
                            font: var(--fa-font-solid);
                            content: "\f146";
                        }
                    }

                    ul.group_listofusers {
                        border-left: 1px dashed #D4D4D4;
                        margin: 6px 0 0 6px;
                        padding: 0;
                        display: none;
                        width: 100%;
                    }

                    &.expanded ul.group_listofusers {
                        display: block;

                        > li label {
                            width: 162px;
                        }
                    }
                }

                .color_input {
                    float: right;
                    margin-right: 2px;

                    input {
                        border: 0 !important;
                        background-color: transparent !important;
                    }
                }
            }
        }

        .planning_link {
            text-align: center;
            display: block;
        }

        .planning_add_filter {
            float: right;
            margin-right: 3px;
        }
    }
</style>
