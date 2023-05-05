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
                'start': new Date(),
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
                    // If the server sent a X-GLPI-Debug-Server-Global header, store it
                    if (xhr.getResponseHeader('X-GLPI-Debug-Server-Global') !== null) {
                        ajax_request.server_global = JSON.parse(xhr.getResponseHeader('X-GLPI-Debug-Server-Global'));
                    }

                    // Ask the server for the debug information it saved for this request
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
                    });
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
                sql_data.total_duration += parseFloat(query['time']);
            });
        });

        return sql_data;
    }

    showDebugToolbar() {
        $('.debug-toolbar-content').removeClass('d-none');
        $('#debug-toolbar').addClass('w-100').css('width', null);
    }

    hideDebugToolbar() {
        $('.debug-toolbar-content').addClass('d-none');
        $('#debug-toolbar-expanded-content').addClass('d-none');
        $('#debug-toolbar').removeClass('w-100').css('width', 'fit-content');
    }

    toggleExtraContentArea(force_show = false) {
        const content_area = $('#debug-toolbar-expanded-content');
        const toggle_icon = $('#debug-toolbar .debug-toolbar-controls button[name="toggle_content_area"] i');
        if (content_area.hasClass('d-none') || force_show) {
            content_area.removeClass('d-none');
            toggle_icon.removeClass('ti-square-arrow-down').addClass('ti-square-arrow-up');
        } else {
            content_area.addClass('d-none');
            toggle_icon.removeClass('ti-square-arrow-up').addClass('ti-square-arrow-down');
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
        const server_performance_button = this.getWidgetButton('server_performance');
        const server_perf = this.initial_request.server_performance;
        const memory_usage_mio = (server_perf.memory_usage / 1024 / 1024).toFixed(2);
        server_performance_button.find('.debug-text').text(`${server_perf.execution_time}s | ${memory_usage_mio}mio`);

        const sql_count = this.getCombinedSQLData().total_requests;
        const sql_button = this.getWidgetButton('sql');
        sql_button.find('.debug-text').text(sql_count);

        const ajax_requests_button = this.getWidgetButton('ajax_requests');
        ajax_requests_button.find('.debug-text').text(this.ajax_requests.length);

        this.getWidgetButton('requests').find('.debug-text').text('Requests');
    }

    showWidget(widget_id, refresh = false, content_area = undefined, data = {}) {
        if (content_area === undefined) {
            content_area = $('#debug-toolbar-expanded-content');
            // if there is a button in the toolbar for this widget, make it active
            const widget_button = this.getWidgetButton(widget_id);
            if (widget_button.length > 0) {
                $('#debug-toolbar .debug-toolbar-widgets .debug-toolbar-widget button').removeClass('active');
                widget_button.find('button').addClass('active');
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
                content_area.append(`<h1>Content for widget ${widget_id} not found</h1>`);
        }
    }

    showServerPerformance(content_area, refresh = false) {
        if (!refresh) {
            content_area.empty();

            content_area.append(`
                <h1>Server performance</h1>
                <table class="table">
                    <tbody></tbody>
                </table>
            `);
        }

        const server_perf = this.initial_request.server_performance;
        const memory_usage_mio = (server_perf.memory_usage / 1024 / 1024).toFixed(2);
        const memory_peak_mio = (server_perf.memory_peak / 1024 / 1024).toFixed(2);
        const memory_limit_mio = (server_perf.memory_limit / 1024 / 1024).toFixed(2);
        let total_execution_time = this.initial_request.server_performance.execution_time;
        this.ajax_requests.forEach((request) => {
            if (request.profile) {
                total_execution_time += request.profile.server_performance.execution_time;
            }
        });
        total_execution_time = total_execution_time.toFixed(2);

        content_area.find('table tbody').empty().append(`
            <tr>
                <td>
                    Initial Execution Time: ${this.initial_request.server_performance.execution_time}s
                    <br>
                    Total Execution Time: ${total_execution_time}s
                </td>
                <td>
                    Memory Usage: ${memory_usage_mio}mio / ${memory_limit_mio}mio
                    <br>
                    Memory Peak: ${memory_peak_mio}mio / ${memory_limit_mio}mio
                </td>
            </tr>
        `);
    }

    showSQLRequests(content_area, refresh = false) {
        const filtered_request_id = content_area.data('request_id');
        if (!refresh) {
            content_area.empty();
            content_area.append(`
                <div class="overflow-auto">
                   <h1></h1>
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
                        <td>${query['time']}ms</td>
                        <td>${query['rows']}</td>
                        <td>${query['warnings']}</td>
                        <td>${query['errors']}</td>
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
            content_area.find('h1').first()
                .text(`${total_requests} Queries took ${total_duration}ms`);
        } else {
            content_area.find('h1').first()
                .text(`${sql_data.total_requests} Queries took ${sql_data.total_duration}ms`);
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
        const globals = matching_profile.globals;
        appendGlobals(globals['post'], content_area.find(`#debugpost${rand}`));
        appendGlobals(globals['get'], content_area.find(`#debugget${rand}`));
        if (selected_request_id === this.initial_request.id) {
            appendGlobals(globals['session'], content_area.find(`#debugsession${rand}`));
        } else {
            content_area.find(`#debugsession${rand}`).html(`<div class="alert alert-warning">Session data is only available for the initial request</div>`);
        }
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

        const time_to_first_paint = paint_timings.filter((timing) => timing.name === 'first-paint')[0].startTime;
        const time_to_dom_interactive = nav_timings.domInteractive;
        const time_to_dom_complete = nav_timings.domComplete;

        const total_resources = resource_timings.length;
        let total_resources_size = resource_timings.reduce((total, timing) => total + timing.transferSize, 0);
        total_resources_size = total_resources_size / 1024 / 1024;

        content_area.append(`
            <table class="table">
                <tbody>
                    <tr><th colspan="4">Timings</th></tr>
                    <tr>
                        <th>Time to first paint</th><td>${time_to_first_paint.toFixed(2)}ms</td>
                        <th>Time to DOM interactive</th><td>${time_to_dom_interactive.toFixed(2)}ms</td>
                    </tr>
                    <tr>
                        <th>Time to DOM complete</th><td>${time_to_dom_complete.toFixed(2)}ms</td>
                    </tr>
                    <tr><th colspan="4">Resource Loading</th></tr>
                    <tr>
                        <th>Total resources</th><td>${total_resources}</td>
                        <th>Total resources size</th><td>${total_resources_size.toFixed(2)}mio</td>
                    </tr>
                </tbody>
            </table>
        `);

        if (perf.memory != undefined) {
            const heap_limit = perf.memory.jsHeapSizeLimit / 1024 / 1024;
            const used_heap = perf.memory.usedJSHeapSize / 1024 / 1024;
            const total_heap = perf.memory.totalJSHeapSize / 1024 / 1024;

            // Non-standard feature supported by Chrome
            content_area.find('tbody').append(`
                <tr><th colspan="4">Memory</th></tr>
                <tr>
                    <th>Used JS Heap</th><td>${used_heap.toFixed(2)}mio</td>
                    <th>Total JS Heap</th><td>${total_heap.toFixed(2)}mio</td>
                </tr>
                <tr>
                    <th>JS Heap Limit</th><td>${heap_limit.toFixed(2)}mio</td>
                </tr>
            `);
        }
    }

    getProfilerCategoryColor(category) {
        const predefined_colors = {
            core: '#526dad',
            db: '#9252ad',
            twig: '#64ad52'
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
                            ${section.category}
                        </span>
                    </td>
                    <td>${section.name}</td><td>${section.start}</td><td>${section.end}</td>
                    <td data-column="duration" data-duration-raw="${duration}">${duration.toFixed(0)}ms</td>
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
                 Hide near-instant sections (<=1ms):
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
                // hide all rows in the table that have the duration column set less than 1ms
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
                <div class="d-flex flex-row h-100 split-panel-h">
                    <div class="left-panel overflow-auto">
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
            content_area.find('#debug-requests-table tbody').append(`
                <tr data-request-id="${this.initial_request.id}" class="cursor-pointer table-active">
                    <td>0</td>
                    <td style="max-width: 200px; white-space: pre-wrap;">${window.location.pathname}</td>
                    <td>-</td>
                    <td>${this.initial_request.globals.server['REQUEST_METHOD'] || '-'}</td>
                    <td>${this.initial_request.server_performance.execution_time * 1000}ms</td>
                </tr>
            `);
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
                content_area.find('#debug-requests-table tbody').append(`
                    <tr data-request-id="${request.id}" class="cursor-pointer">
                        <td>${next_number}</td>
                        <td style="max-width: 200px; white-space: pre-wrap;">${request.url}</td>
                        <td>${request.status}</td>
                        <td>${request.type}</td>
                        <td>${request.time}ms</td>
                    </tr>
                `);
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
                row.find('> td:nth-child(5)').text(`${request.time}ms`);
            }
        });
    }

    showRequestSummary(content_area) {
        content_area.empty();
        const profile = this.getProfile(content_area.data('request_id'));

        const server_perf = profile.server_performance;
        const memory_usage_mio = (server_perf.memory_usage / 1024 / 1024).toFixed(2);
        const memory_peak_mio = (server_perf.memory_peak / 1024 / 1024).toFixed(2);
        const memory_limit_mio = (server_perf.memory_limit / 1024 / 1024).toFixed(2);
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
                            Initial Execution Time: ${total_execution_time}s
                        </td>
                        <td>
                            Memory Usage: ${memory_usage_mio}mio / ${memory_limit_mio}mio
                            <br>
                            Memory Peak: ${memory_peak_mio}mio / ${memory_limit_mio}mio
                        </td>
                    </tr>
                    <tr>
                        <td>
                            SQL Queries: ${total_sql_queries}
                            <br>
                            SQL Duration: ${total_sql_duration}ms
                        </td>
                    </tr>
                </tbody>
            </table>
        `);
    }
};
