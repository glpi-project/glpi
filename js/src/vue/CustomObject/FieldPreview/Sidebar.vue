<script setup>
    import Field from "./Field.vue";
    import {computed, ref, watch, nextTick} from "vue";

    const props = defineProps({
        all_fields: Object,
        sortable_fields: Map,
    });

    const search = ref('');

    const unused_native_fields = computed(() => {
        return new Map(Object.entries(props.all_fields).filter(([key, field]) => {
            return !props.sortable_fields.has(key) && (field.customfields_id ?? -1) < 0;
        }));
    });
    const unused_custom_fields = computed(() => {
        return new Map(Object.entries(props.all_fields).filter(([key, field]) => {
            return !props.sortable_fields.has(key) && (field.customfields_id ?? -1) > 0;
        }));
    });

    function getMatched(fields) {
        if (search.value === '') {
            return fields;
        }
        const results = new Map();
        for (const [key, field] of fields) {
            if (field.text.toLowerCase().includes(search.value.toLowerCase())) {
                results.set(key, field);
            }
        }
        return results;
    }
</script>

<template>
    <div class="h-100 d-flex col-auto flex-column p-0 ps-2 fields-sidebar">
        <span class="fs-2">{{ __('Add more fields') }}</span>
        <input type="text" class="form-control mb-3" name="search" :placeholder="__('Search')" v-model="search" />
        <span class="fs-3">{{ __('Native fields') }}</span>
        <Field v-for="[field_key, unused_field] of getMatched(unused_native_fields)" :key="field_key" :is_active="false">
            <template v-slot:field_label>{{ unused_field.text }}</template>
        </Field>
        <span class="fs-3 mt-3">{{ __('Custom fields') }}</span>
        <Field v-for="[field_key, unused_field] of getMatched(unused_custom_fields)" :key="field_key" :is_active="false">
            <template v-slot:field_label>{{ unused_field.text }}</template>
        </Field>
    </div>
</template>

<style scoped>
    .fields-sidebar {
        border-left: 1px solid var(--tblr-border-color);
        width: 300px;
        .sortable-field.col-sm-6 {
            width: 100%;
        }
    }
</style>
