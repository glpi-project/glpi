/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

import '/build/vue/app.js';
import {flushPromises, mount} from "@vue/test-utils";

describe("Debug Bar", () => {

    beforeAll(() => {
        document.documentElement.setAttribute('data-glpi-theme', 'auror');
        document.documentElement.setAttribute('data-glpi-theme-dark', '0');
        document.body.innerHTML = '';

        window.GLPI = {
            Monaco: {
                colorizeText: (text) => `<div>${text}</div>`,
                createEditor: async (element_id, lang, value) => {
                    const container = document.getElementById(element_id);
                    if (container) {
                        container.innerHTML = `<div>${value}</div>`;
                    }
                    return Promise.resolve({
                        editor: {
                            trigger: vi.fn(),
                            layout: vi.fn(),
                        },
                    });
                }
            }
        };

        vi.spyOn(window.performance, 'getEntriesByType').mockImplementation((entryType) => {
            if (entryType === 'navigation') {
                return [{
                    entryType: 'navigation',
                    startTime: 0,
                    duration: 1234,
                    domInteractive:1234,
                    domComplete: 1234,
                }];
            } else if (entryType === 'paint') {
                return [
                    {
                        name: 'first-paint',
                        startTime: 100,
                    },
                    {
                        name: 'first-contentful-paint',
                        startTime: 150,
                    }
                ];
            } else if (entryType === 'resource') {
                return [
                    {
                        name: 'https://example.com/resource1.js',
                        startTime: 50,
                        duration: 200,
                        transferSize: 1024 * 100, // 100 KB
                    },
                    {
                        name: 'https://example.com/resource2.css',
                        startTime: 300,
                        duration: 100,
                        transferSize: 1024 * 50, // 50 KB
                    }
                ];
            }
        });
        window.performance.memory = {
            usedJSHeapSize: 1024 * 1024 * 10, // 10 MB
            totalJSHeapSize: 1024 * 1024 * 20, // 20 MB
            jsHeapSizeLimit: 1024 * 1024 * 50, // 50 MB
        };

        window.bootstrap = {
            Tooltip: class {
                constructor(element) {
                    this.element = element;
                }
            }
        };
    });

    afterEach(() => {
        window.AjaxMock.end();
    });

    async function mountToolbar() {
        const Toolbar = await import("/js/src/vue/Debug/Toolbar.vue").then(module => module.default);

        // import all widgets at once, assigning each to variable for use in the mount options later, and then await all to reduce test time

        const [
            WidgetButton,
            WidgetServerPerformance,
            WidgetSQLRequests,
            WidgetHTTPRequests,
            WidgetRequestSummary,
            WidgetGlobals,
            WidgetClientPerformance,
            WidgetProfiler,
            WidgetProfilerTable,
            WidgetSearchOptions,
            WidgetThemeSwitcher
        ] = await Promise.all([
            import('/js/src/vue/Debug/WidgetButton.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/ServerPerformance.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/SQLRequests.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/HTTPRequests.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/RequestSummary.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/Globals.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/ClientPerformance.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/Profiler.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/ProfilerTable.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/SearchOptions.vue').then(module => module.default),
            import('/js/src/vue/Debug/Widget/ThemeSwitcher.vue').then(module => module.default),
        ]);

        const wrapper = mount(Toolbar, {
            attachTo: document.body,
            props: {
                initial_request: {
                    "id":"0f9fde97e889ed3f",
                    "parent_id":null,
                    "server_performance":{"execution_time":200,"memory_usage":5176272,"memory_peak":5346088,"memory_limit":268435456},
                    "sql":{"queries":[{"num":0,"query":"SELECT * FROM `glpi_assets_assetdefinitions`","time":0.3948211669921875,"rows":2,"errors":"","warnings":""}]},
                    "globals": {
                        "get": [],
                        "post": [],
                        "session": {
                            "glpicookietest": "testcookie",
                            "valid_id": "fce7f1b423db04cbd32aa7eab70c13d3",
                            "glpi_currenttime": "2026-06-16 18:09:42",
                            "glpi_use_mode": 2,
                            "glpiID": 2,
                        },
                        "server": {
                            "USER": "www-data",
                            "HOME": "/var/www",
                            "SCRIPT_NAME": "/index.php",
                            "REQUEST_URI": "/front/entity.php"
                        },
                    },
                    "profiler":[
                        {
                            "id":"3c961f88-a87f-486f-b853-20cb5e72baac",
                            "parent_id":null,
                            "category":"core",
                            "name": "php_request",
                            "start":1781647782350,
                            "end":1781647782380,
                            "duration":30,
                            "auto_ended":false
                        },
                        {
                            "id":"1954516f-d3fc-4260-8eea-6f9ad526f1a7",
                            "parent_id":"3c961f88-a87f-486f-b853-20cb5e72baac",
                            "category":"boot",
                            "name":"InitializeDbConnection::execute",
                            "start":1781647782351,
                            "end":1781647782362,
                            "duration":11,
                            "auto_ended":false
                        }
                    ],
                }
            },
            global: {
                components: {
                    'widget-button': WidgetButton,
                    'widget-server-performance': WidgetServerPerformance,
                    'widget-sqlrequests': WidgetSQLRequests,
                    'widget-httprequests': WidgetHTTPRequests,
                    'widget-request-summary': WidgetRequestSummary,
                    'widget-globals': WidgetGlobals,
                    'widget-client-performance': WidgetClientPerformance,
                    'widget-profiler': WidgetProfiler,
                    'widget-profiler-table': WidgetProfilerTable,
                    'widget-search-options': WidgetSearchOptions,
                    'widget-theme-switcher': WidgetThemeSwitcher,
                }
            }
        });
        await flushPromises();
        return wrapper;
    }

    it('Debug bar controls', async () => {
        const wrapper = await mountToolbar();

        expect(wrapper.find('button[aria-label="Toggle debug bar"]').attributes('disabled')).toBeDefined();
        expect(wrapper.find('#debug-toolbar-expanded-content').element).not.toBeVisible();

        await wrapper.find('button[aria-label="Toggle debug content area"]').trigger('click');
        expect(wrapper.find('#debug-toolbar-expanded-content').element).toBeVisible();

        await wrapper.find('button[aria-label="Close"]').trigger('click');
        expect(wrapper.find('.debug-toolbar-content').element).not.toBeVisible();

        await wrapper.find('button[aria-label="Toggle debug bar"]').trigger('click');
        expect(wrapper.find('.debug-toolbar-content').element).toBeVisible();
        expect(wrapper.find('#debug-toolbar-expanded-content').element).toBeVisible();

        await wrapper.find('button[aria-label="Toggle debug content area"]').trigger('click');
        expect(wrapper.find('#debug-toolbar-expanded-content').element).not.toBeVisible();
    });

    it('Server performance widget', async () => {
        const wrapper = await mountToolbar();

        const serverPerformanceWidget = wrapper.find('.debug-toolbar-widget[data-glpi-debug-widget-id="server_performance"]');
        expect(serverPerformanceWidget.exists()).toBe(true);
        expect(serverPerformanceWidget.text()).toMatch(/200\s+ms\s+using\s+4\.94\s+MiB/);
        await serverPerformanceWidget.trigger('click');
        expect(wrapper.find('#debug-toolbar-expanded-content').element).toBeVisible();
        const expandedContent = wrapper.find('#debug-toolbar-expanded-content');
        await flushPromises();
        expect(expandedContent.element).toBeVisible();
        const datagridTitles = expandedContent.findAll('.datagrid-title');

        const initialExecutionTime = datagridTitles.find(title => title.text() === 'Initial Execution Time');
        expect(initialExecutionTime.element.nextElementSibling.textContent).toMatch(/200\s+ms/);

        const totalExecutionTime = datagridTitles.find(title => title.text() === 'Total Execution Time');
        expect(totalExecutionTime.element.nextElementSibling.textContent).toMatch(/200\s+ms/);

        const memoryUsage = datagridTitles.find(title => title.text() === 'Memory Usage');
        expect(memoryUsage.element.nextElementSibling.textContent).toMatch(/4\.94\s+MiB\s+\/\s+256\s+MiB/);

        const memoryPeak = datagridTitles.find(title => title.text() === 'Memory Peak');
        expect(memoryPeak.element.nextElementSibling.textContent).toMatch(/5\.1\s+MiB\s+\/\s+256\s+MiB/);
    });

    it('SQL requests', async () => {
        const wrapper = await mountToolbar();

        const sqlWidget = wrapper.find('.debug-toolbar-widget[data-glpi-debug-widget-id="sql"]');
        expect(sqlWidget.exists()).toBe(true);
        expect(sqlWidget.text()).toMatch(/1\s+requests/);
        await sqlWidget.trigger('click');
        expect(wrapper.find('#debug-toolbar-expanded-content').element).toBeVisible();
        const expandedContent = wrapper.find('#debug-toolbar-expanded-content');
        await flushPromises();
        expect(expandedContent.element).toBeVisible();
        const sqlRequestRows = expandedContent.findAll('#debug-sql-request-table tr');
        expect(sqlRequestRows.length).toBe(2); // 1 header + 1 request

        const firstRowCells = sqlRequestRows[1].findAll('td');
        expect(firstRowCells[0].text()).toBe('0f9fde97e889ed3f'); // request ID
        expect(firstRowCells[1].text()).toMatch(/^\d+$/); // num
        expect(firstRowCells[2].text()).toContain('`glpi_assets_assetdefinitions`'); // query
        expect(firstRowCells[3].text()).toMatch(/^\d+\.\d+\sms$/); // time
        expect(firstRowCells[4].text()).toMatch(/^\d+$/); // rows
        expect(firstRowCells[5].text()).toBe(''); // errors
        expect(firstRowCells[6].text()).toBe(''); // warnings
    });

    it('HTTP requests', async () => {
        const wrapper = await mountToolbar();

        const httpWidget = wrapper.find('.debug-toolbar-widget[data-glpi-debug-widget-id="requests"]');
        expect(httpWidget.exists()).toBe(true);
        expect(httpWidget.text()).toMatch(/1\s+requests/);
        await httpWidget.trigger('click');
        expect(wrapper.find('#debug-toolbar-expanded-content').element).toBeVisible();
        const expandedContent = wrapper.find('#debug-toolbar-expanded-content');
        await flushPromises();
        expect(expandedContent.element).toBeVisible();
        const httpRequestRows = expandedContent.findAll('#debug-requests-table tr');
        expect(httpRequestRows.length).toBe(2); // 1 header + 1 request
        expect(httpRequestRows[1].classes()).toContain('table-active');

        let requestDetailsArea = expandedContent.find('.request-details-content-area');

        // Summary Tab
        expect(requestDetailsArea.find('h1').text()).toMatch(/^Request Summary/);
        const summaryCells = requestDetailsArea.findAll('td');
        expect(summaryCells.some(cell => cell.text().match(/Initial Execution Time:\s+\d+ ms/))).toBe(true);
        expect(summaryCells.some(cell => cell.text().match(/Memory Usage:\s+[\d.]+\s+MiB\s+\/\s+[\d.]+\s+MiB/))).toBe(true);
        expect(summaryCells.some(cell => cell.text().match(/Memory Peak:\s+[\d.]+\s+MiB\s+\/\s+[\d.]+\s+MiB/))).toBe(true);
        expect(summaryCells.some(cell => cell.text().match(/SQL Requests:\s+\d+/))).toBe(true);
        expect(summaryCells.some(cell => cell.text().match(/SQL Duration:\s+[\d.]+ ms/))).toBe(true);

        const requestTabs = expandedContent.findAll('.nav-link');
        expect(requestTabs.some(tab => tab.text() === 'Summary' && tab.classes().includes('active'))).toBe(true);
        expect(requestTabs.some(tab => tab.text() === 'Globals')).toBe(true);
        expect(requestTabs.some(tab => tab.text() === 'Profiler')).toBe(true);
        expect(requestTabs.some(tab => tab.text() === 'SQL')).toBe(true);

        // Globals Tab
        await requestTabs.find(tab => tab.text() === 'Globals').trigger('click');
        requestDetailsArea = expandedContent.find('.request-details-content-area');
        const globalsNavItems = requestDetailsArea.findAll('.nav-item');
        await globalsNavItems.find(item => item.text() === 'POST').trigger('click');
        expect(requestDetailsArea.find('.tab-pane[id^="debugpost"] .monaco-editor-container').exists()).toBe(true);
        await globalsNavItems.find(item => item.text() === 'GET').trigger('click');
        expect(requestDetailsArea.find('.tab-pane[id^="debugget"] .monaco-editor-container').exists()).toBe(true);
        await globalsNavItems.find(item => item.text() === 'SESSION').trigger('click');
        expect(requestDetailsArea.find('.tab-pane[id^="debugsession"] .monaco-editor-container').exists()).toBe(true);
        await globalsNavItems.find(item => item.text() === 'SERVER').trigger('click');
        expect(requestDetailsArea.find('.tab-pane[id^="debugserver"] .monaco-editor-container').exists()).toBe(true);

        // Profiler Tab
        await requestTabs.find(tab => tab.text() === 'Profiler').trigger('click');
        requestDetailsArea = expandedContent.find('.request-details-content-area');
        const profilerRows = requestDetailsArea.findAll('tr[data-profiler-section-id]');
        expect(profilerRows.length).toBe(2);
        const profilerCells0 = profilerRows[0].findAll('td');
        expect(profilerCells0[0].text()).toBe('core'); // category
        expect(profilerCells0[1].text()).toBe('php_request'); // name
        expect(profilerCells0[2].text()).toMatch(/^\d+\s+ms$/); // duration
        expect(profilerCells0[3].text()).toMatch(/^[\d.]+%$/); // percent of parent
        expect(profilerCells0[4].text()).toBe('No'); // auto ended

        const profilerCells1 = profilerRows[1].findAll('td');
        expect(profilerCells1[0].classes()).toContain('nesting-spacer');
        expect(profilerCells1[1].text()).toBe('boot'); // category
        expect(profilerCells1[2].text()).toBe('InitializeDbConnection::execute'); // name
        expect(profilerCells1[3].text()).toMatch(/^\d+\s+ms$/); // duration
        expect(profilerCells1[4].text()).toMatch(/^[\d.]+%$/); // percent of parent
        expect(profilerCells1[5].text()).toBe('No'); // auto ended

        // SQL Tab
        await requestTabs.find(tab => tab.text() === 'SQL').trigger('click');
        requestDetailsArea = expandedContent.find('.request-details-content-area');
        const sqlRequestRows = requestDetailsArea.findAll('#debug-sql-request-table tr');
        expect(sqlRequestRows.length).toBe(2); // 1 header + 1 request

        const firstRowCells = sqlRequestRows[1].findAll('td');
        expect(firstRowCells[0].text()).toMatch(/^\d+$/); // num
        expect(firstRowCells[1].text()).toContain('`glpi_assets_assetdefinitions`'); // query
        expect(firstRowCells[2].text()).toMatch(/^\d+\.\d+\sms$/); // time
        expect(firstRowCells[3].text()).toMatch(/^\d+$/); // rows
        expect(firstRowCells[4].text()).toBe(''); // errors
        expect(firstRowCells[5].text()).toBe(''); // warnings
    });

    it('Client performance', async () => {
        const wrapper = await mountToolbar();
        vi.useFakeTimers();
        vi.advanceTimersByTime(500);
        vi.useRealTimers();

        const clientPerformanceWidget = wrapper.find('.debug-toolbar-widget[data-glpi-debug-widget-id="client_performance"]');
        //expect(clientPerformanceWidget.text()).toMatch(/[\d.]+\s+ms/);
        await clientPerformanceWidget.trigger('click');
        expect(wrapper.find('#debug-toolbar-expanded-content').element).toBeVisible();
        const expandedContent = wrapper.find('#debug-toolbar-expanded-content');
        await flushPromises();
        expect(expandedContent.element).toBeVisible();
        const datagridTitles = expandedContent.findAll('.datagrid-title');

        const timeToFirstPaint = datagridTitles.find(title => title.text() === 'Time to first paint');
        expect(timeToFirstPaint.element.nextElementSibling.textContent).toMatch(/\d+\s+ms/);

        const timeToDomInteractive = datagridTitles.find(title => title.text() === 'Time to DOM interactive');
        expect(timeToDomInteractive.element.nextElementSibling.textContent).toMatch(/\d+\s+ms/);

        const timeToDomComplete = datagridTitles.find(title => title.text() === 'Time to DOM complete');
        expect(timeToDomComplete.element.nextElementSibling.textContent).toMatch(/\d+\s+ms/);

        const totalResources = datagridTitles.find(title => title.text() === 'Total resources');
        expect(totalResources.element.nextElementSibling.textContent).toMatch(/^\d+$/);

        const totalResourcesSize = datagridTitles.find(title => title.text() === 'Total resources size');
        expect(totalResourcesSize.element.nextElementSibling.textContent).toMatch(/[\d.]+\s+MiB/);

        const usedJsHeap = datagridTitles.find(title => title.text() === 'Used JS Heap');
        expect(usedJsHeap.element.nextElementSibling.textContent).toMatch(/[\d.]+\s+MiB/);

        const totalJsHeap = datagridTitles.find(title => title.text() === 'Total JS Heap');
        expect(totalJsHeap.element.nextElementSibling.textContent).toMatch(/[\d.]+\s+MiB/);

        const jsHeapLimit = datagridTitles.find(title => title.text() === 'JS Heap Limit');
        expect(jsHeapLimit.element.nextElementSibling.textContent).toMatch(/[\d.]+\s+MiB/);
    });

    it('Search options', async () => {
        const wrapper = await mountToolbar();

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/debug.php', 'GET', {action: 'get_itemtypes'}, () => {
            return ['Profile', 'User'];
        }));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/debug.php', 'GET', {action: 'get_search_options', itemtype: 'Profile'}, () => {
            return {
                "1": {
                    "table": "glpi_profiles",
                    "field": "name",
                    "name": "Name",
                    "datatype": "itemlink",
                    "massiveaction": false,
                    "linkfield": "name",
                    "joinparams": []
                },
                "2": {
                    "table": "glpi_profiles",
                    "field": "id",
                    "name": "ID",
                    "massiveaction": false,
                    "datatype": "number",
                    "linkfield": "id",
                    "joinparams": []
                }
            };
        }));

        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/debug.php', 'GET', {action: 'get_search_options', itemtype: 'User'}, () => {
            return {};
        }));

        const searchOptionsWidget = wrapper.find('.debug-toolbar-widget[data-glpi-debug-widget-id="search_options"]');
        expect(searchOptionsWidget.exists()).toBe(true);
        await searchOptionsWidget.trigger('click');
        expect(wrapper.find('#debug-toolbar-expanded-content').element).toBeVisible();
        const expandedContent = wrapper.find('#debug-toolbar-expanded-content');
        expect(expandedContent.find('.search-opts-table').exists()).toBe(false);

        expandedContent.find('select').element.value = 'Profile';
        await expandedContent.find('select').trigger('change');
        await flushPromises();
        expect(window.AjaxMock.isResponseStackEmpty()).toBe(false);
        expect(expandedContent.find('.search-opts-table').exists()).toBe(true);

        await expandedContent.find('button[aria-label="Toggle manual input"]').trigger('click');
        await flushPromises();
        await expandedContent.find('input').setValue('User');
        await expandedContent.find('input').trigger('change');
        await flushPromises();
        expect(window.AjaxMock.isResponseStackEmpty()).toBe(true);
        expect(expandedContent.find('.search-opts-table').exists()).toBe(true);
    });

    it('Theme switcher', async () => {
        const wrapper = await mountToolbar();

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/debug.php', 'GET', {action: 'get_themes'}, () => {
            return [
                {
                    "key": "auror",
                    "name": "Auror",
                    "is_dark": false,
                    "is_custom": false
                },
                {
                    "key": "midnight",
                    "name": "Midnight",
                    "is_dark": true,
                    "is_custom": false
                },
            ];
        }));

        const themeSwitcherWidget = wrapper.find('.debug-toolbar-widget[data-glpi-debug-widget-id="theme_switcher"]');
        expect(themeSwitcherWidget.exists()).toBe(true);
        await themeSwitcherWidget.trigger('click');
        expect(wrapper.find('#debug-toolbar-expanded-content').element).toBeVisible();
        const expandedContent = wrapper.find('#debug-toolbar-expanded-content');
        await flushPromises();
        expect(expandedContent.element).toBeVisible();

        const paletteSelect = expandedContent.find('select');
        expect(paletteSelect.exists()).toBe(true);

        await paletteSelect.setValue('midnight');
        await paletteSelect.trigger('change');
        expect(document.documentElement.getAttribute('data-glpi-theme')).toBe('midnight');
        expect(document.documentElement.getAttribute('data-glpi-theme-dark')).toBe('1');

        await paletteSelect.setValue('auror');
        await paletteSelect.trigger('change');
        expect(document.documentElement.getAttribute('data-glpi-theme')).toBe('auror');
        expect(document.documentElement.getAttribute('data-glpi-theme-dark')).toBe('0');
    });
});
