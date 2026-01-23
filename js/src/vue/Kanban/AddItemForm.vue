<script setup>
    import {computed, ref} from "vue";

    const props = defineProps({
        data: {
            type: Object,
            required: true
        }
    });

    const emit = defineEmits(['kanban:close_form', 'kanban:add_item']);

    const fields = computed(() => {
        const fields = {};
        $.each(props.data.itemtype_data.fields, (name, options) => {
            fields[name] = {
                type: (options.type || 'text').toLowerCase(),
                value: options.value,
                placeholder: options.placeholder
            };
        });
        return fields;
    });
</script>

<template>
    <div class="kanban-add-form kanban-form d-flex flex-column card">
        <div class="kanban-item-header d-flex justify-content-between">
            <span class="kanban-item-title">
                <i :class="data.itemtype_data.icon"></i>
                {{ data.itemtype_data.name }}
            </span>
            <i class="ti ti-x cursor-pointer" :title="__('Close')" @click.prevent="emit('kanban:close_form')"></i>
        </div>
        <div v-if="data.is_bulk">
            <span class="kanban-item-subtitle" v-text="__('One item per line')"></span>
        </div>
        <div class="kanban-item-content">
            <template v-for="(field, name) in fields">
                <textarea v-if="!data.is_bulk && field.type === 'textarea'" class="form-control w-100" :name="name"
                          :key="`${field.type}_${name}`" :placeholder="field.placeholder" v-model="field.value"></textarea>
                <div v-else-if="!data.is_bulk && field.type === 'raw'" class="w-100" :key="`${field.type}_${name}`"
                     v-html="field.value"></div>
                <input v-else-if="!data.is_bulk || field.type === 'hidden'" :type="field.type" class="form-control w-100"
                       :name="name" :key="`${field.type}_${name}`" :placeholder="field.placeholder" v-model="field.value">
            </template>
            <textarea v-if="data.is_bulk" name="bulk_item_list" class="form-control w-100"
                      v-model="fields['bulk_item_list'].value"></textarea>
        </div>
        <button type="button" class="btn btn-primary mx-auto" name="add" @click="emit('kanban:add_item', {fields: fields})" v-text="__('Save')"></button>
    </div>
</template>

<style scoped lang="scss">

</style>
