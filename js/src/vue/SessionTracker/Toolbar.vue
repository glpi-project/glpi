<script setup >
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */

    import { useId } from "vue";

    const user_filter_id = useId();
    const status_filter_id = useId();
    const type_filter_id = useId();
    const ip_filter_id = useId();
</script>

<template>
    <div class="session-tracker-toolbar gap-2">
        <div>
            <label :for="user_filter_id" class="form-label">{{ _n('User', 'Users', 1) }}</label>
            <input type="search" :id="user_filter_id" class="form-control" />
        </div>
        <div>
            <label :for="status_filter_id" class="form-label">{{ __('Status') }}</label>
            <select :id="status_filter_id" class="form-select">
                <option value="active">{{ __('Active') }}</option>
                <option value="">{{ __('All') }}</option>
            </select>
        </div>
        <div>
            <label :for="type_filter_id" class="form-label">{{ __('Type') }}</label>
            <select :id="type_filter_id" class="form-select">
                <option value="">{{ __('All') }}</option>
                <option value="web">{{ __('Browser') }}</option>
                <option value="api">{{ __('API') }}</option>
            </select>
        </div>
        <div>
            <label :for="ip_filter_id" class="form-label">{{ __('IP address') }}</label>
            <input type="search" :id="ip_filter_id" class="form-control" />
        </div>
        <button class="btn btn-primary align-self-end gap-1">
            <i class="ti ti-filter"></i>
            {{ __('Filter') }}
        </button>
        <button class="btn btn-outline-secondary align-self-end gap-1">
            <i class="ti ti-reload"></i>
            {{ __('Reset') }}
        </button>
    </div>
</template>

<style scoped>
    .session-tracker-toolbar {
        display: flex;
    }
</style>
