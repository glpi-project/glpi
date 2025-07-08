<script setup>
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
    <div :class="`sortable-field align-items-center ${parseInt(field_options.full_width ?? 0) === 1 ? 'col-12' : 'col-12 col-sm-6'} cursor-grab`"
         :data-key="field_key" :data-customfield-id="customfields_id">
        <input type="hidden" name="fields_display[]" :value="field_key" :disabled="!is_active" />
        <slot name="field_options"></slot>
        <div :class="`form-field row flex-grow-1 ${is_active ? 'm-1' : 'mx-0 my-1'}`">
            <div class="col py-2">
                <slot name="field_label"></slot>
                <slot name="field_markers"></slot>
            </div>
            <div v-if="is_active" class="col-auto btn-group shadow-none field-actions">
                <button type="button" class="btn btn-ghost-secondary btn-sm edit-field" :title="__('Edit')">
                    <i class="ti ti-pencil"></i>
                </button>
                <button type="button" class="btn btn-ghost-danger btn-sm hide-field" :title="__('Hide')">
                    <i class="ti ti-eye-off"></i>
                </button>
            </div>
            <div v-if="!is_active && customfields_id > 0" class="col-auto btn-group shadow-none field-actions">
                <button type="button" class="btn btn-ghost-danger btn-sm purge-field" :title="_x('button', 'Delete permanently')">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
    [data-glpi-theme-dark="1"] .form-field {
        border: var(--tblr-border-width) solid var(--tblr-border-color);
    }
    .form-field {
        border-radius: var(--tblr-border-radius);
        border: var(--tblr-border-width) solid transparent;
        background-color: var(--tblr-gray-200);

        & > .col {
            border-left: 1px solid var(--tblr-border-color);
        }

        @media (pointer: fine) {
            & > .field-actions {
                visibility: hidden;
            }

            &:hover > .field-actions {
                visibility: visible;
            }
        }
    }
</style>
