<script setup>
    import {onBeforeMount, ref, computed, watch, onMounted} from "vue";

    const props = defineProps({
        tree: {
            type: Object,
            required: true
        },
        rand: {
            type: String,
            required: false
        },
        /** The maximum number of items to display at once */
        max_items: {
            type: Number,
            required: false,
            default: 15
        },
        /** If true, the nodes will only be added to the DOM when they would be visible on the screen */
        virtual_dom: {
            type: Boolean,
            required: false,
            default: false
        },
        search_filter: {
            type: String,
            required: false,
            default: ''
        }
    });

    const indent_size = 20;

    const tree_data = ref([]);
    /** The start index of the visible items in the tree */
    const start = ref(0);

    /**
     * The total number of non-hidden entries (the entries that could be shown simply by scrolling).
     * This is NOT the number of entries which are actually in the DOM currently.
     */
    let total_filtered = ref(0);

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
            visit_node(node, level, parent);
            if (node.children) {
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
            if (node.hidden) {
                return;
            }
            visible.push(node);
        });

        // Sort the visible nodes by their universal order
        visible.sort((a, b) => a.universal_order - b.universal_order);
        // Remove hidden nodes
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

    watch(visible_tree_data, (new_value) => {
        // These actions are performed outside the computed property for performance reasons and to keep the computed getter side-effect free
        total_filtered.value = new_value.length;
        updateFakeScrollbar();
    });

    // This computed property is separate from the visible_tree_data for performance reasons
    // We can avoid recalculating the visible nodes every time the start index changes
    const visible_in_dom = computed(() => {
        if (visible_tree_data.value === undefined) {
            return [];
        }
        return visible_tree_data.value.slice(start.value, start.value + props.max_items);
    });

    function updateFakeScrollbar() {
        const fake_scrollbar = $(`#verticalScrollbar-${props.rand}`);
        const item_height = 22;
        fake_scrollbar.find('.fake-scrollbar-inner').height(item_height * total_filtered.value);
        fake_scrollbar.scrollTop(item_height * start.value);
    }

    // Update fake scrollbar when the start index changes
    watch(start, () => {
        updateFakeScrollbar();
    });

    /**
     * Watching for changes in the search filter to update the hidden/expanded state of the nodes.
     */
    watch(() => props.search_filter, (new_value) => {
        walkTree(tree_data.value, 0, null, (node) => {
            const match = new_value.length === 0 || node.title.toLowerCase().includes(new_value.toLowerCase());
            if (!node.folder) {
                node.hidden = !match;
            } else {
                node.expanded = match ? 'true' : 'false';
            }
            if (match) {
                for (let i = 0; i < node.parents.length; i++) {
                    node.parents[i].expanded = 'true';
                }
            }
        });
    });

    //TODO This watch can be made a "Once watcher" in Vue 3.4
    watch(() => props.tree, (new_value, old_value) => {
        if (old_value.length !== 0) {
            return;
        }
        // Preprocess the data to add hints about the level and parent(s) of each node
        /**
         * The index of the node as if it were in a flat list.
         * This is used to order the list of visible nodes in case they are added to the visible array out of order.
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
        preprocess(new_value);
        tree_data.value = new_value;
    }, { flush: 'post' });

    onMounted(() => {
        const tree_el = $(`#tree_data${props.rand}`);
        // Handle scrolling to change the start index of the visible items
        tree_el.on('wheel', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const pos_delta = e.originalEvent.deltaY / 120;
            let new_start = start.value + pos_delta;
            if (new_start < 0) {
                new_start = 0;
            }
            if (new_start > total_filtered.value - props.max_items) {
                new_start = total_filtered.value - props.max_items;
            }
            start.value = new_start;
        });
        // Clicking a folder icon will toggle the expanded state of the folder
        tree_el.on('click', '.ti-folder, .ti-folder-open', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const cell = $(e.target).closest('td');
            const node_id = cell.attr('data-node-id');

            // Need to walk the tree to find the node and toggle its expanded state.
            function toggleExpanded(data, id) {
                for (let i = 0; i < data.length; i++) {
                    const node = data[i];
                    if (node.key === id) {
                        node.expanded = node.expanded === 'true' ? 'false' : 'true';
                        return;
                    }
                    if (node.children) {
                        toggleExpanded(node.children, parseInt(id));
                    }
                }
            }
            toggleExpanded(tree_data.value, parseInt(node_id));
        });

        // Update the start index when the fake scrollbar is used
        $(`#verticalScrollbar-${props.rand}`).on('scroll', (e) => {
            const fake_scrollbar = $(e.target);
            const item_height = fake_scrollbar.find('.fake-scrollbar-inner').height() / total_filtered.value;
            start.value = Math.round(fake_scrollbar.scrollTop() / item_height);
        });
    });
</script>

<template>
    <div class="flexbox-item-grow data_tree" :style="`height: ${22 * max_items}px`">
        <table :id="`tree_data${rand}`">
            <colgroup>
                <col>
            </colgroup>
            <thead>
                <tr>
                    <th class="parent-path"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="node in visible_in_dom" :key="node.key" :class="node.selected ? 'fw-bold' : ''">
                    <td :style="{paddingLeft: node.level * indent_size + 'px'}" :data-node-id="node.key">
                        <span v-if="node.folder" class="me-1 cursor-pointer">
                            <i v-if="node.expanded === 'true'" class="ti ti-folder-open"></i>
                            <i v-else class="ti ti-folder"></i>
                        </span>
                        <span v-html="node.title"></span>
                    </td>
                </tr>
            </tbody>
        </table>
        <div :id="`verticalScrollbar-${rand}`" class="position-absolute overflow-auto" style="height:100%; width: 16px; top: 0; right: 0;">
            <div class="fake-scrollbar-inner" style="height: 100%; width: 100%;">
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
