<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2025 Teclib' and contributors.
     */

    const props = defineProps({
        id: {
            type: String,
            required: true,
        },
        title: {
            type: String,
            required: true,
        },
        icon: {
            type: String,
            default: null,
        },
    });
</script>

<template>
    <li class="debug-toolbar-widget d-inline-block p-2" :data-glpi-debug-widget-id="id">
        <button type="button" class="btn btn-icon border-0 p-1" :title="title" data-bs-toggle="tooltip" data-bs-placement="top">
            <i v-if="icon" :class="icon + ' me-1'"></i>
            <span class="debug-text"></span>
        </button>
    </li>
</template>

<style scoped>

</style>
