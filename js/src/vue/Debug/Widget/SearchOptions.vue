<script setup>
    import {computed, onMounted, ref, watch} from "vue";

    const rand = Math.floor(Math.random() * 1000000000);

    const all_itemtypes = ref([]);
    const current_itemtype = ref('');
    const search_options = ref({});

    const sorted_col = ref('opt_id');
    const sort_dir = ref('asc');
    const sorted_search_options = computed(() => {
        let sorted = [];

        $.each(search_options.value, (opt_id, data) => {
            sorted.push({
                opt_id: opt_id,
                table: data['table'],
                field: data['field'],
                name: data['name'],
                linkfield: data['linkfield'],
                datatype: data['datatype'] || '',
                nosearch: data['nosearch'] || false,
                massiveaction: data['massiveaction'] || true,
            });
        });

        // Sort by column
        sorted.sort((a, b) => {
            let a_val = a[sorted_col.value];
            let b_val = b[sorted_col.value];
            // if the sorted_col is the opt_id, need to sort the numbers properly instead of as strings
            if (sorted_col.value === 'opt_id') {
                a_val = parseInt(a_val);
                b_val = parseInt(b_val);
            }
            if (a_val === b_val) {
                return 0;
            }
            if (sort_dir.value === 'asc') {
                return a_val < b_val ? -1 : 1;
            } else {
                return a_val > b_val ? -1 : 1;
            }
        });
        return sorted;
    });

    function setSortedCol(col) {
        if (sorted_col.value === col) {
            if (sort_dir.value === 'asc') {
                sort_dir.value = 'desc';
            } else {
                sort_dir.value = 'asc';
            }
        } else {
            sorted_col.value = col;
            sort_dir.value = 'asc';
        }
    }

    function updateSearchOptions() {
        if (current_itemtype.value === null || current_itemtype.value === '') {
            return;
        }
        $.ajax({
            url: CFG_GLPI.root_doc + '/ajax/debug.php',
            data: {
                action: 'get_search_options',
                itemtype: current_itemtype.value
            },
        }).then((data) => {
            search_options.value = data;
        });
    }

    onMounted(() => {
        $.ajax({
            url: CFG_GLPI.root_doc + '/ajax/debug.php',
            data: {
                action: 'get_itemtypes'
            },
        }).then((data) => {
            all_itemtypes.value = data;
        });
        updateSearchOptions();
    });

    watch(current_itemtype, () => {
        updateSearchOptions();
    });

    const itemtype_input_mode = ref('select');
</script>

<template>
    <div class="py-2 px-3 col-12">
        <div class="d-flex">
            <div class="form-group row">
                <label class="col-5" :for="`itemtype${rand}`">Itemtype</label>
                <div class="col-7">
                    <div class="input-group">
                        <select v-if="itemtype_input_mode === 'select'" class="form-select" :id="`itemtype${rand}`" v-model="current_itemtype">
                            <option value="">-----</option>
                            <option v-for="itemtype in all_itemtypes" :value="itemtype" v-text="itemtype"></option>
                        </select>
                        <input v-else class="form-control" :id="`itemtype${rand}`" v-model.lazy="current_itemtype">
                        <button class="btn btn-sm btn-outline-secondary" @click="itemtype_input_mode = itemtype_input_mode === 'select' ? 'input' : 'select'"
                                title="Toggle manual input">
                            <i class="ti ti-switch-horizontal"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <table class="table search-opts-table" v-if="current_itemtype !== null && current_itemtype !== ''">
            <thead>
                <tr>
                    <th colspan="8" class="text-center">Search Options</th>
                </tr>
                <tr>
                    <th @click="setSortedCol('opt_id')">Search ID</th>
                    <th @click="setSortedCol('table')">Table</th>
                    <th @click="setSortedCol('field')">Field</th>
                    <th @click="setSortedCol('name')">Name</th>
                    <th @click="setSortedCol('linkfield')">Link Field</th>
                    <th @click="setSortedCol('datatype')">Datatype</th>
                    <th @click="setSortedCol('nosearch')">Searchable</th>
                    <th @click="setSortedCol('massiveaction')">Massive Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="sorted_search_options.length" v-for="opt in sorted_search_options" :key="opt.opt_id">
                    <td v-text="opt.opt_id"></td>
                    <td v-text="opt.table"></td>
                    <td v-text="opt.field"></td>
                    <td v-text="opt.name"></td>
                    <td v-text="opt.linkfield"></td>
                    <td v-if="opt.datatype" v-text="opt.datatype"></td>
                    <td v-else><span class="fst-italic">Not specified</span></td>
                    <td v-text="opt.nosearch !== true ? 'Yes' : 'No'"></td>
                    <td v-text="opt.massiveaction !== false ? 'Yes' : 'No'"></td>
                </tr>
                <tr v-else>
                    <td colspan="8" class="text-center">No Search Options</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
    .search-opts-table thead tr:nth-of-type(2) th {
        cursor: pointer;
    }
</style>
