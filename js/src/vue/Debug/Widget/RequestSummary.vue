<script setup>
    const props = defineProps({
        current_profile: {
            type: Object,
            required: true
        },
    });

    const server_perf = props.current_profile.server_performance;
    const memory_usage = +(server_perf.memory_usage / 1024 / 1024).toFixed(2);
    const memory_peak = +(server_perf.memory_peak / 1024 / 1024).toFixed(2);
    const memory_limit = +(server_perf.memory_limit / 1024 / 1024).toFixed(2);
    let total_execution_time = +server_perf.execution_time;

    let total_sql_duration = 0;
    let total_sql_queries = 0;
    $.each(props.current_profile.sql['queries'], (i, query) => {
        total_sql_queries++;
        total_sql_duration += query['time'];
    });
</script>

<template>
    <div>
        <h1>Request Summary ({{ props.current_profile.id }})</h1>
        <table class="table">
            <tbody>
                <tr>
                    <td>
                        Initial Execution Time: {{ total_execution_time }} ms
                    </td>
                    <td>
                        Memory Usage: {{ memory_usage }} MiB / {{ memory_limit }} MiB
                        <br>
                        Memory Peak: {{ memory_peak }} MiB / {{ memory_limit }} MiB
                    </td>
                </tr>
                <tr>
                    <td>
                        SQL Requests: {{ total_sql_queries }}
                        <br>
                        SQL Duration: {{ total_sql_duration.toFixed(1) }} ms
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>

</style>
