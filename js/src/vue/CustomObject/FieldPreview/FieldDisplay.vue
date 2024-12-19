<script setup>
    import {onMounted, computed, reactive, watch, useTemplateRef, nextTick} from 'vue';
    import Field from "./Field.vue";
    import Sidebar from "./Sidebar.vue";

    const props = defineProps({
        items_id: Number,
        toolbar_el: String,
        all_fields: Object,
        fields_display: Array,
        add_edit_fn: String,
        can_create_fields: Boolean,
    });

    const fields_display = props.fields_display;
    const component_root = useTemplateRef('component_root');
    const sortable_fields_container = computed(() => {
        return $(component_root.value).parent();
    });
    /**
     * @typedef {{key: string, label: string, field_options: {}, preview_html: string, label_classes: string, field_classes: string, wrapper_classes: string}} SortableField
     */
    /**
     * @type {Map<string, SortableField>}
     */
    const sortable_fields = reactive(new Map());

    function refreshSortables() {
        nextTick(() => {
            // Need to wait for the DOM changes to be applied
            window.sortable('#sortable-fields', {
                items: '.sortable-field',
                forcePlaceholderSize: false,
                acceptFrom: '.fields-sidebar, #sortable-fields',
            });
            window.sortable('.fields-sidebar', {
                items: '.sortable-field',
                forcePlaceholderSize: false,
                acceptFrom: '#sortable-fields',
            })
        });
    }

    /**
     * Append a field to the sortable list
     * @param keys {string[]} The key of the field to append
     * @param selected_fields_data {{}} The data of the selected fields. If not provided, it will be fetched from the select2 dropdown
     */
    function appendField(keys, selected_fields_data = undefined) {
        const preview_data = [];
        keys.forEach((key) => {
            let selected_field;
            if (selected_fields_data !== undefined && selected_fields_data[key] !== undefined) {
                selected_field = selected_fields_data[key];
            } else {
                selected_field = props.all_fields[key];
            }
            if (selected_field === undefined) {
                return;
            }
            preview_data.push({key: key, selected_field: selected_field});
        });
        preview_data.forEach(({key, selected_field}) => {
            if (!sortable_fields.has(key)) {
                sortable_fields.set(key, {
                    key: key,
                    label: selected_field.text ?? selected_field,
                    field_options: fields_display.find((field) => field.key === key)?.field_options ?? {},
                    customfields_id: selected_field.customfields_id ?? -1,
                });
            }
        });
        refreshSortables();
    }

    function removeField(key) {
        // remove the field from sortable list
        sortable_fields.delete(key);
        refreshSortables();
    }

    /**
     * Refresh the data in the all_fields object
     */
    function refreshAllFields() {
        const url = `ajax/asset/assetdefinition.php?action=get_all_fields&assetdefinitions_id=${props.items_id}`;
        $.get(url, (data) => {
            console.log(data);
        });
    }

    onMounted(() => {
        //for each field in fields_display, add it to the list using the template and slot
        appendField(fields_display.map((field) => field.key));

        const sortable_container = $('#sortable-fields');

        sortable_container.on('dragenter', () => {
            const sort_el = $('.sortable-field.sortable-dragging');
            const classes_to_copy = sort_el.attr('class').split(' ')
                .filter((cls) => !['sortable-dragging'].includes(cls))
                .join(' ');
            sortable_container.find('.sortable-placeholder').attr('class', `sortable-placeholder ${classes_to_copy}`);
        });

        $(component_root.value).on('click', '.edit-field', (e) => {
            const field_el = $(e.target).closest('.sortable-field');
            const field_id = field_el.attr('data-customfield-id');
            const field_key = field_el.attr('data-key');
            if (field_id === undefined || field_id === '-1') {
                const url_params = new URLSearchParams({
                    action: 'get_core_field_editor',
                    assetdefinitions_id: props.items_id,
                    key: field_el.attr('data-key'),
                });
                const sortable_field = sortable_fields.get(field_key);
                for (const [name, value] of Object.entries(sortable_field.field_options)) {
                    url_params.append(`field_options[${name}]`, value);
                }
                const url = `${CFG_GLPI.root_doc}/ajax/asset/assetdefinition.php?${url_params}`;
                window.glpi_ajax_dialog({
                    id: 'core_field_options_editor',
                    modalclass: 'modal-lg',
                    appendTo: `#${$(sortable_fields_container.value).attr('id')}`,
                    title: field_el.find('label').text(),
                    url: url,
                    buttons: [
                        {
                            id: 'save_core_field_options',
                            label: _x('button', 'Save'),
                            class: 'btn-primary',
                        },
                        {
                            id: 'cancel_core_field_options',
                            label: __('Cancel'),
                        }
                    ]
                });
            } else {
                window[props.add_edit_fn](field_id);
            }
        }).on('click', '.hide-field', (e) => {
            const field_key = $(e.target).closest('.sortable-field').attr('data-key');
            removeField(field_key);
        });

        sortable_fields_container.value.on('click', '#save_core_field_options', () => {
            const key_field = $('#core_field_options_editor form input[name="key"]');
            const field_key = key_field.val();
            key_field.remove();
            const field_options = $('#core_field_options_editor form').serializeArray();
            const sortable_field = sortable_fields.get(field_key);

            sortable_field.field_options = {};
            field_options.forEach((option) => {
                const name = option.name.replace('field_options[', '').slice(0, -1);
                sortable_field.field_options[name] = option.value;
            });

            // Reload preview
            appendField([field_key]);

            $('#core_field_options_editor').modal('hide');
        }).on('click', '#cancel_core_field_options', () => {
            $('#core_field_options_editor').modal('hide');
        });

        const create_edit_field_modal = $('#customfield_form_container_modal');
        create_edit_field_modal.on('glpi:submit:success', 'form', (e, data) => {
            const btn_submit = data.submitter;
            const form_data = new FormData(e.target);
            const field_key = `custom_${form_data.get('system_name')}`;

            refreshAllFields();
            if (btn_submit.attr('name') === 'add' || btn_submit.attr('name') === 'update') {
                // Reload preview
                appendField([field_key], {[field_key]: sortable_fields.get(field_key)});
            } else if (btn_submit.attr('name') === 'purge') {
                removeField(field_key);
            }
        });
    });

    watch(sortable_fields, () => {
        // If only one field remains, disable the remove button
        $(component_root.value).find('.hide-field')
            .prop('disabled', sortable_fields.size === 1)
            .prop('title', sortable_fields.size === 1 ? __('Cannot remove the last field') : __('Hide'));

    }, {deep: true});
</script>

<template>
    <div class="col-12 col-xxl-12 flex-column px-n3" ref="component_root">
        <input type="hidden" name="_update_fields_display" value="1" />
        <input type="hidden" name="fields_display" value="" />

        <div class="d-flex flex-row flex-wrap flex-xl-nowrap">
            <div class="row flex-row align-items-start flex-grow-1 d-flex">
                <div class="col">
                    <div class="user-select-none row flex-row" id="sortable-fields">
                        <Field v-for="[field_key, sortable_field] of sortable_fields" :key="field_key"
                               :field_key="field_key" :customfields_id="sortable_field.customfields_id" :field_options="sortable_field.field_options">
                            <template v-slot:field_label>{{ sortable_field.label }}</template>
                            <template v-slot:field_markers>
                                <span v-if="(sortable_field.field_options.required ?? '0').toString() === '1'" class="required">*</span>
                                <i v-if="(sortable_field.field_options.readonly ?? '0').toString() === '1'" class="ti ti-pencil-off ms-2" :title="__('Readonly')"></i>
                            </template>
                            <template v-slot:field_options>
                                <template v-for="(field_option_value, field_option_name) in sortable_field.field_options" :key="field_option_name">
                                    <input type="hidden" :name="`field_options[${field_key}][${field_option_name}]`" :value="field_option_value" />
                                </template>
                            </template>
                        </Field>
                    </div>
                </div>
                <Sidebar :all_fields="all_fields" :sortable_fields="sortable_fields"></Sidebar>
            </div>
        </div>
    </div>
</template>

<style scoped>
    :deep(.sortable-field .btn.hide-field:disabled) {
        pointer-events: auto;
    }
</style>
