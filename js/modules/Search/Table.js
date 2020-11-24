/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

import GenericView from './GenericView.js';

// Explicitly bind to window so Jest tests work properly
window.GLPI = window.GLPI || {};
window.GLPI.Search = window.GLPI.Search || {};

window.GLPI.Search.Table = class Table extends GenericView {

   constructor(result_view_element_id) {
      const element_id = $('#'+result_view_element_id).find('table.search-results').attr('id');
      super(element_id);
   }

   getElement() {
      return $('#'+this.element_id);
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

      if (!multisort && (sort_order === null || sort_order === 'nosort')) {
         // Remove all sorts and set this new column as the primary sort
         all_colums.each((i, c) => {
            const col = $(c);
            col.attr('data-sort-num', null);
            col.attr('data-sort-order', null);
         });
      }

      const new_order = sort_order === 'ASC' ? 'DESC' : (sort_order === 'DESC' ? 'nosort' : 'ASC');
      target_column.attr('data-sort-order', new_order);

      let sort_num = target_column.attr('data-sort-num');

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

      this.refreshResults();
   }

   onLimitChange(target) {
      const new_limit = target.value;
      $(target).closest('form').find('select.search-limit-dropdown').each(function() {
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
      try {
         if (search_overrides['reset']) {
            search_data = {
               action: 'display_results',
               searchform_id: this.element_id,
               itemtype: this.getItemtype(),
               reset: 'reset'
            };
         } else {
            const sort_state = this.getSortState();
            const limit = $(form_el).find('select.search-limit-dropdown').first().val();
            const search_form_values = $(ajax_container).closest('.search-container').find('.search-form-container').serializeArray();
            let search_criteria = {};
            search_form_values.forEach((v) => {
               search_criteria[v['name']] = v['value'];
            });
            const start = $(ajax_container).find('.pagination .page-item.selected .page-link').attr('data-start');
            search_criteria['start'] = start || 0;

            search_data = {
               action: 'display_results',
               searchform_id: this.element_id,
               itemtype: this.getItemtype(),
               glpilist_limit: limit,
            };
            if (sort_state !== null) {
               search_data['sort'] = sort_state['sort'];
               search_data['order'] = sort_state['order'];
            }

            search_data = Object.assign(search_data, search_criteria, search_overrides);
         }

         $(ajax_container).load(CFG_GLPI.root_doc + '/ajax/search.php', search_data, () => {
            this.hideLoadingSpinner();
         });
      } catch (error) {
         this.hideLoadingSpinner();
      }
   }

   registerListeners() {
      const ajax_container = this.getResultsView().getAJAXContainer();
      const search_container = ajax_container.closest('.search-container');

      $(ajax_container).on('click', 'table.search-results th[data-searchopt-id]', (e) => {
         e.stopPropagation();
         const target = $(e.target).closest('th').get(0);
         if (e.ctrlKey) {
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
         this.onPageChange(e.target);
      });

      $(search_container).on('click', '.search-form-container button[name="search"]', (e) => {
         e.preventDefault();
         this.onSearch();
      });
   }

   getItemtype() {
      return this.getElement().closest('form').attr('data-search-itemtype');
   }

   getSortState() {
      const columns = this.getElement().find('thead th[data-searchopt-id]:not([data-searchopt-id=""])[data-sort-order]:not([data-sort-order=""])');
      if (columns.length === 0) {
         return null;
      }
      const sort_state = {
         sort: [],
         order: []
      };
      columns.each((i, c) => {
         const col = $(c);

         const order = col.attr('data-sort-order');
         if (order !== 'nosort') {
            sort_state['sort'][col.attr('data-sort-num') || 0] = col.attr('data-searchopt-id');
            sort_state['order'][col.attr('data-sort-num') || 0] = order;
         }
      });
      return sort_state;
   }
};
