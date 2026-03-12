<script setup>
    import {computed, onMounted, ref, useTemplateRef, watch} from "vue";
    import {useEntitySelector} from "./useEntitySelector.js";

    const props = defineProps({
        current_entity: {
            type: String,
            required: true,
        },
        current_entity_short: {
            type: String,
            required: true,
        },
        csrf_token: {
            type: String,
            required: true,
        },
    });

    const search_input = useTemplateRef('entsearchtext');
    const entity_dropdown_toggle = useTemplateRef('entity_dropdown_toggle');
    const search_filter = ref('');
    const keyboard_shortcut = navigator.platform.toUpperCase().indexOf('MAC') >= 0 ? __('⌥ (option) + ⌘ (command) + E') : __('Ctrl + Alt + E');
    const shortcut_message = __("Tip: You can call this modal with %s keys combination").replace('%s', '<kbd>' + keyboard_shortcut + '</kbd>');
    const max_items = 15;
    const indent_size = 20;

    /** The start index of the visible items in the tree */
    const start = ref(0);
    const fake_scrollbar = useTemplateRef('fake_scrollbar');
    /**
     * The total number of non-hidden entries (the entries that could be shown simply by scrolling).
     * This is NOT the number of entries which are actually in the DOM currently.
     */
    let total_filtered = ref(0);

    const {
        loading,
        tree_data,
        loadTreeData,
        changeFullStructure: doChangeFullStructure,
        changeEntity: doChangeEntity
    } = useEntitySelector({
        csrf_token: props.csrf_token,
    });

    /**
     * Function for enumerating tree data and performing an action on each node.
     * @param data The tree data
     * @param level The current level in the tree
     * @param parent The parent of the current node
     * @param visit_node The function to call on each node
     */
    function walkTree(data, level, parent, visit_node) {
        for (let i = 0; i < data.length; i++) {
            const node = data[i];
            if (visit_node(node, level, parent) === false) {
                return;
            }
            if (node.children.length) {
                walkTree(node.children, level + 1, node, visit_node);
            }
        }
    }

    /**
     * The visible tree nodes based on the start index and the maximum number of items to display at once.
     * Also takes into account the search filter, expanded/collapsed nodes, etc.
     */
    const visible_tree_data = computed(() => {
        const visible = [];

        walkTree(tree_data.value, 0, null, (node) => {
            let hidden = node.hidden;
            if (!hidden) {
                for (let j = 0; j < node.parents.length; j++) {
                    const p = node.parents[j];
                    if (p.children.length && !p.expanded) {
                        hidden = true;
                        break;
                    }
                }
                if (!hidden) {
                    visible.push(node);
                }
            }
        });

        // Sort the visible nodes by their universal order
        visible.sort((a, b) => a.universal_order - b.universal_order);
        return visible;
    });

    watch(visible_tree_data, (new_value) => {
        // These actions are performed outside the computed property for performance reasons and to keep the computed getter side-effect free
        total_filtered.value = new_value.length;
        updateFakeScrollbar();
    }, {flush: 'post'});

    // This computed property is separate from the visible_tree_data for performance reasons
    // We can avoid recalculating the visible nodes every time the start index changes
    const visible_in_dom = computed(() => {
        if (visible_tree_data.value === undefined) {
            return [];
        }
        return visible_tree_data.value.slice(start.value, start.value + max_items);
    });

    function updateFakeScrollbar() {
        const item_height = 32;
        fake_scrollbar.value.querySelector('.fake-scrollbar-inner').style.height = item_height * total_filtered.value + 'px';
        fake_scrollbar.value.scrollTop = item_height * start.value;
    }

    // Update fake scrollbar when the start index changes
    watch(start, () => {
        updateFakeScrollbar();
    });

    /**
     * Watching for changes in the search filter to update the hidden/expanded state of the nodes.
     */
    watch(() => search_filter, (new_value) => {
        walkTree(tree_data.value, 0, null, (node) => {
            const match = new_value.length === 0 || node.label.toLowerCase().includes(new_value.toLowerCase());
            if (!node.children.length) {
                node.hidden = !match;
            } else {
                node.expanded = match;
            }
            if (match) {
                for (let i = 0; i < node.parents.length; i++) {
                    node.parents[i].expanded = true;
                }
            }
        });
        // Reset start index
        start.value = 0;
    });

    function onListScroll(e) {
        // If there are less items than the max_items, don't scroll
        if (total_filtered.value <= max_items) {
            return;
        }

        const pos_delta = e.deltaY / 120;
        let new_start = start.value + pos_delta;
        if (new_start < 0) {
            new_start = 0;
        }
        if (new_start > total_filtered.value - max_items) {
            new_start = total_filtered.value - max_items;
        }
        start.value = new_start;
    }

    function onExpandToggleClick(node) {
        node.expanded = !node.expanded;
    }

    const selected_nodes = computed(() => {
        let selected = null;
        walkTree(tree_data.value, 0, null, (node) => {
            if (node.selected) {
                selected = node;
                return false;
            }
        });
        return [selected.key, ...selected.parents.map((parent) => parent.key)];
    });


    window.hotkeys('ctrl+alt+e, option+command+e', async function(e) {
        e.stopPropagation();
        e.preventDefault();
        $('.user-menu-dropdown-toggle:visible').dropdown('show');
        await new Promise(r => setTimeout(r, 100));
        $('.user-menu-dropdown-toggle:visible').parent().find('.entity-dropdown-toggle').dropdown('show');
        onShowSelector();
    });

    function onShowSelector() {
        if (loading.value || tree_data.value.length > 0) {
            return;
        }
        search_input.value.focus();
        loadTreeData();
    }

    onMounted(() => {
        entity_dropdown_toggle.value.addEventListener('show.bs.dropdown', onShowSelector);
    });

    function changeFullStructure() {
        doChangeFullStructure().then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                window.glpi_toast_error(__('An error occurred while changing the entity. Please try again.'));
            }
        });
    }

    function changeEntity(entity_id, is_recursive) {
        doChangeEntity(entity_id, is_recursive).then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                window.glpi_toast_error(__('An error occurred while changing the entity. Please try again.'));
            }
        });
    }
</script>

<template>
    <div>
        <a ref="entity_dropdown_toggle" href="#" class="dropdown-item dropdown-toggle entity-dropdown-toggle" data-bs-toggle="dropdown"
           data-bs-auto-close="outside" :title="current_entity" :aria-label="__('Select the desired entity')">
            <i class="fa-fw ti ti-stack"></i>
            <span v-text="current_entity_short"></span>
        </a>
        <div class="dropdown-menu p-3">
            <h3>{{ __('Select the desired entity') }}</h3>
            <div class="alert alert-info d-block" v-html="shortcut_message"></div>
            <div class="input-group">
                <input ref="entsearchtext" type="text" class="form-control" name="entsearchtext" :placeholder="__('Search entities')"
                       autocomplete="off" v-model="search_filter">
                <button class="btn btn-icon btn-outline-secondary" :title="__('Clear search')" :aria-label="__('Clear search')"
                   data-bs-toggle="tooltip" data-bs-placement="top"
                   @click="search_filter = ''">
                    <i class="ti ti-x"></i>
                </button>
                <button class="btn btn-secondary" :title="__('Select all')" :aria-label="__('Select all')"
                        data-bs-toggle="tooltip" data-bs-placement="top" @click="changeFullStructure">
                    <i class="ti ti-eye"></i>
                </button>
            </div>

            <div v-if="!loading" class="flexbox-item-grow mt-2 position-relative" :style="`height: calc(30px + ${32 * max_items}px)`">
                <div class="w-100 h-100 overflow-x-auto overflow-y-hidden">
                    <ul class="w-100 list-group rounded-0" @wheel.prevent.stop="onListScroll">
                        <li v-for="node in visible_in_dom" :key="node.key" :class="`list-group-item p-0 border-0 cursor-pointer`" :style="`${node.selected ? 'background-color: var(--tblr-primary)' : ''}`">
                            <div :style="{paddingLeft: node.level * indent_size + 'px'}" :data-node-id="node.key" class="text-nowrap d-flex align-items-center pt-1">
                                <button v-if="node.children.length" :title="node.expanded ? __('Collapse') : __('Expand')"
                                        :aria-label="node.expanded ? __('Collapse') : __('Expand')"
                                        class="btn btn-ghost-secondary btn-sm btn-icon p-1 cursor-pointer collapse-item"
                                        @click.prevent.stop="onExpandToggleClick(node)">
                                    <i :class="node.expanded ? 'ti ti-chevron-down' : 'ti ti-chevron-right'"></i>
                                </button>
                                <div v-else style="width: 25px"></div>
                                <div role="button" :class="selected_nodes.includes(node.key) ? 'fw-bold' : ''" @click.prevent.stop="changeEntity(node.key, false)" class="d-flex align-items-center">
                                    <i :hidden="node.children.length === 0" aria-hidden="true"
                                       class="ti me-1" :class="node.expanded ? 'ti-folder-open' : 'ti-folder'"></i>
                                    {{ node.label }}
                                </div>
                                <button v-if="node.children.length" class="btn btn-ghost-secondary btn-sm btn-icon p-1"
                                        :title="__('Select this entity and all its children')"
                                        :aria-label="__('Select this entity and all its children')"
                                        @click.prevent.stop="changeEntity(node.key, true)" data-bs-toggle="tooltip" data-bs-placement="top">
                                    <i class="ti ti-chevrons-down"></i>
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
                <div ref="fake_scrollbar" class="position-absolute overflow-auto" style="height:100%; width: 16px; top: 0; right: 0;">
                    <div class="fake-scrollbar-inner" style="height: 100%; width: 100%;">
                    </div>
                </div>
            </div>
            <div v-else class="d-flex justify-content-center align-items-center h-100 mt-4">
                <div class="spinner-border" role="status" aria-hidden="true"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .dropdown-menu {
        width: 450px;
        max-width: 85vw;
    }

    ul li {
        height: 32px;
    }

    ul li button {
        min-width: 0;
        min-height: 0;
    }
</style>
