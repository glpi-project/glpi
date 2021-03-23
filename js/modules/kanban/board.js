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

// eslint-disable-next-line no-redeclare
/* global CFG_GLPI */

import KanbanRights from "./rights.js";
import KanbanColumn from "./column.js";
import KanbanCard from "./card.js";
import Teamwork from "../teamwork.js";

/**
 * Kanban Board
 */
export default class KanbanBoard {

   constructor(params = {}) {
      /**
       * Selector for the parent Kanban element. This is specified in PHP and passed in the GLPIKanban constructor.
       * @since 9.5.0
       * @type {string}
       */
      this.element = params['element'] || '';

      /**
       * @type {boolean}
       */
      this.initialized = false;

      /**
       * The AJAX directory.
       * @since 9.5.0
       * @type {string}
       */
      this.ajax_root = CFG_GLPI.root_doc + "/ajax/";

      /**
       * The parent item for this Kanban. In the future, this may be null for personal/unrelated Kanbans. For now, it is expected to be defined.
       * @since 9.5.0
       * @type {Object|{itemtype: string, items_id: number}}
       */
      this.item = params['item'] || null;

      /**
       * User rights object
       * @type {KanbanRights}
       */
      this.rights = new KanbanRights(params['rights'] || {});

      /**
       * The original column state when the Kanban was built or refreshed.
       * It should not be considered up to date beyond the initial build/refresh.
       * @since 9.5.0
       * @since x.x.x Moved to new KanbanBoard class
       * @type {{}}
       */
      this._columns = {};

      /**
       *
       * @type {Object<string, KanbanColumn>}
       */
      this.columns = {};

      /**
       * The current Kanban's configuration options
       */
      this.config = {
         /**
          * Specifies if the user's current palette is a dark theme (darker for example).
          * This will help determine the colors of the generated badges.
          * @name dark_theme
          * @default false
          * @type {boolean}
          * @since 9.5.0
          */
         dark_theme: params['dark_theme'] || false,
         /**
          * Object of itemtypes that can be used as items in the Kanban. They should be in the format:
          * itemtype => [
          *    'name' => Localized itemtype name
          *    'fields' => [
          *       field_name   => [
          *          'placeholder' => placeholder text (optional) = blank,
          *          'type' => input type (optional) default = text,
          *          'value' => value (optional) default = blank
          *       ]
          *    ]
          * ]
          * @name supported_itemtypes
          * @default {}
          * @type {Object}
          * @since 9.5.0
          */
         supported_itemtypes: params['supported_itemtypes'] || {},
         /**
          * Name of the DB field used to specify columns and any extra fields needed to create the column (Ex: color).
          * For example, Projects organize items by the state of the sub-Projects and sub-Tasks.
          * Therefore, the column_field id is 'projectstates_id' with any additional fields needed being specified in extra_fields.
          * @name column_field
          * @type {{id: string, extra_fields: Object}}
          * @since 9.5.0
          */
         column_field: params['column_field'] || {
            id: '',
            extra_fields: {}
         },
         /**
          * Specifies if the Kanban's toolbar (switcher, filters, etc) should be shown.
          * This is true by default, but may be set to false if used on a fullscreen display for example.
          * @name show_toolbar
          * @default true
          * @type {boolean}
          * @since 9.5.0
          */
         show_toolbar: params['show_toolbar'] || true,
         /**
          * If greater than zero, this specifies the amount of time in minutes between background refreshes,
          * During a background refresh, items are added/moved/removed based on the data in the DB.
          * It does not affect items in the process of being created.
          * When sorting an item or column, the background refresh is paused to avoid a disruption or incorrect data.
          * @name background_refresh_interval
          * @default 0
          * @type {number}
          * @since 9.5.0
          */
         background_refresh_interval: params['background_refresh_interval'] || 0,
         /**
          * The maximum number of badges able to be shown before an overflow badge is added.
          * @name max_team_images
          * @default 3
          * @type {number}
          * @since 9.5.0
          */
         max_team_images: params['max_team_images'] || 3,
      };

      /**
       * Filters being applied to the Kanban view.
       * For now, only a simple/regex text filter is supported.
       * This can be extended in the future to support more specific filters specified per itemtype.
       * The name of internal filters like the text filter begin with an underscore.
       * @since 9.5.0
       * @type {{_text: string}}
       */
      this.filters = params['filters'] || {
         _text: ''
      };
      if (this.filters._text === undefined) {
         this.filters._text = '';
      }

      /**
       * The ID of the add column form.
       * @since 9.5.0
       * @type {string}
       */
      this.add_column_form = '';

      /**
       * The ID of the create column form.
       * @since 9.5.0
       * @type {string}
       */
      this.create_column_form = '';

      /**
       * Reference for the background refresh timer
       * @since 9.5.0
       * @type {number|null}
       * @private
       */
      this._backgroundRefreshTimer = null;

      /**
       * The user's state object.
       * This contains an up to date list of columns that should be shown, the order they are in, and if they are folded.
       * @since 9.5.0
       * @type {{
       *    is_dirty: {boolean},
       *    state: {(order_index:{column: {number}, folded:{boolean}, cards:{array}}
       * }}
       * The is_dirty flag indicates if the state was changed and needs saved.
       */
      this.user_state = {is_dirty: false, state: {}};

      /**
       * The last time the Kanban was refreshed. This is used by the server to determine if the state needs sent to the client again.
       * The state will only be sent if there was a change since this time.
       * @type {?string}
       */
      this.last_refresh = null;

      /**
       * Global sorting active state.
       * @since 9.5.0
       * @type {boolean}
       */
      this.is_sorting_active = false;

      this.teamwork = new Teamwork({
         dark_theme: this.config.dark_theme,
         team_image_size: params['team_image_size'] || 24
      });
   }

   /**
    * Initialize the Kanban by loading the user's column state, adding the needed elements to the DOM, and starting the background save and refresh.
    * @since x.x.x
    */
   init() {
      if (this.element === '') {
         return;
      }
      this.applyFilters();
      this.loadState().then((state) => {
         $(this.element).get(0).dispatchEvent(new CustomEvent('kanban:pre_build'));
         this.build();
         $(document).ready(() => {
            $.ajax({
               type: 'GET',
               url: (this.ajax_root + 'kanban.php'),
               data: {
                  action: 'get_switcher_dropdown',
                  itemtype: this.item.itemtype,
                  items_id: this.item.items_id
               },
               contentType: 'application/json',
               success: (data) => {
                  const switcher = $(this.element + " .kanban-toolbar select[name='kanban-board-switcher']");
                  switcher.replaceWith(data);
               }
            });
            this.applyState(state);
            this.registerEventListeners();
            if (this.config.background_refresh_interval > 0) {
               // Wait a short time and then start the background refresh loop
               this.delayRefresh(this);
            }
            $(this.element).get(0).dispatchEvent(new CustomEvent('kanban:post_build'));
         });
      });
      this.initialized = true;
   }

   /**
    * Start a background refresh and then automatically schedule the next one
    * based on {@link this.config.background_refresh_interval}
    * @param {KanbanBoard} board
    * @since x.x.x
    */
   backgroundRefresh(board) {
      if (board.config.background_refresh_interval === 0) {
         return;
      }
      const sorting = $('.ui-sortable-helper');
      // Check if the user is current sorting items
      if (sorting.length > 0) {
         // Wait 10 seconds and try the background refresh again
         board.delayRefresh(board);
         return;
      }
      // Refresh and then schedule the next refresh (minutes)
      board.refresh(null, null, () => {
         board._backgroundRefreshTimer = window.setTimeout(() => {board.backgroundRefresh(board);}, board.config.background_refresh_interval * 60 * 1000);
      }, false);
   }

   /**
    * Delay the background refresh for a short amount of time.
    * This should be called any time the user is in the middle of an action so that the refresh is not disruptive.
    * @since 9.5.0
    */
   delayRefresh(board) {
      window.clearTimeout(board._backgroundRefreshTimer);
      board._backgroundRefreshTimer = window.setTimeout(() => {board.backgroundRefresh(board);}, 10000);
   }

   /**
    * Refresh the Kanban with the new set of columns.
    *    This will clear all existing columns from the Kanban, and replace them with what is provided by the server.
    * @since 9.5.0
    * @param {function} success Callback for when the Kanban is successfully refreshed.
    * @param {function} fail Callback for when the Kanban fails to be refreshed.
    * @param {function} always Callback that is called regardless of the success of the refresh.
    * @param {boolean} initial_load True if this is the first load. On the first load, the user state is not saved.
    */
   refresh(success, fail, always, initial_load) {
      const _refresh = () => {
         $.ajax({
            method: 'GET',
            //async: false,
            url: (this.ajax_root + "kanban.php"),
            data: {
               action: "refresh",
               itemtype: this.item.itemtype,
               items_id: this.item.items_id,
               column_field: this.config.column_field.id
            },
            contentType: 'application/json',
            dataType: 'json'
         }).done((columns, textStatus, jqXHR) => {
            this.preloadBadgeCache({
               trim_cache: true
            });
            this.clearColumns();
            this._columns = columns;
            // $.each(columns, (i, c) => {
            //    if (this.columns[i] === undefined) {
            //       this.appendColumn(i, c);
            //    }
            // });
            this.fillColumns();
            // Re-filter kanban
            this.applyFilters();
            if (success) {
               success(columns, textStatus, jqXHR);
            }
         }).fail((jqXHR, textStatus, errorThrown) => {
            if (fail) {
               fail(jqXHR, textStatus, errorThrown);
            }
         }).always(() => {
            $(this.element).get(0).dispatchEvent(new CustomEvent('kanban:refresh'));
            if (always) {
               always();
            }
         });
      };
      if (initial_load === undefined || initial_load === true) {
         _refresh();
      } else {
         this.loadState(_refresh);
      }
   }

   /**
    * Applies the current filters.
    * @since x.x.x
    */
   applyFilters() {
      // Unhide all items in case they are no longer filtered
      $(this.element + ' .kanban-item').each(function(i, item) {
         $(item).removeClass('filtered-out');
      });

      // Filter using built-in text filter (Check title)
      $(this.element + ' .kanban-item').each((i, item) => {
         const title = $(item).find(".kanban-item-header .kanban-item-title span.pointer").first().text();
         try {
            if (!title.match(new RegExp(this.filters._text, 'i'))) {
               $(item).addClass('filtered-out');
            }
         } catch (err) {
            // Probably not a valid regular expression. Use simple contains matching.
            if (!title.toLowerCase().includes(this.filters._text.toLowerCase())) {
               $(item).addClass('filtered-out');
            }
         }
      });
      // Check specialized filters
      $(this.element).get(0).dispatchEvent(new CustomEvent('kanban:filter'));
   }

   hideEmptyColumns() {
      const bodies = $(".kanban-body");
      bodies.each(function(index, item) {
         if (item.childElementCount === 0) {
            item.parentElement.style.display = "none";
         }
      });
   }

   showEmptyColumns() {
      const columns = $(".kanban-column");
      columns.each(function(index, item) {
         item.style.display = "block";
      });
   }

   /**
    * Restore the Kanban state for the user from the DB if it exists.
    * This restores the visible columns and their collapsed state.
    * @since 9.5.0
    */
   loadState() {
      return $.ajax({
         type: "GET",
         url: (this.ajax_root + "kanban.php"),
         data: {
            action: "load_column_state",
            itemtype: this.item.itemtype,
            items_id: this.item.items_id,
            last_load: this.last_refresh
         },
         contentType: 'application/json'
      });
   }

   applyState(state) {
      if (state['state'] === undefined || state['state'] === null || Object.keys(state['state']).length === 0) {
         return;
      }

      const indices = Object.keys(state['state']);
      for (let i = 0; i < indices.length; i++) {
         const index = indices[i];
         const entry = state['state'][index];

         if (this.columns[entry.column] === undefined) {
            this.loadColumn(entry.column, true, false);
         }
         $(this.element + ' .kanban-columns .kanban-column:nth-child(' + index + ')').after($(this.columns[entry.column].getElement()));

         if (entry.folded === true && !this.columns[entry.column].collapsed) {
            this.columns[entry.column].toggleCollapse();
         }
      }
      this.last_refresh = state['timestamp'];
   }

   /**
    * Reset/save initial Kanban state
    */
   resetState() {

   }

   /**
    * Load a column from the server and append it to the Kanban if it is visible.
    * @since 9.5.0
    * @param {number} column_id The ID of the column to load.
    * @param {boolean} nosave If true, the column state is not saved after adding the new column.
    *    This should be false when the state is being loaded, and new columns are being added as a part of that process.
    *    The default behaviour is to save the column state after adding the column (if successful).
    *    @param {boolean} revalidate If true, all other columns are checked to see if they have an item in this new column.
    *    If they do, the item is removed from that other column and the counter is updated.
    *    This is useful if an item is changed in another tab or by another user to be in the new column after the original column was added.
    * @param {function} callback Function to call after the column is loaded (or fails to load).
    */
   loadColumn(column_id, nosave, revalidate, callback = undefined) {
      nosave = nosave !== undefined ? nosave : false;

      let skip_load = false;
      $.each(this.user_state.state, function(i, c) {
         if (parseInt(c['column']) === parseInt(column_id)) {
            if (!c['visible']) {
               skip_load = true;
            }
            return false;
         }
      });
      if (skip_load) {
         if (callback) {
            callback();
         }
         return;
      }

      $.ajax({
         method: 'GET',
         url: (this.ajax_root + "kanban.php"),
         contentType: 'application/json',
         dataType: 'json',
         async: false,
         data: {
            action: "get_column",
            itemtype: this.item.itemtype,
            items_id: this.item.items_id,
            column_field: this.config.column_field.id,
            column_id: column_id
         }
      }).done((column) => {
         if (column !== undefined) {
            this.appendColumn(column_id, column[column_id], null, revalidate);
         }
      }).always(function() {
         if (callback) {
            callback();
         }
      });
      const column = KanbanColumn.getColumn(this, column_id);
      if (column !== null) {
         this.columns[column_id] = column;
         this.appendColumn(column_id, this.columns[column_id], null, revalidate);
      }
      if (callback) {
         callback();
      }
   }

   /**
    * Append a column to the Kanban
    * @param {number} column_id The ID of the column being added.
    * @param {array} column_params The column data array.
    * @param {string|Element|jQuery} columns_container The container that the columns are in.
    *    If left null, a new JQueryobject is created with the selector "this.element + ' .kanban-container .kanban-columns'".
    * @param {boolean} revalidate If true, all other columns are checked to see if they have an item in this new column.
    *    If they do, the item is removed from that other column and the counter is updated.
    *    This is useful if an item is changed in another tab or by another user to be in the new column after the original column was added.
    */
   appendColumn(column_id, column_params) {
      const column = new KanbanColumn({
         id: column_id,
         board: this,
         name: column_params['name'],
         header_color: column_params['header_color'],
         protected: column_params['protected'],
         folded: column_params['folded']
      });
      column.createElement();
      this.columns[column.getID()] = column;

      let added = [];
      const cards = column_params['items'] !== undefined ? column_params['items'] : [];
      $.each(cards, (i3, card2) => {
         column.addCard(new KanbanCard(column, card2));
         added.push(card2['id']);
         return false;
      });

      $.each(cards, function(card_id, card) {
         if (added.indexOf(card['id']) < 0) {
            column.addCard(new KanbanCard(column, card));
         }
      });

      this.refreshSortables();
   }

   /**
    * Build DOM elements and defer registering event listeners for when the document is ready.
    * @since 9.5.0
    **/
   build() {
      if (this.config.show_toolbar) {
         this.buildToolbar();
      }
      const kanban_container = $("<div class='kanban-container'><div class='kanban-columns'></div></div>").appendTo($(this.element));
      // Dropdown for single additions
      let add_itemtype_dropdown = "<ul id='kanban-add-dropdown' class='kanban-dropdown' style='display: none'>";
      Object.keys(this.config.supported_itemtypes).forEach((itemtype) => {
         add_itemtype_dropdown += "<li id='kanban-add-" + itemtype + "'><span>" + this.config.supported_itemtypes[itemtype]['name'] + '</span></li>';
      });
      add_itemtype_dropdown += '</ul>';
      kanban_container.append(add_itemtype_dropdown);

      // Dropdown for overflow (Column)
      let column_overflow_dropdown = "<ul id='kanban-overflow-dropdown' class='kanban-dropdown' style='display: none'>";
      let add_itemtype_bulk_dropdown = "<ul id='kanban-bulk-add-dropdown' class='' style='display: none'>";
      Object.keys(this.config.supported_itemtypes).forEach((itemtype) => {
         add_itemtype_bulk_dropdown += "<li id='kanban-bulk-add-" + itemtype + "'><span>" + this.config.supported_itemtypes[itemtype]['name'] + '</span></li>';
      });
      add_itemtype_bulk_dropdown += '</ul>';
      const add_itemtype_bulk_link = '<a href="#">' + '<i class="fas fa-list"></i>' + __('Bulk add') + '</a>';
      column_overflow_dropdown += '<li class="dropdown-trigger">' + add_itemtype_bulk_link + add_itemtype_bulk_dropdown + '</li>';
      if (this.rights.canModifyView()) {
         column_overflow_dropdown += "<li class='kanban-remove' data-forbid-protected='true'><span>"  + '<i class="fas fa-trash-alt"></i>' + __('Delete') + "</span></li>";
      }
      column_overflow_dropdown += '</ul>';
      kanban_container.append(column_overflow_dropdown);

      // Dropdown for overflow (Card)
      let card_overflow_dropdown = "<ul id='kanban-item-overflow-dropdown' class='kanban-dropdown' style='display: none'>";
      if (this.rights.canDeleteItem()) {
         card_overflow_dropdown += `
                <li class='kanban-item-goto'>
                   <a href="#"><i class="fas fa-share"></i>${__('Go to')}</a>
                </li>
                <li class='kanban-item-manage-team'>
                   <span>
                      <i class="fas fa-users"></i>${__('Manage team')}
                   </span>
                </li>
                <li class='kanban-item-remove'>
                   <span>
                      <i class="fas fa-trash-alt"></i>${__('Delete')}
                   </span>
                </li>`;
      }
      card_overflow_dropdown += '</ul>';
      kanban_container.append(card_overflow_dropdown);

      $('#kanban-overflow-dropdown li.dropdown-trigger > a').on("click", function(e) {
         $(this).parent().toggleClass('active');
         $(this).parent().find('ul').toggle();
         e.stopPropagation();
         e.preventDefault();
      });

      $('#kanban-item-overflow-dropdown li.dropdown-trigger > a').on("click", function(e) {
         $(this).parent().toggleClass('active');
         $(this).parent().find('ul').toggle();
         e.stopPropagation();
         e.preventDefault();
      });

      const on_refresh = () => {
         if (Object.keys(this.user_state.state).length === 0) {
            // Save new state since none was stored for the user
            this.resetState();
         }
      };
      this.refresh(on_refresh, null, null, true);

      if (this.rights.canModifyView()) {
         this.buildAddColumnForm();
         if (this.rights.canCreateColumn()) {
            this.buildCreateColumnForm();
         }
      }
   }

   buildToolbar() {
      let toolbar = $("<div class='kanban-toolbar'></div>").appendTo($(this.element));
      $("<select name='kanban-board-switcher'></select>").appendTo(toolbar);
      let filter_input = $("<input name='filter' type='text' placeholder='" + __('Search or filter results') + "'/>").appendTo(toolbar);
      if (this.rights.canModifyView()) {
         let add_column = "<input type='button' class='kanban-add-column submit' value='" + __('Add column') + "'/>";
         toolbar.append(add_column);
      }
      filter_input.on('input', (e) => {
         let text = $(e.target).val();
         if (text === null) {
            text = '';
         }
         this.filters._text = text;
         this.applyFilters();
      });
   }

   preserveColumnForms() {
      this.temp_forms = {};
      let columns = $(this.element + " .kanban-column");
      $.each(columns, (i, column) => {
         let forms = $(column).find('.kanban-form');
         if (forms.length > 0) {
            this.temp_forms[column.id] = [];
            $.each(forms, (i2, form) => {
               this.temp_forms[column.id].push($(form).clone());
            });
         }
      });
   }

   restoreColumnForms() {
      if (this.temp_forms !== undefined && Object.keys(this.temp_forms).length > 0) {
         $.each(this.temp_forms, (column_id, forms) => {
            let column = $('#' + column_id);
            if (column.length > 0) {
               let column_body = column.find('.kanban-body').first();
               $.each(forms, (i, form) => {
                  $(form).appendTo(column_body);
               });
            }
         });
         this.temp_forms = {};
      }
   }

   preserveScrolls() {
      this.temp_kanban_scroll = {
         left: $(this.element + ' .kanban-container').scrollLeft(),
         top: $(this.element + ' .kanban-container').scrollTop()
      };
      this.temp_column_scrolls = {};
      let columns = $(this.element + " .kanban-column");
      $.each(columns, (i, column) => {
         let column_body = $(column).find('.kanban-body');
         if (column_body.scrollTop() !== 0) {
            this.temp_column_scrolls[column.id] = column_body.scrollTop();
         }
      });
   }

   restoreScrolls() {
      if (this.temp_kanban_scroll !== null) {
         $(this.element + ' .kanban-container').scrollLeft(this.temp_kanban_scroll.left);
         $(this.element + ' .kanban-container').scrollTop(this.temp_kanban_scroll.top);
      }
      if (this.temp_column_scrolls !== null) {
         $.each(this.temp_column_scrolls, function(column_id, scroll) {
            $('#' + column_id + ' .kanban-body').scrollTop(scroll);
         });
      }
      this.temp_kanban_scroll = {};
      this.temp_column_scrolls = {};
   }

   /**
    * Clear all columns from the Kanban.
    * Should be used in conjunction with {@link fillColumns()} to refresh the Kanban.
    * @since 9.5.0
    */
   clearColumns() {
      this.preserveScrolls();
      this.preserveColumnForms();
      $(this.element + " .kanban-column").remove();
      this._columns = {};
      this.columns = {};
   }

   /**
    * Add all columns to the kanban. This does not clear the existing columns first.
    *    If you are refreshing the Kanban, you should call {@link clearColumns()} first.
    * @since 9.5.0
    */
   fillColumns() {
      let already_processed = [];
      $.each(this.user_state.state, (position, column) => {
         if (column['visible'] !== false && column !== 'false') {
            this.appendColumn(column['column'], this._columns[column['column']]);
         }
         already_processed.push(column['column']);
      });
      $.each(this._columns, (column_id, column) => {
         if (!already_processed.includes(column_id)) {
            if (column['id'] === undefined) {
               this.appendColumn(column_id, column);
            }
         }
      });
      this.restoreColumnForms();
      this.restoreScrolls();
   }


   /**
    * Add all event listeners. At this point, all elements should have been added to the DOM.
    * @since 9.5.0
    */
   registerEventListeners() {
      const add_dropdown = $('#kanban-add-dropdown');
      const column_overflow_dropdown = $('#kanban-overflow-dropdown');
      const card_overflow_dropdown = $('#kanban-item-overflow-dropdown');

      this.refreshSortables();

      if (Object.keys(this.config.supported_itemtypes).length > 0) {
         $(this.element + ' .kanban-container').on('click', '.kanban-add', (e) => {
            const button = $(e.target);
            //Keep menu open if clicking on another add button
            const force_stay_visible = $(add_dropdown.data('trigger-button')).prop('id') !== button.prop('id');
            add_dropdown.css({
               position: 'fixed',
               left: button.offset().left,
               top: button.offset().top + button.outerHeight(true),
               display: (add_dropdown.css('display') === 'none' || force_stay_visible) ? 'inline' : 'none'
            });
            add_dropdown.data('trigger-button', button);
         });
      }
      $(window).on('click', (e) => {
         if (!$(e.target).hasClass('kanban-add')) {
            add_dropdown.css({
               display: 'none'
            });
         }
         if (this.rights.canModifyView()) {
            if (!$.contains($(this.add_column_form)[0], e.target)) {
               $(this.add_column_form).css({
                  display: 'none'
               });
            }
            if (this.rights.canCreateColumn()) {
               if (!$.contains($(this.create_column_form)[0], e.target) && !$.contains($(this.add_column_form)[0], e.target)) {
                  $(this.create_column_form).css({
                     display: 'none'
                  });
               }
            }
         }
      });

      if (Object.keys(this.config.supported_itemtypes).length > 0) {
         $(this.element + ' .kanban-container').on('click', '.kanban-column-overflow-actions', (e) => {
            const button = $(e.target);
            //Keep menu open if clicking on another add button
            const force_stay_visible = $(column_overflow_dropdown.data('trigger-button')).prop('id') !== button.prop('id');
            column_overflow_dropdown.css({
               position: 'fixed',
               left: button.offset().left,
               top: button.offset().top + button.outerHeight(true),
               display: (column_overflow_dropdown.css('display') === 'none' || force_stay_visible) ? 'inline' : 'none'
            });
            // Hide sub-menus by default when opening the overflow menu
            column_overflow_dropdown.find('ul').css({
               display: 'none'
            });
            column_overflow_dropdown.find('li').removeClass('active');
            // If this is a protected column, hide any items with data-forbid-protected='true'. Otherwise show them.
            const column = $(e.target.closest('.kanban-column'));
            if (column.hasClass('kanban-protected')) {
               column_overflow_dropdown.find('li[data-forbid-protected="true"]').hide();
            } else {
               column_overflow_dropdown.find('li[data-forbid-protected="true"]').show();
            }
            column_overflow_dropdown.data('trigger-button', button);
         });
      }
      $(this.element + ' .kanban-container').on('click', '.kanban-item-overflow-actions', (e) => {
         const button = $(e.target);
         //Keep menu open if clicking on another add button
         const force_stay_visible = $(card_overflow_dropdown.data('trigger-button')).prop('id') !== button.prop('id');
         card_overflow_dropdown.css({
            position: 'fixed',
            left: button.offset().left,
            top: button.offset().top + button.outerHeight(true),
            display: (card_overflow_dropdown.css('display') === 'none' || force_stay_visible) ? 'inline' : 'none'
         });
         // Hide sub-menus by default when opening the overflow menu
         card_overflow_dropdown.find('ul').css({
            display: 'none'
         });
         card_overflow_dropdown.find('li').removeClass('active');
         card_overflow_dropdown.data('trigger-button', button);
         const card = $(button.closest('.kanban-item'));

         const form_link = card.data('form_link');
         $(card_overflow_dropdown.find('.kanban-item-goto a')).attr('href', form_link);

         let delete_action = $(card_overflow_dropdown.find('.kanban-item-remove'));
         if (card.hasClass('deleted')) {
            delete_action.html('<span><i class="fas fa-trash-alt"></i>'+__('Purge')+'</span>');
         } else {
            delete_action.html('<span><i class="fas fa-trash-alt"></i>'+__('Delete')+'</span>');
         }
      });

      $(window).on('click', (e) => {
         if (!$(e.target).hasClass('kanban-column-overflow-actions')) {
            column_overflow_dropdown.css({
               display: 'none'
            });
         }
         if (!$(e.target).hasClass('kanban-item-overflow-actions')) {
            card_overflow_dropdown.css({
               display: 'none'
            });
         }
         if (this.rights.canModifyView()) {
            if (!$.contains($(this.add_column_form)[0], e.target)) {
               $(this.add_column_form).css({
                  display: 'none'
               });
            }
            if (this.rights.canCreateColumn()) {
               if (!$.contains($(this.create_column_form)[0], e.target) && !$.contains($(this.add_column_form)[0], e.target)) {
                  $(this.create_column_form).css({
                     display: 'none'
                  });
               }
            }
         }
      });

      $(this.element + ' .kanban-container').on('click', '.kanban-remove', (e) => {
         // Get root dropdown, then the button that triggered it, and finally the column that the button is in
         const column = $(e.target.closest('.kanban-dropdown')).data('trigger-button').closest('.kanban-column');
         // Hide that column
         this.columns[KanbanColumn.getIDFromElement(column)].hide();
      });
      $(this.element + ' .kanban-container').on('click', '.kanban-item-remove', (e) => {
         // Get root dropdown, then the button that triggered it, and finally the card that the button is in
         const card = $(e.target.closest('.kanban-dropdown')).data('trigger-button').closest('.kanban-item').prop('id');
         // Try to delete that card item
         this.deleteCard(card, undefined, undefined);
      });
      $(this.element + ' .kanban-container').on('click', '.kanban-collapse-column', (e) => {
         const column = $(e.target.closest('.kanban-column'));
         this.columns[KanbanColumn.getIDFromElement(column)].toggleCollapse();
      });
      $(this.element).on('click', '.kanban-add-column', () => {
         this.refreshAddColumnForm();
      });
      $(this.add_column_form).on('input', "input[name='column-name-filter']", (e) => {
         const filter_input = $(e.target);
         $(this.add_column_form + ' li').hide();
         $(this.add_column_form + ' li').filter(function() {
            return $(this).text().toLowerCase().includes(filter_input.val().toLowerCase());
         }).show();
      });
      $(this.add_column_form).on('change', "input[type='checkbox']", (e) => {
         const column_id = $(e.target).parent().data('list-id');
         if (column_id !== undefined) {
            if ($(this).is(':checked')) {
               this.showColumn(column_id);
            } else {
               this.hideColumn(column_id);
            }
         }
      });
      $(this.add_column_form).on('submit', 'form', (e) => {
         e.preventDefault();
      });
      $(this.add_column_form).on('click', '.kanban-create-column', () => {
         const toolbar = $(this.element + ' .kanban-toolbar');
         $(this.add_column_form).css({
            display: 'none'
         });
         $(this.create_column_form).css({
            display: 'block',
            position: 'fixed',
            left: toolbar.offset().left + toolbar.outerWidth(true) - $(this.create_column_form).outerWidth(true),
            top: toolbar.offset().top + toolbar.outerHeight(true)
         });
      });
      $(this.create_column_form).on('submit', 'form', (e) => {
         e.preventDefault();

         const toolbar = $(this.element + ' .kanban-toolbar');

         $(this.create_column_form).css({
            display: 'none'
         });
         const name = $(this.create_column_form + " input[name='name']").val();
         $(this.create_column_form + " input[name='name']").val("");
         const color = $(this.create_column_form + " input[name='color']").val();
         KanbanColumn.createServerItem(this, name, {color: color}).then(() => {
            // Refresh add column list
            this.refreshAddColumnForm();
            $(this.add_column_form).css({
               display: 'block',
               position: 'fixed',
               left: toolbar.offset().left + toolbar.outerWidth(true) - $(this.add_column_form).outerWidth(true),
               top: toolbar.offset().top + toolbar.outerHeight(true)
            });
         });
      });
      $('#kanban-add-dropdown li').on('click', (e) => {
         e.preventDefault();
         const selection = $(e.target).closest('li');
         // The add dropdown is a single-level dropdown, so the parent is the ul element
         const dropdown = selection.parent();
         // Get the button that triggered the dropdown and then get the column that it is a part of
         // This is because the dropdown exists outside all columns and is not recreated each time it is opened
         const column_el = $($(dropdown.data('trigger-button')).closest('.kanban-column'));
         // kanban-add-ITEMTYPE (We want the ITEMTYPE token at position 2)
         const itemtype = selection.prop('id').split('-')[2];
         const column = this.columns[KanbanColumn.getIDFromElement(column_el)];
         column.clearForms();
         this.showAddItemForm(column_el, itemtype);
         this.delayRefresh(this);
      });
      $('#kanban-bulk-add-dropdown li').on('click', (e) => {
         e.preventDefault();
         const selection = $(e.target).closest('li');
         // Traverse all the way up to the top-level overflow dropdown
         const dropdown = selection.closest('.kanban-dropdown');
         // Get the button that triggered the dropdown and then get the column that it is a part of
         // This is because the dropdown exists outside all columns and is not recreated each time it is opened
         const column_el = $($(dropdown.data('trigger-button')).closest('.kanban-column'));
         // kanban-bulk-add-ITEMTYPE (We want the ITEMTYPE token at position 3)
         const itemtype = selection.prop('id').split('-')[3];

         // Force-close the full dropdown
         dropdown.css({'display': 'none'});

         const column = this.columns[KanbanColumn.getIDFromElement(column_el)];
         column.clearForms();
         this.showBulkAddItemForm(column_el, itemtype);
         this.delayRefresh(this);
      });
      const switcher = $("select[name='kanban-board-switcher']").first();
      $(this.element + ' .kanban-toolbar').on('select2:select', switcher, (e) => {
         const items_id = e.params.data.id;
         $.ajax({
            type: "GET",
            url: (this.ajax_root + "kanban.php"),
            data: {
               action: "get_url",
               itemtype: this.item.itemtype,
               items_id: items_id
            },
            contentType: 'application/json',
            success: function(url) {
               window.location = url;
            }
         });
      });

      $(this.element).on('input', '.kanban-add-form input, .kanban-add-form textarea', () => {
         this.delayRefresh(this);
      });

      if (!this.rights.canOrderCard()) {
         $(this.element).on('mouseenter', '.kanban-column', (e) => {
            if (this.is_sorting_active) {
               return; // Do not change readonly states if user is sorting elements
            }
            // If user cannot order cards, make items temporarily readonly except for current column.
            $(e.target).find('.kanban-body > li').removeClass('temporarily-readonly');
            $(e.target).siblings().find('.kanban-body > li').addClass('temporarily-readonly');
         });
         $(this.element).on('mouseleave', '.kanban-column', () => {
            if (this.is_sorting_active) {
               return; // Do not change readonly states if user is sorting elements
            }
            $(this.element).find('.kanban-body > li').removeClass('temporarily-readonly');
         });
      }

      $(this.element + ' .kanban-container').on('submit', '.kanban-add-form', (e) => {
         e.preventDefault();
         const form = $(e.target);
         const data = {
            inputs: form.serialize(),
            itemtype: form.prop('id').split('_')[2],
            action: 'add_item'
         };

         $.ajax({
            method: 'POST',
            url: (this.ajax_root + "kanban.php"),
            data: data
         }).done(() => {
            this.refresh();
         });
      });

      $(this.element + ' .kanban-container').on('click', '.kanban-item .kanban-item-title', (e) => {
         e.preventDefault();
         const card = $(e.target).closest('.kanban-item');
         const [itemtype, items_id] = card.prop('id').split('-');
         if ($('#kanban-dialog').length === 0) {
            $(this.element).append('<div id="kanban-dialog"></div>');
            // After initializing the dialog, it gets moved automatically outside the Kanban container. That's why it has an ID instead of a class.
            $(this.element + ' #kanban-dialog').dialog({
               autoOpen: false,
               modal: true,
               resizable: true,
               draggable: true,
               height: 700,
               width: 800
            });
         }
         $('#kanban-dialog').load((this.ajax_root + "kanban.php?action=show_card_edit_form&itemtype="+itemtype+"&card=" + items_id)).dialog("open");
      });

      //$(this.element).on('kanban:post_build', () => {this.loadState();});
   }

   /**
    * (Re-)Create the list of columns that can be shown/hidden.
    * This involves fetching the list of valid columns from the server.
    * @since 9.5.0
    */
   refreshAddColumnForm() {
      let columns_used = [];
      $(this.element + ' .kanban-columns .kanban-column').each(function() {
         const column_id = this.id.split('-');
         columns_used.push(column_id[column_id.length - 1]);
      });
      const column_dialog = $(this.add_column_form);
      const toolbar = $(this.element + ' .kanban-toolbar');
      KanbanColumn.getAvailableColumns(this).then((data) => {
         const form_content = $(this.add_column_form + " .kanban-item-content");
         form_content.empty();
         form_content.append("<input type='text' name='column-name-filter' placeholder='" + __('Search') + "'/>");
         let list = "<ul class='kanban-columns-list'>";
         $.each(data, function(column_id, column) {
            let list_item = "<li data-list-id='"+column_id+"'>";
            if (columns_used.includes(column_id)) {
               list_item += "<input type='checkbox' checked='true'/>";
            } else {
               list_item += "<input type='checkbox'/>";
            }
            list_item += "<span class='kanban-color-preview' style='background-color: "+column['header_color']+"'></span>";
            list_item += column['name'] + "</li>";
            list += list_item;
         });
         list += "</ul>";
         form_content.append(list);
         form_content.append();

         column_dialog.css({
            display: 'block',
            position: 'fixed',
            left: toolbar.offset().left + toolbar.outerWidth(true) - column_dialog.outerWidth(true),
            top: toolbar.offset().top + toolbar.outerHeight(true)
         });
      });
   }

   /**
    * (Re-)Initialize JQuery sortable for all items and columns.
    * This should be called every time a new column or item is added to the board.
    * @since 9.5.0
    */
   refreshSortables() {
      // Make sure all items in the columns can be sorted
      const bodies = $(this.element + ' .kanban-body');
      $.each(bodies, function(b) {
         const body = $(b);
         if (body.data('sortable')) {
            body.sortable('destroy');
         }
      });

      bodies.sortable({
         connectWith: '.kanban-body',
         containment: '.kanban-container',
         appendTo: '.kanban-container',
         items: '.kanban-item:not(.readonly):not(.temporarily-readonly)',
         placeholder: "sortable-placeholder",
         start: (event, ui) => {
            this.is_sorting_active = true;

            const card = ui.item;
            // Track the column and position the card was picked up from
            const current_column = card.closest('.kanban-column').attr('id');
            card.data('source-col', current_column);
            card.data('source-pos', card.index());
         },
         update: (event, ui) => {
            if (event.target === ui.item.parent()[0]) {
               return this.onKanbanCardSort(ui, event.target);
            }
         },
         change: (event, ui) => {
            const card = ui.item;
            const source_column = card.data('source-col');
            const source_position = card.data('source-pos');
            const current_column = ui.placeholder.closest('.kanban-column').attr('id');

            // Compute current position based on list of sortable elements without current card.
            // Indeed, current card is still in DOM (but invisible), making placeholder index in DOM
            // not always corresponding to its position inside list of visible ements.
            const sortable_elements = $('#' + current_column + ' ul.ui-sortable > li:not([id="' + card.attr('id') + '"])');
            const current_position = sortable_elements.index(ui.placeholder);
            card.data('current-pos', current_position);

            if (!this.rights.canOrderCard()) {
               if (current_column === source_column) {
                  if (current_position !== source_position) {
                     ui.placeholder.addClass('invalid-position');
                  } else {
                     ui.placeholder.removeClass('invalid-position');
                  }
               } else {
                  if (!$(ui.placeholder).is(':last-child')) {
                     ui.placeholder.addClass('invalid-position');
                  } else {
                     ui.placeholder.removeClass('invalid-position');
                  }
               }
            }
         },
         stop: (event, ui) => {
            this.is_sorting_active = false;
            ui.item.closest('.kanban-column').trigger('mouseenter'); // force readonly states refresh
         }
      });

      if (this.rights.canModifyView()) {
         // Enable column sorting
         $(this.element + ' .kanban-columns').sortable({
            connectWith: this.element + ' .kanban-columns',
            appendTo: '.kanban-container',
            items: '.kanban-column:not(.kanban-protected)',
            placeholder: "sortable-placeholder",
            handle: '.kanban-column-header',
            tolerance: 'pointer',
            stop: (event, ui) => {
               const column = $(ui.item[0]);
               this.updateColumnPosition(KanbanColumn.getIDFromElement(ui.item[0]), column.index());
            }
         });
         $(this.element + ' .kanban-columns .kanban-column:not(.kanban-protected) .kanban-column-header').addClass('grab');
      }
   }

   /**
    * Callback function for when a kanban item is moved.
    * @since 9.5.0
    * @param {Object}  ui       ui value directly from JQuery sortable function.
    * @param {Element} sortable Sortable object
    * @returns {Boolean}       Returns false if the sort was cancelled.
    **/
   onKanbanCardSort(ui, sortable) {
      const target = sortable.parentElement;
      const source = $(ui.sender);
      const card = $(ui.item[0]);
      const el_params = card.attr('id').split('-');
      const target_params = $(target).attr('id').split('-');
      const column_id = target_params[target_params.length - 1];

      if (el_params.length === 2 && source !== null && !(!this.rights.canOrderCard() && source.length === 0)) {
         $.ajax({
            type: "POST",
            url: (this.ajax_root + "kanban.php"),
            data: {
               action: "update",
               itemtype: el_params[0],
               items_id: el_params[1],
               column_field: this.config.column_field.id,
               column_value: column_id
            },
            contentType: 'application/json',
            error: function() {
               $(sortable).sortable('cancel');
               return false;
            },
            success: () => {
               let pos = card.data('current-pos');
               if (!this.rights.canOrderCard()) {
                  card.appendTo($(target).find('.kanban-body').first());
                  pos = card.index();
               }
               // Update counters. Always pass the column element instead of the kanban body (card container)
               const source_col = $(source).closest('.kanban-column');
               const target_col = $(target).closest('.kanban-column');
               this.columns[KanbanColumn.getIDFromElement(source_col)].updateCounter();
               this.columns[KanbanColumn.getIDFromElement(target_col)].updateCounter();
               card.removeData('source-col');
               this.updateCardPosition(card.attr('id'), target.id, pos);
               return true;
            }
         });
      } else {
         $(sortable).sortable('cancel');
         return false;
      }
   }

   /**
    * Send the new card position to the server.
    * @since 9.5.0
    * @param {string} card The ID of the card being moved.
    * @param {string|number} column The ID or element of the column the card resides in.
    * @param {number} position The position in the column that the card is at.
    * @param {function} error Callback function called when the server reports an error.
    * @param {function} success Callback function called when the server processes the request successfully.
    */
   updateCardPosition(card, column, position) {
      if (typeof column === 'string' && column.lastIndexOf('column', 0) === 0) {
         column = KanbanColumn.getIDFromElement(column);
      }
      return $.ajax({
         type: "POST",
         url: (this.ajax_root + "kanban.php"),
         data: {
            action: "move_item",
            card: card,
            column: column,
            position: position,
            kanban: this.item
         },
         contentType: 'application/json'
      });
   }

   /**
    * Notify the server that the column's position has changed.
    * @since 9.5.0
    * @param {number} column The ID of the column.
    * @param {number} position The position of the column.
    */
   updateColumnPosition(column, position) {
      $.ajax({
         type: "POST",
         url: (this.ajax_root + "kanban.php"),
         data: {
            action: "move_column",
            column: column,
            position: position,
            kanban: this.item
         },
         contentType: 'application/json'
      });
   }

   /**
    * Attempt to get and cache user badges in a single AJAX request to reduce time wasted when using multiple requests.
    * Most time spent on the request is latency so it takes about the same amount of time for 1 or 50 users.
    * If no image is returned from the server, a badge is generated based on the user's initials.
    * @since 9.5.0
    * @param {Object} options Object of options for this function. Supports:
    *    trim_cache - boolean indicating if unused user images should be removed from the cache.
    *       This is useful for refresh scenarios.
    * @see generateUserBadge()
    **/
   preloadBadgeCache(options) {
      // let users = [];
      // $.each(this.columns, (column_id, column) => {
      //    if (column.cards !== undefined) {
      //       $.each(column.cards, (card_id, card) => {
      //          if (card.team !== undefined) {
      //             Object.values(card.team).slice(0, this.config.max_team_images).forEach((teammember) => {
      //                if (teammember['itemtype'] === 'User') {
      //                   if (this.teamwork.team_badge_cache['User'][teammember['items_id']] === undefined) {
      //                      users[teammember['items_id']] = teammember;
      //                   }
      //                }
      //             });
      //          }
      //       });
      //    }
      // });
      // if (users.length === 0) {
      //    return;
      // }
      // $.ajax({
      //    url: (this.ajax_root + "getUserPicture.php"),
      //    async: false,
      //    data: {
      //       users_id: Object.keys(users),
      //       size: this.config.team_image_size
      //    },
      //    contentType: 'application/json',
      //    dataType: 'json'
      // }).done((data) => {
      //    Object.keys(users).forEach((user_id) => {
      //       const teammember = users[user_id];
      //       if (data[user_id] !== undefined) {
      //          this.teamwork.team_badge_cache['User'][user_id] = "<span>" + data[user_id] + "</span>";
      //       } else {
      //          this.teamwork.team_badge_cache['User'][user_id] = this.teamwork.generateUserBadge(teammember);
      //       }
      //    });
      //    if (options !== undefined && options['trim_cache'] !== undefined) {
      //       let cached_colors = JSON.parse(window.sessionStorage.getItem('badge_colors'));
      //       Object.keys(this.teamwork.team_badge_cache['User']).forEach((user_id) => {
      //          if (users[user_id] === undefined) {
      //             delete this.teamwork.team_badge_cache['User'][user_id];
      //             delete cached_colors['User'][user_id];
      //          }
      //       });
      //       window.sessionStorage.setItem('badge_colors', JSON.stringify(cached_colors));
      //    }
      // });
   }

   /**
    * Add a new form to the Kanban column to add a new item of the specified itemtype.
    * @since 9.5.0
    * @param {string|Element|jQuery} column_el The column
    * @param {string} itemtype The itemtype that is being added
    */
   showAddItemForm(column_el, itemtype) {
      if (!(column_el instanceof jQuery)) {
         column_el = $(column_el);
      }

      const uniqueID = Math.floor(Math.random() * 999999);
      const formID = "form_add_" + itemtype + "_" + uniqueID;
      let add_form = "<form id='" + formID + "' class='kanban-add-form kanban-form no-track'>";
      let form_header = "<div class='kanban-item-header'>";
      form_header += `
            <span class='kanban-item-title'>
               <i class="${this.config.supported_itemtypes[itemtype]['icon']}"></i>
               ${this.config.supported_itemtypes[itemtype]['name']}
            </span>`;
      form_header += "<i class='fas fa-times' title='Close' onclick='$(this).parent().parent().remove()'></i></div>";
      add_form += form_header;

      add_form += "<div class='kanban-item-content'>";
      $.each(this.config.supported_itemtypes[itemtype]['fields'], function(name, options) {
         const input_type = options['type'] !== undefined ? options['type'] : 'text';
         const value = options['value'] !== undefined ? options['value'] : '';

         if (input_type.toLowerCase() === 'textarea') {
            add_form += "<textarea name='" + name + "'";
            if (options['placeholder'] !== undefined) {
               add_form += " placeholder='" + options['placeholder'] + "'";
            }
            if (value !== undefined) {
               add_form += " value='" + value + "'";
            }
            add_form += "></textarea>";
         } else if (input_type.toLowerCase() === 'raw') {
            add_form += value;
         } else {
            add_form += "<input type='" + input_type + "' name='" + name + "'";
            if (options['placeholder'] !== undefined) {
               add_form += " placeholder='" + options['placeholder'] + "'";
            }
            if (value !== undefined) {
               add_form += " value='" + value + "'";
            }
            add_form += "/>";
         }
      });
      add_form += "</div>";

      const column_id_elements = column_el.prop('id').split('-');
      const column_value = column_id_elements[column_id_elements.length - 1];
      add_form += "<input type='hidden' name='" + this.config.column_field.id + "' value='" + column_value + "'/>";
      add_form += "<input type='submit' value='" + __('Add') + "' name='add' class='submit'/>";
      add_form += "</form>";
      $(column_el.find('.kanban-body')[0]).append(add_form);
      $('#' + formID).get(0).scrollIntoView(false);
   }

   /**
    * Add a new form to the Kanban column to add multiple new items of the specified itemtype.
    * @since 9.5.0
    * @param {string|Element|jQuery} column_el The column
    * @param {string} itemtype The itemtype that is being added
    */
   showBulkAddItemForm(column_el, itemtype) {
      if (!(column_el instanceof jQuery)) {
         column_el = $(column_el);
      }

      const uniqueID = Math.floor(Math.random() * 999999);
      const formID = "form_add_" + itemtype + "_" + uniqueID;
      let add_form = "<form id='" + formID + "' class='kanban-add-form kanban-form no-track'>";

      add_form += `
            <div class='kanban-item-header'>
                <span class='kanban-item-title'>
                   <i class="${this.config.supported_itemtypes[itemtype]['icon']}"></i>
                   ${this.config.supported_itemtypes[itemtype]['name']}
                </span>
                <i class='fas fa-times' title='Close' onclick='$(this).parent().parent().remove()'></i>
                <div>
                    <span class="kanban-item-subtitle">${__("One item per line")}</span>
                 </div>
           </div>
         `;

      add_form += "<div class='kanban-item-content'>";
      add_form += "<textarea name='bulk_item_list'></textarea>";
      $.each(this.config.supported_itemtypes[itemtype]['fields'], function(name, options) {
         const input_type = options['type'] !== undefined ? options['type'] : 'text';
         const value = options['value'] !== undefined ? options['value'] : '';

         // We want to include all hidden fields as they are usually mandatory (project ID)
         if (input_type === 'hidden') {
            add_form += "<input type='hidden' name='" + name + "'";
            if (value !== undefined) {
               add_form += " value='" + value + "'";
            }
            add_form += "/>";
         }
      });
      add_form += "</div>";

      const column_id_elements = column_el.prop('id').split('-');
      const column_value = column_id_elements[column_id_elements.length - 1];
      add_form += "<input type='hidden' name='" + this.config.column_field.id + "' value='" + column_value + "'/>";
      add_form += "<input type='submit' value='" + __('Add') + "' name='add' class='submit'/>";
      add_form += "</form>";
      $(column_el.find('.kanban-body')[0]).append(add_form);
      $('#' + formID).get(0).scrollIntoView(false);
      $("#" + formID).on('submit', (e) => {
         e.preventDefault();
         const form = $(e.target);
         const data = {
            inputs: form.serialize(),
            itemtype: form.prop('id').split('_')[2],
            action: 'bulk_add_item'
         };

         $.ajax({
            method: 'POST',
            //async: false,
            url: (this.ajax_root + "kanban.php"),
            data: data
         }).done(() => {
            $('#'+formID).remove();
            this.refresh();
         });
      });
   }

   /**
    * Create the add column form and add it to the DOM.
    * @since 9.5.0
    */
   buildAddColumnForm() {
      const uniqueID = Math.floor(Math.random() * 999999);
      const formID = "form_add_column_" + uniqueID;
      this.add_column_form = '#' + formID;
      let add_form = `
            <div id="${formID}" class="kanban-form kanban-add-column-form" style="display: none">
                <form class='no-track'>
                    <div class='kanban-item-header'>
                        <span class='kanban-item-title'>${__('Add a column from existing status')}</span>
                    </div>
                    <div class='kanban-item-content'></div>
         `;
      if (this.rights.canCreateColumn()) {
         add_form += `
               <hr>${__('Or add a new status')}
               <input type='button' class='submit kanban-create-column' value="${__('Create status')}"/>
            `;
      }
      add_form += "</form></div>";
      $(this.element).prepend(add_form);
   }

   /**
    * Create the create column form and add it to the DOM.
    * @since 9.5.0
    */
   buildCreateColumnForm() {
      const uniqueID = Math.floor(Math.random() * 999999);
      const formID = "form_create_column_" + uniqueID;
      this.create_column_form = '#' + formID;
      let create_form = `
            <div id='${formID}' class='kanban-form kanban-create-column-form' style='display: none'>
                <form class='no-track'>
                    <div class='kanban-item-header'>
                        <span class='kanban-item-title'>${__('Create status')}</span>
                    </div>
                    <div class='kanban-item-content'>
                    <input name='name'/>
         `;
      $.each(this.config.column_field.extra_fields, function(name, field) {
         if (name === undefined) {
            return true;
         }
         let value = (field.value !== undefined) ? field.value : '';
         if (field.type === undefined || field.type === 'text') {
            create_form += "<input name='" + name + "' value='" + value + "'/>";
         } else if (field.type === 'color') {
            if (value.length === 0) {
               value = '#000000';
            }
            create_form += "<input type='color' name='" + name + "' value='" + value + "'/>";
         }
      });
      create_form += "</div>";
      create_form += "<input type='button' class='submit kanban-create-column' value='" + __('Create status') + "'/>";
      create_form += "</form></div>";
      $(this.element).prepend(create_form);
   }
}
