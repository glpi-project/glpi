<script setup>
    /* global glpi_toast_info, glpi_toast_warning, glpi_toast_error */
    /**
     * @typedef ColumnMetadata
     * @property {boolean} protected If the column is protected from being deleted or modified.
     *      Typically seen for the "No status" column or other "virtual" columns.
     * @property {string} [header_color] The color that represents this column and that will be displayed in the header.
     * @property {string} [header_fg_color] The color of the text in the header.
     * @property {boolean} drop_only If cards can only be dropped into this column but not displayed in it.
     *      Typically seen for columns that may contain a lot of items is a completed state like the "Closed" column.
     * @property {string} [color_class] The CSS class that represents this column.
     * @property {string} name The name of the column
     * @property {{}[]} items The items that are in this column.
     */
    import Card from "./Card.vue";
    import {computed, ref} from "vue";
    import {Rights} from "./Rights.js";
    import {TeamBadgeProvider} from "./TeamBadgeProvider.js";
    import AddItemForm from './AddItemForm.vue';

    const props = defineProps({
        rights: {
            type: Rights,
            required: true
        },
        column_field_id: {
            type: String,
            required: true
        },
        column_id: {
            type: Number,
            required: true
        },
        /** @type {ColumnMetadata} */
        column_data: {
            type: Object,
            required: true
        },
        supported_itemtypes: {
            type: Object,
            required: true
        },
        team_badge_provider: {
            type: TeamBadgeProvider,
            required: true
        }
    });

    const emit = defineEmits([
        'kanban:column_fold', 'kanban:card_delete', 'kanban:card_restore', 'kanban:refresh',
        'kanban:column_hide', 'kanban:card_show_details'
    ]);

    const element_id = computed(() => {
        return `column-${props.column_field_id}-${props.column_id}`;
    });
    const bg_color = computed(() => {
        return props.column_data['header_color'] ?? 'transparent';
    });
    const text_color = computed(() => {
        return props.column_data['header_fg_color'] ?? '';
    });
    const card_count = computed(() => {
        return Object.values(props.column_data.items || {}).filter(item => {
            return !item._filtered_out;
        }).length;
    });

    const itemtypes_can_create = computed(() => {
        const all_itemtypes = props.supported_itemtypes;
        const can_create = {};
        for (const itemtype in all_itemtypes) {
            if (all_itemtypes[itemtype].allow_create && all_itemtypes[itemtype]['allow_bulk_add'] !== false) {
                can_create[itemtype] = all_itemtypes[itemtype];
            }
        }
        return can_create;
    });

    function toggleFolded() {
        emit('kanban:column_fold', {
            column_id: props.column_id,
            folded: !props.column_data.folded
        });
    }

    const cards_to_show = computed(() => {
        return Object.values(props.column_data.items || {}).filter(item => {
            return !item._filtered_out;
        });
    });

    function getIcon(card) {
        const itemtype = card.id.split('-').shift();
        return props.supported_itemtypes[itemtype].icon || '';
    }

    const opened_form_type = ref(null);
    const opened_form_data = ref({});

    /**
     * Close any open item form
     */
    function closeItemForms() {
        opened_form_type.value = null;
        opened_form_data.value = {};
    }

    /**
     * Add a new form to the Kanban column to add a new item of the specified itemtype.
     * @param {string} itemtype The itemtype that is being added
     * @param {boolean} [bulk=false] If the item is being added in bulk
     */
    function showAddItemForm(itemtype, bulk = false) {
        opened_form_type.value = 'AddItemForm';
        opened_form_data.value = {
            is_bulk: bulk,
            itemtype: itemtype,
            itemtype_data: structuredClone(props.supported_itemtypes[itemtype]),
        };
        if (bulk) {
            opened_form_data.value.itemtype_data.fields = {
                bulk_item_list: {
                    type: 'textarea',
                    value: '',
                }
            };
        }
    }

    async function addItem(e) {
        const values = {};
        values[props.column_field_id] = props.column_id;
        $.each(e.fields, (name, options) => {
            values[name] = options.value;
        });
        return $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                inputs: values,
                itemtype: opened_form_data.value.itemtype,
                action: opened_form_data.value.is_bulk ? 'bulk_add_item' : 'add_item',
            }
        }).done(() => {
            emit('kanban:refresh');
            const itemtype = opened_form_data.value.itemtype;
            const is_bulk = opened_form_data.value.is_bulk;
            closeItemForms();
            showAddItemForm(itemtype, is_bulk);
        }).always(() => {
            $.ajax({
                method: 'GET',
                url: (CFG_GLPI.root_doc + "/ajax/displayMessageAfterRedirect.php"),
                data: {
                    'get_raw': true
                }
            }).done((messages) => {
                $.each(messages, (level, level_messages) => {
                    $.each(level_messages, (index, message) => {
                        switch (parseInt(level)) {
                            case 1:
                                glpi_toast_error(message);
                                break;
                            case 2:
                                glpi_toast_warning(message);
                                break;
                            default:
                                glpi_toast_info(message);
                        }
                    });
                });
            });
        });
    }

    function scrollToForm() {
        const form = $(`#${element_id.value} .kanban-body .kanban-form`).get(0);
        if (form) {
            form.scrollIntoView(false);
        }
    }

    const drop_only_message = __('This column cannot support showing cards due to how many cards would be shown. You can still drag cards into this column.');
</script>

<template>
    <div :id="element_id" :class="`kanban-column card ${column_data.folded ? 'collapsed' : ''} ${column_data['_protected'] ? 'kanban-protected' : ''}`"
         :data-drop-only="`${column_data.drop_only ? 'true' : false}`">
        <header class="kanban-column-header">
            <div class="kanban-column-header-content p-2 pb-0">
                <span class="content-left">
                    <i v-if="rights.canModifyView()"
                       class="ti ti-caret-right-filled kanban-collapse-column cursor-pointer" :title="__('Toggle collapse')"
                       @click="toggleFolded()"></i>
                    <span :class="`kanban-column-title badge ${column_data.color_class || ''}`" v-text="column_data.name"
                          :style="`${bg_color ? 'background-color:' + bg_color : 'transparent'}; ${text_color ? 'color:' + text_color : 'color: var(--tblr-body-color)'}`"></span>
                </span>
                <span class="content-right">
                    <span class="kanban_nb badge bg-secondary text-secondary-fg" v-text="card_count"></span>
                    <span class="kanban-column-toolbar align-middle">
                        <template v-if="rights.canCreateItem() && (rights.getAllowedColumnsForNewCards().length === 0 || rights.getAllowedColumnsForNewCards().includes(column_id))">
                            <div class="dropdown d-inline-block">
                                <button type="button" :id="`kanban_add_${element_id}`"
                                        class="kanban-add ti ti-plus" :title="__('Add')"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside"></button>
                                <ul id="kanban-add-dropdown" class="kanban-dropdown dropdown-menu" role="menu">
                                    <li :id="`kanban-add-${itemtype}`" class="dropdown-item cursor-pointer"
                                        v-for="(data, itemtype) in itemtypes_can_create" :key="itemtype"
                                        @click.prevent="showAddItemForm(itemtype)">
                                        <span>{{ data.name }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="dropdown d-inline-block">
                                <button :id="`kanban_column_overflow_actions_${element_id}`"
                                        class="kanban-column-overflow-actions ti ti-dots"
                                        :title="__('More')" data-bs-toggle="dropdown" data-bs-auto-close="outside"></button>
                                <ul id="kanban-overflow-dropdown" class="kanban-dropdown dropdown-menu" role="menu">
                                    <li class="dropdown-trigger dropdown-item">
                                        <div class="dropdown dropend">
                                            <a href="#" class="w-100" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                                <i class="ti ti-list"></i>{{ __('Bulk add') }}
                                            </a>
                                            <ul id="kanban-bulk-add-dropdown" class="dropdown-menu" role="menu">
                                                <li :id="`kanban-bulk-add-${itemtype}`" class="dropdown-item cursor-pointer"
                                                    v-for="(data, itemtype) in itemtypes_can_create" :key="itemtype"
                                                    @click.prevent="showAddItemForm(itemtype, true)">
                                                    <span>{{ data.name }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li class="kanban-remove dropdown-item cursor-pointer" v-if="!column_data['_protected']"
                                        @click="emit('kanban:column_hide')">
                                        <span>
                                            <i class="ti ti-trash"></i>{{ __('Delete') }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </template>
                    </span>
                </span>
            </div>
        </header>
        <ul class="kanban-body card-body">
            <template v-if="column_data.drop_only">
                <li class="position-relative mx-auto mt-2" style="width: 250px"
                    v-html="drop_only_message">
                </li>
            </template>
            <template v-if="!column_data.folded">
                <Card v-for="card in cards_to_show" :key="card.id"
                      :id="card.id" :title="card.title" :card_content="card.content" :icon="getIcon(card)"
                      :metadata="card._metadata" :team="card._team" :title_tooltip="card.title_tooltip"
                      :read_only="card._readonly" :form_link="card._form_link" :rights="rights"
                      :team_badge_provider="team_badge_provider"
                      @kanban:card_delete="emit('kanban:card_delete', {card_id: card.id})"
                      @kanban:card_restore="emit('kanban:card_restore', {card_id: card.id})"
                      @kanban:card_show_details="emit('kanban:card_show_details', {card_id: card.id})"></Card>
                <AddItemForm v-if="opened_form_type === 'AddItemForm'" @kanban:add_item="addItem" :data="opened_form_data"
                             @vue:mounted="scrollToForm" @kanban:close_form="closeItemForms"></AddItemForm>
            </template>
        </ul>
    </div>
</template>

<style scoped lang="scss">
    :deep(.kanban-form), :deep(.kanban-item) {
        text-align: left;
        padding-left: 0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 10%);
        min-height: 50px;
        margin-top: 10px;
        border-radius: 5px;
        min-width: 250px;

        input {
            &[type="checkbox"] {
                margin-right: 5px;
            }

            &:not([type="checkbox"]) {
                display: block;
            }
        }

        .kanban-item-subtitle {
            padding: 5px 10px 0;
            font-style: italic;
            font-weight: normal;
        }

        .kanban-item-content {
            margin-bottom: 5px;
            padding: 0 10px;

            .kanban-core-content {
                display: flex;
                flex-wrap: wrap;
                margin: 10px 0;
            }
        }

        &.filtered-out {
            display: none;
        }

        .kanban-item-header {
            button {
                background-color: inherit;
                border: none;
                color: inherit;
            }

            .kanban-dropdown.dropdown-menu {
                a {
                    color: inherit;
                    text-decoration: none !important;
                }
            }

            .kanban-item-title .ti {
                float: none;

                &:last-of-type {
                    margin-right: 5px;
                }
            }

            padding: 5px 10px 0;
            font-weight: bold;

            a:hover {
                text-decoration: underline;
            }

            a {
                padding: unset;
            }
        }

        .kanban-item-team {
            display: flex;
            padding-right: 10px;
            padding-bottom: 10px;
            margin-right: 10px;

            @media (prefers-reduced-motion: no-preference) {
                &:hover {
                    margin-right: 0;

                    > span {
                        margin-right: -10px;

                        &:last-of-type {
                            margin-right: -5px;
                        }
                    }
                }
            }

            @media (prefers-reduced-motion) {
                margin-right: 0;

                > span {
                    margin-right: -10px !important;
                    &:last-of-type {
                        margin-right: -5px !important;
                    }
                }
            }

            > span {
                margin-right: -15px;
                border-radius: 50%;
                border: 3px solid var(--tblr-card-bg);
                box-sizing: content-box;
                min-height: 24px;

                &:first-of-type {
                    margin-left: auto;
                }

                img {
                    border-radius: 50%;
                }

                &.fa-stack {
                    width: 2em;
                }
            }
        }
    }

    .kanban-column {
        margin-right: 16px;
        width: 400px;
        height: 600px;
        border-radius: 5px;
        flex-direction: column;
        flex: 0 0 auto;
        text-align: center;
        border-top: 5px solid v-bind(bg_color);

        &[data-drop-only="true"] .kanban-body {
            background: #fffa90;
            color: #777620;
        }

        &.collapsed {
            min-width: unset;
            width: calc(1.2em + 20px);
            flex: 0 0 auto;

            .kanban-column-header {
                box-shadow: unset;

                .kanban-column-header-content {
                    flex-direction: column;

                    .content-left {
                        display: contents;
                    }

                    .kanban-collapse-column {
                        transform: rotate(90deg);
                        transform-origin: center;
                        display: inline-block;
                        margin: calc(50% - 8px) 0;
                        white-space: nowrap;
                    }

                    .kanban-column-title,
                    .kanban_nb {
                        writing-mode: vertical-lr;
                        margin-top: 10px;
                        margin-left: 0;
                        padding: 12px 3px;
                    }

                    .kanban-collapse-column {
                        margin: 0 0 8px;
                    }

                    .kanban-column-toolbar {
                        display: none;
                    }
                }
            }

            .kanban-body {
                display: none;
            }
        }

        .kanban-column-header {
            font-size: 1.2em;
            margin-bottom: 5px;

            button {
                background-color: inherit;
                border: none;
                color: inherit;
            }

            .kanban-column-header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .pointer {
                opacity: 0.3;

                &:hover {
                    opacity: 1;
                }
            }

            i.fas,
            i.fa-solid {
                cursor: pointer;
                flex: 0 1 auto;
                position: relative;

                // Increase click area to make it easier to collapse/expand boards
                &::after {
                    content: "";
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                }
            }

            .kanban-column-title {
                margin-left: 2px;
                padding: 3px 12px;
            }

            .kanban_nb {
                margin-left: 10px;
            }

            .kanban-column-toolbar {
                margin-left: auto;
                flex-direction: column;
                flex: 0 1 auto;

                i {
                    margin-left: 0.2rem;
                }
            }
        }

        .kanban-body {
            min-height: 150px;
            padding: 0 5px;
            height: calc(100% - (1.2em + 35px));
            overflow-y: auto;
            overflow-x: hidden;
            list-style: none;

            .kanban-add-form {
                padding-top: 10px !important;
                padding-bottom: 10px !important;

                input {
                    margin: 8px 0 0;
                }

                textarea {
                    margin: 8px 0;
                }
            }
        }
    }
</style>
