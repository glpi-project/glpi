<script setup>
    import {onMounted, computed, ref, reactive, watch, useTemplateRef, nextTick} from 'vue';
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

    const initial_fields = ref(props.all_fields);
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
                acceptFrom: false,
            });
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
                selected_field = initial_fields.value[key];
            }
            if (selected_field === undefined) {
                return;
            }
            preview_data.push({key: key, selected_field: selected_field});
        });
        preview_data.forEach(({key, selected_field}) => {
            if (!sortable_fields.has(key)) {
                const next_order_position = sortable_fields.size;
                sortable_fields.set(key, {
                    key: key,
                    label: selected_field.text ?? selected_field,
                    field_options: fields_display.find((field) => field.key === key)?.field_options ?? {},
                    customfields_id: selected_field.customfields_id ?? -1,
                    is_active: selected_field.is_active ?? true,
                    order: fields_display.find((field) => field.key === key)?.order ?? next_order_position,
                });
            }
        });
        refreshSortables();
    }

    function removeField(key) {
        // remove the field from sortable list
        sortable_fields.get(key).is_active = false;
        refreshSortables();
    }

    /**
     * Refresh the data in the all_fields object
     */
    function refreshAllFields() {
        const url = `${CFG_GLPI.root_doc}/ajax/asset/assetdefinition.php?action=get_all_fields&assetdefinitions_id=${props.items_id}`;
        $.get(url, (data) => {
            const new_fields = {};
            $.each(data['results'], (key, field) => {
                new_fields[field.id] = field;
            });
            appendField(Object.keys(new_fields), new_fields)
            refreshSortables();
        });
    }

    onMounted(() => {
        $.each(initial_fields.value, (key, field) => {
            const field_data = field;
            field_data.is_active = fields_display.find((field) => field.key === key) !== undefined;
            appendField([key], {[key]: field_data});
        });

        const sortable_container = $('#sortable-fields');

        sortable_container.on('dragenter', () => {
            const sort_el = $('.sortable-field.sortable-dragging');
            const classes_to_copy = sort_el.attr('class').split(' ')
                .filter((cls) => !['sortable-dragging'].includes(cls))
                .join(' ');
            sortable_container.find('.sortable-placeholder').attr('class', `sortable-placeholder ${classes_to_copy} px-2`);
            if (sortable_container.find('.sortable-placeholder .sortable-placeholder-inner').length === 0) {
                sortable_container.find('.sortable-placeholder').append(`<div class="sortable-placeholder-inner"></div>`);
            }
        });

        // Change is_active property of the field when done dragging
        sortable_container.on('sortupdate', (e) => {
            const origin_container = e.originalEvent.detail.origin.container;
            const destination_container = e.originalEvent.detail.destination.container;
            // Do nothing here if the origin and destination are the same
            if (origin_container === destination_container) {
                return;
            }
            const moved_field = $(e.originalEvent.detail.item);
            const moved_to_displayed = $(destination_container).attr('id') === 'sortable-fields';

            if (moved_to_displayed) {
                const sortable_field = sortable_fields.get(moved_field.attr('data-key'));
                sortable_field.is_active = true;
                // Recalculate the order of the fields to match the index in the displayed list
                sortable_fields.forEach((field) => {
                    field.order = $(component_root.value).find('.sortable-field').index($(`.sortable-field[data-key="${CSS.escape(field.key)}"]`));
                });
            } else {
                removeField(moved_field.attr('data-key'));
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
                    if (Array.isArray(value)) {
                        value.forEach((item) => {
                            url_params.append(`field_options[${name}][]`, item);
                        })
                    } else {
                        url_params.append(`field_options[${name}]`, value);
                    }
                }
                const url = `${CFG_GLPI.root_doc}/ajax/asset/assetdefinition.php?${url_params}`;
                window.glpi_ajax_dialog({
                    id: 'core_field_options_editor',
                    modalclass: 'modal-xl',
                    appendTo: `#${CSS.escape($(sortable_fields_container.value).attr('id'))}`,
                    title: _.escape(field_el.text()),
                    url: url,
                    buttons: [
                        {
                            id: 'cancel_core_field_options',
                            label: __('Cancel'),
                            class: 'btn-ghost-secondary',
                        },
                        {
                            id: 'save_core_field_options',
                            label: _x('button', 'Save'),
                            class: 'btn-primary',
                        },
                    ]
                });
            } else {
                window[props.add_edit_fn](field_id);
                $('#customfield_form_container_modal .modal-title').text(field_el.text());
            }
        }).on('click', '.hide-field', (e) => {
            const field_key = $(e.target).closest('.sortable-field').attr('data-key');
            removeField(field_key);
        }).on('click', '.purge-field', (e) => {
            // Only custom fields can be purged
            const field_key = $(e.target).closest('.sortable-field').attr('data-key');
            const custom_fields_id = $(e.target).closest('.sortable-field').attr('data-customfield-id');

            // Submit a form via AJAX to delete the field
            $.ajax({
                url: `${CFG_GLPI.root_doc}/ajax/asset/customfield.php`,
                type: 'POST',
                data: {
                    customfielddefinitions_id: custom_fields_id,
                    action: 'purge_field'
                },
                success: () => {
                    sortable_fields.delete(field_key);
                    refreshSortables();
                },
                complete: () => {
                    displayAjaxMessageAfterRedirect();
                }
            });
        });

        sortable_fields_container.value.on('click', '#save_core_field_options', () => {
            const key_field = $('#core_field_options_editor form input[name="key"]');
            const field_key = key_field.val();
            key_field.remove();
            const field_options = $('#core_field_options_editor form').serializeArray();
            const sortable_field = sortable_fields.get(field_key);

            sortable_field.field_options = {};
            field_options.forEach((option) => {
                let name = option.name.replace('field_options[', '');
                if (name.endsWith('[]')) { // We are in array, we store the key as the value
                    name = name.slice(0, -3);
                    if (!Array.isArray(sortable_field.field_options[name])) {
                        sortable_field.field_options[name] = [];
                    }
                    sortable_field.field_options[name].push(option.value);
                } else { // OG code, remove the ]
                    name = name.slice(0, -1);
                    sortable_field.field_options[name] = option.value;
                }
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
                appendField([field_key], {[field_key]: {
                    text: form_data.get('label'),
                }});
                const sortable_field = sortable_fields.get(field_key);

                sortable_field.field_options = {};
                form_data.entries().forEach(([name, value]) => {
                    if (name.startsWith('field_options[')) {
                        const is_array = name.endsWith('[]');
                        const option_name = name.replace('field_options[', '').replace(/\[\]/, '');
                        if (is_array) {
                            sortable_field.field_options[option_name] = sortable_field.field_options[option_name] ?? [];
                            if (!Array.isArray(sortable_field.field_options[option_name])) {
                                sortable_field.field_options[option_name] = [sortable_field.field_options[option_name]];
                            }
                            sortable_field.field_options[option_name].push(value);
                        } else {
                            sortable_field.field_options[option_name] = value;
                        }
                    }
                });
            } else if (btn_submit.attr('name') === 'purge') {
                removeField(field_key);
            }
        });
    });

    const active_fields = computed(() => {
        const ordered_active_fields = [];
        [...sortable_fields].filter(([key, field]) => field.is_active)
            .sort((a, b) => a[1].order - b[1].order)
            .forEach(([key, field]) => {
                ordered_active_fields.push({...field, key: key});
            });
        return ordered_active_fields;
    });

    const inactive_fields = computed(() => {
        return new Map([...sortable_fields].filter(([key, field]) => !field.is_active));
    });

    watch(active_fields, () => {
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
                    <div class="user-select-none row flex-row p-2" id="sortable-fields" style="min-height: 50px">
                        <Field v-for="sortable_field of active_fields" :key="sortable_field.key"
                               :field_key="sortable_field.key" :customfields_id="sortable_field.customfields_id" :field_options="sortable_field.field_options"
                               :is_active="sortable_field.is_active">
                            <template v-slot:field_label>{{ sortable_field.label }}</template>
                            <template v-slot:field_markers>
                                <span v-if="parseInt(sortable_field.field_options.required ?? 0) === 1" class="required">*</span>
                                <i v-if="parseInt(sortable_field.field_options.readonly ?? 0) === 1" class="ti ti-pencil-off ms-2" :title="__('Readonly')"></i>
                            </template>
                            <template v-slot:field_options>
                                <template v-for="(field_option_value, field_option_name) in sortable_field.field_options" :key="field_option_name">
                                    <input
                                        v-if="Array.isArray(field_option_value)"
                                        v-for="value in field_option_value"
                                        type="hidden"
                                        :name="`field_options[${sortable_field.key}][${field_option_name}][]`"
                                        :value="value"
                                    />
                                    <input
                                        v-else
                                        type="hidden"
                                        :name="`field_options[${sortable_field.key}][${field_option_name}]`"
                                        :value="field_option_value"
                                    />
                                </template>
                            </template>
                        </Field>
                    </div>
                </div>
                <Sidebar :inactive_fields="inactive_fields" :add_edit_fn="add_edit_fn"></Sidebar>
            </div>
        </div>
    </div>
</template>

<style scoped>
    :deep(.sortable-field .btn.hide-field:disabled) {
        pointer-events: auto;
    }
    :deep(.sortable-placeholder) {
        background: unset;
        border: unset;
        height: 38px;
        .sortable-placeholder-inner {
            border: 2px dashed #dad55e;
            background: #fff99038;
            height: 100%;
        }
    }
</style>
