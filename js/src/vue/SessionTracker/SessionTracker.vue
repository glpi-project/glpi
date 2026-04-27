<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */
    import Toolbar from "./Toolbar.vue";
    import SessionDatatable from "./SessionDatatable.vue";
    import {onMounted, ref, useTemplateRef} from "vue";
    import ConfirmationDialog from "../Common/ConfirmationDialog.vue";

    const loading = ref(true);
    const error = ref(null);
    const sessions = ref([]);
    const revoke_all_dialog = useTemplateRef('revoke_all_dialog');
    const revoke_dialog = useTemplateRef('revoke_dialog');

    onMounted(() => {
        refreshSessions();
    });

    function refreshSessions(filters = {
        user: '',
        status: '',
        type: '',
        ip: '',
    }) {
        loading.value = true;
        error.value = null;
        const query_params = new URLSearchParams();
        if (filters.user) {
            query_params.append('user', filters.user);
        }
        if (filters.status) {
            query_params.append('status', filters.status);
        }
        if (filters.type) {
            query_params.append('type', filters.type);
        }
        if (filters.ip) {
            query_params.append('ip', filters.ip);
        }
        fetch(`${CFG_GLPI.root_doc}/Security/Sessions?${query_params.toString()}`, {
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
            loading.value = false;
        }).catch((err) => {
            error.value = err.message;
            loading.value = false;
        });
    }

    function revokeAllSessions() {
        revoke_all_dialog.value.confirm().then((confirmed) => {
            if (confirmed) {
                glpi_toast_info('Not implemented yet');
            }
        });
    }

    function revokeSession(session_token_hash) {
        revoke_dialog.value.confirm().then((confirmed) => {
            if (confirmed) {
                glpi_toast_info('Not implemented yet');
            }
        });
    }
</script>

<template>
    <section class="d-grid h-100 gap-2 mw-100">
        <ConfirmationDialog ref="revoke_all_dialog" :message="__('Are you sure you want to revoke all active sessions (excluding the current session)?')"
                            confirm_class="btn-danger" :confirm_text="__('Revoke')"/>
        <ConfirmationDialog ref="revoke_dialog" :message="__('Are you sure you want to revoke this session?')"
                            confirm_class="btn-danger" :confirm_text="__('Revoke')"/>
        <header class="d-flex justify-content-between">
            <div>
                <h1>
                    <i class="ti ti-shield-lock" aria-hidden="true"></i>
                    {{ __('Session list') }}
                </h1>
            </div>
            <div>
                <button class="btn btn-outline-danger gap-1" @click="revokeAllSessions">
                    <i class="ti ti-logout" aria-hidden="true"></i>
                    {{ __('Revoke all active sessions') }}
                </button>
            </div>
        </header>
        <div class="card">
            <div class="card-body">
                <Toolbar @filter="refreshSessions"/>
            </div>
        </div>
        <div class="overflow-x-auto">
            <SessionDatatable v-if="error === null && sessions.length > 0" :loading="loading" :sessions="sessions" @revoke="revokeSession"/>
            <div v-else-if="!loading && sessions.length === 0" class="alert alert-info" role="alert">
                <div class="d-flex">
                    <div class="me-2">
                        <i class="ti ti-info-circle fs-2x alert-icon" aria-hidden="true"></i>
                    </div>
                    <span class="alert-heading">
                        {{ __('No sessions found') }}
                    </span>
                </div>
            </div>
            <div v-else-if="error" class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <div class="me-2">
                        <i class="ti ti-exclamation-circle fs-2x alert-icon" aria-hidden="true"></i>
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
        grid-template-columns: minmax(400px, 1fr);
        grid-template-rows: auto auto 1fr;
    }
</style>
