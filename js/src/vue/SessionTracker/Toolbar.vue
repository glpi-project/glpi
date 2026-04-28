<script setup >
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import {onMounted, onUnmounted, useId, ref, useTemplateRef, computed} from "vue";

    const emit = defineEmits(['filter']);

    const user_filter_id = useId();
    const status_filter_id = useId();
    const type_filter_id = useId();
    const ip_filter_id = useId();
    const filters_popover_id = useId();

    const ip_field = useTemplateRef('ip_field');

    const user_filter = ref('');
    const status_filter = ref('active');
    const type_filter = ref('all');
    const ip_filter = ref('');

    const is_mobile = ref(window.innerWidth <= 768);
    const active_filters_count = ref(0);

    const resize_listener = () => {
        is_mobile.value = window.innerWidth <= 768;
    };
    const ipv4_pattern = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
    const ipv6_pattern = /^(([0-9a-fA-F]{1,4}:){7}([0-9a-fA-F]{1,4}|:))|(([0-9a-fA-F]{1,4}:){1,7}:)|(([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4})|(([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2})|(([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3})|(([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4})|(([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5})|([0-9a-fA-F]{1,4}:)((:[0-9a-fA-F]{1,4}){1,6})|(:)((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}$/;

    onMounted(() => {
        window.addEventListener('resize', resize_listener);
    });

    onUnmounted(() => {
        window.removeEventListener('resize', resize_listener);
    });

    function applyFilters() {
        // Validate IP filter before emitting filter event
        const ip_filter_value = ip_filter.value.trim();
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

        let count = 0;
        if (user_filter.value.trim() !== '') count++;
        if (status_filter.value !== 'active') count++;
        if (type_filter.value !== 'all') count++;
        if (ip_filter.value.trim() !== '') count++;
        active_filters_count.value = count;

        emit('filter', {
            user: user_filter.value,
            status: status_filter.value,
            type: type_filter.value,
            ip: ip_filter.value,
        });
    }
    function resetFilters() {
        // reset filters and then emit filter event to update the session list
        user_filter.value = '';
        status_filter.value = 'active';
        type_filter.value = 'all';
        ip_filter.value = '';
        applyFilters();
    }
</script>

<template>
    <div class="session-tracker-toolbar gap-2">
        <Teleport defer :disabled="!is_mobile" :to="`#${filters_popover_id} .filters-content`">
            <label style="grid-column: 1" :for="user_filter_id" class="form-label">{{ _n('User', 'Users', 1) }}</label>
            <input ref="user_field" style="grid-column: 1" type="search" :id="user_filter_id" class="form-control" v-model="user_filter" />
            <label style="grid-column: 2" :for="status_filter_id" class="form-label">{{ __('Status') }}</label>
            <select ref="status_field" style="grid-column: 2" :id="status_filter_id" class="form-select" v-model="status_filter">
                <option value="active">{{ __('Active') }}</option>
                <option value="all">{{ __('All') }}</option>
            </select>
            <label style="grid-column: 3" :for="type_filter_id" class="form-label">{{ __('Type') }}</label>
            <select ref="type_field" style="grid-column: 3" :id="type_filter_id" class="form-select" v-model="type_filter">
                <option value="all">{{ __('All') }}</option>
                <option value="web">{{ __('Browser') }}</option>
                <option value="api">{{ __('API') }}</option>
            </select>
            <label style="grid-column: 4" :for="ip_filter_id" class="form-label">{{ __('IP address') }}</label>
            <input ref="ip_field" style="grid-column: 4" type="search" :id="ip_filter_id" class="form-control"
                   :aria-describedby="`${ip_filter_id}helper`" v-model="ip_filter" />
            <span style="grid-column: 4" :id="`${ip_filter_id}helper`" class="form-hint">{{ __('CIDR notation and comma separated values are supported.') }}</span>
        </Teleport>
        <div style="grid-column: 5; grid-row: 2" class="d-flex align-self-center gap-2">
            <button class="btn btn-primary align-self-end gap-1" @click="is_mobile ? undefined : applyFilters"
                    :aria-controls="is_mobile ? filters_popover_id : undefined" :aria-expanded="false" :aria-haspopup="is_mobile"
                    :data-bs-toggle="is_mobile ? 'dropdown' : undefined" data-bs-placement="bottom">
                <i class="ti ti-filter" aria-hidden="true"></i>
                {{ is_mobile ? __('Filters') : __('Filter') }}
                <span v-if="is_mobile && active_filters_count > 0" class="badge bg-info text-info-fg badge-notification badge-pill" v-text="active_filters_count"></span>
            </button>
            <div :id="filters_popover_id" class="dropdown-menu dropdown-menu-card" role="region">
                <div class="p-3">
                    <div class="filters-content mb-3 d-flex gap-1 flex-column"></div>
                    <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-outline-secondary align-self-end gap-1" @click="resetFilters">
                            <i class="ti ti-reload" aria-hidden="true"></i>
                            {{ __('Reset') }}
                        </button>
                        <button class="btn btn-primary gap-1" @click="applyFilters">
                            <i class="ti ti-filter" aria-hidden="true"></i>
                            {{ __('Filter') }}
                        </button>
                    </div>
                </div>
            </div>
            <button v-if="!is_mobile" class="btn btn-outline-secondary align-self-end gap-1" @click="resetFilters">
                <i class="ti ti-reload" aria-hidden="true"></i>
                {{ __('Reset') }}
            </button>
        </div>
    </div>
</template>

<style scoped>
    .session-tracker-toolbar {
        display: grid;
        grid-template-columns: repeat(4, minmax(100px, 300px)) auto;
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
