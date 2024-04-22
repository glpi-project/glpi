<script setup>
    import {onMounted, ref, computed, reactive, watch} from 'vue';
    import Field from "./Field.vue";

    const props = defineProps({
        items_id: Number,
        toolbar_el: String,
        all_fields: Object,
        fields_display: Array,
        add_edit_fn: String,
    });

    const initial_all_fields = props.all_fields;
    const fields_display = props.fields_display;
    const toolbar_el = $(props.toolbar_el);
    //TODO Vue 3.5: useTemplateRef('component_root')
    const component_root = ref(null);
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

    function getSelectedField(key) {
        let selected_field = initial_all_fields[key];
        if (selected_field === undefined) {
            const opt = $(`select[name="new_field"] option[value="${key}"]`);
            if (opt.length > 0) {
                selected_field = {
                    text: opt.text(),
                    customfields_id: opt.attr('data-customfield-id') ?? -1,
                };
            }
        }
        return selected_field;
    }

    /**
     * Fetch the field preview for the given fields and update the fields in the sortable list
     * @param {{key: string, selected_field: {}}[]} fields
     */
    function appendFieldPreview(fields) {
        const payload = {
            action: 'get_field_placeholder',
            assetdefinitions_id: props.items_id,
            fields: []
        };

        fields.forEach(({key, selected_field}) => {
            if (!sortable_fields.has(key)) {
                sortable_fields.set(key, {
                    key: key,
                    label: selected_field.text ?? selected_field,
                    field_options: fields_display.find((field) => field.key === key)?.field_options ?? {},
                    customfields_id: selected_field.customfields_id ?? -1,
                });
            }

            const field_options = {};
            for (const [name, value] of Object.entries(sortable_fields.get(key).field_options)) {
                field_options[name] = value;
            }
            payload.fields.push({
                assetdefinitions_id: props.items_id,
                customfields_id: selected_field.customfields_id ?? -1,
                key: key,
                label: sortable_fields.get(key).label,
                type: selected_field.type ?? '',
                field_options: field_options,
            });
        });
        $.ajax({
            method: 'POST',
            url: `${CFG_GLPI.root_doc}/ajax/asset/assetdefinition.php`,
            data: payload
        }).then((data) => {
            if (typeof data !== 'object') {
                return;
            }
            fields.forEach(({key}) => {
                updateFieldPreview(key, data[key]);
            });
        });
    }

    function updateFieldPreview(key, data) {
        const placeholder_el = $(`<div>${data}</div>`);
        const sortable_field = sortable_fields.get(key);
        sortable_field.preview_html = placeholder_el.find('.field-container').html();
        sortable_field.label_classes = `${placeholder_el.find('label').attr('class')} cursor-grab`;
        sortable_field.field_classes = `${placeholder_el.find('.field-container').attr('class')} btn-group shadow-none`;
        sortable_field.wrapper_classes = `${placeholder_el.find('.form-field').attr('class')} flex-grow-1`;
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
                toolbar_el.find('select[name="new_field"]').val(key).trigger('change');
                selected_field = getSelectedField(key);
            }
            if (selected_field === undefined) {
                return;
            }
            preview_data.push({key: key, selected_field: selected_field});
        });
        appendFieldPreview(preview_data);
        // Clear the select2 value
        toolbar_el.find('select[name="new_field"]').val('').trigger('change');
    }

    function removeField(key) {
        // remove the field from sortable list
        sortable_fields.delete(key);
    }

    onMounted(() => {
        //for each field in fields_display, add it to the list using the template and slot
        appendField(fields_display.map((field) => field.key));

        const sortable_container = $('#sortable-fields');
        const new_field_dropdown = toolbar_el.find('select[name="new_field"]');

        sortable_container.on('dragenter', () => {
            const sort_el = $('.sortable-field.sortable-dragging');
            const classes_to_copy = sort_el.attr('class').split(' ')
                .filter((cls) => !['sortable-dragging'].includes(cls))
                .join(' ');
            sortable_container.find('.sortable-placeholder').attr('class', `sortable-placeholder ${classes_to_copy}`);
        });

        // add field action
        $('#add-field').on('click', () => {
            //get select2 value
            const field_key = new_field_dropdown.val();
            if (field_key && field_key !== 0 ) {
                appendField([field_key]);
            }
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
            const field_key = `custom_${form_data.get('name')}`;

            if (btn_submit.attr('name') === 'add') {
                new_field_dropdown.data('select2').dataAdapter.query('', (data) => {
                    data.results.forEach((result) => {
                        if (result.id === field_key) {
                            appendField([field_key], {[field_key]: result});
                        }
                    });
                });
            } else if (btn_submit.attr('name') === 'update') {
                // Reload preview
                appendField([field_key], {[field_key]: sortable_fields.get(field_key)});
            } else if (btn_submit.attr('name') === 'purge') {
                removeField(field_key);
            }
        });
    });

    watch(sortable_fields, () => {
        window.sortable('#sortable-fields', {
            items: '.sortable-field',
            forcePlaceholderSize: false,
        });
        // If only one field remains, disable the remove button
        $(component_root.value).find('.hide-field')
            .prop('disabled', sortable_fields.size === 1)
            .prop('title', sortable_fields.size === 1 ? __('Cannot remove the last field') : __('Hide'));

    }, {deep: true});
</script>

<template>
    <div class="col-12 col-xxl-12 flex-column" ref="component_root">
        <input type="hidden" name="_update_fields_display" value="1" />
        <input type="hidden" name="fields_display" value="" />
        <div class="d-flex flex-row flex-wrap flex-xl-nowrap">
            <div class="row flex-row align-items-start flex-grow-1">
                <div class="user-select-none row flex-row" id="sortable-fields">
                    <Field v-for="[field_key, sortable_field] of sortable_fields" :key="field_key"
                           :field_key="field_key" :customfields_id="sortable_field.customfields_id" :label_classes="sortable_field.label_classes"
                           :field_classes="sortable_field.field_classes" :wrapper_classes="sortable_field.wrapper_classes">
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
                        <template v-slot:field_preview v-if="sortable_field.preview_html">
                            <div v-html="sortable_field.preview_html" style="display: contents"></div>
                        </template>
                    </Field>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    :deep(.sortable-field .btn.hide-field:disabled) {
        pointer-events: auto;
    }
</style>
