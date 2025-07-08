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

import '/js/modules/Search/Table.js';
import {jest} from '@jest/globals';

describe('Search Table', () => {
    beforeEach(() => {
        jest.clearAllMocks();
        // Mock reload function
        delete window.location;
        Object.defineProperty(window, 'location', {
            value: {
                href: '',
                reload: jest.fn().mockImplementation(() => {})
            },
            writable: true,
            configurable: true,
        });
        window_reload_spy = jest.spyOn(window.location, 'reload');
        window.AjaxMock.start();
    });
    $(document.body).append(`
    <div class="search-container">
        <form class="search-form-container">
            <input name="criteria[0][link]" value="AND"/>
            <input name="criteria[0][field]" value="view"/>
            <input name="criteria[0][searchtype]" value="contains"/>
            <input name="criteria[0][value]" value="criteria_test"/>
            <input name="is_deleted" value="0"/>
            <input name="as_map" value="0"/>
            <button name="search" type="button"/>
            <a class="btn search-reset" href="#"></a>
        </form>
        <div class="ajax-container search-display-data">
            <form id="massformComputer" data-search-itemtype="Computer">
                <div class="search-card">
                    <select class="search-limit-dropdown">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15" selected="selected">15</option>
                        <option value="20">20</option>
                    </select>
                    <div class="table-responsive-md">
                        <table id="search_9439839" class="search-results">
                            <thead>
                                <th data-searchopt-id="1" data-sort-order="ASC" data-sort-num="0"></th>
                                <th data-searchopt-id="2" data-sort-order="nosort"></th>
                                <th data-searchopt-id="3" data-sort-order="nosort"></th>
                                <th data-searchopt-id="4" data-sort-order="nosort"></th>
                            </thead>
                        </table>
                    </div>
                    <select class="search-limit-dropdown">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15" selected="selected">15</option>
                        <option value="20">20</option>
                    </select>
                    <ul class="pagination">
                        <li class="page-item">
                            <a class="page-link page-link-start" href="#" data-start="0">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link page-link-prev" href="#" data-start="15">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        <li class="page-item active">
                            <a class="page-link page-link-num" href="#" data-start="30">30</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link page-link-num" href="#" data-start="45">45</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link page-link-next" href="#" data-start="60">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link page-link-last" href="#" data-start="75">
                            <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </form>
        </div>
    </div>
`);
    window.GLPI.Search.Table.prototype.getResultsView = jest.fn(() => {
        return {
            setID(id) {
                return id;
            },
            getElement: () => {
                return $('#massformComputer');
            },
            getAJAXContainer: () => {
                return $('#massformComputer').closest('.ajax-container');
            }
        };
    });

    const real_table = new GLPI.Search.Table('massformComputer');
    $.fn.load = jest.fn().mockImplementation((url, data, callback) => {
        callback();
    });

    let window_reload_spy = null;
    const table_showSpinner = jest.spyOn(real_table, 'showLoadingSpinner');
    const table_hideSpinner = jest.spyOn(real_table, 'hideLoadingSpinner');
    const table_onLimitChange = jest.spyOn(real_table, 'onLimitChange');
    const table_onSearch = jest.spyOn(real_table, 'onSearch');
    const table_onPageChange = jest.spyOn(real_table, 'onPageChange');

    const table_el = real_table.getElement();
    const restore_initial_sort_state = () => {
        table_el.find('th').attr('data-sort-order', 'nosort');
        table_el.find('th').attr('data-sort-num', null);
        table_el.find('th').eq(0).attr('data-sort-order', 'ASC');
        table_el.find('th').eq(0).attr('data-sort-num', 0);
    };

    const ctrl_click = $.Event('click');
    ctrl_click.ctrlKey = true;

    test('Class exists', () => {
        expect(GLPI).toBeDefined();
        expect(GLPI.Search).toBeDefined();
        expect(GLPI.Search.Table).toBeDefined();
    });
    test('getElement', () => {
        expect(real_table.getElement().length).toBe(1);
    });
    test('getItemtype', () => {
        expect(real_table.getItemtype()).toBe('Computer');
    });
    test('getResultsView', () => {
        expect(real_table.getResultsView()).toBeObject();
    });
    test('setSortStateFromColumns', () => {
        const verify_initial_sort_state = () => {
            const state = real_table.setSortStateFromColumns();
            expect(state['sort'].length).toBe(1);
            expect(state['order'].length).toBe(1);
            expect(state['sort'][0]).toBe('1');
            expect(state['order'][0]).toBe('ASC');
        };
        restore_initial_sort_state();
        verify_initial_sort_state();

        // Manually modify data for existing sorted column and test again
        real_table.getElement().find('th').eq(0).attr('data-sort-order', 'DESC');
        let state = real_table.setSortStateFromColumns();
        expect(state['sort'].length).toBe(1);
        expect(state['order'].length).toBe(1);
        expect(state['sort'][0]).toBe('1');
        expect(state['order'][0]).toBe('DESC');

        // Manually add new sort
        real_table.getElement().find('th').eq(2).attr('data-sort-order', 'ASC');
        real_table.getElement().find('th').eq(2).attr('data-sort-num', '1');
        state = real_table.setSortStateFromColumns();
        expect(state['sort'].length).toBe(2);
        expect(state['order'].length).toBe(2);
        expect(state['sort'][1]).toBe('3');
        expect(state['order'][1]).toBe('ASC');

        // Ctrl-Click to add new sort
        real_table.getElement().find('th').eq(3).trigger(ctrl_click);
        state = real_table.setSortStateFromColumns();
        expect(state['sort'].length).toBe(3);
        expect(state['order'].length).toBe(3);
        expect(state['sort'][2]).toBe('4');
        expect(state['order'][2]).toBe('ASC');

        // Click previous column again to switch it to DESC. Expect the other columns to remain.
        real_table.getElement().find('th').eq(3).click();
        state = real_table.setSortStateFromColumns();
        expect(state['sort'].length).toBe(3);
        expect(state['order'].length).toBe(3);
        expect(state['sort'][2]).toBe('4');
        expect(state['order'][2]).toBe('DESC');

        // Click previous column again. We should be back at a nosort state for it (excluded from sort state).
        real_table.getElement().find('th').eq(3).click();
        state = real_table.setSortStateFromColumns();
        expect(state['sort'].length).toBe(2);
        expect(state['order'].length).toBe(2);
        expect(state['sort'][0]).toBe('1');
        expect(state['order'][0]).toBe('DESC');
        expect(state['sort'][1]).toBe('3');
        expect(state['order'][1]).toBe('ASC');

        // Click new column to become the only sort
        real_table.getElement().find('th').eq(1).click();
        state = real_table.setSortStateFromColumns();
        expect(state['sort'].length).toBe(1);
        expect(state['order'].length).toBe(1);
        expect(state['sort'][0]).toBe('2');
        expect(state['order'][0]).toBe('ASC');

        // Restore sort
        restore_initial_sort_state();
        verify_initial_sort_state();

        // Cycle the sort order of the only selected column and ensure that is only goes through ASC and DESC and not no sort.
        const sorted = $('th[data-sort-num="0"]');
        expect(sorted.length).toBe(1);
        sorted.click();
        expect(sorted.attr('data-sort-order')).toBe('DESC');
        sorted.click();
        expect(sorted.attr('data-sort-order')).toBe('ASC');
        sorted.click();
        expect(sorted.attr('data-sort-order')).toBe('DESC');
        sorted.click();
        expect(sorted.attr('data-sort-order')).toBe('ASC');

        // Restore sort
        restore_initial_sort_state();
        verify_initial_sort_state();

        // Ensure non-consecutive sorts do not add extra, null sorts.
        real_table.getElement().find('th').eq(0).attr('data-sort-num', '1');
        real_table.getElement().find('th').eq(0).attr('data-sort-order', 'ASC');
        real_table.getElement().find('th').eq(2).attr('data-sort-num', '1');
        real_table.getElement().find('th').eq(2).attr('data-sort-order', 'ASC');
        state = real_table.setSortStateFromColumns();
        expect(state['sort'].length).toBe(2);
        expect(state['order'].length).toBe(2);

        // Restore sort
        restore_initial_sort_state();
        verify_initial_sort_state();
    });
    test('AJAX refresh on sort', async () => {
        restore_initial_sort_state();
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/search.php', 'GET', {}, () => {
            return $('div.ajax-container').html();
        }));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/displayMessageAfterRedirect.php', 'GET', {}, () => {
            return {};
        }));

        real_table.getElement().find('th').eq(0).click();
        // Wait for mocked AJAX response to resolve
        await new Promise(process.nextTick);
        expect(table_showSpinner).toHaveBeenCalledTimes(1);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(table_hideSpinner).toHaveBeenCalledTimes(1);
        restore_initial_sort_state();
    });
    test('AJAX refresh on limit change', async () => {
        const history_push_spy = jest.spyOn(window.history, 'pushState');
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/search.php', 'GET', {
            glpilist_limit: '10',
        }, () => {
            return $('div.ajax-container').html();
        }));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/displayMessageAfterRedirect.php', 'GET', {}, () => {
            return {};
        }));
        real_table.getElement().closest('form').find('select.search-limit-dropdown').first().val(10).trigger('change');

        // Wait for mocked AJAX response to resolve
        await new Promise(process.nextTick);
        expect(table_showSpinner).toHaveBeenCalledTimes(1);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(table_hideSpinner).toHaveBeenCalledTimes(1);
        expect(table_onLimitChange).toHaveBeenCalledTimes(1);
        expect(history_push_spy).toHaveBeenCalledTimes(1);
    });
    test('AJAX refresh on search', async () => {
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/search.php', 'GET', {
            'criteria[0][link]': 'AND',
            'criteria[0][field]': 'view',
            'criteria[0][searchtype]': 'contains',
            'criteria[0][value]': 'criteria_test',
            'is_deleted': '0',
            'as_map': '0',
        }, () => {
            return $('div.ajax-container').html();
        }));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/displayMessageAfterRedirect.php', 'GET', {}, () => {
            return {};
        }));
        real_table.getElement().closest('.search-container').find('form.search-form-container button[name="search"]').trigger('click');
        // Wait for mocked AJAX response to resolve
        await new Promise(process.nextTick);
        expect(table_showSpinner).toHaveBeenCalledTimes(1);
        expect(window.AjaxMock.isResponseStackEmpty()).toBeTrue();
        expect(table_hideSpinner).toHaveBeenCalledTimes(1);
        expect(table_onSearch).toHaveBeenCalledTimes(1);
    });
    test('AJAX refresh on page change', async () => {
        const pagination = real_table.getElement().closest('.search-container').find('.pagination');
        const pagination_items = pagination.find('li');

        const expectOnlyActive = (index) => {
            for (let i = 0; i < pagination_items.length; i++) {
                expect(pagination_items.eq(i).hasClass('selected')).toBe(i === index);
            }
        };

        // This is more to test that no other test changes the active item
        expect(pagination_items.eq(2).hasClass('active')).toBeTrue();

        const click_order = [0, 1, 3, 4, 5, 2];
        for (let i = 0; i < click_order.length; i++) {
            const p = click_order[i];
            pagination_items.eq(p).find('.page-link').click();
            // Wait for mocked AJAX response to resolve
            expect(table_onPageChange).toHaveBeenCalledTimes(i + 1);
            expectOnlyActive(p);
        }
    });
    test('Show and Hide loading spinner', () => {
        const el = real_table.getElement();
        // Be sure no overlay exists yet
        el.parent().find('.spinner-overlay').remove();
        real_table.showLoadingSpinner();
        const overlay = el.parent().find('.spinner-overlay');
        expect(overlay.length).toBe(1);
        expect(overlay.css('visibility')).toBe('visible');
        real_table.hideLoadingSpinner();
        expect(overlay.length).toBe(1);
        expect(overlay.css('visibility')).toBe('hidden');
        // Re-show overlay and ensure it is visible
        real_table.showLoadingSpinner();
        expect(overlay.length).toBe(1);
        expect(overlay.css('visibility')).toBe('visible');
    });
    test('Reload after refresh exception', async () => {
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/search.php', 'GET', {}, () => {
            return $('div.ajax-container').html();
        }));
        window.AjaxMock.addMockResponse(new window.AjaxMockResponse('//ajax/displayMessageAfterRedirect.php', 'GET', {}, () => {
            return {};
        }));
        const get_itemtype = jest.spyOn(real_table, 'getItemtype');
        get_itemtype.mockImplementation(() => {
            throw 'Test exception';
        });
        real_table.refreshResults();
        // Wait for mocked AJAX response to resolve
        await new Promise(process.nextTick);
        expect(table_hideSpinner).toHaveBeenCalledTimes(0);
        // We expect the refresh to fail before the AJAX call
        expect(window.AjaxMock.isResponseStackEmpty()).toBeFalse();
        expect(window_reload_spy).toHaveBeenCalledTimes(1);
    });
});
