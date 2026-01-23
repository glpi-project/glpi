<script setup>
    import {computed, nextTick, ref} from "vue";
    import {Rights} from "./Rights.js";
    import {TeamBadgeProvider} from "./TeamBadgeProvider.js";

    const props = defineProps({
        id: {
            type: String,
            required: true
        },
        read_only: {
            type: Boolean,
            required: false,
            default: false
        },
        title: {
            type: String,
            required: true
        },
        title_tooltip: {
            type: String,
            required: false,
            default: undefined
        },
        icon: {
            type: String,
            required: false,
            default: ''
        },
        card_content: {
            type: String,
            required: false,
            default: ''
        },
        team: {
            type: Object,
            required: false,
            default: () => {}
        },
        metadata: {
            type: Object,
            required: false,
            default: () => {}
        },
        form_link: {
            type: String,
            required: false,
            default: undefined
        },
        rights: {
            type: Rights,
            required: true
        },
        team_badge_provider: {
            type: TeamBadgeProvider,
            required: true
        },
        due_date: {
            type: String,
            required: false,
            default: undefined
        }
    });

    const emit = defineEmits([
        'kanban:card_delete', 'kanban:card_restore', 'kanban:card_show_details'
    ]);
    const is_deleted = computed(() => {
        return !!(props.metadata.is_deleted || 0);
    });
    const card_overflow_dropdown = ref(null);
    const btn_overflow = ref(null);

    const render_badges = ref(true);
    const badges_to_show = computed(() => {
        if (!render_badges.value) {
            return [];
        }
        const members = Object.values(props.team).slice(0, props.team_badge_provider.max_team_images);
        $.each(members, (i, member) => {
            member.content = props.team_badge_provider.getTeamBadge(member);
            member.hash = props.team_badge_provider.getTeamBadgeHash(member);
        });
        return members;
    });

    $(props.team_badge_provider.event_target).on('kanban:team_badge:changed', () => {
        render_badges.value = false;
        nextTick(() => {
            render_badges.value = true;
        });
    });
</script>

<template>
    <li :id="id" :class="`kanban-item card shadow-none ${read_only ? 'readonly' : ''} ${is_deleted ? 'deleted' : ''}`">
        <div class="kanban-item-header d-flex justify-content-between">
            <span class="kanban-item-title d-flex align-items-center">
                <i :class="icon"></i>
                <span class="cursor-pointer" v-text="title" @click="emit('kanban:card_show_details')"></span>
            </span>
            <div class="dropdown">
                <button type="button" class="kanban-item-overflow-actions cursor-pointer pt-0 b-0"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="ti ti-dots" ref="btn_overflow"></i>
                </button>
                <ul ref="card_overflow_dropdown" class="kanban-dropdown dropdown-menu" role="menu">
                    <li class="kanban-item-goto dropdown-item" v-if="form_link">
                        <a :href="form_link" class="w-100">
                            <i class="ti ti-share-3"></i>{{ __('Go to') }}
                        </a>
                    </li>
                    <li class="kanban-item-restore dropdown-item cursor-pointer" v-if="rights.canDeleteItem() && is_deleted"
                        @click="emit('kanban:card_restore')">
                        <span>
                            <i class="ti ti-trash-off"></i>{{ __('Restore') }}
                        </span>
                    </li>
                    <li class="kanban-item-remove dropdown-item cursor-pointer" v-if="rights.canDeleteItem()"
                        @click="emit('kanban:card_delete')">
                        <span>
                            <i class="ti ti-trash"></i>{{ is_deleted ? __('Purge') : __('Delete') }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        <div v-if="metadata.content" class="kanban-description-preview" v-text="metadata.content"></div>
        <div class="kanban-item-content" v-html="card_content"></div>
        <div class="d-flex justify-content-between">
            <div class="kanban-item-team position-relative">
                <span v-for="member in badges_to_show" :key="member.hash"
                      v-html="member.content"></span>
                <span v-if="Object.values(team).length > team_badge_provider.max_team_images"
                      v-html="team_badge_provider.generateOverflowBadge(Object.values(team).length - team_badge_provider.max_team_images)"></span>
            </div>
            <div class="align-self-center kanban-item-due-date">
                <span v-if="due_date" :title="__('Planned end date')">
                    <i class="ti ti-calendar"></i>
                    <span v-text="due_date"></span>
                </span>
            </div>
        </div>
    </li>
</template>
