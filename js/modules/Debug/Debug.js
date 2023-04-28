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
            this.ajax_requests.push({
                'id': ajax_id,
                'status': '...',
                'status_type': 'info',
                'type': settings.type,
                'data': settings.data,
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
                        url: CFG_GLPI['url_base'] + '/ajax/debug.php',
                        data: {
                            'ajax_id': ajax_id,
                        }
                    }).done((data) => {
                        ajax_request.profile = data;
                        const content_area = $('#debug-toolbar-expanded-content');
                        if (content_area.data('active-widget') !== undefined) {
                            this.showWidget(content_area.data('active-widget'));
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
                sql_data.total_duration += parseFloat(query['time'].match(/(\d+)(\.\d+)?/)[0]);
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
    }

    showWidget(widget_id) {
        const content_area = $('#debug-toolbar-expanded-content');
        content_area.empty();
        content_area.data('active-widget', widget_id);

        switch (widget_id) {
            case 'server_performance':
                this.showServerPerformance(content_area);
                break;
            case 'sql':
                this.showSQLRequests(content_area);
                break;
            case 'globals':
                this.showGlobals(content_area);
                break;
            case 'ajax_requests':
                this.showAJAXRequests(content_area);
                break;
            case 'client_performance':
                this.showClientPerformance(content_area);
                break;
            case 'profiler':
                this.showProfiler(content_area);
                break;
            default:
                content_area.append(`<h1>Content for widget ${widget_id} not found</h1>`);
        }
    }

    showServerPerformance(content_area) {
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
        content_area.append(`
            <h1>Server performance</h1>
            <table class="table">
                <tbody>
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
                </tbody>
            </table>
        `);
    }

    showSQLRequests(content_area) {
        content_area.append(`
            <div>
               <h1></h1>
               <table id="debug-sql-request-table" class="table card-table">
                  <thead>
                  <tr>
                     <th>Request ID</th><th>N°</th><th>Query</th><th>Time</th><th>Rows</th><th>Warnings</th><th>Errors</th>
                  </tr>
                  </thead>
                  <tbody></tbody>
               </table>
            </div>
        `);
        const sql_table = content_area.find('table').first();
        const sql_table_body = sql_table.find('tbody').first();

        const sql_data = this.getCombinedSQLData();

        $.each(sql_data['queries'], (request_id, queries) => {
            queries.forEach((query) => {
                const clean_sql = query['query']
                    .replace('>', `&gt;`)
                    .replace('<', `&lt;`)
                    .replace('UNION', `</br>UNION</br>`)
                    .replace('FROM', `</br>FROM`)
                    .replace('WHERE', `</br>WHERE`)
                    .replace('INNER JOIN', `</br>INNER JOIN`)
                    .replace('LEFT JOIN', `</br>LEFT JOIN`)
                    .replace('ORDER BY', `</br>ORDER BY`)
                    .replace('SORT', `</br>SORT`);
                sql_table_body.append(`
                    <tr>
                        <td>${request_id}</td>
                        <td>${query['num']}</td>
                        <td style="max-width: 50vw; white-space: break-spaces;">${clean_sql}</td>
                        <td>${query['time']}</td>
                        <td>${query['rows']}</td>
                        <td>${query['warnings']}</td>
                        <td>${query['errors']}</td>
                    </tr>
                `);
            });
        });

        content_area.find('h1').first()
            .text(`${sql_data.total_requests} Queries took ${sql_data.total_duration.toFixed(3)} seconds`);

        initSortableTable('debug-sql-request-table');
    }

    showGlobals(content_area) {
        /**
         *
         * @param {{}} data
         * @param pad
         * @param js_expand
         * @returns {string}
         */
        const getCleanArray = (data, pad = 0, js_expand = false) => {
            // check data is non-empty array or non-empty object
            let is_empty = data === undefined || data === null || (typeof data === 'object' && Object.keys(data).length === 0);

            if (is_empty) {
                return 'Empty array';
            }
            let html = `
                <table class="table table-striped card-table">
                    <tr>
                        <th>KEY</th>
                        <th>=&gt;</th>
                        <th>VALUE</th>
                    </tr>
            `;
            $.each(data, (key, value) => {
                const row_rand = Math.floor(Math.random() * 1000000);
                let arrow = '=>';
                if (js_expand && typeof value === 'object') {
                    arrow = `<a class="fw-bolder" href="javascript:showHideDiv('content${key}${row_rand}', '', '', '')">=></a>`;
                }

                let val = typeof value === 'string' ? escapeMarkupText(value) : value;
                let val_extra = '';
                if (val !== null && typeof val === 'object') {
                    val = `array(${Object.keys(val).length})`;
                    if (js_expand) {
                        val = `<a class="fw-bolder" href="javascript:showHideDiv('content${key}${row_rand}', '', '', '')">${val}</a>`;
                    }
                    val_extra = `<div id="content${key}${row_rand}" style="${js_expand ? 'display: none' : ''}">${getCleanArray(value, pad + 1)}</div>`;
                }
                html += `<tr><td>${key}</td><td>${arrow}</td><td>${val}${val_extra}</td></tr>`;
            });
            html += '</table>';
            return html;
        };

        const rand = Math.floor(Math.random() * 1000000);
        content_area.append(`
            <div>
               <label>
                  Request:
                  <select name="request_id">
                     <option value="${this.initial_request.id}">${this.initial_request.id} (Initial Request)</option>
                  </select>
               </label>
               <div id="debugpanel${rand}" class="container-fluid card p-0" style="min-width: 400px; max-width: 90vw">
                  <ul class="nav nav-tabs" data-bs-toggle="tabs">
                     <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#debugpost${rand}">POST</a></li>
                     <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#debugget${rand}">GET</a></li>
                     <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#debugsession${rand}">SESSION</a></li>
                     <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#debugserver${rand}">SERVER</a></li>
                  </ul>
            
                  <div class="card-body overflow-auto">
                     <div class="tab-content">
                        <div id="debugpost${rand}" class="tab-pane active"></div>
                        <div id="debugget${rand}" class="tab-pane"></div>
                        <div id="debugsession${rand}" class="tab-pane"></div>
                        <div id="debugserver${rand}" class="tab-pane"></div>
                     </div>
                  </div>
               </div>
            </div>
        `);

        // Add all AJAX request IDs to the select
        this.ajax_requests.forEach((request) => {
            content_area.find('select[name="request_id"]').append(`<option value="${request.id}">${request.id} (${request.url})</option>`);
        });
        const selected_request_id = content_area.data('globals_request_id') || this.initial_request.id;
        // Make sure the selected request ID is actually selected
        content_area.find(`select[name="request_id"] option[value="${selected_request_id}"]`).prop('selected', true);

        content_area.find('select[name="request_id"]').off('change').on('change', (e) => {
            content_area.data('globals_request_id', $(e.target).val());
            this.showWidget('globals');
        });

        const matching_profile = this.getProfile(selected_request_id);
        const globals = matching_profile.globals;
        content_area.find(`#debugpost${rand}`).html(getCleanArray(globals['post'], 0, true));
        content_area.find(`#debugget${rand}`).html(getCleanArray(globals['get'], 0, true));
        content_area.find(`#debusession${rand}`).html(getCleanArray(globals['session'], 0, true));
        content_area.find(`#debugserver${rand}`).html(getCleanArray(globals['server'], 0, true));
    }

    showAJAXRequests(content_area) {
        content_area.append(`
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th style="max-width: 50vw">URL</th>
                        <th>ID</th>
                    </tr>
                </thead>
                <tbody>
                    ${this.ajax_requests.map((request) => `
                        <tr>
                            <td class="alert alert-${request.status_type}">${request.status}</td>
                            <td>${request.time !== undefined ? request.time + 'ms' : 'pending'}</td>
                            <td>${request.type}</td>
                            <td style="max-width: 50vw; white-space: pre-wrap">${request.url}</td>
                            <td>${request.id}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `);
    }

    showClientPerformance(content_area) {
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

    getProfilerTable(profiler_sections, is_nested = false, parent_duration = 0) {
        let table = `
            <table class="table table-striped card-table table-hover">
                <thead>
                    <tr>
                       ${is_nested ? '<th style="min-width: 2rem"></th>' : ''}
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

        const sections = profiler_sections.sort((a, b) => a.start - b.start);
        const top_level_parent_id = sections[0].parent_id;
        const top_level_sections = sections.filter((section) => section.parent_id === top_level_parent_id);

        top_level_sections.forEach((section) => {
            const cat_colors = this.getProfilerCategoryColor(section.category);
            const duration = section.end - section.start;

            let percent_of_parent = 100;
            if (is_nested) {
                percent_of_parent = (duration / parent_duration) * 100;
            }
            percent_of_parent = percent_of_parent.toFixed(2);

            table += `
                <tr data-profiler-section-id="${section.id}">
                    ${is_nested ? '<td style="min-width: 2rem"></td>' : ''}
                    <td>
                        <span style='padding: 5px; border-radius: 25%; background-color: ${cat_colors.bg_color}; color: ${cat_colors.text_color}'>
                            ${section.category}
                        </span>
                    </td>
                    <td>${section.name}</td><td>${section.start}</td><td>${section.end}</td>
                    <td data-column="duration" data-duration-raw="${duration}">${duration.toFixed(0)}ms</td>
                    <td>${percent_of_parent}%</td>
                </tr>
            `;

            const children = sections.filter((child) => child.parent_id === section.id);
            if (children.length > 0) {
                const children_table = this.getProfilerTable(children, true, duration);
                table += `<tr>${children_table}</tr>`;
            }
        });

        table += '</tbody></table>';

        return table;
    }

    showProfiler(content_area) {
        content_area.append(`
            <div>
                <label>
                  Request:
                  <select name="request_id">
                     <option value="${this.initial_request.id}">${this.initial_request.id} (Initial Request)</option>
                  </select>
               </label>
               <label>
                 Hide near-instant sections (<=1ms):
                 <input type="checkbox" name="hide_instant_sections">
               </label>
            </div>
        `);

        const request_select = content_area.find('select[name="request_id"]');

        // Add the AJAX requests to the select
        this.ajax_requests.forEach((request) => {
            request_select.append(`<option value="${request.id}">${request.id} (${request.url})</option>`);
        });

        const selected_request_id = content_area.data('profiler_request_id') || this.initial_request.id;
        // Make sure the selected request ID is actually selected
        request_select.find(`option[value="${selected_request_id}"]`).prop('selected', true);

        request_select.off('change').on('change', (e) => {
            content_area.data('profiler_request_id', $(e.target).val());
            this.showWidget('profiler');
        });

        const hide_instant_sections_box = content_area.find('input[name="hide_instant_sections"]');
        const hide_instant_sections = content_area.data('profiler_hide_instant_sections') || true;
        hide_instant_sections_box.prop('checked', hide_instant_sections);

        hide_instant_sections_box.off('change').on('change', (e) => {
            const hide = $(e.target).prop('checked');
            content_area.data('profiler_hide_instant_sections', hide);
            const table_rows = content_area.find('table tbody tr');

            // Start by un-hiding all rows
            table_rows.removeClass('d-none');

            if (hide) {
                // hide all rows in the table that have the duration column set less than 1ms
                table_rows.each((index, row) => {
                    const duration_cell = $(row).find('td[data-column="duration"]');
                    const duration_value = parseFloat(duration_cell.attr('data-duration-raw'));
                    if (duration_value <= 1.0) {
                        $(row).addClass('d-none');
                    }
                });
            }
        });


        const matching_profile = this.getProfile(selected_request_id);
        const profiler = matching_profile.profiler || {};

        // get profiler entries and sort them by start time
        // Logically, child entries should remain under their parent since everything is done synchronously
        const profiler_sections = Object.values(profiler);

        content_area.find('> div').append(this.getProfilerTable(profiler_sections));
        hide_instant_sections_box.trigger('change');
    }
};
