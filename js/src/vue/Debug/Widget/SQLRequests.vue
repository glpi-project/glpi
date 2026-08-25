<script setup>
    /* global copyTextToClipboard */
    /* global _ */
    import {computed, reactive, ref, watch} from "vue";

    const props = defineProps({
        initial_request: {
            type: Object,
            required: false
        },
        ajax_requests: {
            type: Array,
            required: false
        },
        current_profile: {
            type: Object,
            required: false
        },
    });

    const is_global_mode = computed(() => {
        return props.current_profile === undefined && props.ajax_requests !== undefined;
    });

    function getCombinedSQLData() {
        const sql_data = {
            total_requests: 0,
            total_duration: 0,
            queries: {}
        };
        if (is_global_mode.value) {
            sql_data.queries[props.initial_request.id] = props.initial_request.sql.queries;
            props.ajax_requests.forEach((request) => {
                if (request.profile && request.profile.sql !== undefined) {
                    sql_data.queries[request.id] = request.profile.sql.queries;
                }
            });
        } else {
            sql_data.queries[props.current_profile.id] = props.current_profile.sql.queries;
        }
        $.each(sql_data.queries, (request_id, data) => {
            // update the total counters
            data.forEach((query) => {
                sql_data.total_requests += 1;
                sql_data.total_duration += query['time'];
            });
        });

        return sql_data;
    }

    // Longest bound value shown as-is; the copy button always yields the full value.
    const MAX_PARAM_LENGTH = 512;

    function formatParamValue(value) {
        if (value === null || value === undefined) {
            return {type: 'NULL', display: 'NULL'};
        }
        if (typeof value === 'boolean') {
            return {type: 'bool', display: value ? 'true' : 'false'};
        }
        if (typeof value === 'number') {
            return {type: 'number', display: String(value)};
        }
        if (typeof value === 'object') {
            // Should not happen, but never render "[object Object]"
            return {type: 'object', display: JSON.stringify(value)};
        }

        const full = String(value);
        // Neutralize control chars so a binary payload cannot wreck the layout
        // eslint-disable-next-line no-control-regex -- matching control chars is the point here
        let display = full.replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g, '\uFFFD');
        let suffix = '';
        if (display.length > MAX_PARAM_LENGTH) {
            display = display.substring(0, MAX_PARAM_LENGTH);
            suffix = ` … (${full.length} chars)`;
        }
        return {type: 'string', display: `'${display}'${suffix}`};
    }

    function buildParamsList(params) {
        if (params === null || params === undefined) {
            return [];
        }
        // Params are positional, but PHP may serialize them as an object if they have string keys
        const entries = Array.isArray(params)
            ? params.map((value, i) => [String(i + 1), value])
            : Object.entries(params);

        return entries.map(([label, value]) => ({label: label, raw: value, ...formatParamValue(value)}));
    }

    const sorted_col = ref(is_global_mode.value ? 'request_id' : 'num');
    const sort_dir = ref('asc');
    const sorted_queries_data = computed(() => {
        let sorted = [];

        const sql_data = getCombinedSQLData();
        $.each(sql_data.queries, (request_id, data) => {
            data.forEach((query) => {
                // Profiles recorded before prepared statements (or by plugins) have no raw_query
                const raw_query = query['raw_query'] !== undefined && query['raw_query'] !== null
                    ? query['raw_query']
                    : query['query'];
                const params_list = buildParamsList(query['params']);
                sorted.push({
                    request_id: request_id,
                    num: query['num'],
                    time: query['time'],
                    query: query['query'],
                    raw_query: raw_query,
                    params_list: params_list,
                    has_params: params_list.length > 0,
                    has_raw_query: raw_query !== query['query'],
                    rows: query['rows'],
                    warnings: _.escape(query['warnings']),
                    errors: _.escape(query['errors']),
                });
            });
        });

        // Filter by current profile id
        if (!is_global_mode.value) {
            sorted = sorted.filter((query) => {
                return query.request_id === props.current_profile.id;
            });
        }

        // Sort by column
        sorted.sort((a, b) => {
            let a_val = a[sorted_col.value];
            let b_val = b[sorted_col.value];
            if (sorted_col.value === 'time') {
                a_val = parseFloat(a_val);
                b_val = parseFloat(b_val);
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
    function copyToClipboard(e, text, normalize_whitespace = true) {
        // Normalize whitespace as spaces and trim
        copyTextToClipboard(normalize_whitespace ? text.replace(/\s+/g, ' ').trim() : text);

        // change temporary the button icon to a check then after a while return to the original icon
        const icon = $(e.currentTarget).find('i');
        icon.removeClass('ti-clipboard-copy').addClass('ti-check');
        setTimeout(() => {
            icon.removeClass('ti-check').addClass('ti-clipboard-copy');
        }, 1000);
    }

    function cleanSQLQuery(query) {
        const newline_keywords = ['UNION', 'FROM', 'WHERE', 'INNER JOIN', 'LEFT JOIN', 'ORDER BY', 'SORT'];
        const post_newline_keywords = ['UNION'];
        query = query.replace(/\n/g, ' ');

        return Promise.resolve(window.GLPI.Monaco.colorizeText(query, 'sql')).then((html) => {
            // get all 'span' elements with mtk6 class (keywords) and insert the needed line breaks
            const newline_before_selector = newline_keywords.map((keyword) => `span.mtk6:contains(${CSS.escape(keyword)})`).join(',');
            const post_newline_selector = post_newline_keywords.map((keyword) => `span.mtk6:contains(${CSS.escape(keyword)})`).join(',');
            return $($.parseHTML(html)).find(newline_before_selector).before('</br>').end().find(post_newline_selector).after('</br>').end().html();
        });
    }

    const colorized_queries = reactive(new Map());
    const expanded_rows = reactive(new Set());

    function rowKey(query) {
        return `${query.request_id}-${query.num}`;
    }

    // Both the interpolated and the prepared query may be colorized, hence the field suffix
    function codeKey(query, field) {
        return `${rowKey(query)}-${field}`;
    }

    function ensureColorized(key, sql) {
        if (colorized_queries.has(key)) {
            return;
        }
        // Show uncolored query until the colorized version is ready
        colorized_queries.set(key, _.escape(sql));
        cleanSQLQuery(sql).then((html) => {
            colorized_queries.set(key, html);
        });
    }

    function isExpanded(query) {
        return expanded_rows.has(rowKey(query));
    }

    function toggleParams(query) {
        const key = rowKey(query);
        if (expanded_rows.has(key)) {
            expanded_rows.delete(key);
            return;
        }
        expanded_rows.add(key);
        // Only colorize the prepared query for rows the user actually opens
        if (query.has_raw_query) {
            ensureColorized(codeKey(query, 'raw_query'), query.raw_query);
        }
    }

    function copyParams(e, query) {
        copyToClipboard(e, JSON.stringify(query.params_list.map((param) => param.raw), null, 2), false);
    }

    watch(() => sorted_queries_data.value, () => {
        sorted_queries_data.value.forEach((query) => {
            ensureColorized(codeKey(query, 'query'), query.query);
        });
    }, {
        immediate: true,
        deep: true
    });
</script>

<template>
    <div class="overflow-auto py-2 px-3">
        <table id="debug-sql-request-table" class="table card-table">
            <thead>
                <tr>
                    <th v-if="is_global_mode" @click="setSortedCol('request_id')">Request ID</th>
                    <th @click="setSortedCol('num')">Number</th>
                    <th @click="setSortedCol('query')">Query</th>
                    <th @click="setSortedCol('time')">Time</th>
                    <th @click="setSortedCol('rows')">Rows</th>
                    <th @click="setSortedCol('warnings')">Warnings</th>
                    <th @click="setSortedCol('errors')">Errors</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="query in sorted_queries_data" :key="query.request_id + '-' + query.num">
                    <td v-if="is_global_mode"><button class="btn btn-link request-link">{{ query.request_id }}</button></td>
                    <td>{{ query.num }}</td>
                    <td>
                        <div class="d-flex align-items-start" style="max-width: 50vw;">
                            <div style="max-width: 50vw; white-space: break-spaces;" class="w-100">
                                <code class="d-block cm-s-default border-0" v-html="colorized_queries.get(codeKey(query, 'query'))"></code>
                            </div>
                            <button type="button" @click="copyToClipboard($event, query.query)" class="ms-1 copy-code btn btn-sm btn-ghost-secondary" title="Copy query to clipboard">
                                <i class="ti ti-clipboard-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                        <template v-if="query.has_params">
                            <button type="button" class="toggle-sql-params btn btn-sm btn-ghost-secondary px-1 py-0 mt-1"
                                    :aria-expanded="isExpanded(query) ? 'true' : 'false'" aria-label="Toggle prepared statement and parameters"
                                    @click="toggleParams(query)">
                                <i :class="isExpanded(query) ? 'ti ti-chevron-down' : 'ti ti-chevron-right'" aria-hidden="true"></i>
                                <span class="ms-1">Prepared statement</span>
                                <span class="badge bg-secondary text-secondary-fg ms-1">{{ query.params_list.length }}</span>
                            </button>
                            <div v-if="isExpanded(query)" class="sql-params-panel border rounded p-2 mt-1">
                                <div v-if="query.has_raw_query" class="d-flex align-items-start">
                                    <div style="white-space: break-spaces;" class="w-100">
                                        <code class="d-block cm-s-default border-0" v-html="colorized_queries.get(codeKey(query, 'raw_query'))"></code>
                                    </div>
                                    <button type="button" @click="copyToClipboard($event, query.raw_query)" class="ms-1 copy-raw-query btn btn-sm btn-ghost-secondary" title="Copy prepared query to clipboard">
                                        <i class="ti ti-clipboard-copy" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="d-flex align-items-start mt-2">
                                    <ul class="sql-params-list list-unstyled mb-0 w-100">
                                        <li v-for="param in query.params_list" :key="param.label" class="d-flex">
                                            <span class="param-index text-muted me-2">{{ param.label }}</span>
                                            <span class="param-type text-muted me-2">{{ param.type }}</span>
                                            <span class="param-value font-monospace">{{ param.display }}</span>
                                        </li>
                                    </ul>
                                    <button type="button" @click="copyParams($event, query)" class="ms-1 copy-params btn btn-sm btn-ghost-secondary" title="Copy parameters to clipboard">
                                        <i class="ti ti-clipboard-copy" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </td>
                    <td>{{ query.time.toFixed(1) }}&nbsp;ms</td>
                    <td>{{ query.rows }}</td>
                    <td>{{ query.warnings }}</td>
                    <td>{{ query.errors }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
    #debug-sql-request-table thead tr th {
        cursor: pointer;
    }
    #debug-sql-request-table tbody tr td:nth-of-type(3) {
        max-width: 50vw;
        white-space: break-spaces;
    }
    #debug-sql-request-table tbody tr td:nth-of-type(4) {
        white-space: nowrap;
    }
    #debug-sql-request-table::v-deep(span.mtk1) {
        color: var(--tblr-body-color);
    }
    #debug-sql-request-table code {
        color: var(--tblr-body-color);
    }
    #debug-sql-request-table .sql-params-panel {
        max-height: 40vh;
        overflow: auto;
    }
    #debug-sql-request-table .param-index {
        min-width: 2rem;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    #debug-sql-request-table .param-type {
        min-width: 4rem;
    }
    #debug-sql-request-table .param-value {
        word-break: break-word;
    }
</style>
