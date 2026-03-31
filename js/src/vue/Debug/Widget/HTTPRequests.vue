<script setup>
    import {computed, onMounted, ref, watch} from "vue";
    import RequestTimeline from "./RequestTimeline.vue";

    const props = defineProps({
        initial_request: {
            type: Object,
            required: true
        },
        ajax_requests: {
            type: Array,
            required: true
        },
        widgets: {
            type: Object,
            required: true
        },
    });

    const is_mounted = ref(false);
    onMounted(() => {
        is_mounted.value = true;
    });

    const rand = Math.floor(Math.random() * 1000000);
    const REQUEST_PATH_LENGTH = 100;
    const content_area = ref(null);
    const $content_area = computed(() => {
        return $(content_area.value);
    });

    const show_timeline = ref(false);
    const current_request_id = ref(props.initial_request.id);
    const current_profile = computed(() => {
        if (current_request_id.value === props.initial_request.id) {
            return props.initial_request;
        }
        return props.ajax_requests.find((request) => request.id === current_request_id.value).profile;
    });

    $('#debug-toolbar').on('keyup', (e) => {
        e.preventDefault();
        e.stopPropagation();
        // ignore input inside monaco editors
        if ($(e.target).closest('.monaco-editor').length > 0) {
            return;
        }
        if (e.keyCode === 84) { // 't'
            show_timeline.value = !show_timeline.value;
        }
    });

    const is_dragging = ref(false);
    const left_panel_width = ref(null);
    const left_panel_flexbasis = computed(() => {
        if (left_panel_width.value === null) {
            return '33%';
        }
        return left_panel_width.value + 'px';
    });

    function onMouseDown(e) {
        if (e.buttons === 1) {
            is_dragging.value = true;
            e.preventDefault();
        }
    }

    function onMouseMove(e) {
        if (is_dragging.value && e.buttons === 1) {
            const left_column = $content_area.value.find('.split-panel-h .left-panel');
            left_panel_width.value = e.pageX - left_column.offset().left;
        }
    }

    function onMouseUp() {
        is_dragging.value = false;
    }

    function urlNeedsTruncated(url) {
        return url.length > REQUEST_PATH_LENGTH;
    }

    const currentPath = computed(() => {
        return window.location.pathname;
    });

    const sorted_col = ref('number');
    const sort_dir = ref('asc');
    const sorted_requests_data = computed(() => {
        const sorted = [];
        sorted.push({
            id: props.initial_request.id,
            number: 0,
            url: currentPath.value,
            status: '-',
            type: props.initial_request.globals.server['REQUEST_METHOD'] || '-',
            duration: props.initial_request.server_performance.execution_time,
        });
        for (const request of props.ajax_requests) {
            sorted.push({
                id: request.id,
                number: sorted.length,
                url: request.url,
                status: request.status,
                type: request.type,
                duration: request.time,
            });
        }
        // Sort by column
        sorted.sort((a, b) => {
            let a_val = a[sorted_col.value];
            let b_val = b[sorted_col.value];
            if (sorted_col.value === 'duration') {
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

    const seen_request_ids = props.ajax_requests.map(r => r.id);

    watch(props.ajax_requests, (old_v, new_v) => {
        for (const request of new_v) {
            if (seen_request_ids.indexOf(request.id) === -1) {
                seen_request_ids.push(request.id);
                setTimeout(() => {
                    // Need this timeout because this watcher is called before the DOM is updated
                    const row = $(`tr[data-request-id="${CSS.escape(request.id)}"]`);
                    row.css('background-color', '#FFFF7B80');
                    setTimeout(() => {
                        row.css('background-color', 'transparent');
                    }, 2000);
                }, 10);
            }
        }
    });

    function expandRequestURL(e) {
        const btn = $(e.currentTarget);
        const td = btn.closest('td');
        td.text(td.attr('title'));
        btn.hide();
    }

    function selectRow(e) {
        current_request_id.value = $(e.currentTarget).attr('data-request-id');
    }

    const active_subwidget = ref('request_summary');
    const active_subwidget_component = computed(() => {
        if (active_subwidget.value === null) {
            return null;
        }
        const widget = props.widgets.find(w => w.id === active_subwidget.value);
        if (widget === undefined) {
            return null;
        }
        return widget.component_registered_name;
    });
    function switchSubwidget(widget_id) {
        active_subwidget.value = widget_id;
    }

    function onRequestChanged() {
        current_request_id.value = $content_area.value.data('requests_request_id') || props.initial_request.id;
    }
</script>

<template>
    <div class="h-100" ref="content_area" @mousemove="onMouseMove($event)" @mouseup="onMouseUp()">
        <div class="request-timeline" v-if="is_mounted && show_timeline">
            <RequestTimeline :initial_request="initial_request" :ajax_requests="props.ajax_requests" :content_area="content_area"
                             @change_request="onRequestChanged"></RequestTimeline>
        </div>
        <div class="d-flex flex-row h-100 split-panel-h">
            <div class="left-panel">
                <div class="overflow-auto h-100 me-2">
                    <table id="debug-requests-table" class="table table-hover mb-1">
                        <thead>
                            <tr>
                                <th @click="setSortedCol('number')">Number</th>
                                <th @click="setSortedCol('url')">URL</th>
                                <th @click="setSortedCol('status')">Status</th>
                                <th @click="setSortedCol('type')">Type</th>
                                <th @click="setSortedCol('duration')">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="request in sorted_requests_data" :key="request.id" :data-request-id="request.id"
                                :class="`cursor-pointer ${request.id === current_request_id ? 'table-active' : ''}`" @click="selectRow($event)">
                                <td>{{ request.number }}</td>
                                <td :title="request.url"
                                    :data-truncated="urlNeedsTruncated(request.url)">{{ request.url.substring(0, REQUEST_PATH_LENGTH) }}<button
                                        v-if="urlNeedsTruncated(request.url)" class="ms-1 badge bg-secondary text-secondary-fg" name="show_full_url"
                                        @click="expandRequestURL($event)">
                                        <i class="ti ti-dots"></i>
                                    </button>
                                </td>
                                <td>{{ request.status }}</td>
                                <td>{{ request.type }}</td>
                                <td>{{ request.duration }} ms</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="resize-handle" @mousedown.stop.prevent="onMouseDown($event)"></div>
            <div class="right-panel overflow-auto ms-2 flex-grow-1">
                <div :id="`debugpanel${rand}`" class="p-0 mt-n1">
                    <ul class="nav nav-tabs" data-bs-toggle="tabs">
                        <li class="nav-item">
                            <button @click="switchSubwidget('request_summary')"
                                    class="nav-link active" data-bs-toggle="tab" data-glpi-debug-widget-id="request_summary">Summary</button>
                        </li>
                        <li class="nav-item">
                            <button @click="switchSubwidget('sql')"
                                    class="nav-link" data-bs-toggle="tab" data-glpi-debug-widget-id="sql">SQL</button>
                        </li>
                        <li class="nav-item">
                            <button @click="switchSubwidget('globals')"
                                    class="nav-link" data-bs-toggle="tab" data-glpi-debug-widget-id="globals">Globals</button>
                        </li>
                        <li class="nav-item">
                            <button @click="switchSubwidget('profiler')"
                                    class="nav-link" data-bs-toggle="tab" data-glpi-debug-widget-id="profiler">Profiler</button>
                        </li>
                    </ul>

                    <div class="card-body overflow-auto p-1">
                        <div class="tab-content request-details-content-area">
                            <div v-if="current_profile">
                                <component v-if="active_subwidget" :is="active_subwidget_component" :current_profile="current_profile"></component>
                            </div>
                            <div v-else>
                                <div class="alert alert-danger">
                                    <span>No debug data was found for this request immediately after it finished. Some requests like /front/locale.php will never have data as they intentionally close the session.</span>
                                </div>
                                <button type="button" class="btn btn-primary" :data-request-id="current_request_id"><i class="ti ti-reload"></i>Retry</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    div.left-panel {
        border-right: 1px solid #808080;
        min-width: 100px;
        flex: 0 0 v-bind(left_panel_flexbasis);
    }
    .split-panel-h .resize-handle {
        cursor: col-resize;
        width: 10px;
        z-index: 1030;
        margin-left: -0.5rem;
        margin-right: -0.25rem;
    }
    #debug-requests-table thead tr th {
        cursor: pointer;
    }
    #debug-requests-table thead tr th:nth-child(2) {
        max-width: 200px;
        white-space: pre-wrap;
    }
    #debug-requests-table tbody tr td:nth-child(2) {
        max-width: 200px;
        white-space: pre-wrap;
    }
    #debug-requests-table tbody {
        white-space: nowrap
    }
    #debug-requests-table thead th {
        position: sticky;
        top: 0;
    }
</style>
