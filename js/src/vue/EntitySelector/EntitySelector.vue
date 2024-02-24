<script setup>
    /* global hotkeys, typewatch, initTooltips */
    import {ref, computed, onMounted, watch} from 'vue';
    import TreeView from "../Components/TreeView.vue";

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
        entity_data.value = data;
    });
    const search_filter = ref('');
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

            <TreeView :tree="entity_data" :rand="rand" :max_items="15" :virtual_dom="true" :search_filter="search_filter" />
        </div>
    </div>
</template>

<style scoped>
    .data_tree {
        width: 450px;
        max-width: 85vw;
        position: relative;

        table {
            width: 100%;
        }
    }
</style>
