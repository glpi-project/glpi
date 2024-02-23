<script setup>
    /* global hotkeys, typewatch, initTooltips */
    import {ref, computed, onMounted, watch} from 'vue';

    const props = defineProps({
        current_entity: {
            type: String,
            required: true,
        },
        current_entity_short: {
            type: String,
            required: true,
        },
        rand: {
            type: String,
            required: false,
        },
        target: {
            type: String,
            required: true,
        },
    });

    const rand = props.rand || Math.floor((Math.random() * 10000));
    /** The full label for the currently selected entity */
    let current_entity_label = props.current_entity;
    /** The short label for the currently selected entity */
    let current_entity_short_label = props.current_entity_short;

    // Translated strings (gettext has trouble extracting these from the template)
    const recursive_icon = `<i class="fas fa-angle-double-down" title="${__("+ sub-entities")}"></i>`;
    let recursive_message = __("Click on the %s icon to load the elements of the selected entity, as well as its sub-entities.");
    recursive_message = recursive_message.replace('%s', recursive_icon);
    const is_mac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const keyboard_shortcut = __(is_mac ? '⌥ (option) + ⌘ (command) + E' : 'Ctrl + Alt + E');
    const shortcut_message = __("Tip: You can call this modal with %s keys combination").replace('%s', '<kbd>' + keyboard_shortcut + '</kbd>');
    const select_entity_message = __("Select the desired entity");
    const clear_search_message = __("Clear search");
    const select_all_message = __("Select all");
    const search_placeholder_message = __("Search entity");

    // when the shortcut for entity form is called
    hotkeys('ctrl+alt+e, option+command+e', async function(e) {
        e.stopPropagation();
        e.preventDefault();
        $('.user-menu-dropdown-toggle').dropdown('show');
        await new Promise(r => setTimeout(r, 100));
        $('.entity-dropdown-toggle').dropdown('show');
        $('input[name=entsearchtext]').filter(":visible")[0].focus();
    });

    const entity_data = ref([]);
    $.ajax({
        url: CFG_GLPI.root_doc + "/ajax/entitytreesons.php",
        type: "GET",
    }).then((data) => {
        // Preprocess the data to add hints about the level and parent(s) of each entity
        /**
         * The index of the entity as if it were in a flat list.
         * This is used to order the list of visible entities in case they are added to the visible array out of order.
         */
        let universal_order_i = 0;
        function preprocess(data, level = 0, parents = []) {
            data.forEach((item) => {
                item.level = level;
                // Save array of parent objects (will be references rather than copies)
                item.parents = parents;
                item.universal_order = universal_order_i++;
                if (item.children) {
                    preprocess(item.children, level + 1, [...parents, item]);
                }
                if (item.folder && item.expanded === undefined) {
                    // If the folder has no expanded state specified, it is collapsed
                    item.expanded = 'false';
                }
            });
        }
        preprocess(data);
        entity_data.value = data;
    });
    const search_filter = ref('');

    /** The start index of the visible items in the entity tree */
    const start = ref(0);
    /** The maximum number of items to display at once */
    const max_items = 15;

    /**
     * Function for enumerating tree data and performing an action on each node.
     * @param data The tree data
     * @param level The current level in the tree
     * @param parent The parent of the current node
     * @param visit_node The function to call on each node
     */
    function walkTree(data, level, parent, visit_node) {
        for (let i = 0; i < data.length; i++) {
            const entity = data[i];
            visit_node(entity, level, parent);
            if (entity.children) {
                walkTree(entity.children, level + 1, entity, visit_node);
            }
        }
    }

    /**
     * Watching for changes in the search filter to update the hidden/expanded state of the entities.
     */
    watch(search_filter, (new_value) => {
        walkTree(entity_data.value, 0, null, (entity) => {
            const match = new_value.length === 0 || entity.title.toLowerCase().includes(new_value.toLowerCase());
            if (!entity.folder) {
                entity.hidden = !match;
            } else {
                entity.expanded = match ? 'true' : 'false';
            }
            if (match) {
                for (let i = 0; i < entity.parents.length; i++) {
                    entity.parents[i].expanded = 'true';
                }
            }
        });
    });

    /**
     * The total number of non-hidden entries (the entries that could be shown simply by scrolling).
     * This is NOT the number of entries which are actually in the DOM currently.
     */
    let total_filtered = ref(0);

    /**
     * The visible entity data based on the start index and the maximum number of items to display at once.
     * Also takes into account the search filter, expanded/collapsed nodes, etc.
     */
    const visible_entity_data = computed(() => {
        const visible = [];

        walkTree(entity_data.value, 0, null, (entity) => {
            if (entity.hidden) {
                return;
            }
            visible.push(entity);
        });

        // Sort the visible entities by their universal order
        visible.sort((a, b) => a.universal_order - b.universal_order);
        // Remove hidden entities
        for (let i = 0; i < visible.length; i++) {
            if (visible[i].hidden) {
                visible.splice(i, 1);
                i--;
            } else {
                for (let j = 0; j < visible[i].parents.length; j++) {
                    const p = visible[i].parents[j];
                    if (p.folder && p.expanded !== 'true') {
                        visible.splice(i, 1);
                        i--;
                        break;
                    }
                }
            }
        }
        return visible;
    });

    watch(visible_entity_data, (new_value) => {
        // These actions are performed outside the computed property for performance reasons and to keep the computed getter side-effect free
        total_filtered.value = new_value.length;
        updateFakeScrollbar();
    });

    // This computed property is separate from the visible_entity_data for performance reasons
    // We can avoid recalculating the visible entities every time the start index changes
    const visible_in_dom = computed(() => {
        if (visible_entity_data.value === undefined) {
            return [];
        }
        return visible_entity_data.value.slice(start.value, start.value + max_items);
    });

    onMounted(() => {
        const entity_tree = $(`#tree_entity${rand}`);
        // Handle scrolling to change the start index of the visible items
        entity_tree.on('wheel', function (e) {
            const pos_delta = e.originalEvent.deltaY / 120;
            let new_start = start.value + pos_delta;
            if (new_start < 0) {
                new_start = 0;
            }
            if (new_start > total_filtered.value - max_items) {
                new_start = total_filtered.value - max_items;
            }
            start.value = new_start;
        });
        // Clicking a folder icon will toggle the expanded state of the folder
        entity_tree.on('click', '.ti-folder, .ti-folder-open', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const cell = $(e.target).closest('td');
            const entity_id = cell.attr('data-entity-id');

            // Need to walk the tree to find the entity and toggle its expanded state.
            function toggleExpanded(data, id) {
                for (let i = 0; i < data.length; i++) {
                    const entity = data[i];
                    if (entity.key === id) {
                        entity.expanded = entity.expanded === 'true' ? 'false' : 'true';
                        return;
                    }
                    if (entity.children) {
                        toggleExpanded(entity.children, parseInt(id));
                    }
                }
            }
            toggleExpanded(entity_data.value, parseInt(entity_id));
        });

        // Update the start index when the fake scrollbar is used
        $(`#verticalScrollbar-${rand}`).on('scroll', (e) => {
            const fake_scrollbar = $(e.target);
            const item_height = fake_scrollbar.find('.fake-scrollbar-inner').height() / total_filtered.value;
            start.value = Math.round(fake_scrollbar.scrollTop() / item_height);
        });
    });

    function updateFakeScrollbar() {
        const fake_scrollbar = $(`#verticalScrollbar-${rand}`);
        const item_height = 22;
        fake_scrollbar.find('.fake-scrollbar-inner').height(item_height * total_filtered.value);
        fake_scrollbar.scrollTop(item_height * start.value);
    }

    // Update fake scrollbar when the start index changes
    watch(start, () => {
        updateFakeScrollbar();
    });
</script>

<template>
    <div>
        <a href="#" class="dropdown-item dropdown-toggle entity-dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" :title="current_entity_label">
            <i class="fa-fw ti ti-stack"></i>
            <span v-text="current_entity_short_label"></span>
        </a>
        <div class="dropdown-menu p-3">
            <h3 v-text="select_entity_message"></h3>
            <div class="alert alert-info" role="alert" v-html="shortcut_message"></div>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i>
                <span class="ms-2">
                    <span v-html="recursive_message"></span>
                </span>
            </div>

            <form :id="`entsearchform${rand}`">
                <div class="input-group">
                    <input type="text" class="form-control" name="entsearchtext" :id="`entsearchtext${rand}`"
                           :placeholder="search_placeholder_message" autocomplete="off" v-model="search_filter">
                    <a class="btn btn-icon btn-outline-secondary" href="#" :id="`entsearchtext${rand}_clear`"
                       :title="clear_search_message" data-bs-toggle="tooltip" data-bs-placement="top"
                       @click="search_filter = ''">
                        <i class="ti ti-x"></i>
                    </a>
                    <a :href="`${target}?active_entity=all`" class="btn btn-secondary"
                       :title="select_all_message" data-bs-toggle="tooltip" data-bs-placement="top">
                        <i class="ti ti-eye"></i>
                    </a>
                </div>
            </form>

            <div class="fancytree-grid-container flexbox-item-grow entity_tree" :style="`height: ${22 * max_items}px`">
                <table :id="`tree_entity${rand}`">
                    <colgroup>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="parent-path"></th>
                        </tr>
                    </thead>
                    <tbody class="overflow-auto">
                        <tr v-for="entity in visible_in_dom" :key="entity.key" :class="entity.selected ? 'fw-bold' : ''">
                            <td :style="{paddingLeft: entity.level * 20 + 'px'}" :data-entity-id="entity.key">
                                <span v-if="entity.folder" class="me-1 cursor-pointer">
                                    <i v-if="entity.expanded === 'true'" class="ti ti-folder-open"></i>
                                    <i v-else class="ti ti-folder"></i>
                                </span>
                                <span v-html="entity.title"></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div :id="`verticalScrollbar-${rand}`" class="position-absolute overflow-auto" style="height:100%; width: 16px; top: 0; right: 0;">
                    <div class="fake-scrollbar-inner" style="height: 100%; width: 100%;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
