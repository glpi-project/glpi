<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2025 Teclib' and contributors.
     */

    import Kanban from "./Kanban.vue";
</script>

<template>
    <Suspense>
        <Kanban v-bind="$attrs"></Kanban>
        <template #fallback>
            <div class="d-flex justify-content-center align-items-center h-100">
                <div class="spinner-border" role="status" aria-hidden="true"></div>
            </div>
        </template>
    </Suspense>
</template>

<style scoped>

</style>
