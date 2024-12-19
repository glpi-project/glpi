<script setup>
    import {computed} from 'vue';

    const props = defineProps({
        field_key: String,
        customfields_id: {
            type: Number,
            default: -1,
        },
        field_options: {
            type: Object,
            default: () => ({}),
        },
        is_active: {
            type: Boolean,
            default: true,
        },
    });
</script>

<template>
    <div :class="`sortable-field align-items-center ${field_options.full_width ? 'col-12' : 'col-12 col-sm-6'}`"
         :data-key="field_key" :data-customfield-id="customfields_id">
        <input type="hidden" name="fields_display[]" :value="field_key" />
        <slot name="field_options"></slot>
        <div :class="`form-field row flex-grow-1 m-2`">
            <div class="col-auto align-content-center">
                <i class="ti ti-grip-vertical sort-handle"></i>
            </div>
            <div class="col py-2">
                <slot name="field_markers"></slot>
                <slot name="field_label"></slot>
            </div>
            <div v-if="is_active" class="col-auto btn-group shadow-none field-actions">
                <button type="button" class="btn btn-ghost-secondary btn-sm edit-field" :title="__('Edit')">
                    <i class="ti ti-pencil"></i>
                </button>
                <button type="button" class="btn btn-ghost-danger btn-sm hide-field" :title="__('Hide')">
                    <i class="ti ti-eye-off"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .form-field {
        border: var(--tblr-border-width) solid var(--tblr-border-color);
        border-radius: var(--tblr-border-radius);

        & > .col {
            border-left: 1px solid var(--tblr-border-color);
        }

        & > .field-actions {
            visibility: hidden;
        }
        &:hover > .field-actions {
            visibility: visible;
        }
    }
</style>
