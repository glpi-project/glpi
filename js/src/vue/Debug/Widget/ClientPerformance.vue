<script setup>
    const props = defineProps({});

    const perf = window.performance;
    const nav_timings = window.performance.getEntriesByType('navigation')[0];
    const paint_timings = window.performance.getEntriesByType('paint');
    const resource_timings = window.performance.getEntriesByType('resource');

    let paint_timing = paint_timings.filter((timing) => timing.name === 'first-paint');
    let paint_timing_label = 'Time to first paint';
    if (paint_timing.length === 0) {
        // Firefox doesn't have first-paint for whatever reason
        paint_timing = paint_timings.filter((timing) => timing.name === 'first-contentful-paint');
    }
    const time_to_first_paint = paint_timing.length > 0 ? paint_timing[0].startTime : -1;
    const time_to_dom_interactive = nav_timings.domInteractive;
    const time_to_dom_complete = nav_timings.domComplete;

    const total_resources = resource_timings.length;
    let total_resources_size = resource_timings.reduce((total, timing) => total + timing.transferSize, 0);
    total_resources_size = total_resources_size / 1024 / 1024;

    const has_memory_perf_support = perf.memory !== undefined;
    let heap_limit = 0;
    let used_heap = 0;
    let total_heap = 0;
    if (has_memory_perf_support) {
        heap_limit = perf.memory.jsHeapSizeLimit / 1024 / 1024;
        used_heap = perf.memory.usedJSHeapSize / 1024 / 1024;
        total_heap = perf.memory.totalJSHeapSize / 1024 / 1024;
    }
</script>

<template>
    <div class="py-2 px-3 col-xxl-7 col-xl-9 col-12">
        <h2 class="mb-3">Client performance</h2>
        <h3 class="mb-2">Timings</h3>
        <div class="datagrid">
            <div class="datagrid-item">
                <div class="datagrid-title">{{ paint_timing_label }}</div>
                <div class="datagrid-content">{{ +time_to_first_paint.toFixed(2) }} ms</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Time to DOM interactive</div>
                <div class="datagrid-content">{{ +time_to_dom_interactive.toFixed(2) }} ms</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Time to DOM complete</div>
                <div class="datagrid-content">{{ +time_to_dom_complete.toFixed(2) }} ms</div>
            </div>
        </div>
        <h3 class="mt-3 mb-2">Resource Loading</h3>
        <div class="datagrid">
            <div class="datagrid-item">
                <div class="datagrid-title">Total resources</div>
                <div class="datagrid-content">{{ total_resources }}</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Total resources size</div>
                <div class="datagrid-content">{{ +total_resources_size.toFixed(2) }} MiB</div>
            </div>
            <!-- Keep empty item at the end to align with previous grid -->
            <div class="datagrid-item"></div>
        </div>
        <h3 v-if="has_memory_perf_support" class="mt-3 mb-2">Memory</h3>
        <div v-if="has_memory_perf_support" class="datagrid">
            <div class="datagrid-item">
                <div class="datagrid-title">Used JS Heap</div>
                <div class="datagrid-content">{{ +used_heap.toFixed(2) }} MiB</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Total JS Heap</div>
                <div class="datagrid-content">{{ +total_heap.toFixed(2) }} MiB</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">JS Heap Limit</div>
                <div class="datagrid-content">{{ +heap_limit.toFixed(2) }} MiB</div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
