<script setup >
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {useId, useTemplateRef} from "vue";

    const emit = defineEmits(['filter']);

    const user_filter_id = useId();
    const status_filter_id = useId();
    const type_filter_id = useId();
    const ip_filter_id = useId();

    const user_field = useTemplateRef('user_field');
    const status_field = useTemplateRef('status_field');
    const type_field = useTemplateRef('type_field');
    const ip_field = useTemplateRef('ip_field');

    const ipv4_pattern = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
    const ipv6_pattern = /^(([0-9a-fA-F]{1,4}:){7}([0-9a-fA-F]{1,4}|:))|(([0-9a-fA-F]{1,4}:){1,7}:)|(([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4})|(([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2})|(([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3})|(([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4})|(([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5})|([0-9a-fA-F]{1,4}:)((:[0-9a-fA-F]{1,4}){1,6})|(:)((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}$/;

    function applyFilters() {
        // Validate IP filter before emitting filter event
        const ip_filter_value = ip_field.value.value.trim();
        if (ip_filter_value !== '') {
            const ip_filter_values = ip_filter_value.split(',').map(v => v.trim());
            for (const value of ip_filter_values) {
                if (!ipv4_pattern.test(value) && !ipv6_pattern.test(value) && !/^.+\/\d+$/.test(value)) {
                    ip_field.value.setCustomValidity(__('Please enter a valid IP address, CIDR notation, or comma separated values.'));
                    ip_field.value.reportValidity();
                    return;
                }
            }
        }

        emit('filter', {
            user: user_field.value.value,
            status: status_field.value.value,
            type: type_field.value.value,
            ip: ip_field.value.value,
        });
    }
    function resetFilters() {
        // reset filters and then emit filter event to update the session list
        user_field.value.value = '';
        status_field.value.value = 'active';
        type_field.value.value = 'all';
        ip_field.value.value = '';
        applyFilters();
    }
</script>

<template>
    <div class="session-tracker-toolbar gap-2">
        <label style="grid-column: 1" :for="user_filter_id" class="form-label">{{ _n('User', 'Users', 1) }}</label>
        <input ref="user_field" style="grid-column: 1" type="search" :id="user_filter_id" class="form-control" />
        <label style="grid-column: 2" :for="status_filter_id" class="form-label">{{ __('Status') }}</label>
        <select ref="status_field" style="grid-column: 2" :id="status_filter_id" class="form-select">
            <option value="active" selected>{{ __('Active') }}</option>
            <option value="all">{{ __('All') }}</option>
        </select>
        <label style="grid-column: 3" :for="type_filter_id" class="form-label">{{ __('Type') }}</label>
        <select ref="type_field" style="grid-column: 3" :id="type_filter_id" class="form-select">
            <option value="all" selected>{{ __('All') }}</option>
            <option value="web">{{ __('Browser') }}</option>
            <option value="api">{{ __('API') }}</option>
        </select>
        <label style="grid-column: 4" :for="ip_filter_id" class="form-label">{{ __('IP address') }}</label>
        <input ref="ip_field" style="grid-column: 4" type="search" :id="ip_filter_id" class="form-control"
               :aria-describedby="`${ip_filter_id}helper`" />
        <span style="grid-column: 4" :id="`${ip_filter_id}helper`" class="form-hint">{{ __('CIDR notation and comma separated values are supported.') }}</span>
        <div style="grid-column: 5; grid-row: 2" class="d-flex align-self-center gap-2">
            <button class="btn btn-primary align-self-end gap-1" @click="applyFilters">
                <i class="ti ti-filter" aria-hidden="true"></i>
                {{ __('Filter') }}
            </button>
            <button class="btn btn-outline-secondary align-self-end gap-1" @click="resetFilters">
                <i class="ti ti-reload" aria-hidden="true"></i>
                {{ __('Reset') }}
            </button>
        </div>
    </div>
</template>

<style scoped>
    .session-tracker-toolbar {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr auto;
        grid-template-rows: repeat(3, auto);

        & > label {
            grid-row: 1;
        }

        & > .form-control, & > .form-select {
            grid-row: 2;
        }

        & > .form-hint {
            grid-row: 3;
        }
    }

    @media (max-width: 768px) {
        .session-tracker-toolbar {
            display: flex;
            flex-wrap: wrap;
        }
    }
</style>
