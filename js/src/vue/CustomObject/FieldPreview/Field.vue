<script setup>
    import {computed} from 'vue';

    const props = defineProps({
        field_key: String,
        customfields_id: {
            type: Number,
            default: -1,
        },
        label_classes: {
            type: String,
            default: 'col-form-label cursor-grab col-xxl-5 text-xxl-end',
        },
        field_classes: {
            type: String,
            default: 'col-xxl-7 field-container btn-group shadow-none',
        },
        wrapper_classes: {
            type: String,
            default: 'form-field row flex-grow-1',
        },
    });

    const sortable_classes = computed(() => {
        return props.wrapper_classes.split(' ').filter((cls) => cls.startsWith('col-')).join(' ');
    });
</script>

<template>
    <div :class="`sortable-field align-items-center p-1 ${(!!$slots.field_preview) ? sortable_classes : 'col-12 col-sm-6'}`"
         :data-key="field_key" :data-customfield-id="customfields_id" :style="`display: ${(!!$slots.field_preview) ? 'flex' : 'none'};`">
        <input type="hidden" name="fields_display[]" :value="field_key" />
        <slot name="field_options"></slot>
        <div :class="wrapper_classes">
            <label :class="label_classes">
                <slot name="field_label"></slot>
                <slot name="field_markers"></slot>
                <i class="ti ti-grip-vertical sort-handle align-middle"></i>
            </label>
            <div :class="field_classes">
                <slot name="field_preview"></slot>
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
    .sortable-field .btn-group .select2-container {
        flex-basis: auto;
        width: 100% !important;
    }
</style>
