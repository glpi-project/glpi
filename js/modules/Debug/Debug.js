/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/* global initSortableTable, escapeMarkupText, luminance, hexToRgb */

/**
 * @typedef ProfilerSection
 * @property {string} id
 * @property {string|null} parent_id
 * @property {string} category
 * @property {string} name
 * @property {number} start
 * @property {number} end
 */

/**
 * @typedef Profile
 * @property {string} id
 * @property {string} parent_id
 * @property {{
 *     execution_time: number,
 *     memory_usage: number,
 *     memory_peak: number,
 *     memory_limit: number,
 * }} server_performance
 * @property {{
 *     total_requests: number,
 *     total_duration: number,
 *     queries: {
 *         request_id: string,
 *         num: number,
 *         query: string,
 *         time: number,
 *         rows: number,
 *         warnings: string[],
 *         errors: string[],
 *     }
 * }} sql
 * @property {Object.<string, any>} globals
 * @property {ProfilerSection[]} [profiler]
 */

/**
 * @typedef AJAXRequestData
 * @property {string} id
 * @property {{}|null} data
 * @property {string} url
 * @property {{}|null} server_global
 * @property {string} type
 * @property {Date} start
 * @property {number} time
 * @property {number} status
 * @property {string} status_type
 * @property {Profile|null} profile
 */

/**
 * @typedef ClientTimingData
 * @property {string} type
 * @property {string} name
 * @property {number} start
 * @property {number} end
 * @property {Object.<string, {
 *     x: number,
 *     y: number,
 *     width: number,
 *     height: number
 * }>} bounds
 * @property {{
 *     queued: Array,
 *     redirect: Array,
 *     fetch: Array,
 *     dns: Array,
 *     connection: Array,
 *     initial_connection: Array,
 *     ssl: Array,
 *     request: Array,
 *     response: Array,
 * }} sections
 */

window.GLPI = window.GLPI || {};
window.GLPI.Debug = new class Debug {
    constructor() {
        /**
         * @type {AJAXRequestData[]}
         */
        this.ajax_requests = [];
        /**
         * @type {Profile|null}
         */
        this.initial_request = null;
        this.widgets = [];

        this.TIMING_COLORS = {
            queued: '#808080',
            redirect: '#00aaaa',
            fetch: '#004400',
            dns: '#00cc88',
            connection: '#ffaa00',
            initial_connection: '#ffaa88',
            ssl: '#cc00cc',
            request: '#00aa00',
            response: '#0000ee',
        };

        this.REQUEST_PATH_LENGTH = 100;
    }

    init(initial_request) {
        this.initial_request = initial_request;

        $.each(this.initial_request.sql.queries, (i, query) => {
            this.initial_request.sql.queries[i].query = this.cleanSQLQuery(query.query);
        });

        this.refreshWidgetButtons();

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
            this.ajax_requests.push({
                'id': ajax_id,
                'status': '...',
                'status_type': 'info',
                'type': settings.type,
                'data': data,
                'url': settings.url,
                'start': event.timeStamp,
            });
            this.refreshWidgetButtons();
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
                const ajax_request = this.ajax_requests.find((request) => request.id === ajax_id);
                if (ajax_request !== undefined) {
                    ajax_request.status = xhr.status;
                    ajax_request.time = new Date() - ajax_request.start;
                    ajax_request.status_type = xhr.status >= 200 && xhr.status < 300 ? 'success' : 'danger';

                    // Ask the server for the debug information it saved for this request
                    this.requestAjaxDebugData(ajax_id);
                }
            }
            this.refreshWidgetButtons();
        });

        $('#debug-toolbar').on('click', '.debug-toolbar-widget', (e) => {
            const widget_id = $(e.currentTarget).attr('data-glpi-debug-widget-id');
            this.showWidget(widget_id);
            this.toggleExtraContentArea(true);
        });

        const resize_handle = $('#debug-toolbar > .resize-handle');
        const expanded_content_area = $('#debug-toolbar-expanded-content');

        let is_dragging = false;
        resize_handle.on('mousedown', (e) => {
            if (e.buttons === 1) {
                is_dragging = true;
                e.preventDefault();
            }
        });
        $(document).on('mousemove', (e) => {
            if (is_dragging && e.buttons === 1) {
                const page_height = $(window).height();
                let new_height = page_height - e.pageY;
                new_height = Math.max(new_height, 200);
                expanded_content_area.css('height', `${new_height}px`);
            }
        });
        $(document).on('mouseup', () => {
            is_dragging = false;
        });

        expanded_content_area.on('click', 'button.request-link', (e) => {
            const request_id = $(e.currentTarget).text();
            // Show the requests widget and select the request
            this.showWidget('requests');
            // Find the request in the table and select it
            const request_row = $(`#debug-toolbar-expanded-content #debug-requests-table tr[data-request-id="${request_id}"]`);
            if (request_row.length > 0) {
                request_row[0].scrollIntoView();
                request_row.click();
            }
        });
    }

    requestAjaxDebugData(ajax_id, reload_widget = false) {
        const ajax_request = this.ajax_requests.find((request) => request.id === ajax_id);
        $.ajax({
            url: CFG_GLPI.root_doc + '/ajax/debug.php',
            data: {
                'ajax_id': ajax_id,
            }
        }).done((data) => {
            ajax_request.profile = data;

            $.each(ajax_request.profile.sql.queries, (i, query) => {
                ajax_request.profile.sql.queries[i].query = this.cleanSQLQuery(query.query);
            });

            const content_area = $('#debug-toolbar-expanded-content');
            if (content_area.data('active-widget') !== undefined) {
                this.showWidget(content_area.data('active-widget'), true);
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
            this.refreshWidgetButtons();

            if (reload_widget) {
                // reload active widget
                this.showWidget(content_area.data('active-widget'), true);
            }
        });
    }

    cleanSQLQuery(query) {
        const newline_keywords = ['UNION', 'FROM', 'WHERE', 'INNER JOIN', 'LEFT JOIN', 'ORDER BY', 'SORT'];
        const post_newline_keywords = ['UNION'];
        let clean_query = '';
        window.CodeMirror.runMode(query, 'text/x-sql', (text, style) => {
            text.replace('>', `&gt;`).replace('<', `&lt;`);
            if (style !== null && style !== undefined) {
                if (newline_keywords.includes(text.toUpperCase())) {
                    clean_query += '</br>';
                }
                clean_query += `<span class="cm-${style.replace(' ', '')}">${text}</span>`;
                if (post_newline_keywords.includes(text.toUpperCase())) {
                    clean_query += '</br>';
                }
            } else {
                clean_query += text;
            }
        });

        return clean_query;
    }

    getCombinedSQLData() {
        const sql_data = {
            total_requests: 0,
            total_duration: 0,
            queries: {}
        };
        sql_data.queries[this.initial_request.id] = this.initial_request.sql.queries;
        this.ajax_requests.forEach((request) => {
            if (request.profile && request.profile.sql !== undefined) {
                sql_data.queries[request.id] = request.profile.sql.queries;
            }
        });
        $.each(sql_data.queries, (request_id, data) => {
            // update the total counters
            data.forEach((query) => {
                sql_data.total_requests += 1;
                sql_data.total_duration += parseInt(query['time']);
            });
        });

        return sql_data;
    }

    showDebugToolbar() {
        $('.debug-logo').prop('disabled', true);
        $('.debug-toolbar-content').removeClass('d-none');
        $('#debug-toolbar').addClass('w-100').css('width', null);
        $('body').removeClass('debug-folded');
    }

    hideDebugToolbar() {
        $('.debug-logo').prop('disabled', false);
        $('.debug-toolbar-content').addClass('d-none');
        $('#debug-toolbar-expanded-content').addClass('d-none');
        $('#debug-toolbar').removeClass('w-100').css('width', 'fit-content');
        $('body').addClass('debug-folded');
    }

    toggleExtraContentArea(force_show = false) {
        const content_area = $('#debug-toolbar-expanded-content');
        const toggle_icon = $('#debug-toolbar .debug-toolbar-controls button[name="toggle_content_area"] i');
        if (content_area.hasClass('d-none') || force_show) {
            content_area.removeClass('d-none');
            toggle_icon.removeClass('ti-square-arrow-up').addClass('ti-square-arrow-down');
        } else {
            content_area.addClass('d-none');
            toggle_icon.removeClass('ti-square-arrow-down').addClass('ti-square-arrow-up');
        }
    }

    getProfile(request_id) {
        if (request_id === this.initial_request.id) {
            return this.initial_request;
        }
        return this.ajax_requests.find((request) => request.id === request_id).profile;
    }

    getWidgetButton(widget_id) {
        return $(`#debug-toolbar .debug-toolbar-widgets li[data-glpi-debug-widget-id="${widget_id}"]`);
    }

    refreshWidgetButtons() {
        // Server performance
        const server_perf = this.initial_request.server_performance;
        const memory_usage = +(server_perf.memory_usage / 1024 / 1024).toFixed(2);
        const server_performance_button_label = `${server_perf.execution_time} <span class="text-muted"> ms using </span> ${memory_usage} <span class="text-muted"> MiB </span>`;
        this.getWidgetButton('server_performance').find('.debug-text').html(server_performance_button_label);

        // Database performance
        const sql_data = this.getCombinedSQLData();
        const database_button_label = `${sql_data.total_requests} <span class="text-muted"> requests </span>`;
        this.getWidgetButton('sql').find('.debug-text').html(database_button_label);

        // Requests
        this.getWidgetButton('requests').find('.debug-text').html(`${this.ajax_requests.length} <span class="text-muted"> requests </span>`);

        // Client performances
        const dom_timing = +window.performance.getEntriesByType('navigation')[0].domComplete.toFixed(2);
        const client_performance_button_label = `${dom_timing} <span class="text-muted"> ms </span>`;
        this.getWidgetButton('client_performance').find('.debug-text').html(client_performance_button_label);
    }

    showWidget(widget_id, refresh = false, content_area = undefined, data = {}) {
        if (content_area === undefined) {
            content_area = $('#debug-toolbar-expanded-content');
            // if there is a button in the toolbar for this widget, make it active
            const widget_button = this.getWidgetButton(widget_id);
            if (widget_button.length > 0) {
                $('#debug-toolbar .debug-toolbar-widgets .debug-toolbar-widget').removeClass('active');
                widget_button.addClass('active');
            }
        }
        content_area.data('active-widget', widget_id);

        $.each(data, (key, value) => {
            content_area.data(key, value);
        });

        switch (widget_id) {
            case 'server_performance':
                this.showServerPerformance(content_area, refresh);
                break;
            case 'sql':
                this.showSQLRequests(content_area, refresh);
                break;
            case 'globals':
                this.showGlobals(content_area);
                break;
            case 'client_performance':
                this.showClientPerformance(content_area, refresh);
                break;
            case 'profiler':
                this.showProfiler(content_area, refresh);
                break;
            case 'requests':
                this.showRequests(content_area, refresh);
                break;
            case 'request_summary':
                this.showRequestSummary(content_area);
                break;
            default:
                content_area.empty();
                content_area.append(`<div class="alert alert-danger"><h1>Content for widget ${widget_id} not found</h1></div>`);
        }
    }

    showServerPerformance(content_area, refresh = false) {
        if (!refresh) {
            content_area.empty();

            content_area.append(`
                <div class="py-2 px-3 col-xxl-7 col-xl-9 col-12">
                    <h2 class="mb-3">Server performance</h2>
                    <div class="datagrid"></div>
                </div>
            `);
        }

        const server_perf = this.initial_request.server_performance;
        const memory_usage = (server_perf.memory_usage / 1024 / 1024).toFixed(2);
        const memory_peak = (server_perf.memory_peak / 1024 / 1024).toFixed(2);
        const memory_limit = (server_perf.memory_limit / 1024 / 1024).toFixed(2);
        let total_execution_time = this.initial_request.server_performance.execution_time;
        this.ajax_requests.forEach((request) => {
            if (request.profile) {
                total_execution_time += request.profile.server_performance.execution_time;
            }
        });
        total_execution_time = total_execution_time.toFixed(2);

        content_area.find('.datagrid').empty().append(`
            <div class="datagrid-item">
                <div class="datagrid-title">Initial Execution Time</div>
                <div class="datagrid-content">${+this.initial_request.server_performance.execution_time} ms</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Total Execution Time</div>
                <div class="datagrid-content">${+total_execution_time} ms</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Memory Usage</div>
                <div class="datagrid-content h-100 col-8">${+memory_usage} MiB / ${+memory_limit} MiB</div>
            </div>
            <div class="datagrid-item">
                <div class="datagrid-title">Memory Peak</div>
                <div class="datagrid-content">${+memory_peak} MiB / ${+memory_limit} MiB</div>
            </div>
        `);
    }

    showSQLRequests(content_area, refresh = false) {
        const filtered_request_id = content_area.data('request_id');
        if (filtered_request_id !== undefined && this.getProfile(filtered_request_id) === undefined) {
            this.showMissingRequestData(content_area, content_area.data('request_id'));
            return;
        }
        if (!refresh) {
            content_area.empty();
            content_area.append(`
                <div class="overflow-auto py-2 px-3">
                   <h2 class="mb-3"></h2>
                   <table id="debug-sql-request-table" class="table card-table">
                      <thead>
                      <tr>
                         ${filtered_request_id === undefined ? '<th>Request ID</th>' : ''}
                         <th>Number</th><th>Query</th><th>Time</th><th>Rows</th><th>Warnings</th><th>Errors</th>
                      </tr>
                      </thead>
                      <tbody></tbody>
                   </table>
                </div>
            `);
            initSortableTable('debug-sql-request-table');
        }

        const sql_table = content_area.find('table').first();
        const sql_table_body = sql_table.find('tbody').first();

        // get all the request IDs present in the SQL data (first column values)
        let request_ids_present = new Set();
        sql_table_body.find('tr td:first-child').each((index, cell) => {
            request_ids_present.add($(cell).text());
        });

        const sql_data = this.getCombinedSQLData();

        let rows_to_append = '';
        $.each(sql_data['queries'], (request_id, queries) => {
            if (request_ids_present.has(request_id) ||
                (filtered_request_id !== undefined && filtered_request_id !== request_id)) {
                return;
            }
            queries.forEach((query) => {
                //Note: keep the query cell as a single line, or it will ruin the formatting of the contents
                rows_to_append += `
                    <tr>
                        ${filtered_request_id === undefined ? `<td><button class="btn btn-link request-link">${request_id}</button></td>` : ''}
                        <td>${query['num']}</td>
                        <td style="max-width: 50vw; white-space: break-spaces;"><code class="d-block cm-s-default border-0">${query['query']}</code></td>
                        <td data-value-unit="ms">${query['time']} ms</td>
                        <td>${query['rows']}</td>
                        <td>${escapeMarkupText(query['warnings'])}</td>
                        <td>${escapeMarkupText(query['errors'])}</td>
                    </tr>
                `;
            });
        });
        sql_table_body.append(rows_to_append);

        if (filtered_request_id !== undefined) {
            let total_requests = 0;
            let total_duration = 0;
            $.each(sql_data['queries'], (request_id, queries) => {
                if (request_id === filtered_request_id) {
                    total_requests += queries.length;
                    queries.forEach((query) => {
                        total_duration += parseFloat(query['time']);
                    });
                }
            });
            content_area.find('h2').first()
                .text(`${total_requests} Queries took ${total_duration} ms`);
        } else {
            content_area.find('h2').first()
                .text(`${sql_data.total_requests} Queries took ${sql_data.total_duration} ms`);
        }

        if (sql_table.data('sort')) {
            sql_table.find('thead th').eq(sql_table.data('sort')).click().click();
        }
    }

    showGlobals(content_area) {
        const appendGlobals = (data, container) => {
            if (data === undefined || data === null) {
                container.append('Empty array');
                return;
            }

            let data_string = data;
            try {
                data_string = JSON.stringify(data, null, ' ');
            } catch (e) {
                if (typeof data !== 'string') {
                    container.append('Empty array');
                    return;
                }
            }

            const editor = window.CodeMirror(container.get(0), {
                value: data_string,
                mode: 'application/json',
                lineNumbers: true,
                readOnly: true,
                foldGutter: true,
                gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
            });
            container.data('editor', editor);
        };

        const rand = Math.floor(Math.random() * 1000000);
        content_area.empty();
        content_area.append(`
            <div>
               <div id="debugpanel${rand}" class="container-fluid card p-0 border-top-0" style="min-width: 400px; max-width: 90vw">
                  <ul class="nav nav-pills" data-bs-toggle="tabs">
                     <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#debugpost${rand}">POST</a></li>
                     <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#debugget${rand}">GET</a></li>
                     <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#debugsession${rand}">SESSION</a></li>
                     <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#debugserver${rand}">SERVER</a></li>
                  </ul>

                  <div class="card-body overflow-auto p-1">
                     <div class="tab-content">
                        <div id="debugpost${rand}" class="cm-s-default tab-pane active"></div>
                        <div id="debugget${rand}" class="cm-s-default tab-pane"></div>
                        <div id="debugsession${rand}" class="cm-s-default tab-pane"></div>
                        <div id="debugserver${rand}" class="cm-s-default tab-pane"></div>
                     </div>
                  </div>
               </div>
            </div>
        `);

        const selected_request_id = content_area.data('request_id');

        const matching_profile = this.getProfile(selected_request_id);
        if (matching_profile === undefined) {
            this.showMissingRequestData(content_area, content_area.data('request_id'));
            return;
        }

        const globals = matching_profile.globals;
        appendGlobals(globals['post'], content_area.find(`#debugpost${rand}`));
        appendGlobals(globals['get'], content_area.find(`#debugget${rand}`));
        appendGlobals(globals['session'], content_area.find(`#debugsession${rand}`));
        appendGlobals(globals['server'], content_area.find(`#debugserver${rand}`));

        content_area.on('shown.bs.tab', 'a[data-bs-toggle="tab"]', (e) => {
            const target = $(e.target).attr('href');
            const target_el = content_area.find(target);
            const previously_shown = target_el.data('previously_shown') || false;
            const editor = target_el.data('editor');
            if (!previously_shown && editor) {
                editor.refresh();

                setTimeout(() => {
                    // Stupid solution to fold all levels except the first one.
                    // foldCode(0), would fold the first level only and doesn't handle nested levels.
                    const total_lines = editor.lineCount();
                    // Must start from the bottom, otherwise it doesn't fold parent levels
                    for (let i = total_lines - 1; i > 1; i--) {
                        editor.foldCode(window.CodeMirror.Pos(i, 0));
                    }
                }, 100);
            }
            target_el.data('previously_shown', true);
        });

        // trigger shown.bs.tab on the first tab manually since the event is not triggered on page load
        content_area.find('a[data-bs-toggle="tab"]').first().trigger('shown.bs.tab');
    }

    showClientPerformance(content_area, refresh = false) {
        if (!refresh) {
            content_area.empty();
        }
        const perf = window.performance;
        const nav_timings = window.performance.getEntriesByType('navigation')[0];
        const paint_timings = window.performance.getEntriesByType('paint');
        const resource_timings = window.performance.getEntriesByType('resource');

        let paint_timing = paint_timings.filter((timing) => timing.name === 'first-paint');
        let paint_timing_label = 'Time to first paint';
        if (paint_timing.length === 0) {
            // Firefox doesn't have first-paint for whatever reason
            paint_timing = paint_timings.filter((timing) => timing.name === 'first-contentful-paint');
            paint_timing_label = 'Time to first contentful paint';
        }
        const time_to_first_paint = paint_timing.length > 0 ? paint_timing[0].startTime : -1;
        const time_to_dom_interactive = nav_timings.domInteractive;
        const time_to_dom_complete = nav_timings.domComplete;

        const total_resources = resource_timings.length;
        let total_resources_size = resource_timings.reduce((total, timing) => total + timing.transferSize, 0);
        total_resources_size = total_resources_size / 1024 / 1024;

        content_area.append(`
            <div class="py-2 px-3 col-xxl-7 col-xl-9 col-12">
                <h2 class="mb-3">Client performance</h2>
                <h3 class="mb-2">Timings</h3>
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">${paint_timing_label}</div>
                        <div class="datagrid-content">${+time_to_first_paint.toFixed(2)} ms</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Time to DOM interactive</div>
                        <div class="datagrid-content">${+time_to_dom_interactive.toFixed(2)} ms</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Time to DOM complete</div>
                        <div class="datagrid-content">${+time_to_dom_complete.toFixed(2)} ms</div>
                    </div>
                </div>
                <h3 class="mt-3 mb-2">Resource Loading</h3>
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Total resources</div>
                        <div class="datagrid-content">${total_resources}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Total resources size</div>
                        <div class="datagrid-content">${+total_resources_size.toFixed(2)} MiB</div>
                    </div>
                    <!-- Keep empty item at the end to align with previous grid -->
                    <div class="datagrid-item"></div>
                </div>
            </div>
        `);

        if (perf.memory != undefined) {
            const heap_limit = perf.memory.jsHeapSizeLimit / 1024 / 1024;
            const used_heap = perf.memory.usedJSHeapSize / 1024 / 1024;
            const total_heap = perf.memory.totalJSHeapSize / 1024 / 1024;

            // Non-standard feature supported by Chrome
            content_area.find('.datagrid:last').append(`
                <h3 class="mt-3 mb-2">Memory</h3>
                <div class="datagrid">
                    <div class="datagrid-item">
                        <div class="datagrid-title">Used JS Heap</div>
                        <div class="datagrid-content">${+used_heap.toFixed(2)}</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">Total JS Heap</div>
                        <div class="datagrid-content">${+total_heap.toFixed(2)} MiB</div>
                    </div>
                    <div class="datagrid-item">
                        <div class="datagrid-title">JS Heap Limit</div>
                        <div class="datagrid-content">${+heap_limit.toFixed(2)} MiB</div>
                    </div>
                </div>
            `);
        }
    }

    getProfilerCategoryColor(category) {
        const predefined_colors = {
            core: '#526dad',
            db: '#9252ad',
            twig: '#64ad52',
            plugins: '#a077a6',
        };
        let bg_color = '';
        if (predefined_colors[category] !== undefined) {
            bg_color = predefined_colors[category];
        } else {
            let hash = 0;
            for (let i = 0; i < category.length; i++) {
                hash = category.charCodeAt(i) + ((hash << 5) - hash);
            }
            let color = '#';
            for (let i = 0; i < 3; i++) {
                const value = (hash >> (i * 8)) & 0xFF;
                color += ('00' + value.toString(16)).substr(-2);
            }
            bg_color = color;
        }

        const rgb = hexToRgb(bg_color);
        const text_color = luminance([rgb['r'], rgb['g'], rgb['b']]) > 0.5 ? 'var(--dark)' : 'var(--light)';

        return {
            bg_color: bg_color,
            text_color: text_color
        };
    }

    getProfilerTable(parent_id, profiler_sections, nest_level = 0, parent_duration = 0) {
        let table = `
            <table class="table table-striped card-table">
                <thead>
                    <tr>
                       ${'<th style="min-width: 2rem"></th>'.repeat(nest_level)}
                       <th>Category</th>
                       <th>Name</th>
                       <th>Start</th>
                       <th>End</th>
                       <th>Duration</th>
                       <th>Percent of parent</th>
                   </tr>
                </thead>
                <tbody>
        `;

        const col_count = 6 + nest_level;

        const top_level_sections = profiler_sections.filter((section) => section.parent_id === parent_id);

        top_level_sections.forEach((section) => {
            const cat_colors = this.getProfilerCategoryColor(section.category);
            const duration = section.end - section.start;

            let percent_of_parent = 100;
            if (nest_level > 0) {
                percent_of_parent = (duration / parent_duration) * 100;
            }
            percent_of_parent = percent_of_parent.toFixed(2);

            table += `
                <tr data-profiler-section-id="${section.id}">
                    ${'<td style="min-width: 2rem"></td>'.repeat(nest_level)}
                    <td>
                        <span class="category-badge" style='background-color: ${cat_colors.bg_color}; color: ${cat_colors.text_color}'>
                            ${escapeMarkupText(section.category)}
                        </span>
                    </td>
                    <td>${escapeMarkupText(section.name)}</td><td>${section.start}</td><td>${section.end}</td>
                    <td data-column="duration" data-duration-raw="${duration}">${duration.toFixed(0)} ms</td>
                    <td>${percent_of_parent}%</td>
                </tr>
            `;

            const children = profiler_sections.filter((child) => child.parent_id === section.id);
            if (children.length > 0) {
                const children_table = this.getProfilerTable(section.id, profiler_sections, nest_level + 1, duration);
                table += `<tr><td colspan="${col_count}">${children_table}</td></tr>`;
            }
        });

        table += '</tbody></table>';

        return table;
    }

    showProfiler(content_area, refresh = false) {
        if (!refresh) {
            content_area.empty();
        }
        content_area.append(`
            <div>
               <label>
                 Hide near-instant sections (&lt;= 1 ms):
                 <input type="checkbox" name="hide_instant_sections">
               </label>
            </div>
        `);

        const selected_request_id = content_area.data('request_id');


        const hide_instant_sections_box = content_area.find('input[name="hide_instant_sections"]');
        const hide_instant_sections = content_area.data('profiler_hide_instant_sections') || true;
        hide_instant_sections_box.prop('checked', hide_instant_sections);

        hide_instant_sections_box.off('change').on('change', (e) => {
            const hide = $(e.target).prop('checked');
            content_area.data('profiler_hide_instant_sections', hide);
            const table_rows = content_area.find('tr');

            // Start by un-hiding all rows
            table_rows.removeClass('d-none');

            if (hide) {
                // hide all rows in the table that have the duration column set less than 1 ms
                table_rows.each((index, row) => {
                    const duration_cell = $(row).find('> td[data-column="duration"]');
                    if (duration_cell.length > 0) {
                        const duration_value = parseFloat(duration_cell.attr('data-duration-raw'));
                        if (duration_value <= 1.0) {
                            $(row).addClass('d-none');
                        }
                    }
                });
            }

            // If any table has no visible rows, hide the whole table
            content_area.find('table').each((index, table) => {
                const table_el = $(table);
                const table_parent = table_el.parent();
                if (table_el.find('> tbody > tr:not(.d-none)').length === 0) {
                    table_el.addClass('d-none');
                    if (table_parent.prop('tagName') === 'TD') {
                        table_parent.addClass('d-none');
                    }
                } else {
                    table_el.removeClass('d-none');
                    if (table_parent.prop('tagName') === 'TD') {
                        table_parent.removeClass('d-none');
                    }
                }
            });
        });


        const matching_profile = this.getProfile(selected_request_id);
        if (matching_profile === undefined) {
            this.showMissingRequestData(content_area, selected_request_id);
            return;
        }
        const profiler = matching_profile.profiler || {};

        // get profiler entries and sort them by start time
        // Logically, child entries should remain under their parent since everything is done synchronously
        const profiler_sections = Object.values(profiler).sort((a, b) => a.start - b.start);

        content_area.find('> div').append(this.getProfilerTable(null, profiler_sections));
        hide_instant_sections_box.trigger('change');
    }

    showRequests(content_area, refresh = false) {
        if (!refresh) {
            content_area.empty();
            const rand = Math.floor(Math.random() * 1000000);
            content_area.append(`
                <div class="request-timeline"></div>
                <div class="d-flex flex-row h-100 split-panel-h">
                    <div class="left-panel">
                        <div class="overflow-auto h-100 me-2">
                            <table id="debug-requests-table" class="table table-hover mb-1">
                                <thead>
                                    <tr>
                                        <th>Number</th>
                                        <th style="max-width: 200px; white-space: pre-wrap;">URL</th>
                                        <th>Status</th>
                                        <th>Type</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody style="white-space: nowrap">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="resize-handle"></div>
                    <div class="right-panel overflow-auto ms-2 flex-grow-1">
                        <div id="debugpanel${rand}" class="p-0 mt-n1">
                            <ul class="nav nav-tabs" data-bs-toggle="tabs">
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-glpi-debug-widget-id="request_summary">Summary</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-glpi-debug-widget-id="sql">SQL</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-glpi-debug-widget-id="globals">Globals</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-glpi-debug-widget-id="profiler">Profiler</button>
                                </li>
                            </ul>

                            <div class="card-body overflow-auto p-1">
                                <div class="tab-content request-details-content-area">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            this.showRequestTimeline(content_area.find('.request-timeline').eq(0));
            const truncated_pathname = window.location.pathname.substring(0, this.REQUEST_PATH_LENGTH);
            const is_truncated = truncated_pathname.length < window.location.pathname.length;
            content_area.find('#debug-requests-table tbody').append(`
                <tr data-request-id="${this.initial_request.id}" class="cursor-pointer table-active">
                    <td>0</td>
                    <td style="max-width: 200px; white-space: pre-wrap;" title="${window.location.pathname}" data-truncated="${is_truncated ? 'true' : 'false'}">${truncated_pathname}</td>
                    <td>-</td>
                    <td>${this.initial_request.globals.server['REQUEST_METHOD'] || '-'}</td>
                    <td>${this.initial_request.server_performance.execution_time} ms</td>
                </tr>
            `);
            if (is_truncated) {
                content_area.find(`tr[data-request-id="${this.initial_request.id}"] td[data-truncated="true"]`).append(
                    `<button type="button" class="ms-1 badge bg-secondary" name="show_full_url">
                        <i class="ti ti-dots"></i>
                    </button>`
                );
            }
            const resize_handle = content_area.find('.resize-handle');
            // Make the resize handle draggable to resize the left column
            let is_dragging = false;
            resize_handle.on('mousedown', (e) => {
                if (e.buttons === 1) {
                    is_dragging = true;
                    e.preventDefault();
                }
            });
            content_area.on('mousemove', (e) => {
                if (is_dragging && e.buttons === 1) {
                    const left_column = content_area.find('> div > div:first-child');
                    const new_width = e.pageX - left_column.offset().left;
                    left_column.css('flex', `0 0 ${new_width}px`);
                }
            });
            content_area.on('mouseup', () => {
                is_dragging = false;
            });
            content_area.on('click', 'button[data-glpi-debug-widget-id]', (e) => {
                const widget_id = $(e.currentTarget).attr('data-glpi-debug-widget-id');
                content_area.data('requests_active_widget', widget_id);
                this.showWidget(widget_id, false, content_area.find('.request-details-content-area'), {
                    request_id: content_area.data('requests_request_id') || this.initial_request.id,
                });
            });
            content_area.on('click', '#debug-requests-table tbody tr', (e) => {
                content_area.data('requests_request_id', $(e.currentTarget).attr('data-request-id'));
                $(e.currentTarget).addClass('table-active').siblings().removeClass('table-active');
                this.showWidget(content_area.data('requests_active_widget') || 'request_summary', false, content_area.find('.request-details-content-area'), {
                    request_id: content_area.data('requests_request_id') || this.initial_request.id,
                });
            });
            content_area.on('click', 'button[name="show_full_url"]', (e) => {
                const btn = $(e.currentTarget);
                const td = btn.closest('td');
                td.text(td.attr('title'));
                btn.hide();
            });
            if (content_area.data('requests_request_id') === undefined) {
                content_area.data('requests_request_id', this.initial_request.id);
            }
            if (content_area.find('.request-details-content-area').data('request_id') === undefined) {
                content_area.find('.request-details-content-area').data('request_id', this.initial_request.id);
            }

            content_area.find('button[data-glpi-debug-widget-id="request_summary"]').click();
            initSortableTable('debug-requests-table');
        }

        // Add all AJAX requests to the list that aren't already there
        this.ajax_requests.forEach((request) => {
            const row = content_area.find(`tr[data-request-id="${request.id}"]`);
            if (row.length === 0) {
                const next_number = content_area.find('#debug-requests-table tbody tr').length;
                const truncated_url = request.url.substring(0, this.REQUEST_PATH_LENGTH);
                const is_truncated = truncated_url.length < request.url.length;
                content_area.find('#debug-requests-table tbody').append(`
                    <tr data-request-id="${request.id}" class="cursor-pointer">
                        <td>${next_number}</td>
                        <td style="max-width: 200px; white-space: pre-wrap;" title="${request.url}" data-truncated="${is_truncated ? 'true' : 'false'}">${truncated_url}</td>
                        <td>${request.status}</td>
                        <td>${request.type}</td>
                        <td data-value-unit="ms">${request.time} ms</td>
                    </tr>
                `);
                if (is_truncated) {
                    if (content_area.find(`tr[data-request-id="${request.id}"] td[data-truncated="true"] button[name="show_full_url"]`).length === 0) {
                        content_area.find(`tr[data-request-id="${request.id}"] td[data-truncated="true"]`).append(
                            `<button type="button" class="ms-1 badge bg-secondary" name="show_full_url">
                        <i class="ti ti-dots"></i>
                    </button>`
                        );
                    }
                }
                // set the background color of the new row to a pale yellow and fade it out
                const new_row = content_area.find(`tr[data-request-id="${request.id}"]`);
                new_row.css('background-color', '#FFFF7B80');
                setTimeout(() => {
                    new_row.css('background-color', 'transparent');
                }, 2000);

                const requests_table = content_area.find('#debug-requests-table');
                if (requests_table.data('sort')) {
                    requests_table.find('thead th').eq(requests_table.data('sort')).click().click();
                } else {
                    // scroll to the bottom of the table
                    requests_table.parent().scrollTop(requests_table.parent()[0].scrollHeight);
                }
            } else {
                row.find('> td:nth-child(3)').text(request.status);
                row.find('> td:nth-child(5)').text(`${request.time} ms`);
            }
        });
    }

    showMissingRequestData(content_area, request_id) {
        content_area.empty();
        content_area.append(`
            <div class="alert alert-danger">
            <span>No debug data was found for this request immediately after it finished. Some requests like /front/locale.php will never have data as they intentionally close the session.</span>
            </div>
            <button type="button" class="btn btn-primary" data-request-id="${request_id}"><i class="ti ti-reload"></i>Retry</button>
        `);
        content_area.find('button').on('click', (e) => {
            const btn = $(e.currentTarget);
            const request_id = btn.data('request-id');
            this.requestAjaxDebugData(request_id, true);
        });
    }

    showRequestSummary(content_area) {
        content_area.empty();
        const profile = this.getProfile(content_area.data('request_id'));

        if (profile === undefined) {
            this.showMissingRequestData(content_area, content_area.data('request_id'));
            return;
        }
        const server_perf = profile.server_performance;
        const memory_usage = (server_perf.memory_usage / 1024 / 1024).toFixed(2);
        const memory_peak = (server_perf.memory_peak / 1024 / 1024).toFixed(2);
        const memory_limit = (server_perf.memory_limit / 1024 / 1024).toFixed(2);
        let total_execution_time = profile.server_performance.execution_time;

        let total_sql_duration = 0;
        let total_sql_queries = 0;
        $.each(profile.sql['queries'], (i, query) => {
            total_sql_queries++;
            total_sql_duration += parseFloat(query['time']);
        });
        content_area.append(`
            <h1>Request Summary (${profile.id})</h1>
            <table class="table">
                <tbody>
                    <tr>
                        <td>
                            Initial Execution Time: ${total_execution_time} ms
                        </td>
                        <td>
                            Memory Usage: ${memory_usage} MiB / ${memory_limit} MiB
                            <br>
                            Memory Peak: ${memory_peak} MiB / ${memory_limit} MiB
                        </td>
                    </tr>
                    <tr>
                        <td>
                            SQL Requests: ${total_sql_queries}
                            <br>
                            SQL Duration: ${total_sql_duration} ms
                        </td>
                    </tr>
                </tbody>
            </table>
        `);
    }

    /**
     * Get all timings for requests
     */
    getAllRequestTimings() {
        const navigation_timings = window.performance.getEntriesByType('navigation')[0];
        const resource_timings = window.performance.getEntriesByType('resource');

        const timings = [];
        timings.push({
            type: 'navigation',
            name: navigation_timings.name,
            start: navigation_timings.startTime,
            end: navigation_timings.responseEnd,
            bounds: {},
            sections: {
                queued: [navigation_timings.startTime, navigation_timings.redirectStart],
                redirect: [navigation_timings.redirectStart, navigation_timings.redirectEnd],
                fetch: [navigation_timings.redirectEnd, navigation_timings.redirectStart],
                dns: [navigation_timings.domainLookupStart, navigation_timings.domainLookupEnd],
                connection: [navigation_timings.connectStart, navigation_timings.connectEnd],
                initial_connection: [navigation_timings.connectStart, navigation_timings.secureConnectionStart],
                ssl: [navigation_timings.secureConnectionStart, navigation_timings.connectEnd],
                request: [navigation_timings.requestStart, navigation_timings.responseStart], // Mainly waiting for the server to respond
                response: [navigation_timings.responseStart, navigation_timings.responseEnd],
            }
        });

        $.each(resource_timings, (i, resource_timing) => {
            timings.push({
                type: resource_timing.initiatorType,
                name: resource_timing.name,
                start: resource_timing.startTime,
                end: resource_timing.responseEnd,
                bounds: {},
                sections: {
                    queued: [resource_timing.startTime, resource_timing.redirectStart !== 0 ? resource_timing.redirectStart : resource_timing.domainLookupStart],
                    redirect: [resource_timing.redirectStart, resource_timing.redirectEnd],
                    fetch: [resource_timing.redirectEnd, resource_timing.redirectStart],
                    dns: [resource_timing.domainLookupStart, resource_timing.domainLookupEnd],
                    connection: [resource_timing.connectStart, resource_timing.connectEnd],
                    initial_connection: [resource_timing.connectStart, resource_timing.secureConnectionStart],
                    ssl: [resource_timing.secureConnectionStart, resource_timing.connectEnd],
                    request: [resource_timing.requestStart, resource_timing.responseStart], // Mainly waiting for the server to respond
                    response: [resource_timing.responseStart, resource_timing.responseEnd],
                }
            });
        });

        // find the longest duration based on the response end
        let longest_duration = 0;
        $.each(timings, (i, timing) => {
            const response_end = timing.sections.response[1];
            if (response_end > longest_duration) {
                longest_duration = response_end;
            }
        });

        return {
            end_ts: longest_duration,
            timings: timings
        };
    }

    showRequestTimeline(content_area) {
        content_area.empty();

        const timing_data = this.getAllRequestTimings();
        const end_ts = timing_data.end_ts;
        const timings = timing_data.timings;
        const time_origin = window.performance.timeOrigin;

        // group timings into sections so that there are no overlaps (based on start and end times)
        const sections = [];
        const hasOverlap = (section, start, end) => {
            // check if the start or end time would fall within any of the timings in the given section
            let overlap = false;
            $.each(section, (i, timing) => {
                if ((start >= timing.start && start <= timing.end) || (end >= timing.start && end <= timing.end)) {
                    overlap = true;
                    return false;
                }
            });
            return overlap;
        };

        $.each(timings, (i, timing) => {
            let section = null;
            $.each(sections, (i, s) => {
                if (!hasOverlap(s, timing.start, timing.end)) {
                    section = s;
                    return false;
                }
            });

            if (section === null) {
                section = [timing];
                sections.push(section);
            } else {
                section.push(timing);
            }
        });

        const TIMELINE_REFRESH_RATE = 10; // 10 FPS
        const DIVIDER_WIDTH = 150;
        const ROW_HEIGHT = 4;
        const ROW_MARGIN = 2;

        content_area.append(`
            <canvas class="d-none" height="${(sections.length * (ROW_HEIGHT + ROW_MARGIN)) + 12}"></canvas>
        `);
        const canvas_el = content_area.find('canvas').eq(0);

        content_area.closest('#debug-toolbar').on('keyup', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (e.keyCode === 84) { // 't'
                canvas_el.toggleClass('d-none');
            }
        });

        /**
         * @type {CanvasRenderingContext2D}
         */
        const ctx = canvas_el[0].getContext('2d');

        const division_length = 100;

        const text_color = canvas_el.css('color');

        const refresh = window.setInterval(() => {
            if (content_area.find('canvas').length === 0) {
                window.clearInterval(refresh);
                return;
            }
            canvas_el.trigger('render');
        }, 1000 / TIMELINE_REFRESH_RATE);
        const is_entry_selected = (entry, entry_i, all_entries) => {
            const selected_request = canvas_el.closest('#debug-toolbar-expanded-content').data('requests_request_id');
            let is_selected = false;
            if (selected_request === this.initial_request.id && entry.type === 'navigation') {
                is_selected = true;
            } else {
                const ajax_request = this.ajax_requests.find(r => r.id === selected_request);
                if (ajax_request === undefined) {
                    return false;
                }
                const matches_by_url = [];
                $.each(all_entries, (i, e) => {
                    if (e.name.endsWith(ajax_request.url)) {
                        matches_by_url.push({
                            i: i,
                            entry: e,
                        });
                    }
                });
                if (matches_by_url.length === 1 && matches_by_url[0].i === entry_i) {
                    is_selected = true;
                } else {
                    // find the match that is closest to the section start
                    let closest_match = null;
                    matches_by_url.forEach((i, request) => {
                        if (closest_match === null) {
                            closest_match = request;
                            return;
                        }
                        const ajax_start = request.start - time_origin;
                        if (Math.abs(ajax_start - entry.start) < Math.abs(closest_match.start - entry.start)) {
                            closest_match = request;
                        }
                    });
                    is_selected = closest_match !== null && closest_match.i === entry_i;
                }
            }
            return is_selected;
        };

        let hover_data = null;
        canvas_el.on('render', () => {
            if (canvas_el.hasClass('d-none')) {
                return;
            }
            canvas_el.attr('width', canvas_el.parent().width());
            const canvas_width = canvas_el.width();
            const canvas_height = canvas_el.height();

            // round end_ts to nearest 100 ms
            const end_ts_rounded = Math.ceil(end_ts / 100) * 100;
            const division_count = Math.min(Math.ceil(canvas_width / DIVIDER_WIDTH), Math.ceil(end_ts_rounded / division_length));
            const dividers = [];
            for (let i = 0; i < division_count; i++) {
                dividers.push({
                    canvas_x: Math.round(canvas_width / division_count * i),
                    time: Math.ceil((end_ts_rounded / division_count) * i / 100) * 100
                });
            }

            ctx.fillStyle = '#80808040';
            ctx.fillRect(0, 0, canvas_width, canvas_height);
            // draw division lines
            $.each(dividers, (i, divider) => {
                ctx.fillStyle = text_color;
                ctx.strokeStyle = text_color;
                ctx.font = ctx.font.replace(/\d+px/, '10px');
                ctx.beginPath();
                ctx.moveTo(divider.canvas_x, 0);
                ctx.lineTo(divider.canvas_x, canvas_height);
                ctx.stroke();
                ctx.fillText(`${divider.time} ms`, divider.canvas_x + 2, 10);
            });

            // draw sections
            $.each(sections, (i, row) => {
                const row_y = (i * (ROW_HEIGHT + ROW_MARGIN)) + 12;
                $.each(row, (i, entry) => {
                    const is_selected = is_entry_selected(entry, i, row);
                    const timings = entry.sections;
                    $.each(timings, (t, timing) => {
                        // if timing start and end are 0, skip
                        if (timing[0] === 0 && timing[1] === 0) {
                            return;
                        }
                        const color = this.TIMING_COLORS[t] || '#00aa00';
                        const width = Math.round((timing[1] - timing[0]) / end_ts * canvas_width);
                        const x = Math.round(timing[0] / end_ts * canvas_width);
                        entry.bounds[t] = {
                            x: x,
                            y: row_y,
                            width: width,
                            height: ROW_HEIGHT
                        };
                        ctx.fillStyle = color;
                        ctx.fillRect(x, row_y, width, ROW_HEIGHT);
                        if (is_selected) {
                            const stroke_style = ctx.strokeStyle;
                            ctx.strokeStyle = '#ffff00';
                            ctx.strokeRect(x, row_y, width, ROW_HEIGHT);
                            ctx.strokeRect(x - 1, row_y - 1, width + 2, ROW_HEIGHT + 2);
                            ctx.strokeStyle = stroke_style;
                        }
                    });
                });
            });

            // draw tooltip
            if (hover_data !== null) {
                ctx.fillStyle = '#808080';
                const section = hover_data.target.section;
                const duration = section.sections[hover_data.target.timing][1] - section.sections[hover_data.target.timing][0];
                let section_name = section.name;
                if (section_name.length > 100) {
                    section_name = section_name.slice(0, 100) + '...';
                }
                const text = `${section_name} ${hover_data.target.timing} (${duration.toFixed(0)} ms)`;
                ctx.font = ctx.font.replace(/\d+px/, '14px');
                const text_width = ctx.measureText(text).width;

                ctx.fillRect(section.bounds[hover_data.target.timing].x, section.bounds[hover_data.target.timing].y + ROW_HEIGHT, text_width + 4, 18);
                ctx.fillStyle = text_color;
                ctx.fillText(text, section.bounds[hover_data.target.timing].x + 2, section.bounds[hover_data.target.timing].y + ROW_HEIGHT + 14);
            }
        });

        canvas_el.on('mousemove', (e) => {
            // get canvas x and y
            const canvas_x = e.offsetX;
            const canvas_y = e.offsetY;

            // find the section that the mouse is over
            let hover_target = null;
            $.each(sections, (i, row) => {
                $.each(row, (i, entry) => {
                    $.each(entry.bounds, (t, bounds) => {
                        if (canvas_x >= bounds.x && canvas_x <= bounds.x + bounds.width && canvas_y >= bounds.y && canvas_y <= bounds.y + bounds.height) {
                            hover_target = {
                                section: entry,
                                timing: t
                            };
                            return false;
                        }
                    });
                    if (hover_target !== null) {
                        return false;
                    }
                });
                if (hover_target !== null) {
                    return false;
                }
            });
            if (hover_target !== null) {
                hover_data = {
                    target: hover_target,
                };
                canvas_el.css('cursor', 'pointer');
            } else {
                hover_data = null;
                canvas_el.css('cursor', 'default');
            }
        });

        canvas_el.on('mouseleave', () => {
            hover_data = null;
            canvas_el.css('cursor', 'default');
        });

        canvas_el.on('click', () => {
            if (hover_data !== null) {
                const section = hover_data.target.section;
                let selected_request_id = null;
                if (section.type === 'navigation') {
                    selected_request_id = this.initial_request.id;
                } else {
                    const matches_by_url = [];
                    $.each(this.ajax_requests, (i, request) => {
                        if (section.name.endsWith(request.url)) {
                            matches_by_url.push(request);
                        }
                    });
                    if (matches_by_url.length === 1) {
                        selected_request_id = matches_by_url[0].id;
                    } else {
                        // find the match that is closest to the section start
                        let closest_match = null;
                        matches_by_url.forEach((i, request) => {
                            if (closest_match === null) {
                                closest_match = request;
                                return;
                            }
                            const ajax_start = request.start - time_origin;
                            if (Math.abs(ajax_start - section.start) < Math.abs(closest_match.start - section.start)) {
                                closest_match = request;
                            }
                        });
                        if (closest_match !== null) {
                            selected_request_id = closest_match.id;
                        }
                    }
                }

                if (selected_request_id !== null) {
                    const main_content = canvas_el.closest('#debug-toolbar-expanded-content');
                    main_content.data('requests_request_id', selected_request_id);
                    this.showWidget(main_content.data('requests_active_widget') || 'request_summary', false, main_content.find('.request-details-content-area'), {
                        request_id: main_content.data('requests_request_id'),
                    });
                    canvas_el.trigger('render');
                }
            }
        });
    }
};
