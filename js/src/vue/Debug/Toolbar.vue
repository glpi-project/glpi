<script setup>
    /**
     * @typedef {{ id: string, parent_id: string|null, category: string, name: string, start: number, end: number }} ProfilerSection
     * @typedef {{ execution_time: number, memory_usage: number, memory_peak: number, memory_limit: number }} ServerPerformanceMetrics
     * @typedef {{ total_requests: number, total_duration: number, queries: { request_id: string, num: number, query: string, time: number, rows: number, warnings: string[], errors: string[] } }} SQLMetrics
     * @typedef {{ id: string, parent_id: string, server_performance: ServerPerformanceMetrics, sql: SQLMetrics, globals: Object.<string, string>, [profiler]: ProfilerSection[] }} Profile
     * @typedef {{ id: string, data: Object.<string, string>, url: string, server_global: Object.<string, string>, type: string, start: Date, time: number, status: number, status_type: string, profile: Profile|null }} AJAXRequestData
     * @typedef {{ x: number, y: number, width: number, height: number }} ClientTimingBounds
     * @typedef {{ queued: Array, redirect: Array, fetch: Array, dns: Array, connection: Array, initial_connection: Array, ssl: Array, request: Array, response: Array }} ClientTimingSections
     * @typedef {{ type: string, name: string, start: number, end: number, bounds: Object.<string, ClientTimingBounds>, sections: ClientTimingSections }} ClientTimingData
     * @typedef {{ id: string, show: function(content_area: jQuery, refresh: boolean) }} Widget
     */
    /**
     * @typedef MainWidget
     * @extends Widget
     * @property {boolean} main_widget=true
     * @property {string} title
     * @property {string|null} icon
     * @property {function(button: jQuery)} refreshButton
     */
    /**
     * @typedef SubWidget
     * @extends Widget
     * @property {boolean} main_widget=false
     */
    import {computed, ref, watch} from "vue";

    const props = defineProps({
        initial_request: {
            type: Object,
            default: null,
        },
    });

    const active_widget = ref(null);
    const active_widget_component = computed(() => {
        if (active_widget.value === null) {
            return null;
        }
        const widget = widgets.find(w => w.id === active_widget.value);
        if (widget === undefined) {
            return null;
        }
        return widget.component_registered_name;
    });

    const ajax_requests = ref([]);

    const current_request = ref(props.initial_request.id);
    const current_profile = computed(() => {
        if (current_request.value === null) {
            return undefined;
        }
        return getProfile(current_request.value);
    });

    const initial_load = ref(true);
    /**
     * @type {(MainWidget|SubWidget)[]}
     */
    const widgets = [
        {
            id: 'server_performance',
            title: 'Server performance',
            icon: 'ti ti-clock-play',
            main_widget: true, // This widget shows directly in the toolbar
            component_registered_name: 'widget-server-performance',
            refreshButton: (button) => {
                const server_perf = props.initial_request.server_performance;
                const memory_usage = +(server_perf.memory_usage / 1024 / 1024).toFixed(2);
                const server_performance_button_label = `${_.escape(server_perf.execution_time)} <span class="text-muted"> ms using </span> ${_.escape(memory_usage)} <span class="text-muted"> MiB </span>`;
                button.find('.debug-text').html(server_performance_button_label);
            }
        },
        {
            id: 'sql',
            title: 'SQL Requests',
            icon: 'ti ti-database',
            main_widget: true, // This widget shows directly in the toolbar
            component_registered_name: 'widget-sqlrequests',
            refreshButton: (button) => {
                const sql_data = getCombinedSQLData();
                const database_button_label = `${_.escape(sql_data.total_requests)} <span class="text-muted"> requests </span>`;
                button.find('.debug-text').html(database_button_label);
            }
        },
        {
            id: 'requests',
            title: 'HTTP Requests',
            icon: 'ti ti-refresh',
            main_widget: true, // This widget shows directly in the toolbar
            component_registered_name: 'widget-httprequests',
            refreshButton: (button) => {
                button.find('.debug-text').html(`${ajax_requests.value.length + 1} <span class="text-muted"> requests </span>`);
            }
        },
        {
            id: 'client_performance',
            title: 'Client performance',
            icon: 'ti ti-brand-javascript',
            main_widget: true, // This widget shows directly in the toolbar
            component_registered_name: 'widget-client-performance',
            refreshButton: (button) => {
                if (button.find('.debug-text').text().trim() === '') {
                    setTimeout(() => {
                        const dom_timing = +window.performance.getEntriesByType('navigation')[0].domComplete.toFixed(2);
                        const client_performance_button_label = `${_.escape(dom_timing)} <span class="text-muted"> ms </span>`;
                        button.find('.debug-text').html(client_performance_button_label);
                    }, 200);
                }
            }
        },
        {
            id: 'search_options',
            title: 'Search Options',
            icon: 'ti ti-list-search',
            main_widget: true, // This widget shows directly in the toolbar
            component_registered_name: 'widget-search-options',
            refreshButton: (button) => {}
        },
        {
            id: 'theme_switcher',
            title: 'Palette Switcher',
            icon: 'ti ti-palette',
            main_widget: true, // This widget shows directly in the toolbar
            component_registered_name: 'widget-theme-switcher',
            refreshButton: (button) => {
                button.find('.debug-text').html(`<span class="text-muted">Theme: </span> ${_.escape(document.documentElement.attributes['data-glpi-theme'].value)}`);
            }
        },
        {
            id: 'globals',
            main_widget: false,
            component_registered_name: 'widget-globals',
        },
        {
            id: 'profiler',
            main_widget: false,
            component_registered_name: 'widget-profiler',
        },
        {
            id: 'request_summary',
            main_widget: false,
            component_registered_name: 'widget-request-summary',
        }
    ];

    $(document).ajaxSend((event, xhr, settings) => {
        // If the request is going to the debug AJAX endpoint, don't do anything
        if (settings.url.indexOf('ajax/debug.php') !== -1) {
            return;
        }
        const ajax_id = Math.random().toString(16).slice(2);
        // Tag the request with an id to identify it on the server side
        xhr.setRequestHeader('X-Glpi-Ajax-ID', ajax_id);
        // Need to set the header here too so it is accessible in the ajaxComplete event
        xhr.headers = xhr.headers || {};
        xhr.headers['X-Glpi-Ajax-ID'] = ajax_id;
        const parent_id = $('html').attr('data-glpi-request-id');
        if (parent_id !== undefined) {
            xhr.setRequestHeader('X-Glpi-Ajax-Parent-ID', parent_id);
        }

        let data = settings.data;
        if (settings.type !== 'POST' && data === undefined) {
            // get data from query string
            data = {};
            const query_string = settings.url.split('?')[1];
            if (query_string !== undefined) {
                query_string.split('&').forEach((pair) => {
                    const [key, value] = pair.split('=');
                    data[key] = value;
                });
            }
        } else if (typeof data === 'string') {
            // Post data is a URI encoded string similar to a query string. Values may be JSON strings
            // so we need to parse it and convert it back to an object
            const data_object = {};
            data.split('&').forEach((pair) => {
                const [key, value] = pair.split('=');
                data_object[key] = decodeURIComponent(value);
                // try parsing the value as JSON
                try {
                    data_object[key] = JSON.parse(data_object[key]);
                } catch (e) {
                    // ignore
                }
            });
            data = data_object;
        }
        ajax_requests.value.push({
            'id': ajax_id,
            'status': '...',
            'status_type': 'info',
            'type': settings.type,
            'data': data,
            'url': settings.url,
            'start': event.timeStamp,
        });
        refreshWidgetButtons();
    });

    $(document).ajaxComplete((event, xhr, settings) => {
        // If the request is going to the debug AJAX endpoint, don't do anything
        if (settings.url.indexOf('ajax/debug.php') !== -1) {
            return;
        }
        if (xhr.headers === undefined) {
            return;
        }
        const ajax_id = xhr.headers['X-Glpi-Ajax-ID'];
        if (ajax_id !== undefined) {
            const ajax_request = ajax_requests.value.find((request) => request.id === ajax_id);
            if (ajax_request !== undefined) {
                ajax_request.status = xhr.status;
                ajax_request.time = new Date() - ajax_request.start;
                ajax_request.status_type = xhr.status >= 200 && xhr.status < 300 ? 'success' : 'danger';

                // Ask the server for the debug information it saved for this request
                requestAjaxDebugData(ajax_id);
            }
        }
        refreshWidgetButtons();
    });

    const is_dragging = ref(false);
    $(document).on('mousemove', (e) => {
        if (is_dragging.value && e.buttons === 1) {
            const page_height = $(window).height();
            let new_height = page_height - e.pageY;
            new_height = Math.max(new_height, 200);
            $('#debug-toolbar-expanded-content').css('height', `${new_height}px`);
        }
    });
    $(document).on('mouseup', () => {
        is_dragging.value = false;
    });

    function getMainWidgets() {
        return widgets.filter((widget) => widget.main_widget);
    }

    function getCombinedSQLData() {
        const sql_data = {
            total_requests: 0,
            total_duration: 0,
            queries: {}
        };
        sql_data.queries[props.initial_request.id] = props.initial_request.sql.queries;
        ajax_requests.value.forEach((request) => {
            if (request.profile && request.profile.sql !== undefined) {
                sql_data.queries[request.id] = request.profile.sql.queries;
            }
        });
        $.each(sql_data.queries, (request_id, data) => {
            // update the total counters
            data.forEach((query) => {
                sql_data.total_requests += 1;
                sql_data.total_duration += query['time'];
            });
        });

        return sql_data;
    }

    const show_content_area = ref(false);
    const show_toolbar = ref(true);

    function switchWidget(widget_id, refresh = false, content_area = undefined, data = {}) {
        if (content_area === undefined) {
            content_area = $('#debug-toolbar-expanded-content');
        }
        // Copy data into data properties of the content_area
        Object.keys(data).forEach((key) => {
            content_area.data(key, data[key]);
        });
        if (refresh) {
            active_widget.value = null;
        }
        active_widget.value = widget_id;
        show_content_area.value = true;
    }

    function refreshWidgetButtons() {
        $.each(getMainWidgets(), (i, /** @type MainWidget */ widget) => {
            widget.refreshButton($(`#debug-toolbar .debug-toolbar-widgets li[data-glpi-debug-widget-id="${CSS.escape(widget.id)}"]`));
        });
        initial_load.value = false;
    }

    watch(show_toolbar, (new_value) => {
        if (new_value) {
            $('body').removeClass('debug-folded');
        } else {
            $('body').addClass('debug-folded');
        }
    });

    function getProfile(request_id) {
        if (request_id === props.initial_request.id) {
            return props.initial_request;
        }
        return ajax_requests.value.find((request) => request.id === request_id).profile;
    }

    function requestAjaxDebugData(ajax_id, reload_widget = false) {
        const ajax_request = ajax_requests.value.find((request) => request.id === ajax_id);
        $.ajax({
            url: CFG_GLPI.root_doc + '/ajax/debug.php',
            data: {
                'ajax_id': ajax_id,
            }
        }).done((data) => {
            if (!data) {
                return;
            }
            ajax_request.profile = data;

            const content_area = $('#debug-toolbar-expanded-content');
            if (content_area.data('active-widget') !== undefined) {
                switchWidget(content_area.data('active-widget'), true);
            }
            // Move server global to the profile
            if (ajax_request.server_global !== undefined) {
                ajax_request.profile.globals['server'] = ajax_request.server_global;
            }
            // Move the data to either the get or post global depending on the request type
            if (ajax_request.type === 'POST') {
                ajax_request.profile.globals['post'] = ajax_request.data;
            } else {
                ajax_request.profile.globals['get'] = ajax_request.data;
            }
            refreshWidgetButtons();

            if (reload_widget) {
                // reload active widget
                switchWidget(content_area.data('active-widget'), true);
            }
        });
    }
</script>

<template>
    <div id="debug-toolbar" :class="'position-fixed bottom-0 card ' + (show_toolbar ? 'w-100' : '')" tabindex="0" :style="show_toolbar ? '' : 'width: fit-content'">
        <div class="resize-handle mt-n2" @mousedown.prevent="$event.buttons === 1 && (is_dragging = true)"></div>
        <div class="d-flex flex-row align-items-center">
            <div class="debug-toolbar-badge d-flex">
                <button type="button" class="btn btn-icon border-0 px-3 opacity-100 debug-logo" @click="show_toolbar = true" :disabled="show_toolbar"
                        title="Toggle debug bar">
                    <i class="ti ti-bug"></i>
                </button>
            </div>
            <div :class="'debug-toolbar-content w-100 justify-content-between align-items-center ' + (show_toolbar ? 'd-flex' : '')" v-show="show_toolbar">
                <ul class="debug-toolbar-widgets nav nav-tabs align-items-center border-0" data-bs-toggle="tabs">
                    <widget-button v-for="(widget) in getMainWidgets()" :id="widget.id" :title="widget.title" :icon="widget.icon"
                                   v-on:click="switchWidget(widget.id)" :active="widget.id === active_widget" @vue:mounted="refreshWidgetButtons"
                    ></widget-button>
                </ul>
                <div class="debug-toolbar-controls">
                    <div class="debug-toolbar-control">
                        <button type="button" class="btn btn-icon border-0 p-1" name="toggle_content_area" @click="show_content_area = !show_content_area"
                                title="Toggle debug content area">
                            <i :class="show_content_area ? 'ti ti-square-arrow-up' : 'ti ti-square-arrow-down'"></i>
                        </button>
                        <button type="button" class="btn btn-icon border-0 p-1" title="Close" @click="show_toolbar = false">
                            <i class="ti ti-square-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div id="debug-toolbar-expanded-content" class="w-100 card pe-2" v-show="show_content_area && show_toolbar">
            <component v-if="active_widget" :is="active_widget_component" :initial_request="props.initial_request"
               :ajax_requests="ajax_requests" @switchWidget="switchWidget" :widgets="widgets"
               @refreshButton="refreshWidgetButtons"></component>
        </div>
    </div>
</template>

<style scoped>
    .resize-handle {
        cursor: row-resize;
        height: 10px;
        z-index: 1030
    }
    #debug-toolbar {
        z-index: 1030; /* bootstrap $zindex-fixed (if this need to upped, keep it under 1040) */
        outline: none;
    }
    .debug-toolbar-badge button {
        box-shadow: none;
    }
    .debug-toolbar-widgets .debug-toolbar-widget {
        &.active, &:hover, &[active="true"] {
            border-top: 3px solid var(--tblr-primary) !important;
            margin-top : -3px;
        }

        button {
            box-shadow: none;
        }
    }
    #debug-toolbar-expanded-content {
        height: 30vh;
        overflow: auto;
    }
    :deep(.datagrid) {
        --tblr-datagrid-padding   : 1.5rem;
        --tblr-datagrid-item-width: 15rem;

        display                   : grid;
        grid-gap                  : var(--tblr-datagrid-padding);
        grid-template-columns     : repeat(auto-fit, minmax(var(--tblr-datagrid-item-width), 1fr));
    }
    :deep(.datagrid-title) {
        font-size     : .625rem;
        font-weight   : 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        line-height   : 1rem;
        color         : var(--tblr-muted);
        margin-bottom : .25rem;
    }
</style>
