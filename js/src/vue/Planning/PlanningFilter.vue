<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {ref, useTemplateRef} from "vue";

    const props = defineProps({
        filter_key: {
            type: [String, Number],
            required: true,
        },
        filter_data: {
            type: Object,
            required: true,
        },
        parent_filter_key: {
            type: [String, Number],
            required: false,
        },
    });

    defineEmits(['deleteFilter', 'toggleFilter']);

    const event_type = props.filter_data.filter_data.type;
    const event_name = props.filter_key;
    const expanded = ref(props.filter_data.expanded === true || props.filter_data.expanded === 'expanded');
    const label_title = props.filter_data.title;
    const url_not_allowed_label = __('URL "%s" is not allowed by your administrator.')
        .replace('%s', props.filter_data.filter_data.url ?? '')
        .replace(/&quot;/g, '"');

    //TODO These params are not passed to Vue
    const entities_id = props.filter_data.entities_id ?? null;
    const is_recursive = props.filter_data.is_recursive ?? null;
    const token = props.filter_data.token ?? null;

    const ical_export_url = `${CFG_GLPI.root_doc}/front/planning.php?genical=1&uID=${props.filter_data.uID}&gID=${props.filter_data.gID}&entities_id=${entities_id}&is_recursive=${is_recursive}&token=${token}`;
    const csv_export_url = `${CFG_GLPI.root_doc}/front/planningcsv.php?uID=${props.filter_data.uID}&gID=${props.filter_data.gID}`;

    function copyCalDAVUrl() {
        copyTextToClipboard(props.filter_data.caldav_url);
        alert(__('CalDAV URL has been copied to clipboard'));
    }

    function exportFromURL(url) {
        window.open(url, '_blank');
    }
</script>

<template>
    <li :class="`${event_type} ${expanded ? 'expanded' : ''}`" class="p-1 pe-0 d-flex flex-wrap align-items-center">
        <input type="checkbox" :id="filter_key" name="filters[]" class="form-check-input" :value="filter_key"
               :checked="filter_data.filter_data.display" @change="$emit('toggleFilter', props.filter_key, event_type, $event.target.checked, parent_filter_key)"/>
        <i v-if="event_type !== 'event_filter'" :class="`ms-1 pb-1 actor_icon ti ti-${event_type.split('_')[0] === 'group' ? 'users' : 'user'}`"></i>
        <label :for="filter_key" class="ps-1 overflow-hidden d-inline-block text-nowrap">
            {{ label_title }}
            <i v-if="event_type === 'external' && !filter_data.filter_data.url_safe"
               class="ti ti-alert-triangle text-warning float-end" :title="url_not_allowed_label" :aria-label="url_not_allowed_label">
            </i>
        </label>
        <div class="ms-auto d-flex align-items-center">
            <span v-if="event_type !== 'group_users' && filter_key !== 'OnlyBgEvents' && filter_key !== 'StateDone'">
                <input type="color" class="border-0" :name="`${filter_key}_color`"
                       :aria-label="__('%s color').replace('%s', label_title)" :value="filter_data.color"/>
            </span>
            <button v-if="event_type === 'group_users'" class="btn btn-sm btn-icon btn-ghost-secondary p-1"
                    :title="__('Toggle filters')" @click="filter_data.expanded = !filter_data.expanded">
                <i :class="filter_data.expanded ? 'ti ti-caret-up-filled' : 'ti ti-caret-down-filled'" role="presentation"></i>
            </button>
            <div v-if="event_type !== 'event_filter'" class="filter_option dropstart d-inline-block position-relative m-1" data-bs-toggle="dropdown">
                <i class="ti ti-dots cursor-pointer"></i>
                <ul class="dropdown-menu p-0">
                    <li v-if="filter_data.params.show_delete" class="dropdown-item p-0">
                        <button class="btn btn-ghost-secondary btn-sm p-2 w-100 border-radius-0 justify-content-start"
                                @click="$emit('deleteFilter', props.filter_key, event_type)">
                            {{ __('Delete') }}
                        </button>
                    </li>
                    <li v-if="filter_data.show_export_buttons" class="dropdown-item p-0">
                        <button class="btn btn-ghost-secondary btn-sm p-2 w-100 border-radius-0 justify-content-start"
                                @click="exportFromURL(ical_export_url)">
                            {{ _x('button', 'Export') }} - {{ __('Ical') }}
                        </button>
                    </li>
                    <li v-if="filter_data.show_export_buttons" class="dropdown-item p-0">
                        <button class="btn btn-ghost-secondary btn-sm p-2 w-100 border-radius-0 justify-content-start"
                                @click="exportFromURL(`${filter_data.webcal_base_url}${ical_export_url}`)">
                            {{ _x('button', 'Export') }} - {{ __('Webcal') }}
                        </button>
                    </li>
                    <li v-if="filter_data.show_export_buttons" class="dropdown-item p-0">
                        <button class="btn btn-ghost-secondary btn-sm p-2 w-100 border-radius-0 justify-content-start"
                                @click="exportFromURL(csv_export_url)">
                            {{ _x('button', 'Export') }} - {{ __('CSV') }}
                        </button>
                    </li>
                    <li v-if="filter_data.show_export_buttons && filter_data.caldav_url" class="dropdown-item p-0">
                        <button class="btn btn-ghost-secondary btn-sm p-2 w-100 border-radius-0 justify-content-start" @click.prevent="copyCalDAVUrl">
                            {{ __('Copy CalDAV URL to clipboard') }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <ul v-if="filter_data.caldav_url && event_type === 'group_users'"
            class="p-0 mt-1 ms-1 w-100" :class="filter_data.expanded ? '' : 'd-none'">
            <PlanningFilter v-for="(user_filter_data, user_filter_key) in filter_data.child_filters" :key="user_filter_key"
                            :filter_key="user_filter_key" :filter_data="user_filter_data" :parent_filter_key="filter_key"
                            @toggleFilter="(...args) => $emit('toggleFilter', ...args)"/>
        </ul>
    </li>
</template>

<style scoped>
    .filter_option {
        width: 12px;
        height: 12px;
        left: 0;
    }

    label {
        line-height: 16px;
        text-overflow: ellipsis;
    }

    ul:not(.dropdown-menu) {
        border-left: 1px dashed #D4D4D4;
    }
</style>
