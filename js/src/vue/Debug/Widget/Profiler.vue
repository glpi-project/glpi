<script setup>
    import {ref} from "vue";

    const props = defineProps({
        current_profile: {
            type: Object,
            required: true
        },
    });
    const profiler_sections = Object.values(props.current_profile.profiler || {}).sort((a, b) => a.start - b.start);;
    const hide_instant_sections = ref(true);
</script>

<template>
    <div>
        <label>
            Hide near-instant sections (&lt;= 1 ms):
            <input type="checkbox" name="hide_instant_sections" v-model="hide_instant_sections">
        </label>
        <widget-profiler-table :parent_duration="0" :nest_level="0" :profiler_sections="profiler_sections" :parent_id="null"
            :hide_instant_sections="hide_instant_sections"></widget-profiler-table>
    </div>
</template>

<style scoped>

</style>
