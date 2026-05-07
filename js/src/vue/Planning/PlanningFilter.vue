<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {ref, useTemplateRef} from "vue";

    const props = defineProps({
        filter_key: {
            type: String,
            required: true,
        },
        filter_data: {
            type: Object,
            required: true,
        },
    });

    defineEmits(['deleteFilter']);

    const options_list = useTemplateRef('options_list');

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
    <li :class="`${event_type} ${expanded ? 'expanded' : ''}`">
        <input type="checkbox" :id="filter_key" name="filters[]" class="form-check-input" :value="filter_key" :checked="filter_data.filter_data.display" />
        <i v-if="event_type !== 'event_filter'" :class="`ms-1 pb-1 actor_icon ti ti-${event_type.split('_')[0] === 'group' ? 'users' : 'user'}`"></i>
        <label :for="filter_key">
            {{ label_title }}
            <i v-if="event_type === 'external' && !filter_data.filter_data.url_safe"
               class="ti ti-alert-triangle" :title="url_not_allowed_label" :aria-label="url_not_allowed_label">
            </i>
        </label>
        <div class="ms-auto d-flex align-items-center">
            <span v-if="event_type !== 'group_users' && filter_key !== 'OnlyBgEvents' && filter_key !== 'StateDone'" class="color_input">
                <input type="color" :name="`${filter_key}_color`" :aria-label="__('%s color').replace('%s', label_title)" :value="filter_data.color"/>
            </span>
            <span v-if="event_type === 'group_users'" class="toggle cursor-pointer"></span>
            <div v-if="event_type !== 'event_filter'" class="filter_option dropstart d-inline-block position-relative" data-bs-toggle="dropdown">
                <i class="ti ti-dots cursor-pointer"></i>
                <ul ref="options_list" class="dropdown-menu p-0">
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
        <ul v-if="filter_data.caldav_url && event_type === 'group_users'" class="group_listofusers filters">
            <PlanningFilter v-for="(user_filter_data, user_filter_key) in filter_data.child_filters" :key="user_filter_key" :filter_key="user_filter_key" :filter_data="user_filter_data"/>
        </ul>
    </li>
</template>

<style scoped>
    .filter_option {
        width: 12px;
        height: 12px;
        margin: 3px 2px;
        left: 0;
    }
</style>
