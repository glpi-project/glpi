/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* global bootstrap, validateFormWithBootstrap, displaySessionMessages */
/* global _ */

import GenericView from '/js/modules/Search/GenericView.js';

// Explicitly bind to window so Jest tests work properly
window.GLPI = window.GLPI || {};
window.GLPI.Search = window.GLPI.Search || {};

window.GLPI.Search.Table = class Table extends GenericView {
    // track sort/order changes globally
    sort_state = {
        sort: [],
        order: []
    };

    constructor(result_view_element_id, push_history = true, forced_params = {}) {
        const element_id = $(`#${CSS.escape(result_view_element_id)}`).parent().find('table.search-results').attr('id');
        super(element_id);

        this.push_history = push_history;
        this.forced_params = forced_params;

        const search_container = this.getElement().closest('.search-container');
        this.embedded_mode = !(search_container.length > 0 && search_container.find('form.search-form-container').length > 0);

        if (!this.embedded_mode) {
            // Remove 'criteria' from the forced params as we want it to be determined by the form
            delete this.forced_params.criteria;
        }

        this.shiftSelectAllCheckbox();
        this.toggleSavedSearch(true);
    }

    toggleSavedSearch(isDisable) {
        if (isDisable) {
            $('.bookmark_record')
                .attr('title', _.unescape(__('Submit current search before saving it')))
                .prop('disabled', true);
        } else {
            $('.bookmark_record')
                .attr('title', _.unescape(__('Save current search')))
                .prop('disabled', false);
        }
    }

    getElement() {
        return $(`#${CSS.escape(this.element_id)}`);
    }

    /**
    * Handle sorting when a column is clicked
    * @param target The target column element
    * @param multisort If true, allow adding a new sort.
    *    If false, this will only allow the user to change the sort order for the column if it is already sorted or change the sorted column.
    */
    onColumnSortClick(target, multisort = false) {
        const target_column = $(target);
        const all_colums = this.getElement().find('thead th');
        const sort_order = target_column.attr('data-sort-order');
        const nb_col_sorted = $('[data-sort-num]').length;

        if (!multisort && (sort_order === null || sort_order === 'nosort')) {
            // Remove all sorts and set this new column as the primary sort
            all_colums.each((i, c) => {
                const col = $(c);
                col.attr('data-sort-num', null);
                col.attr('data-sort-order', null);
            });
        }

        const new_order = sort_order === 'ASC'
            ? 'DESC'
            : (
                sort_order === 'DESC'
                    ? (
                        nb_col_sorted > 1
                            ? 'nosort'
                            : 'ASC'
                    )
                    : 'ASC'
            );
        target_column.attr('data-sort-order', new_order);

        const sort_num = target_column.attr('data-sort-num');

        const recalulate_sort_nums = () => {
            let sort_nums = [];

            // Add sort nums to an array in order
            all_colums.each((i, c) => {
                const col = $(c);
                if (col.attr('data-sort-num') !== undefined) {
                    sort_nums[col.attr('data-sort-num')] = col.attr('data-searchopt-id');
                }
            });

            // Re-index array
            sort_nums = sort_nums.filter(v => v);

            // Clear sort-nums from all columns or change value
            all_colums.each((i, c) => {
                const col = $(c);
                col.attr('data-sort-num', undefined);
                const col_sort_num = sort_nums.indexOf(col.attr('data-searchopt-id'));
                if (col_sort_num !== -1) {
                    col.attr('data-sort-num', col_sort_num);
                }
            });
        };

        recalulate_sort_nums();
        if (sort_num === undefined && new_order !== 'nosort') {
            target_column.attr('data-sort-num', all_colums.filter(function() {
                return $(this).attr('data-sort-num') !== undefined;
            }).length);
        } else if (new_order === 'nosort') {
            target_column.attr('data-sort-num', undefined);
        }

        this.setSortStateFromColumns();
        this.refreshResults();
    }

    onLimitChange(target) {
        const new_limit = target.value;

        this.getResultsView().getAJAXContainer().find('select.search-limit-dropdown').each(function() {
            $(this).val(new_limit);
        });

        this.refreshResults({start: 0});
    }

    onPageChange(target) {
        const page_link = $(target);
        page_link.closest('.pagination').find('.page-item').removeClass('selected');
        page_link.closest('.page-item').addClass('selected');

        this.refreshResults();
    }

    refreshResults(search_overrides = {}) {
        this.showLoadingSpinner();
        const el = this.getElement();
        const form_el = el.closest('form');
        const ajax_container = el.closest('.ajax-container');
        let search_data = {};

        const handle_search_failure = () => {
            // Fallback to a page reload
            window.location.reload();
        };

        try {
            const limit = $(form_el).find('select.search-limit-dropdown').first().val();
            let search_form_values = $(ajax_container).closest('.search-container').find('.search-form-container').serializeArray();
            // Drop sort and order from search form values as we rely on the data on the table headers as the source of truth
            search_form_values = search_form_values.filter((v) => {
                return v['name'] !== 'sort[]' && v['name'] !== 'order[]';
            });
            const search_criteria = {};
            search_form_values.forEach((v) => {
                search_criteria[v['name']] = v['value'];
            });
            const start = $(ajax_container).find('.pagination .page-item.selected .page-link').attr('data-start');
            search_criteria['start'] = start || 0;

            if (search_overrides['reset']) {
                search_data = {
                    action: 'display_results',
                    searchform_id: this.element_id,
                    itemtype: this.getItemtype(),
                    reset: 'reset'
                };
            } else {
                search_data = {
                    action: 'display_results',
                    searchform_id: this.element_id,
                    itemtype: this.getItemtype(),
                    glpilist_limit: limit,
                };
                if (this.sort_state !== null) {
                    search_data['sort'] = this.sort_state['sort'];
                    search_data['order'] = this.sort_state['order'];
                }

                search_data = Object.assign(search_data, search_criteria, this.forced_params, search_overrides);
            }

            if (this.push_history) {
                history.pushState('', '', `?${$.param(Object.assign(search_criteria, this.sort_state, search_overrides))}`);
            }

            $.ajax({
                url: `${CFG_GLPI.root_doc}/ajax/search.php`,
                method: 'GET',
                data: search_data,
            }).then((content) => {
                if (!(typeof content === "string" && content.includes('search-card'))) {
                    handle_search_failure();
                    return;
                }
                ajax_container.html(content);

                // Rebind the search form from the new content
                this.getResultsView().setID(ajax_container.find(".masssearchform").attr('id'));

                this.getElement().trigger('search_refresh', [this.getElement()]);
                displaySessionMessages();
                this.hideLoadingSpinner();
                this.shiftSelectAllCheckbox();
                this.toggleSavedSearch(false);
            }, () => {
                handle_search_failure();
            });
        } catch {
            handle_search_failure();
        }
    }

    // permit to [shift] select checkboxes
    shiftSelectAllCheckbox() {
        $(`#${this.element_id} tbody input[type="checkbox"]`).shiftSelectable();
    }

    registerListeners() {
        super.registerListeners();
        const ajax_container = this.getResultsView().getAJAXContainer();
        const search_container = ajax_container.closest('.search-container');

        $(ajax_container).on('click', 'table.search-results th[data-searchopt-id]', (e) => {
            e.stopPropagation();
            const target = $(e.target).closest('th').get(0);

            if ($(target).data('nosort')) {
                return;
            }
            if (e.ctrlKey || e.metaKey) {
            // Multisort mode
                this.onColumnSortClick(target, true);
            } else {
            // Single sort mode or just changing sort orders
                this.onColumnSortClick(target, false);
            }
        });

        $(ajax_container).on('change', 'select.search-limit-dropdown', (e) => {
            this.onLimitChange(e.target);
        });

        $(ajax_container).on('click', '.pagination .page-link', (e) => {
            e.preventDefault();
            this.onPageChange(e.target);
        });

        $(search_container).on('click', '.search-form-container button[name="search"]', (e) => {
            e.preventDefault();
            if (window.validateFormWithBootstrap(e)) {
                this.onSearch();
            }
        });

        $(ajax_container).on('click', '.trigger-sort', (e) => {
            e.preventDefault();
            this.setSortStateFromSelects();
            this.refreshResults();
        });
        $(ajax_container).on('click', '.sort-reset', (e) => {
            // force removal of tooltip
            const tooltip = bootstrap.Tooltip.getInstance($(e.currentTarget)[0]);
            if (tooltip !== null) {
                tooltip.dispose();
            }

            e.preventDefault();
            this.resetSortState();
            this.refreshResults();
        });

        $(ajax_container).on('click', '.sort-container .add_sort', (e) => {
            const sort_container = ajax_container.find('.sort-container');
            e.preventDefault();
            this.setSortStateFromSelects();
            const sort_count = this.sort_state['sort'].length;
            const idor_token = sort_container.find('input[name="_idor_token"]').val();
            $.post(`${CFG_GLPI.root_doc}/ajax/search.php`, {
                action: 'display_sort_criteria',
                itemtype: this.getItemtype(),
                num: sort_count + 1,
                p: this.sort_state,
                _idor_token: idor_token,
                used: this.sort_state['sort'],
            }).done((content) => {
                sort_container.find('.list-group').append(content);
                this.setSortStateFromSelects();
            });
        });

        $(ajax_container).on('change', 'select[name^="sort"], select[name^="order"]', (e) => {
            e.preventDefault();
            this.setSortStateFromSelects();
        });

        $(ajax_container).on('click', '.sort-container .remove-order-criteria', (e) => {
            e.preventDefault();

            // force removal of tooltip
            const tooltip = bootstrap.Tooltip.getInstance($(e.currentTarget)[0]);
            if (tooltip !== null) {
                tooltip.dispose();
            }

            const rowID = $(e.currentTarget).data('rowid');
            $(`#${rowID}`).remove();

            this.setSortStateFromSelects();
        });
    }

    getItemtype() {
        return this.getElement().closest('form').attr('data-search-itemtype');
    }

    resetSortState() {
        this.sort_state = {
            sort: [],
            order: []
        };
    }

    setSortStateFromColumns() {
        let columns = this.getElement()
            .find('thead th[data-searchopt-id]:not([data-searchopt-id=""])[data-sort-num]:not([data-sort-num=""])');
        if (columns.length === 0) {
            return null;
        }
        // sort solumns by sort-num
        columns = $(columns.get().sort((a, b) => {
            return $(a).attr('data-sort-num') - $(b).attr('data-sort-num');
        }));

        this.resetSortState();

        columns.each((i, c) => {
            const col = $(c);
            const order = col.attr('data-sort-order');
            if (order !== 'nosort') {
                this.sort_state['sort'].push(col.attr('data-searchopt-id'));
                this.sort_state['order'].push(order);
            }
        });
        return this.sort_state;
    }

    setSortStateFromSelects() {
        const sort = [];
        const order = [];

        $('select[name^="sort"]').each(function() {
            sort.push($(this).val());
        });
        $('select[name^="order"]').each(function() {
            order.push($(this).val());
        });

        this.resetSortState();

        // fill with new values
        $.each(sort, (i, v) => {
            this.sort_state['sort'].push(v);
            this.sort_state['order'].push(order[i]);
        });
    }
};
