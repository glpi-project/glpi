<script setup>
    /*!
     * GLPI - Gestionnaire Libre de Parc Informatique
     * SPDX-License-Identifier: GPL-3.0-or-later
     * SPDX-FileCopyrightText: 2015-2026 Teclib' and contributors.
     */
    import Skeleton from "../Common/Skeleton.vue";

    defineProps({
        loading: {
            type: Boolean,
            default: false,
        },
        sessions: {
            type: Array,
            required: true,
        },
        current_page: {
            type: Number,
            default: 1,
        },
    });

    defineEmits(['revoke']);

    const user_form_url = `${CFG_GLPI.root_doc}/front/user.form.php?id=`;
    const datetime_format = new Intl.DateTimeFormat(document.documentElement.lang, {
        dateStyle: 'short',
        timeStyle: 'medium',
    });
    /** A placeholder datetime in the user's locale, used to calculate the width of the datetime column skeleton loader. */
    const loader_datetime = datetime_format.format(new Date('2026-01-01T12:00:00Z'));

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

    /**
     * Format a timestamp to a relative time (e.g. "5 minutes ago") if the difference between the current time and the timestamp is less than the given cutoff, otherwise return the absolute time (e.g. "2024-01-01 12:00:00").
     * @param timestamp The timestamp to format, in a format that can be parsed by the JavaScript Date object (e.g. "2024-01-01T12:00:00Z").
     * @param relativeCutoff The cutoff in seconds for when to switch from relative time (e.g. "5 minutes ago") to absolute time (e.g. "2024-01-01 12:00:00").
     * If the difference between the current time and the timestamp is greater than this cutoff, the function will return the absolute time instead of the relative time.
     * Defaults to 24 hours.
     */
    function formatRelativeTime(timestamp, relativeCutoff = 24 * 60 * 60) {
        if (!timestamp) {
            return '';
        }
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now.getTime() - date.getTime()) / 1000); // difference in seconds

        if (relativeCutoff !== undefined && diff > relativeCutoff) {
            return datetime_format.format(date);
        }

        if (diff < 15) {
            return __('just now');
        }

        if (diff < 60) return __('%s seconds ago').replace('%s', diff);
        if (diff < 3600) return __('%s minutes ago').replace('%s', Math.floor(diff / 60));
        if (diff < 86400) return __('%s hours ago').replace('%s', Math.floor(diff / 3600));
        if (diff < 604800) return __('%s days ago').replace('%s', Math.floor(diff / 86400));
        return datetime_format.format(date);
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
        <TransitionGroup tag="tbody" name="list" aria-live="polite" aria-atomic="true" :aria-busy="loading">
            <tr v-if="!loading" v-for="session in sessions" :key="session.id">
                <td>
                    <span class="d-flex gap-1">
                        <i class="ti ti-world" aria-hidden="true"></i>
                        {{ __('Browser') }}
                    </span>
                </td>
                <td>
                    <a :href="`${user_form_url}${session.users_id}`">{{ session.user_name }}</a>
                </td>
                <td>
                    <span class="d-flex gap-1">
                        <i :class="getAgentIcon(session.user_agent_info)" aria-hidden="true"></i>
                        {{ getAgentDescription(session.user_agent_info) }}
                        <span v-if="session.current_session" class="badge badge-outline bg-transparent text-info">{{ __('Current session') }}</span>
                    </span>
                </td>
                <td>{{ session.ip_address }}</td>
                <td>
                    <time :datetime="session.logged_in_at">{{ formatRelativeTime(session.logged_in_at) }}</time>
                </td>
                <td>
                    <time :datetime="session.last_activity_at ?? session.logged_out_at">{{ formatRelativeTime(session.last_activity_at ?? session.logged_out_at) }}</time>
                </td>
                <td>
                    <span v-if="session.logout_reason" :class="getLogoutReasonClass(session.logout_reason)">{{ getLogoutReasonLabel(session.logout_reason) }}</span>
                    <span v-else class="badge badge-outline bg-transparent text-success">{{ __('Active') }}</span>
                </td>
                <td>
                    <button v-if="!session.logged_out_at && !session.current_session"
                            class="btn btn-outline-danger btn-sm gap-1"
                            @click="$emit('revoke', session.session_token_hash)">
                        <i class="ti ti-logout" aria-hidden="true"></i>
                        {{ __('Revoke') }}
                    </button>
                </td>
            </tr>
            <tr v-else v-for="n in 20" :key="n" aria-hidden="true">
                <td><Skeleton :width="`${__('Browser').length + 2}ch`" /></td>
                <td><Skeleton width="16ch" /></td>
                <td><Skeleton width="30ch" /></td>
                <td><Skeleton width="15ch" /></td>
                <td><Skeleton :width="`${loader_datetime.length}ch`" /></td>
                <td><Skeleton :width="`${loader_datetime.length}ch`" /></td>
                <td><Skeleton width="10ch" /></td>
                <td><Skeleton width="10ch" /></td>
            </tr>
        </TransitionGroup>
    </table>
    <div class="flex-grow-1 d-flex flex-wrap flex-md-nowrap align-items-center justify-content-between mb-2 search-pager">
        <ul class="pagination m-0 mt-sm-2 mt-md-0 align-items-center">
            <li class="page-item">
                <button class="page-link" :title="__('Start')" :aria-label="__('Start')">
                    <i class="ti ti-chevrons-left" aria-hidden="true"></i>
                </button>
            </li>
            <li class="page-item">
                <button class="page-link" :title="__('Previous')" :aria-label="__('Previous')">
                    <i class="ti ti-chevron-left" aria-hidden="true"></i>
                </button>
            </li>
            <li class="page-item active selected">
                <span class="page-link page-link-num" v-text="current_page"></span>
            </li>
            <li class="page-item">
                <button class="page-link" :title="__('Next')" :aria-label="__('Next')">
                    <i class="ti ti-chevron-right" aria-hidden="true"></i>
                </button>
            </li>
            <li class="page-item">
                <button class="page-link" :title="__('End')" :aria-label="__('End')">
                    <i class="ti ti-chevrons-right" aria-hidden="true"></i>
                </button>
            </li>
        </ul>
    </div>
</template>

<style scoped>
    @media (prefers-reduced-motion: reduce) {
        .list-enter-active,
        .list-leave-active,
        .list-move {
            transition-duration: 0.001s !important;
        }
    }

    .list-move, /* apply transition to moving elements */
    .list-enter-active,
    .list-leave-active {
        transition: all 0.5s ease;
    }

    .list-enter-from,
    .list-leave-to {
        opacity: 0;
        transform: translateX(30px);
    }

    /* ensure leaving items are taken out of layout flow so that moving
       animations can be calculated correctly. */
    .list-leave-active {
        position: absolute;
    }
</style>
