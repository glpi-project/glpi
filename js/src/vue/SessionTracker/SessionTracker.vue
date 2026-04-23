<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */
    import Toolbar from "./Toolbar.vue";
    import SessionDatatable from "./SessionDatatable.vue";
    import {onMounted, ref} from "vue";

    const error = ref(null);
    const sessions = ref([]);

    onMounted(async () => {
        fetch(`${CFG_GLPI.root_doc}/Security/Sessions`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(async (response) => {
            if (!response.ok) {
                error.value = `${response.status} ${response.statusText}`;
                return;
            }
            sessions.value = await response.json();
        }).catch((err) => {
            error.value = err.message;
        });
    });
</script>

<template>
    <section class="d-grid h-100 gap-2">
        <header class="d-flex justify-content-between">
            <div>
                <h1>
                    <i class="ti ti-shield-lock"></i>
                    {{ __('Session list') }}
                </h1>
            </div>
            <div>
                <button class="btn btn-outline-danger gap-1">
                    <i class="ti ti-logout"></i>
                    {{ __('Revoke all active sessions') }}
                </button>
            </div>
        </header>
        <div class="card">
            <div class="card-body">
                <Toolbar />
            </div>
        </div>
        <div>
            <SessionDatatable v-if="error === null" :sessions="sessions"/>
            <div v-else class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <div class="me-2">
                        <i class="ti ti-exclamation-circle fs-2x alert-icon"></i>
                    </div>
                    <span class="alert-heading">
                        {{ __('Failed to load the session list') }}
                        <br>
                        {{ error }}
                    </span>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
    section {
        grid-template-rows: auto auto 1fr;
    }
</style>
