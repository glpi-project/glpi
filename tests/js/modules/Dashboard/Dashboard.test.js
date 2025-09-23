/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* global GLPI */

import {GLPIDashboard} from '/js/modules/Dashboard/Dashboard.js';
import {jest} from '@jest/globals';

describe('Dashboard', () => {
    beforeAll(() => {
        // Make backups of some methods on the prototype we will mock by default because they are called from the constructor
        GLPIDashboard.prototype._refreshDashboard = GLPIDashboard.prototype.refreshDashboard;
    });
    beforeEach(() => {
        jest.clearAllMocks();
        // clear timer mock
        jest.useRealTimers();
        window.AjaxMock.end();

        // Mock some instance methods we don't want to test but are called from the constructor
        GLPIDashboard.prototype.refreshDashboard = jest.fn().mockImplementation(() => {});

        // Mock GridStack
        window.GridStack = {
            init: () => {
                return new class extends EventTarget {
                    constructor() {
                        super();
                        this.setStatic = jest.fn().mockImplementation(() => {});
                        this.addWidget = jest.fn().mockImplementation(() => {});
                    }
                    on(event, callback) {
                        this.addEventListener(event, callback);
                    }
                };
            },
        };

        // basic DOM for dashboard
        $(document).off('click').off('submit');
        $('body').empty();
        $('body').append(`
            <div id="dashboard-12345">
                <input type="text" class="dashboard-name" value="mytitle"/>
                <div class="toolbar">
                    <button class="edit-dashboard"></button>
                    <select name="dashboard" class="dashboard_select">
                        <option value="other_dashboard_1">Other Dashboard 1</option>
                        <option value="current_dashboard" selected>Current Dashboard</option>
                        <option value="other_dashboard_2">Other Dashboard 2</option>
                    </select>
                </div>
                <div class="grid-stack">
                    <div class="grid-stack-item" gs-id="1"><div class="card">test1</div></div>
                    <div class="grid-stack-item" gs-id="2"><div class="card">test2</div></div>
                    <div class="grid-stack-item" gs-id="3"><div class="card">test3</div></div>
                </div>
            </div>
        `);
    });

    test('Class availability', () => {
        // Expect class to be available as a global
        expect(window.GLPI.Dashboard.GLPIDashboard).toBeDefined();
        // Expect old reference to be available too
        expect(window.GLPIDashboard).toBeDefined();
    });

    /**
     * Super important feature to test.
     */
    test('\x1b[31mE\x1b[33ma\x1b[32ms\x1b[36mt\x1b[34me\x1b[35mr\x1b[0m', () => {
        expect(true).toBe(true);
        // Legacy fake timers needed to support setInterval
        jest.useFakeTimers('legacy');

        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.easter();

        let colors = ['', '', ''];
        const new_colors = ['', '', ''];

        for (let i = 0; i < 10; i++) {
            jest.advanceTimersByTime(30);

            $('.grid-stack-item .card').each((i, elem) => {
                new_colors[i] = $(elem).css('background-color');
            });
            // At least one color should be different
            expect(new_colors).not.toEqual(colors);
            colors = [...new_colors];
        }
    });

    test('saveMarkdown', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').find('.grid-stack-item').first().append(`<textarea name="markdown">test</textarea>`);
        dashboard.saveMarkdown($('body').find('.grid-stack-item textarea').first());
        expect(dashboard.markdown_contents[1]).toBe('test');
        $('body').find('.grid-stack-item').first().hasClass('dirty');
    });

    test('setWidgetFromForm default values', () => {
        // Mock glpi_close_all_dialogs
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});

        const dashboard = new GLPIDashboard({'rand': '12345'});

        //Mock getUuidV4 from legacy script common.js
        window.getUuidV4 = jest.fn().mockImplementation(() => {
            // return a UUIDv4
            return '12345678-1234-1234-1234-123456789012';
        });

        dashboard.addWidget = jest.fn().mockImplementation(() => {});
        dashboard.setWidgetFromForm({
            serializeArray: () => {
                return [];
            }
        });

        expect(dashboard.addWidget).toHaveBeenCalledWith({
            card_options: {
                color: null,
                widgettype: null,
                palette: null,
                use_gradient: 0,
                point_labels: 0,
                legend: 0,
                limit: 7,
                card_id: undefined,
                gridstack_id: 'undefined_12345678-1234-1234-1234-123456789012',
                force: true,
                apply_filters: {},
            },
            gridstack_id: 'undefined_12345678-1234-1234-1234-123456789012',
        });

        // Expect glpi_close_all_dialogs to be called
        expect(window.glpi_close_all_dialogs).toHaveBeenCalledTimes(1);
    });

    test('setWidgetFromForm custom form values', () => {
        // Mock glpi_close_all_dialogs
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});

        const dashboard = new GLPIDashboard({'rand': '12345'});

        //Mock getUuidV4 from legacy script common.js
        window.getUuidV4 = jest.fn().mockImplementation(() => {
            // return a UUIDv4
            return '12345678-1234-1234-1234-123456789012';
        });

        dashboard.addWidget = jest.fn().mockImplementation(() => {});
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                '1': 'test',
            };
        });
        dashboard.setWidgetFromForm({
            serializeArray: () => {
                return [
                    {name: 'color', value: '#ff00ff'},
                    {name: 'widgettype', value: 'testWidget'},
                    {name: 'palette', value: 'testPalette'},
                    {name: 'use_gradient', value: 1},
                    {name: 'point_labels', value: 1},
                    {name: 'legend', value: 1},
                    {name: 'limit', value: 10},
                    {name: 'card_id', value: 'mycard'},
                ];
            }
        });

        expect(dashboard.addWidget).toHaveBeenCalledWith({
            card_id: 'mycard',
            card_options: {
                card_id: 'mycard',
                color: '#ff00ff',
                widgettype: 'testWidget',
                palette: 'testPalette',
                use_gradient: 1,
                point_labels: 1,
                legend: 1,
                limit: 10,
                gridstack_id: 'mycard_12345678-1234-1234-1234-123456789012',
                force: true,
                apply_filters: {'1': 'test'},
            },
            // TODO These duplicated values probably shouldn't be here
            color: '#ff00ff',
            widgettype: 'testWidget',
            palette: 'testPalette',
            use_gradient: 1,
            point_labels: 1,
            legend: 1,
            limit: 10,
            gridstack_id: 'mycard_12345678-1234-1234-1234-123456789012',
        });

        // Expect glpi_close_all_dialogs to be called
        expect(window.glpi_close_all_dialogs).toHaveBeenCalledTimes(1);
    });

    test('setWidgetFromForm No Card', () => {
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addWidget = jest.fn().mockImplementation(() => {});
        const result = dashboard.setWidgetFromForm({
            serializeArray: () => {
                return [{name: 'card_id', value: '0'}];
            }
        });
        expect(result).toBeFalse();
        expect(dashboard.addWidget).not.toHaveBeenCalled();
        expect(window.glpi_close_all_dialogs).toHaveBeenCalledTimes(1);
    });

    test('setWidgetFromForm Edit No Old ID', () => {
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addWidget = jest.fn().mockImplementation(() => {});
        const result = dashboard.setWidgetFromForm({
            serializeArray: () => {
                return [{name: 'old_id', value: '0'}];
            }
        });
        expect(result).toBeFalse();
        expect(dashboard.addWidget).not.toHaveBeenCalled();
        expect(window.glpi_close_all_dialogs).toHaveBeenCalledTimes(1);
    });

    test('setWidgetFromForm Edit Remove Old Card', () => {
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addWidget = jest.fn().mockImplementation(() => {});
        dashboard.grid.removeWidget = jest.fn().mockImplementation(() => {});
        dashboard.setWidgetFromForm({
            serializeArray: () => {
                return [
                    {name: 'color', value: '#ff00ff'},
                    {name: 'widgettype', value: 'testWidget'},
                    {name: 'use_gradient', value: 1},
                    {name: 'point_labels', value: 1},
                    {name: 'limit', value: 10},
                    {name: 'card_id', value: '6'},
                    {name: 'old_id', value: '2'},
                ];
            }
        });
        expect(dashboard.grid.removeWidget).toHaveBeenCalledWith(expect.toSatisfy((widget) => {
            return $(widget).attr('gs-id') === '2';
        }));
        expect(dashboard.addWidget).toHaveBeenCalled();
        expect(window.glpi_close_all_dialogs).toHaveBeenCalledTimes(1);
    });

    test('setWidgetFromForm Encoded Card Options', () => {
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addWidget = jest.fn().mockImplementation(() => {});
        dashboard.setWidgetFromForm({
            serializeArray: () => {
                return [
                    {name: 'color', value: '#ff00ff'},
                    {name: 'widgettype', value: 'testWidget'},
                    {name: 'use_gradient', value: 1},
                    {name: 'point_labels', value: 1},
                    {name: 'limit', value: 10},
                    {name: 'card_id', value: '6'},
                    {name: 'card_options', value: JSON.stringify({test: 'test'})},
                ];
            }
        });
        expect(dashboard.addWidget).toHaveBeenCalledWith(expect.toSatisfy((opts) => {
            return opts.card_options['test'] === 'test' && opts.card_id === '6'
                && opts.card_options['gridstack_id'] === '6_12345678-1234-1234-1234-123456789012';
        }));
        expect(window.glpi_close_all_dialogs).toHaveBeenCalledTimes(1);
    });

    /**
     * Test the calling of the AJAX endpoint for the action 'get_card' from the method setWidgetFromForm
     */
    test('setWidgetFromForm ajax', async () => {
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });

        window.getUuidV4 = jest.fn().mockImplementation(() => {
            return '12345678-1234-1234-1234-123456789012';
        });

        $('body').append(`<div id="widget12345"><div class="grid-stack-item-content"></div></div>`);
        dashboard.addWidget = jest.fn().mockImplementation(() => {
            return $('#widget12345');
        });

        dashboard.fitNumbers = jest.fn();
        dashboard.animateNumbers = jest.fn();
        dashboard.saveDashboard = jest.fn();

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            action: 'get_card',
            dashboard: 'current_dashboard',
            card_id: undefined,
            cache_key: '',
            args: {
                color: null,
                widgettype: null,
                palette: null,
                use_gradient: 0,
                point_labels: 0,
                legend: 0,
                limit: 7,
                card_id: undefined,
                gridstack_id: 'undefined_12345678-1234-1234-1234-123456789012',
                force: true,
                apply_filters: {'1': 'test'},
            }
        }, () => {
            return $(`<div class="test-content"></div>`);
        }));

        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                '1': 'test',
            };
        });

        dashboard.setWidgetFromForm({
            serializeArray: () => {
                return [];
            }
        });
        await new Promise(process.nextTick);

        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.fitNumbers).toHaveBeenCalledWith(
            expect.toSatisfy((widget) => {
                return widget.attr('id') === 'widget12345';
            })
        );
        expect(dashboard.animateNumbers).toHaveBeenCalledWith(
            expect.toSatisfy((widget) => {
                return widget.attr('id') === 'widget12345';
            })
        );
        expect(dashboard.saveDashboard).toHaveBeenCalledTimes(1);
        expect($('#widget12345').html()).toBe(`<div class="grid-stack-item-content"><div class="test-content"></div></div>`);
    });

    test('addWidget', () => {
        const widget_params = {
            x: 1,
            y: 2,
            width: 3,
            height: 4,
            card_options: {
                card_id: 'mycard',
            },
            gridstack_id: 'mycard_12345678-1234-1234-1234-123456789012',
        };

        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addWidget(widget_params);

        expect(dashboard.grid.addWidget).toHaveBeenCalledWith(
            expect.toSatisfy((widget_info) => {
                const html_el = $(`<div>${widget_info.content}</div>`);
                const has_refresh = html_el.find('.controls i.refresh-item').length === 1;
                const has_edit = html_el.find('.controls i.edit-item').length === 1;
                const has_delete = html_el.find('.controls i.delete-item').length === 1;
                const has_content = html_el.find('div.grid-stack-item-content').length === 1;
                return widget_info.x === 1 && widget_info.y === 2 && widget_info.w === 3 && widget_info.h === 4
                    && widget_info.autoPosition === false && widget_info.id === 'mycard_12345678-1234-1234-1234-123456789012'
                    && has_refresh && has_edit && has_delete && has_content;
            }),
        );
    });

    test('addWidget autoPosition', () => {
        const widget_params = {
            card_options: {
                card_id: 'mycard',
            },
            gridstack_id: 'mycard_12345678-1234-1234-1234-123456789012',
        };

        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addWidget(widget_params);

        expect(dashboard.grid.addWidget).toHaveBeenCalledWith(
            expect.toSatisfy((widget_info) => {
                const html_el = $(`<div>${widget_info.content}</div>`);
                const has_refresh = html_el.find('.controls i.refresh-item').length === 1;
                const has_edit = html_el.find('.controls i.edit-item').length === 1;
                const has_delete = html_el.find('.controls i.delete-item').length === 1;
                const has_content = html_el.find('div.grid-stack-item-content').length === 1;
                return widget_info.x === -1 && widget_info.y === -1 && widget_info.w === 2 && widget_info.h === 2
                    && widget_info.autoPosition === true && widget_info.id === 'mycard_12345678-1234-1234-1234-123456789012'
                    && has_refresh && has_edit && has_delete && has_content;
            }),
        );
    });

    test('addWidget return', () => {
        const widget_params = {
            x: 1,
            y: 2,
            width: 3,
            height: 4,
            card_options: {
                card_id: 'mycard',
            },
            gridstack_id: 'mycard_12345678-1234-1234-1234-123456789012',
        };

        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append('<div id="mock_widget12345" data-card-options="">test</div>');
        dashboard.grid.addWidget = jest.fn().mockImplementation(() => {
            return $('#mock_widget12345')[0];
        });
        const created_widget = dashboard.addWidget(widget_params);

        expect(dashboard.grid.addWidget).toHaveBeenCalledWith(
            expect.toSatisfy((widget_info) => {
                const html_el = $(`<div>${widget_info.content}</div>`);
                const has_refresh = html_el.find('.controls i.refresh-item').length === 1;
                const has_edit = html_el.find('.controls i.edit-item').length === 1;
                const has_delete = html_el.find('.controls i.delete-item').length === 1;
                const has_content = html_el.find('div.grid-stack-item-content').length === 1;
                return widget_info.x === 1 && widget_info.y === 2 && widget_info.w === 3 && widget_info.h === 4
                    && widget_info.autoPosition === false && widget_info.id === 'mycard_12345678-1234-1234-1234-123456789012'
                    && has_refresh && has_edit && has_delete && has_content;
            }),
        );

        expect(created_widget.attr('data-card-options')).toBe(JSON.stringify(widget_params.card_options));
    });

    test('setFilterFromForm', async () => {
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        $('body').append(`<div id="filter-selector"></div>`);
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.filters_selector = '#filter-selector';
        dashboard.saveFilter = jest.fn().mockImplementation(() => {});

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            action: 'get_filter',
            filter_id: 'myfilter',
        }, () => {
            return $(`<div class="filter-content"></div>`);
        }));

        dashboard.setFilterFromForm({
            serializeArray: () => {
                return [
                    {name: 'filter_id', value: 'myfilter'}
                ];
            }
        });
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.saveFilter).toHaveBeenCalledWith('myfilter', []);
        expect(window.glpi_close_all_dialogs).toHaveBeenCalledTimes(1);
        expect($('#filter-selector .filter-content').length).toBe(1);
    });

    test('refreshDashboard', async () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard'
        });
        dashboard.grid.removeAll = jest.fn().mockImplementation(() => {});
        dashboard.grid.makeWidget = jest.fn().mockImplementation(() => {});
        dashboard.getCardsAjax = jest.fn().mockImplementation(() => {});
        $('body').find('.grid-stack').empty();

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            action: 'get_dashboard_items',
            dashboard: 'current_dashboard',
        }, () => {
            return `
                <div class="grid-stack-item" gs-id="4"><div class="card"></div></div>
                <div class="grid-stack-item" gs-id="5"><div class="card"></div></div>
                <div class="grid-stack-item" gs-id="6"><div class="card"></div></div>
            `;
        }));

        // Restore refreshDashboard so it can be tested
        dashboard.refreshDashboard = GLPIDashboard.prototype._refreshDashboard;
        dashboard.refreshDashboard();
        // We need to wait for the next tick to be sure that the async code is finished
        await new Promise(process.nextTick);

        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.grid.removeAll).toHaveBeenCalledTimes(1);
        expect(dashboard.grid.makeWidget).toHaveBeenNthCalledWith(1, expect.toSatisfy((html) => {
            return $(html).attr('gs-id') === '4';
        }));
        expect(dashboard.grid.makeWidget).toHaveBeenNthCalledWith(2, expect.toSatisfy((html) => {
            return $(html).attr('gs-id') === '5';
        }));
        expect(dashboard.grid.makeWidget).toHaveBeenNthCalledWith(3, expect.toSatisfy((html) => {
            return $(html).attr('gs-id') === '6';
        }));
        expect(dashboard.getCardsAjax).toHaveBeenCalledTimes(1);
    });

    test('refreshDashboard embed', async () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard'
        });
        dashboard.embed = true;
        dashboard.token = 'mytoken';
        dashboard.entities_id = 3;
        dashboard.is_recursive = 1;
        dashboard.grid.removeAll = jest.fn().mockImplementation(() => {});
        dashboard.grid.makeWidget = jest.fn().mockImplementation(() => {});
        dashboard.getCardsAjax = jest.fn().mockImplementation(() => {});
        $('body').find('.grid-stack').empty();

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            action: 'get_dashboard_items',
            dashboard: 'current_dashboard',
            embed: 1,
            token: 'mytoken',
            entities_id: 3,
            is_recursive: 1,
        }, () => {
            return `
                <div class="grid-stack-item" gs-id="4"><div class="card"></div></div>
                <div class="grid-stack-item" gs-id="5"><div class="card"></div></div>
                <div class="grid-stack-item" gs-id="6"><div class="card"></div></div>
            `;
        }));

        // Restore refreshDashboard so it can be tested
        dashboard.refreshDashboard = GLPIDashboard.prototype._refreshDashboard;
        dashboard.refreshDashboard();
        // We need to wait for the next tick to be sure that the async code is finished
        await new Promise(process.nextTick);

        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.grid.removeAll).toHaveBeenCalledTimes(1);
        expect(dashboard.grid.makeWidget).toHaveBeenNthCalledWith(1, expect.toSatisfy((html) => {
            return $(html).attr('gs-id') === '4';
        }));
        expect(dashboard.grid.makeWidget).toHaveBeenNthCalledWith(2, expect.toSatisfy((html) => {
            return $(html).attr('gs-id') === '5';
        }));
        expect(dashboard.grid.makeWidget).toHaveBeenNthCalledWith(3, expect.toSatisfy((html) => {
            return $(html).attr('gs-id') === '6';
        }));
        expect(dashboard.getCardsAjax).toHaveBeenCalledTimes(1);
    });

    test('setLastDashboard', () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard'
        });

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            dashboard: 'current_dashboard',
            page: 'http://localhost/',
            action: 'set_last_dashboard',
        }, () => {return true;}));
        dashboard.setLastDashboard();
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });

    test('saveFilter', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.filters_selector = '#filter-selector';

        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                'filter1': 'filter1_value',
            };
        });
        dashboard.setFiltersInDB = jest.fn().mockImplementation(() => {});
        window.sortable = jest.fn().mockImplementation(() => {});
        dashboard.refreshCardsImpactedByFilter = jest.fn().mockImplementation(() => {});

        dashboard.saveFilter('filter2', 'filter2_value');

        expect(dashboard.getFiltersFromDB).toHaveBeenCalledTimes(1);
        expect(dashboard.setFiltersInDB).toHaveBeenCalledWith({
            'filter1': 'filter1_value',
            'filter2': 'filter2_value',
        });
        expect(window.sortable).toHaveBeenCalledWith('#filter-selector', 'reload');
        expect(dashboard.refreshCardsImpactedByFilter).toHaveBeenCalledWith('filter2');
    });

    test('refreshCardsImpactedByFilter', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append(`
            <div class="dashboard">
                <div class="grid-stack-item" gs-id="4"><div class="card filter-filter1"></div></div>
                <div class="grid-stack-item" gs-id="5"><div class="card filter-filter2"></div></div>
                <div class="grid-stack-item" gs-id="6"><div class="card filter-filter1"></div></div>
            </div>
        `);
        dashboard.getCardsAjax = jest.fn().mockImplementation(() => {});

        dashboard.refreshCardsImpactedByFilter('filter1');

        expect(dashboard.getCardsAjax).toHaveBeenNthCalledWith(1, `[gs-id="${CSS.escape(4)}"]`);
        expect(dashboard.getCardsAjax).toHaveBeenNthCalledWith(2, `[gs-id="${CSS.escape(6)}"]`);
    });

    test('saveDashboard', async () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard'
        });
        dashboard.refreshDashboard = jest.fn().mockImplementation(() => {});

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'save_items',
            dashboard: 'current_dashboard',
            items: [], //FIXME There are no items here because the function uses a :visible selector and jsdom doesn't acutally do any layout so nothing is visible
            title: 'mytitle',
        }, () => {return true;}));

        dashboard.saveDashboard();
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(0);
    });

    test('saveDashboard Force Refresh', async () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard'
        });
        dashboard.refreshDashboard = jest.fn().mockImplementation(() => {});

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'save_items',
            dashboard: 'current_dashboard',
            items: [], //FIXME There are no items here because the function uses a :visible selector and jsdom doesn't acutally do any layout so nothing is visible
            title: 'mytitle',
        }, () => {return true;}));

        dashboard.saveDashboard(true);
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(1);
    });

    test('computeWidth', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append(`
            <div class="parent1" style="width: 100px; height: 150px">
                <div><div class="child-item"></div></div>
            </div>
            <div class="parent2" style="width: 200px; height: 100px">
                <div><div class="child-item"></div></div>
            </div>
            <div class="parent3" style="width: 200px; height: 50px">
                <div><div class="child-item"></div></div>
            </div>
        `);
        const items = $('.child-item');

        dashboard.computeWidth(items);

        // Only computed when width is bigger than height
        expect(items.eq(0).css('width')).toEqual('0px');
        expect(items.eq(1).css('width')).toEqual('71.42857142857143%');
        expect(items.eq(2).css('width')).toEqual('35.714285714285715%');
    });

    test('resetComputedWidth', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append(`
            <div class="parent1" style="width: 100px; height: 150px">
                <div><div class="child-item"></div></div>
            </div>
            <div class="parent2" style="width: 200px; height: 100px">
                <div><div class="child-item"></div></div>
            </div>
            <div class="parent3" style="width: 200px; height: 50px">
                <div><div class="child-item"></div></div>
            </div>
        `);
        const items = $('.child-item');
        dashboard.resetComputedWidth(items);

        expect(items.eq(0).css('width')).toEqual('100%');
        expect(items.eq(1).css('width')).toEqual('100%');
        expect(items.eq(2).css('width')).toEqual('100%');
    });

    test('fitNumbers', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.dash_width = 1000;
        $('body').append(`
            <div class="parent1">
                <div class="big-number big-number-1">
                    <div class="formatted-number">1</div>
                    <div class="label">Label 1</div>
                </div>
                <div class="summary-numbers summary-numbers-1">
                    <div class="line">
                        <div class="formatted-number">1</div>
                        <div class="label">Label 1</div>
                    </div>
                </div>
            </div>
        `);

        dashboard.resetComputedWidth = jest.fn().mockImplementation(() => {});
        dashboard.computeWidth = jest.fn().mockImplementation(() => {});
        $.fn.fitText = jest.fn().mockImplementation(() => {});

        dashboard.fitNumbers($('.parent1'));
        expect($.fn.fitText).toHaveBeenNthCalledWith(1, 1.16);
        expect($.fn.fitText).toHaveBeenNthCalledWith(2, 1.16 - 0.65);
        expect($.fn.fitText).toHaveBeenNthCalledWith(3, 1.16 - 0.2);
        expect($.fn.fitText).toHaveBeenNthCalledWith(4, 1.16 - 0.2, {minFontSize: '12px'});
        expect(dashboard.computeWidth).toHaveBeenNthCalledWith(1, expect.toSatisfy(
            (item) => {return item.hasClass('formatted-number') && item.closest('.big-number-1').length === 1;}
        ));
        expect(dashboard.computeWidth).toHaveBeenNthCalledWith(2, expect.toSatisfy(
            (item) => {return item.hasClass('label') && item.closest('.big-number-1').length === 1;}
        ));
        expect(dashboard.resetComputedWidth).toHaveBeenNthCalledWith(1, expect.toSatisfy(
            (item) => {return item.hasClass('formatted-number') && item.closest('.big-number-1').length === 1;}
        ));
        expect(dashboard.resetComputedWidth).toHaveBeenNthCalledWith(2, expect.toSatisfy(
            (item) => {return item.hasClass('label') && item.closest('.big-number-1').length === 1;}
        ));
    });

    test('fitNumbers small width', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.dash_width = 500;
        $('body').append(`
            <div class="parent1">
                <div class="big-number big-number-1">
                    <div class="formatted-number">1</div>
                    <div class="label">Label 1</div>
                </div>
                <div class="summary-numbers summary-numbers-1">
                    <div class="line">
                        <div class="formatted-number">1</div>
                        <div class="label">Label 1</div>
                    </div>
                </div>
            </div>
        `);

        dashboard.resetComputedWidth = jest.fn().mockImplementation(() => {});
        dashboard.computeWidth = jest.fn().mockImplementation(() => {});
        $.fn.fitText = jest.fn().mockImplementation(() => {});

        dashboard.fitNumbers($('.parent1'));
        expect($.fn.fitText).toHaveBeenNthCalledWith(1, 1.8);
        expect($.fn.fitText).toHaveBeenNthCalledWith(2, 1.8 - 0.65);
        expect($.fn.fitText).toHaveBeenNthCalledWith(3, 1.8 - 0.2);
        expect($.fn.fitText).toHaveBeenNthCalledWith(4, 1.8 - 0.2, {minFontSize: '12px'});
        expect(dashboard.computeWidth).toHaveBeenNthCalledWith(1, expect.toSatisfy(
            (item) => {return item.hasClass('formatted-number') && item.closest('.big-number-1').length === 1;}
        ));
        expect(dashboard.computeWidth).toHaveBeenNthCalledWith(2, expect.toSatisfy(
            (item) => {return item.hasClass('label') && item.closest('.big-number-1').length === 1;}
        ));
        expect(dashboard.resetComputedWidth).toHaveBeenNthCalledWith(1, expect.toSatisfy(
            (item) => {return item.hasClass('formatted-number') && item.closest('.big-number-1').length === 1;}
        ));
        expect(dashboard.resetComputedWidth).toHaveBeenNthCalledWith(2, expect.toSatisfy(
            (item) => {return item.hasClass('label') && item.closest('.big-number-1').length === 1;}
        ));
    });

    test('animateNumbers Int', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append(`
            <div class="parent1">
                <div class="big-number big-number-1">
                    <div class="formatted-number"><div class="number" data-precision="0">10000</div></div>
                    <div class="label">Label 1</div>
                </div>
            </div>
        `);

        jest.useFakeTimers();
        dashboard.animateNumbers($('.parent1'));
        expect(parseInt($('.parent1 .formatted-number').text())).toBeLessThan(10000);
        jest.advanceTimersByTime(500);
        expect(parseInt($('.parent1 .formatted-number').text())).toBeGreaterThan(400);
        expect(parseInt($('.parent1 .formatted-number').text())).toBeLessThan(10000);
        jest.advanceTimersByTime(500);
        expect(parseInt($('.parent1 .formatted-number').text())).toBe(10000);
        jest.advanceTimersByTime(500);
        expect(parseInt($('.parent1 .formatted-number').text())).toBe(10000);
    });

    test('animateNumbers Float', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append(`
            <div class="parent1">
                <div class="big-number big-number-1">
                    <div class="formatted-number"><div class="number" data-precision="3">10000.505</div></div>
                    <div class="label">Label 1</div>
                </div>
            </div>
        `);

        jest.useFakeTimers();
        dashboard.animateNumbers($('.parent1'));
        expect(parseFloat($('.parent1 .formatted-number').text())).toBeLessThan(10000);
        jest.advanceTimersByTime(500);
        expect(parseFloat($('.parent1 .formatted-number').text())).toBeGreaterThan(400);
        expect(parseFloat($('.parent1 .formatted-number').text())).toBeLessThan(10000);
        jest.advanceTimersByTime(500);
        expect(parseFloat($('.parent1 .formatted-number').text())).toBe(10000.505);
        jest.advanceTimersByTime(500);
        expect(parseFloat($('.parent1 .formatted-number').text())).toBe(10000.505);
    });

    test('animateNumbers Non-Number', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append(`
            <div class="parent1">
                <div class="big-number big-number-1">
                    <div class="formatted-number"><div class="number" data-precision="3">Test</div></div>
                    <div class="label">Label 1</div>
                </div>
            </div>
        `);

        jest.useFakeTimers();
        dashboard.animateNumbers($('.parent1'));
        expect($('.parent1 .formatted-number').text()).toBe('Test');
        jest.advanceTimersByTime(500);
        expect($('.parent1 .formatted-number').text()).toBe('Test');
        jest.advanceTimersByTime(500);
        expect($('.parent1 .formatted-number').text()).toBe('Test');
    });

    test('animateNumbers Int + Suffix', () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'filters_selector': '.filters',
        });
        $('body').append(`
            <div class="parent1">
                <div class="big-number big-number-1">
                    <div class="formatted-number"><div class="number" data-precision="0">10000</div><div class="suffix">Suffix</div></div>
                    <div class="label">Label 1</div>
                </div>
            </div>
        `);

        jest.useFakeTimers();
        dashboard.animateNumbers($('.parent1'));
        expect($('.parent1 .formatted-number .number').text()).toBe('0');
        jest.advanceTimersByTime(1000);
        expect($('.parent1 .formatted-number .number').text()).toBe('10000');
    });

    test('setEditMode', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.saveDashboard = jest.fn().mockImplementation(() => {});
        dashboard.grid.setStatic = jest.fn().mockImplementation(() => {});
        window.sortable = jest.fn().mockImplementation(() => {});
        $('body').append(`<div class="grid-stack-item dirty"></div><div class="grid-stack-item dirty"></div>`);

        dashboard.setEditMode(true);
        expect(dashboard.edit_mode).toBe(true);
        expect($('.edit-dashboard').get(0)).toHaveClass('active');
        expect(dashboard.element.get(0)).toHaveClass('edit-mode');
        expect(dashboard.grid.setStatic).toHaveBeenCalledWith(false);
        // not call as there is no filters expect(window.sortable).toHaveBeenCalledWith('#dashboard-12345 .filters', 'enable');
        expect(dashboard.saveDashboard).not.toHaveBeenCalled();

        $('body .grid-stack-item').remove();

        jest.clearAllMocks();

        dashboard.setEditMode(false);
        expect(dashboard.edit_mode).toBe(false);
        expect($('.edit-dashboard').get(0)).not.toHaveClass('active');
        expect(dashboard.element.get(0)).not.toHaveClass('edit-mode');
        expect(dashboard.grid.setStatic).toHaveBeenCalledWith(true);
        // not call as there is no filters expect(window.sortable).toHaveBeenCalledWith('#dashboard-12345 .filters', 'disable');
        expect(dashboard.saveDashboard).not.toHaveBeenCalled();

        $('body').append(`<div class="grid-stack-item dirty"></div><div class="grid-stack-item dirty"></div>`);
        dashboard.setEditMode(false);
        expect(dashboard.edit_mode).toBe(false);
        expect(dashboard.saveDashboard).toHaveBeenCalledTimes(1);
    });

    test('toggleFullscreenMode', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        window.GoInFullscreen = jest.fn().mockImplementation(() => {});
        window.GoOutFullscreen = jest.fn().mockImplementation(() => {});
        dashboard.setEditMode = jest.fn().mockImplementation(() => {});
        $('body').append(`<button class="toggle-fullscreen active"></button>`);

        dashboard.toggleFullscreenMode($('button.toggle-fullscreen'));
        expect($('button.toggle-fullscreen').get(0)).not.toHaveClass('active');
        expect(dashboard.setEditMode).not.toHaveBeenCalled();
        expect(window.GoInFullscreen).not.toHaveBeenCalled();
        expect(window.GoOutFullscreen).toHaveBeenCalled();

        jest.clearAllMocks();

        dashboard.toggleFullscreenMode($('button.toggle-fullscreen'));
        expect($('button.toggle-fullscreen').get(0)).toHaveClass('active');
        expect(dashboard.setEditMode).toHaveBeenCalledWith(false);
        expect(window.GoInFullscreen).toHaveBeenCalledWith(expect.toSatisfy((arg) => {
            return $(arg).attr('id') === 'dashboard-12345';
        }));
        expect(window.GoOutFullscreen).not.toHaveBeenCalled();
    });

    test('clone', async () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        dashboard.addNewDashbardInSelect = jest.fn().mockImplementation(() => {});

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'clone_dashboard',
            dashboard: 'current_dashboard',
        }, () => {
            return {
                title: 'New dashboard',
                key: 'new_dashboard',
            };
        }));

        dashboard.clone();
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.addNewDashbardInSelect).toHaveBeenCalledWith('New dashboard', 'new_dashboard');
    });

    test('delete', async () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        window.confirm = jest.fn().mockImplementationOnce(() => {
            return false;
        }).mockImplementationOnce(() => {
            return true;
        });
        dashboard.setLastDashboard = jest.fn().mockImplementation(() => {});

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'delete_dashboard',
            dashboard: 'current_dashboard',
        }, () => {
            return true;
        }));

        dashboard.delete();
        await new Promise(process.nextTick);
        // Confirm dialog should return false (not confirmed), so the dashboard shouldn't be deleted
        expect(window.AjaxMock.isResponseStackEmpty()).toBeFalse();
        expect($('.dashboard_select option[value="current_dashboard"]').length).toBe(1);
        expect($('.dashboard_select option').length).toBe(3);

        dashboard.delete();
        await new Promise(process.nextTick);
        // Confirm dialog should return true (confirmed), so the dashboard should be deleted
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.setLastDashboard).toHaveBeenCalled();
        expect($('.dashboard_select option[value="current_dashboard"]').length).toBe(0);
        expect($('.dashboard_select option').length).toBe(2);
    });

    test('addForm', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        window.glpi_ajax_dialog = jest.fn().mockImplementation(() => {});
        dashboard.addForm();
        expect(window.glpi_ajax_dialog).toHaveBeenCalledWith(expect.toSatisfy((arg) => {
            return arg.params.action === 'add_new' && arg.url === '//ajax/dashboard.php';
        }));
    });

    test('addNew', async () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addNewDashbardInSelect = jest.fn().mockImplementation(() => {});
        dashboard.setEditMode = jest.fn().mockImplementation(() => {});

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'save_new_dashboard',
            title: 'mytitle',
            context: 'core'
        }, () => {
            return 'new_dashboard';
        }));

        dashboard.addNew({
            title: 'mytitle',
        });
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.addNewDashbardInSelect).toHaveBeenCalledWith('mytitle', 'new_dashboard');
        expect(dashboard.setEditMode).toHaveBeenCalledWith(true);
    });

    test('addNew Other Context', async () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.context = 'other_context';
        dashboard.addNewDashbardInSelect = jest.fn().mockImplementation(() => {});
        dashboard.setEditMode = jest.fn().mockImplementation(() => {});

        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'save_new_dashboard',
            title: 'mytitle',
            context: 'other_context'
        }, () => {
            return 'new_dashboard';
        }));

        dashboard.addNew({
            title: 'mytitle',
        });
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(dashboard.addNewDashbardInSelect).toHaveBeenCalledWith('mytitle', 'new_dashboard');
        expect(dashboard.setEditMode).toHaveBeenCalledWith(true);
    });

    test('addNewDashbardInSelect', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('.dashboard_select').off('change');
        expect($('.dashboard_select option[value="new_dashboard"]').length).toBe(0);
        dashboard.addNewDashbardInSelect('New dashboard', 'new_dashboard');
        const new_option = $('.dashboard_select option[value="new_dashboard"]');
        expect(new_option.length).toBe(1);
        expect(new_option.text()).toBe('New dashboard');
        expect(new_option.is(':selected')).toBeTrue();
    });

    test('getCardsAjax multi-mode all', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.ajax_cards = true;
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                'filter1': 'value1',
                'filter2': 'value2',
            };
        });
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});

        const gridstack_items = $('.grid-stack-item');
        $.each(gridstack_items, (index, item) => {
            $(item).data('card-options', {});
        });
        // When ajax_cards is true, getCardsAjax should return an array of promises
        expect(Array.isArray(dashboard.getCardsAjax())).toBeTrue();
    });

    test('getCardsAjax multi-mode embed', async () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.ajax_cards = true;
        dashboard.embed = true;
        dashboard.token = 'mytoken';
        dashboard.entities_id = 3;
        dashboard.is_recursive = 1;
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                'filter1': 'value1',
                'filter2': 'value2',
            };
        });
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});
        const gridstack_items = $('.grid-stack-item');
        $.each(gridstack_items, (index, item) => {
            $(item).data('card-options', {});
        });
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            embed: 1,
            action: 'get_card',
            token: 'mytoken',
            entities_id: 3,
            is_recursive: 1,
            card_id: 1
        }, () => {return true;}));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            embed: 1,
            action: 'get_card',
            token: 'mytoken',
            entities_id: 3,
            is_recursive: 1,
            card_id: 2
        }, () => {return true;}));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            embed: 1,
            action: 'get_card',
            token: 'mytoken',
            entities_id: 3,
            is_recursive: 1,
            card_id: 3
        }, () => {return true;}));

        dashboard.getCardsAjax();
        await new Promise(process.nextTick);

        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });

    test('getCardsAjax multi-mode single', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.ajax_cards = true;
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                'filter1': 'value1',
                'filter2': 'value2',
            };
        });
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});

        const gridstack_items = $('.grid-stack-item');
        $.each(gridstack_items, (index, item) => {
            $(item).data('card-options', {});
        });
        // When ajax_cards is true, getCardsAjax should return an array of promises
        expect(Array.isArray(dashboard.getCardsAjax('[gs-id="2"]'))).toBeTrue();
    });

    test('getCardsAjax multi-mode Error', async () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.ajax_cards = true;
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {};
        });
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});

        const gridstack_items = $('.grid-stack-item');
        $.each(gridstack_items, (index, item) => {
            $(item).data('card-options', {});
        });
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            action: 'get_card',
        }, () => {
            return true;
        }, false, 'error'));

        // When ajax_cards is false, getCardsAjax should return a single promise
        dashboard.getCardsAjax('[gs-id="2"]');
        await new Promise(process.nextTick);
        expect($('.grid-stack-item[gs-id="2"] .empty-card.card-error').length).toBe(1);
        // card 1 and 3 should still be present without error
        expect($('.grid-stack-item > *:not(.empty-card)').length).toBe(2);
    });

    test('getCardsAjax single-mode all', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.ajax_cards = false;
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                'filter1': 'value1',
                'filter2': 'value2',
            };
        });
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});

        const gridstack_items = $('.grid-stack-item');
        $.each(gridstack_items, (index, item) => {
            $(item).data('card-options', {});
        });
        // When ajax_cards is false, getCardsAjax should return a single promise
        const result = dashboard.getCardsAjax();
        expect(typeof result).toBe('object');
        expect(result.then).toBeDefined();
    });

    test('getCardsAjax single-mode embed', async () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.ajax_cards = false;
        dashboard.embed = true;
        dashboard.token = 'mytoken';
        dashboard.entities_id = 3;
        dashboard.is_recursive = 1;
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                'filter1': 'value1',
                'filter2': 'value2',
            };
        });
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});
        const gridstack_items = $('.grid-stack-item');
        $.each(gridstack_items, (index, item) => {
            $(item).data('card-options', {});
        });
        window.AjaxMock.start();
        const data = {
            "force": 0,
            "d_cache_key": "",
            "cards": [
                {"card_id":"1","force":0,"args":{"gridstack_id":"1","apply_filters":{"filter1":"value1","filter2":"value2"}},"c_cache_key":""},
                {"card_id":"2","force":0,"args":{"gridstack_id":"2","apply_filters":{"filter1":"value1","filter2":"value2"}},"c_cache_key":""},
                {"card_id":"3","force":0,"args":{"gridstack_id":"3","apply_filters":{"filter1":"value1","filter2":"value2"}},"c_cache_key":""}
            ],
            "embed": 1,
            "token": "mytoken",
            "entities_id": 3,
            "is_recursive": 1
        };
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'get_cards',
            data: JSON.stringify(data)
        }, () => {
            return ['<div></div>'];
        }));

        dashboard.getCardsAjax();
        await new Promise(process.nextTick);

        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });

    test('getCardsAjax single-mode single', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.ajax_cards = false;
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                'filter1': 'value1',
                'filter2': 'value2',
            };
        });
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});

        const gridstack_items = $('.grid-stack-item');
        $.each(gridstack_items, (index, item) => {
            $(item).data('card-options', {});
        });
        // When ajax_cards is false, getCardsAjax should return a single promise
        const result = dashboard.getCardsAjax('[gs-id="2"]');
        expect(typeof result).toBe('object');
        expect(result.then).toBeDefined();
    });

    test('getCardsAjax single-mode Error', async () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.ajax_cards = false;
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {};
        });
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});

        const gridstack_items = $('.grid-stack-item');
        $.each(gridstack_items, (index, item) => {
            $(item).data('card-options', {});
        });
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'get_cards',
        }, () => {
            return true;
        }, false, 'error'));

        // When ajax_cards is false, getCardsAjax should return a single promise
        dashboard.getCardsAjax('[gs-id="2"]');
        await new Promise(process.nextTick);
        expect($('.grid-stack-item[gs-id="2"] .empty-card.card-error').length).toBe(1);
        // card 1 and 3 should still be present without error
        expect($('.grid-stack-item > *:not(.empty-card)').length).toBe(2);
    });

    test('initFilters', async () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                'filter1': 'value1',
                'filter2': ['value2'],
                'filter3': [],
            };
        });
        window.sortable = jest.fn().mockImplementation((el) => {
            return $(el);
        });
        window.AjaxMock.start();

        dashboard.initFilters();
        // The call should exit early because there is no filters_selector set in the dashboard object
        expect(dashboard.getFiltersFromDB).not.toHaveBeenCalled();

        dashboard.filters_selector = '.filters';
        $('#dashboard-12345').append('<div class="filters"></div>');
        const init_filter_event_handler = jest.fn().mockImplementation(() => {});
        $(document).on('glpiDasbhoardInitFilter', init_filter_event_handler);
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            action: 'get_dashboard_filters',
            filters: {
                'filter1': 'value1',
                'filter2': ['value2'],
                'filter3': '',
            }
        }, () => {
            return `<div class="new-filter-selector"></div>`;
        }));

        dashboard.initFilters();
        await new Promise(process.nextTick);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(init_filter_event_handler).toHaveBeenCalled();
        expect($('.filters').length).toBe(1);
        expect($('.filters .new-filter-selector').length).toBe(1);
        expect(window.sortable).toHaveBeenNthCalledWith(1, '.filters', {
            placeholderClass: 'filter-placeholder',
            orientation: 'horizontal'
        });
        expect(window.sortable).toHaveBeenNthCalledWith(2, '.filters', 'disable');
    });

    test('getFiltersFromDB', () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'GET', {
            action: 'get_filter_data',
            dashboard: 'current_dashboard',
        }, () => {
            return JSON.stringify({
                'filter1': 'value1',
                'filter2': ['value2'],
                'filter3': '',
            });
        }));

        // eslint-disable-next-line no-unused-vars
        const result = dashboard.getFiltersFromDB();
        //TODO filters returned are undefined. Maybe an issue with the mock AJAX handling and synchronous AJAX calls
        //Maybe this can be made async

        // expect(result).toEqual({
        //     'filter1': 'value1',
        //     'filter2': ['value2'],
        //     'filter3': '',
        // });
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });

    test('setFiltersInDB', () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'save_filter_data',
            dashboard: 'current_dashboard',
            filters: JSON.stringify({
                'filter1': 'value1',
                'filter2': null
            })
        }, () => {
            return true;
        }));
        dashboard.setFiltersInDB({
            'filter1': 'value1',
            'filter2': undefined
        });

        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
    });

    test('Change selected dashboard', () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        dashboard.refreshDashboard = jest.fn().mockImplementation(() => {});
        dashboard.setLastDashboard = jest.fn().mockImplementation(() => {});
        dashboard.initFilters = jest.fn().mockImplementation(() => {});

        $('.dashboard_select option[value="other_dashboard_2"]').prop('selected', true).trigger('change');
        expect(dashboard.refreshDashboard).toHaveBeenCalled();
        expect(dashboard.setLastDashboard).toHaveBeenCalled();
        expect(dashboard.initFilters).toHaveBeenCalled();
        expect($('.dashboard-name').val()).toBe('Other Dashboard 2');
    });

    test('Click add dashboard button', () => {
        $('#dashboard-12345 .toolbar').append('<button class="add-dashboard"></button>');
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addForm = jest.fn().mockImplementation(() => {});
        $('#dashboard-12345 .toolbar .add-dashboard').trigger('click');
        expect(dashboard.addForm).toHaveBeenCalled();
    });

    test('Submit add dashboard form', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.addNew = jest.fn().mockImplementation(() => {});
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        $('body').append(`
            <form class="display-add-dashboard-form">
                <input type="text" name="title" id="title_12345" class="form-control" value="New Dashboard">
            </form>
        `);
        $('.display-add-dashboard-form').submit();
        expect(dashboard.addNew).toHaveBeenCalledWith({
            'title': 'New Dashboard',
        });
        expect(window.glpi_close_all_dialogs).toHaveBeenCalled();
    });

    test('Click delete dashboard button', () => {
        $('#dashboard-12345 .toolbar').append('<button class="delete-dashboard"></button>');
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.delete = jest.fn().mockImplementation(() => {});
        $('#dashboard-12345 .toolbar .delete-dashboard').trigger('click');
        expect(dashboard.delete).toHaveBeenCalled();
    });

    test('Click clone dashboard button', () => {
        $('#dashboard-12345 .toolbar').append('<button class="clone-dashboard"></button>');
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.clone = jest.fn().mockImplementation(() => {});
        $('#dashboard-12345 .toolbar .clone-dashboard').trigger('click');
        expect(dashboard.clone).toHaveBeenCalled();
    });

    test('Click open embed form button', () => {
        $('#dashboard-12345 .toolbar').append('<button class="open-embed"></button>');
        new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        window.glpi_ajax_dialog = jest.fn().mockImplementation(() => {});
        $('#dashboard-12345 .toolbar .open-embed').trigger('click');
        expect(window.glpi_ajax_dialog).toHaveBeenCalledWith(expect.toSatisfy((params) => {
            return params.params.action === 'display_embed_form' && params.params.dashboard === 'current_dashboard';
        }));
    });

    test('Click toggle edit mode button', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        const edit_btn = $('#dashboard-12345 .toolbar .edit-dashboard');
        dashboard.setEditMode = jest.fn().mockImplementation(() => {});
        edit_btn.trigger('click');
        expect(dashboard.setEditMode).toHaveBeenCalledWith(true);
        edit_btn.addClass('active');
        jest.clearAllMocks();
        edit_btn.trigger('click');
        expect(dashboard.setEditMode).toHaveBeenCalledWith(false);
    });

    test('Click toggle fullscreen button', () => {
        $('#dashboard-12345 .toolbar').append('<button class="toggle-fullscreen"></button>');
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.toggleFullscreenMode = jest.fn().mockImplementation(() => {});
        $('#dashboard-12345 .toolbar .toggle-fullscreen').trigger('click');
        expect(dashboard.toggleFullscreenMode).toHaveBeenCalled();
    });

    test('Click toggle night mode button', () => {
        $('#dashboard-12345 .toolbar').append('<button class="night-mode"></button>');
        new GLPIDashboard({'rand': '12345'});
        $('#dashboard-12345 .toolbar .night-mode').trigger('click');
        expect($('#dashboard-12345').hasClass('theme-dark')).toBeTrue();
        expect($('#dashboard-12345 .toolbar .night-mode').hasClass('active')).toBeTrue();
        $('#dashboard-12345 .toolbar .night-mode').trigger('click');
        expect($('#dashboard-12345').hasClass('theme-dark')).toBeFalse();
        expect($('#dashboard-12345 .toolbar .night-mode').hasClass('active')).toBeFalse();
    });

    test('Click refresh mode button', () => {
        $('#dashboard-12345 .toolbar').append('<button class="auto-refresh"></button>');
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.refreshDashboard = jest.fn().mockImplementation(() => {});
        CFG_GLPI.refresh_views = 1; // Refresh every minute
        const refresh_btn = $('#dashboard-12345 .toolbar .auto-refresh');

        jest.useFakeTimers('legacy');
        refresh_btn.trigger('click');
        expect(refresh_btn.hasClass('active')).toBeTrue();
        jest.advanceTimersByTime(60000);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(1);
        jest.advanceTimersByTime(60000);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(2);
        refresh_btn.trigger('click');
        expect(refresh_btn.hasClass('active')).toBeFalse();
        jest.advanceTimersByTime(60000);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(2);
        jest.advanceTimersByTime(60000);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(2);
        refresh_btn.trigger('click');
        expect(refresh_btn.hasClass('active')).toBeTrue();
        jest.advanceTimersByTime(60000);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(3);
    });

    test('Click Refresh Mode button with Invalid CFG_GLPI.refresh_views', () => {
        $('#dashboard-12345 .toolbar').append('<button class="auto-refresh"></button>');
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.refreshDashboard = jest.fn().mockImplementation(() => {});
        CFG_GLPI.refresh_views = 'not a number';
        const refresh_btn = $('#dashboard-12345 .toolbar .auto-refresh');

        jest.useFakeTimers('legacy');
        refresh_btn.trigger('click');
        expect(refresh_btn.hasClass('active')).toBeTrue();
        jest.advanceTimersByTime(60000 * 30);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(1);
        jest.advanceTimersByTime(60000 * 30);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(2);

        // Click again to disable
        refresh_btn.trigger('click');
        jest.clearAllMocks();

        CFG_GLPI.refresh_views = 0;
        refresh_btn.trigger('click');
        expect(refresh_btn.hasClass('active')).toBeTrue();
        jest.advanceTimersByTime(60000 * 30);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(1);
        jest.advanceTimersByTime(60000 * 30);
        expect(dashboard.refreshDashboard).toHaveBeenCalledTimes(2);
    });

    test('Click save rights button', () => {
        new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        $('body').append(`
            <form class="display-rights-form">
                <button type="button" class="save_rights"></button>
                <select name="rights" multiple="multiple">
                    <option value="users_id-1" selected>User 1</option>
                    <option value="users_id-2">User 2</option>
                    <option value="users_id-3" selected>User 3</option>
                    <option value="profiles_id-1" selected>Profile 1</option>
                </select>
                <select name="is_private">
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                </select>
            </form>
        `);
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        window.AjaxMock.start();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/dashboard.php', 'POST', {
            action: 'save_rights',
            dashboard: 'current_dashboard',
            rights: {
                'users_id': ['1', '3'],
                'profiles_id': ['1'],
            },
            is_private: false
        }, () => {
            return true;
        }));

        $('.display-rights-form .save_rights').trigger('click');
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(window.glpi_close_all_dialogs).toHaveBeenCalled();
    });

    test('Click widget delete button', () => {
        $('#dashboard-12345 .grid-stack-item[gs-id="2"]').empty().append(`
            <div><button class="delete-item"></button></div>
        `);
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.grid.removeWidget = jest.fn().mockImplementation(() => {});
        dashboard.saveDashboard = jest.fn().mockImplementation(() => {});

        $('#dashboard-12345 .grid-stack-item[gs-id="2"] .delete-item').trigger('click');
        expect(dashboard.grid.removeWidget).toHaveBeenCalledTimes(1);
        expect(dashboard.grid.removeWidget).toHaveBeenCalledWith(expect.toSatisfy((widget) => {
            return $(widget).attr('gs-id') === '2';
        }));
        expect(dashboard.saveDashboard).toHaveBeenCalled();
    });

    test('Click widget refresh button', () => {
        $('#dashboard-12345 .grid-stack-item[gs-id="2"]').empty().append(`
            <div><button class="refresh-item"></button></div>
        `);
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.getCardsAjax = jest.fn().mockImplementation(() => {});

        $('#dashboard-12345 .grid-stack-item[gs-id="2"] .refresh-item').trigger('click');
        expect(dashboard.getCardsAjax).toHaveBeenCalledTimes(1);
        expect(dashboard.getCardsAjax).toHaveBeenCalledWith(`[gs-id="${CSS.escape(2)}"]`);
    });

    test('Click widget edit button', () => {
        $('#dashboard-12345 .grid-stack-item[gs-id="2"]').empty().append(`
            <div><button class="edit-item"></button></div>
        `);
        $('#dashboard-12345 .grid-stack-item[gs-id="2"]').data('card-options', {
            card_id: 'mycard_id'
        });
        new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        window.glpi_ajax_dialog = jest.fn().mockImplementation(() => {});

        $('#dashboard-12345 .grid-stack-item[gs-id="2"] .edit-item').trigger('click');
        expect(window.glpi_ajax_dialog).toHaveBeenCalledTimes(1);
        expect(window.glpi_ajax_dialog).toHaveBeenCalledWith(expect.toSatisfy((params) => {
            return params.url === '//ajax/dashboard.php' && params.params.action === 'display_edit_widget'
                && params.params.gridstack_id === '2' && params.params.dashboard === 'current_dashboard'
                && params.params.card_id === 'mycard_id';
        }));
    });

    test('Click widget add button', () => {
        $('#dashboard-12345 .grid-stack').append(`
            <div class="cell-add"></div>
        `);
        new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        window.glpi_ajax_dialog = jest.fn().mockImplementation(() => {});

        $('#dashboard-12345 .cell-add').trigger('click');
        expect(window.glpi_ajax_dialog).toHaveBeenCalledWith(expect.toSatisfy((params) => {
            return params.url === '//ajax/dashboard.php' && params.params.action === 'display_add_widget'
                && params.params.dashboard === 'current_dashboard';
        }));
    });

    test('Click filter add button', () => {
        $('#dashboard-12345').append(`
            <div class="filters_toolbar">
                <button class="add-filter"></button>
            </div>
        `);
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        window.glpi_ajax_dialog = jest.fn().mockImplementation(() => {});
        window.glpi_close_all_dialogs = jest.fn().mockImplementation(() => {});
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                filter1: 'value1',
                filter2: 'value2',
            };
        });

        $('#dashboard-12345 .filters_toolbar .add-filter').trigger('click');
        expect(window.glpi_ajax_dialog).toHaveBeenCalledWith(expect.toSatisfy((params) => {
            return params.url === '//ajax/dashboard.php' && params.params.action === 'display_add_filter'
                && params.params.dashboard === 'current_dashboard' && params.params.used.includes('filter1')
                && params.params.used.includes('filter2');
        }));
        expect(window.glpi_close_all_dialogs).toHaveBeenCalled();
        expect(dashboard.getFiltersFromDB).toHaveBeenCalled();
    });

    test('Click filter delete button', () => {
        $('#dashboard-12345').append(`
            <div class="filters_toolbar">
                <div class="filter" data-filter-id="filter1">
                    <button class="delete-filter"></button>
                </div>
            </div>
        `);
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        dashboard.getFiltersFromDB = jest.fn().mockImplementation(() => {
            return {
                filter1: 'value1',
                filter2: 'value2',
            };
        });
        dashboard.setFiltersInDB = jest.fn().mockImplementation(() => {});
        dashboard.refreshCardsImpactedByFilter = jest.fn().mockImplementation(() => {});

        $('#dashboard-12345 .filters_toolbar .filter .delete-filter').trigger('click');
        expect(dashboard.getFiltersFromDB).toHaveBeenCalled();
        expect(dashboard.setFiltersInDB).toHaveBeenCalledWith({
            filter2: 'value2',
        });
        expect(dashboard.refreshCardsImpactedByFilter).toHaveBeenCalledWith('filter1');
    });

    test('Single ajax mode - Animate on load', () => {
        //let  = false;
        const MockGLPIDashboard = class extends GLPIDashboard {
            fitNumbers() {}
        };
        MockGLPIDashboard.prototype.fitNumbers = jest.fn().mockImplementation(() => {});
        let dashboard = new MockGLPIDashboard({
            'rand': '12345',
            'ajax_cards': true,
        });
        expect(dashboard.fitNumbers).not.toHaveBeenCalled();
        dashboard = new MockGLPIDashboard({
            'rand': '12345',
            'ajax_cards': false,
        });
        expect(dashboard.fitNumbers).toHaveBeenCalled();
    });

    test('Update CSS and Fit Numbers on Resize', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        jest.useFakeTimers();
        $(window).trigger('resize');
        jest.advanceTimersByTime(250);
        expect(dashboard.fitNumbers).toHaveBeenCalled();
    });

    test('Do not Update CSS and Fit Numbers on Propagated Resize', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        jest.useFakeTimers();
        $('body').trigger('resize');
        jest.advanceTimersByTime(250);
        expect(dashboard.fitNumbers).not.toHaveBeenCalled();
    });

    test('Save Dashboard on GridStack DragStop', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.saveDashboard = jest.fn().mockImplementation(() => {});
        dashboard.grid.dispatchEvent(new Event('dragstop'));
        expect(dashboard.saveDashboard).toHaveBeenCalled();
    });

    test('Save Dashboard on GridStack ResizeStop', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.saveDashboard = jest.fn().mockImplementation(() => {});
        dashboard.grid.dispatchEvent(new Event('resizestop'));
        expect(dashboard.saveDashboard).toHaveBeenCalled();
    });

    test('Resize and Animate Numbers on GridStack ResizeStop', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        dashboard.resetComputedWidth = jest.fn().mockImplementation(() => {});
        dashboard.fitNumbers = jest.fn().mockImplementation(() => {});
        dashboard.animateNumbers = jest.fn().mockImplementation(() => {});
        dashboard.grid.dispatchEvent(new Event('resizestop'));
        expect(dashboard.resetComputedWidth).toHaveBeenCalledTimes(2);
        expect(dashboard.fitNumbers).toHaveBeenCalledTimes(1);
        expect(dashboard.animateNumbers).toHaveBeenCalledTimes(1);
    });

    test('Add/Update Widget after Form Submit', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append(`<form class="display-widget-form"></form>`);
        dashboard.setWidgetFromForm = jest.fn().mockImplementation(() => {});
        $('.display-widget-form').trigger('submit');
        expect(dashboard.setWidgetFromForm).toHaveBeenCalled();
    });

    test('Add/Update Filter after Form Submit', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('body').append(`<form class="display-filter-form"></form>`);
        dashboard.setFilterFromForm = jest.fn().mockImplementation(() => {});
        $('.display-filter-form').trigger('submit');
        expect(dashboard.setFilterFromForm).toHaveBeenCalled();
    });

    test('Rename Dahboard', () => {
        const dashboard = new GLPIDashboard({
            'rand': '12345',
            'current': 'current_dashboard',
        });
        $('#dashboard-12345 .dashboard-name').val('current_dashboard_new');
        $('#dashboard-12345').append(`<button type="button" class="save-dashboard-name"></button>`);
        dashboard.saveDashboard = jest.fn().mockImplementation(() => {});
        $('#dashboard-12345 .save-dashboard-name').trigger('click');

        expect(dashboard.saveDashboard).toHaveBeenCalled();
        expect($(`#dashboard-12345 .dashboard_select option[value="current_dashboard"]`).text()).toBe('current_dashboard_new');
    });

    test('Save Markdown on Input', () => {
        const dashboard = new GLPIDashboard({'rand': '12345'});
        $('#dashboard-12345').append(`<div class="card markdown"><textarea class="markdown_content"></textarea></div>`);
        dashboard.saveMarkdown = jest.fn().mockImplementation(() => {});
        $('#dashboard-12345 .markdown_content').trigger('input');
        expect(dashboard.saveMarkdown).toHaveBeenCalled();
    });
});
