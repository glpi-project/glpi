<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */
    defineProps({
        sessions: {
            type: Array,
            required: true,
        },
    });

    const user_form_url = `${CFG_GLPI.root_doc}/front/user.form.php?id=`;

    const agent_type_icons = new Map([
        ['browser', new Map([
            ['chrome', 'ti ti-brand-chrome'],
            ['edge', 'ti ti-brand-edge'],
            ['firefox', 'ti ti-brand-firefox'],
            ['safari', 'ti ti-brand-safari'],
            ['opera', 'ti ti-brand-opera'],
        ])],
    ]);

    function getAgentIcon(user_agent_info) {
        const type_icons = agent_type_icons.get(user_agent_info.client.type);
        if (type_icons) {
            const icon = type_icons.get(user_agent_info.client.name.toLowerCase());
            if (icon) {
                return icon;
            }
        }
        return 'ti ti-help';
    }

    function getAgentDescription(user_agent_info) {
        return `${user_agent_info.client.name} ${user_agent_info.client.version} - ${user_agent_info.os.name} ${user_agent_info.os.version}`.trim();
    }

    function getLogoutReasonLabel(logout_reason) {
        switch (logout_reason) {
            case 'user':
                return __('User logout', 'logout_reason');
            case 'admin':
                return __('Admin revoked', 'logout_reason');
            case 'expired':
                return __('Session expired', 'logout_reason');
            default:
                return logout_reason;
        }
    }

    function getLogoutReasonClass(logout_reason) {
        switch (logout_reason) {
            case 'user':
                return 'badge badge-outline bg-transparent text-success';
            case 'admin':
                return 'badge badge-outline bg-transparent text-danger';
            case 'expired':
                return 'badge badge-outline bg-transparent text-info';
            default:
                return '';
        }
    }
</script>

<template>
    <table class="table table-striped">
        <thead>
        <tr>
            <th scope="col">{{ __('Type') }}</th>
            <th scope="col">{{ __('User') }}</th>
            <th scope="col">{{ __('Details') }}</th>
            <th scope="col">{{ __('IP address') }}</th>
            <th scope="col">{{ __('Login') }}</th>
            <th scope="col">{{ __('Last activity') }}</th>
            <th scope="col">{{ __('Status') }}</th>
            <th scope="col">{{ __('Actions') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="session in sessions" :key="session.id">
            <td class="gap-1">
                <i class="ti ti-world"></i>
                {{ __('Browser') }}
            </td>
            <td>
                <a :href="`${user_form_url}${session.users_id}`">{{ session.user_name }}</a>
            </td>
            <td>
                <i :class="getAgentIcon(session.user_agent_info)"></i>
                {{ getAgentDescription(session.user_agent_info) }}
            </td>
            <td>{{ session.ip_address }}</td>
            <td>{{ session.created_at }}</td>
            <td>{{ session.last_activity_at }}</td>
            <td>
                <span v-if="session.logout_reason" :class="getLogoutReasonClass(session.logout_reason)">{{ getLogoutReasonLabel(session.logout_reason) }}</span>
                <span v-else class="badge badge-outline bg-transparent text-success">{{ __('Active') }}</span>
            </td>
            <td>
                <button v-if="!session.logged_out_at" class="btn btn-outline-danger btn-sm gap-1">
                    <i class="ti ti-logout"></i>
                    {{ __('Revoke') }}
                </button>
            </td>
        </tr>
        </tbody>
    </table>
</template>

<style scoped>

</style>
