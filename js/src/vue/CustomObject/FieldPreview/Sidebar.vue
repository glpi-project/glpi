<script setup>
    import Field from "./Field.vue";
    import {computed, ref, onMounted} from "vue";

    const props = defineProps({
        inactive_fields: Map,
        add_edit_fn: String,
    });

    const search = ref('');

    const unused_native_fields = computed(() => {
        return new Map([...props.inactive_fields].filter(([key, field]) => (field.customfields_id ?? -1) < 0));
    });
    const unused_custom_fields = computed(() => {
        return new Map([...props.inactive_fields].filter(([key, field]) => (field.customfields_id ?? -1) >= 0));
    });

    function getMatched(fields) {
        if (search.value === '') {
            return fields;
        }
        const results = new Map();
        for (const [key, field] of fields) {
            if (field.label.toLowerCase().includes(search.value.toLowerCase())) {
                results.set(key, field);
            }
        }
        return results;
    }

    onMounted(() => {
        $('.fields-sidebar .new-custom-field').on('click', () => {
            window[props.add_edit_fn](-1);
        });
    });

</script>

<template>
    <div class="h-100 d-flex col-auto flex-column p-2 px-3 fields-sidebar">
        <span class="fs-2">{{ __('Add more fields') }}</span>
        <input type="text" class="form-control mb-3" name="search" :placeholder="__('Search')" v-model="search" />
        <span v-if="unused_native_fields.size > 0" class="fs-3">{{ __('Native fields') }}</span>
        <Field v-for="[field_key, unused_field] of getMatched(unused_native_fields)" :key="field_key" :field_key="field_key"
               :is_active="false">
            <template v-slot:field_label>{{ unused_field.label }}</template>
        </Field>
        <span class="fs-3 mt-3">{{ __('Custom fields') }}</span>
        <Field v-for="[field_key, unused_field] of getMatched(unused_custom_fields)" :key="field_key" :field_key="field_key"
               :is_active="false" :customfields_id="unused_field.customfields_id">
            <template v-slot:field_label>{{ unused_field.label }}</template>
        </Field>
        <div class="align-items-center col-12">
            <div class="form-field row flex-grow-1 mx-0 my-1 new-custom-field cursor-pointer btn btn-sm btn-ghost-secondary w-100" role="button">
                <div class="col py-2 text-center">
                    <i class="ti ti-plus"></i>
                    {{ __('New field') }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .fields-sidebar {
        border-left: 1px solid var(--tblr-border-color);
        width: 300px;
        .sortable-field.col-sm-6 {
            width: 100%;
        }
        .form-field.new-custom-field {
            border: var(--tblr-border-width) dashed var(--tblr-border-color);
            border-radius: var(--tblr-border-radius);
        }
    }
</style>
