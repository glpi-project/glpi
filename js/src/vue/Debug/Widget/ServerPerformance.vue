<script setup>
    import {computed} from "vue";

    const props = defineProps({
        initial_request: {
            type: Object,
            required: true
        },
        ajax_requests: {
            type: Array,
            required: true
        }
    });

    const server_perf = props.initial_request.server_performance;
    const memory_usage = +(server_perf.memory_usage / 1024 / 1024).toFixed(2);
    const memory_peak = +(server_perf.memory_peak / 1024 / 1024).toFixed(2);
    const memory_limit = +(server_perf.memory_limit / 1024 / 1024).toFixed(2);
    const total_execution_time = computed(() => {
        let total = props.initial_request.server_performance.execution_time;
        for (const request of props.ajax_requests) {
            if (request.profile !== undefined) {
                total += +request.profile.server_performance.execution_time;
            }
        }
        return +total;
    });
</script>

<template>
    <div class="py-2 px-3 col-xxl-7 col-xl-9 col-12">
        <h2 class="mb-3">Server performance</h2>
        <div class="datagrid">
            <div class="datagrid-item">
                <div class="datagrid-title">Initial Execution Time</div>
                <div class="datagrid-content">{{ +props.initial_request.server_performance.execution_time }} ms</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Total Execution Time</div>
                <div class="datagrid-content">{{ total_execution_time }} ms</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Memory Usage</div>
                <div class="datagrid-content h-100 col-8">{{ memory_usage}} MiB / {{ memory_limit }} MiB</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Memory Peak</div>
                <div class="datagrid-content">{{ memory_peak}} MiB / {{ memory_limit }} MiB</div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
