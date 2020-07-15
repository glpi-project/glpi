/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

(function(){
   window.GLPIKanban = function() {
      /**
       * Self-reference for property access in functions.
       */
      var self = this;

      /**
       * Selector for the parent Kanban element. This is specified in PHP and passed in the GLPIKanban constructor.
       * @since 9.5.0
       * @type {string}
       */
      this.element = "";

      /**
       * The original column state when the Kanban was built or refreshed.
       * It should not be considered up to date beyond the initial build/refresh.
       * @since 9.5.0
       * @type {Array}
       */
      this.columns = {};

      /**
       * The AJAX directory.
       * @since 9.5.0
       * @type {string}
       */
      this.ajax_root = CFG_GLPI.root_doc + "/ajax/";

      /**
       * The maximum number of badges able to be shown before an overflow badge is added.
       * @since 9.5.0
       * @type {number}
       */
      this.max_team_images = 3;

      /**
       * The size in pixels for the team badges.
       * @since 9.5.0
       * @type {number}
       */
      this.team_image_size = 24;

      /**
       * The parent item for this Kanban. In the future, this may be null for personal/unrelated Kanbans. For now, it is expected to be defined.
       * @since 9.5.0
       * @type {Object|{itemtype: string, items_id: number}}
       */
      this.item = null;

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
       * @since 9.5.0
       * @type {Object}
       */
      this.supported_itemtypes = {};

      /**
       * If true, then a button will be added to each column to allow new items to be added.
       * When an item is added, a request is made via AJAX to create the item in the DB.
       * Permissions are re-checked server-side during this request.
       * Users will still be limited by {@link limit_addcard_columns} both client-side and server-side.
       * @since 9.5.0
       * @type {boolean}
       */
      this.allow_add_item = false;

      /**
       * If true, then a button will be added to the add column form that lets the user create a new column.
       * For Projects as an example, it would create a new project state.
       * Permissions are re-checked server-side during this request.
       * @since 9.5.0
       * @type {boolean}
       */
      this.allow_create_column = false;

      /**
       * Global permission for being able to modify the Kanban state/view.
       * This includes the order of cards in the columns.
       * @since 9.5.0
       * @type {boolean}
       */
      this.allow_modify_view = false;

      /**
       * Limits the columns that the user can add cards to.
       * By default, it is empty which allows cards to be added to all columns.
       * If you don't want the user to add cards to any column, {@link allow_add_item} should be false.
       * @since 9.5.0
       * @type {Array}
       */
      this.limit_addcard_columns = [];

      /**
       * Global right for ordering cards.
       * @since 9.5.0
       * @type {boolean}
       */
      this.allow_order_card = false;

      /**
       * Specifies if the user's current palette is a dark theme (darker for example).
       * This will help determine the colors of the generated badges.
       * @since 9.5.0
       * @type {boolean}
       */
      this.dark_theme = false;

      /**
       * Name of the DB field used to specify columns and any extra fields needed to create the column (Ex: color).
       * For example, Projects organize items by the state of the sub-Projects and sub-Tasks.
       * Therefore, the column_field id is 'projectstates_id' with any additional fields needed being specified in extra_fields..
       * @since 9.5.0
       * @type {{id: string, extra_fields: Object}}
       */
      this.column_field = {id: '', extra_fields: {}};

      /**
       * Specifies if the Kanban's toolbar (switcher, filters, etc) should be shown.
       * This is true by default, but may be set to false if used on a fullscreen display for example.
       * @since 9.5.0
       * @type {boolean}
       */
      this.show_toolbar = true;

      /**
       * Filters being applied to the Kanban view.
       * For now, only a simple/regex text filter is supported.
       * This can be extended in the future to support more specific filters specified per itemtype.
       * The name of internal filters like the text filter begin with an underscore.
       * @since 9.5.0
       * @type {{_text: string}}
       */
      this.filters = {
         _text: ''
      };

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
       * Cache for images to reduce network requests and keep the same generated image between cards.
       * @since 9.5.0
       * @type {{Group: {}, User: {}, Supplier: {}, Contact: {}}}
       */
      this.team_badge_cache = {
         User: {},
         Group: {},
         Supplier: {},
         Contact: {}
      };

      /**
       * If greater than zero, this specifies the amount of time in minutes between background refreshes,
       * During a background refresh, items are added/moved/removed based on the data in the DB.
       * It does not affect items in the process of being created.
       * When sorting an item or column, the background refresh is paused to avoid a disruption or incorrect data.
       * @since 9.5.0
       * @type {number} Time in minutes between background refreshes.
       */
      this.background_refresh_interval = 0;

      /**
       * Internal refresh function
       * @since 9.5.0
       * @type {function}
       * @private
       */
      var _backgroundRefresh = null;

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

      /**
       * Parse arguments and assign them to the object's properties
       * @since 9.5.0
       * @param {Object} args Object arguments
       */
      var initParams = function(args) {
         var overridableParams = [
            'element', 'max_team_images', 'team_image_size', 'item',
            'supported_itemtypes', 'allow_add_item', 'allow_add_column', 'dark_theme', 'background_refresh_interval',
            'column_field', 'allow_modify_view', 'limit_addcard_columns', 'allow_order_card', 'allow_create_column'
         ];
         if (args.length === 1) {
            for (var i = 0; i < overridableParams.length; i++) {
               var param = overridableParams[i];
               if (args[0][param] !== undefined) {
                  self[param] = args[0][param];
               }
            }
         }
         if (self.filters._text === undefined) {
            self.filters._text = '';
         }
         self.filter();
      };

      /**
       * Build DOM elements and defer registering event listeners for when the document is ready.
       * @since 9.5.0
      **/
      var build = function() {
         if (self.show_toolbar) {
            buildToolbar();
         }
         var kanban_container = $("<div class='kanban-container'><div class='kanban-columns'></div></div>").appendTo($(self.element));

         // Dropdown for single additions
         var add_itemtype_dropdown = "<ul id='kanban-add-dropdown' class='kanban-dropdown' style='display: none'>";
         Object.keys(self.supported_itemtypes).forEach(function(itemtype) {
            add_itemtype_dropdown += "<li id='kanban-add-" + itemtype + "'>" + self.supported_itemtypes[itemtype]['name'] + '</li>';
         });
         add_itemtype_dropdown += '</ul>';
         kanban_container.append(add_itemtype_dropdown);

         // Dropdown for overflow
         var overflow_dropdown = "<ul id='kanban-overflow-dropdown' class='kanban-dropdown' style='display: none'>";
         var add_itemtype_bulk_dropdown = "<ul id='kanban-bulk-add-dropdown' class='' style='display: none'>";
         Object.keys(self.supported_itemtypes).forEach(function(itemtype) {
            add_itemtype_bulk_dropdown += "<li id='kanban-bulk-add-" + itemtype + "'>" + self.supported_itemtypes[itemtype]['name'] + '</li>';
         });
         add_itemtype_bulk_dropdown += '</ul>';
         var add_itemtype_bulk_link = '<a href="#">' + '<i class="fas fa-list"></i>' + __('Bulk add') + '</a>';
         overflow_dropdown += '<li class="dropdown-trigger">' + add_itemtype_bulk_link + add_itemtype_bulk_dropdown + '</li>';
         if (self.allow_modify_view) {
            overflow_dropdown += "<li class='kanban-remove' data-forbid-protected='true'>"  + '<i class="fas fa-trash-alt"></i>' + __('Delete') + "</li>";
            //}
         }
         overflow_dropdown += '</ul>';
         kanban_container.append(overflow_dropdown);

         $('#kanban-overflow-dropdown li.dropdown-trigger').on("click", function(e) {
            $(this).toggleClass('active');
            $(this).find('ul').toggle();
            e.stopPropagation();
            e.preventDefault();
         });

         var on_refresh = function() {
            if (Object.keys(self.user_state.state).length === 0) {
               // Save new state since none was stored for the user
               saveState(true, true);
            }
         };
         self.refresh(on_refresh, null, null, true);

         if (self.allow_modify_view) {
            buildAddColumnForm();
            if (self.allow_create_column) {
               buildCreateColumnForm();
            }
         }
      };

      var buildToolbar = function() {
         var toolbar = $("<div class='kanban-toolbar'></div>").appendTo(self.element);
         $("<select name='kanban-board-switcher'></select>").appendTo(toolbar);
         var filter_input = $("<input name='filter' type='text' placeholder='" + __('Search or filter results') + "'/>").appendTo(toolbar);
         if (self.allow_modify_view) {
            var add_column = "<input type='button' class='kanban-add-column submit' value='" + __('Add column') + "'/>";
            toolbar.append(add_column);
         }
         filter_input.on('input', function() {
            var text = $(this).val();
            if (text === null) {
               text = '';
            }
            self.filters._text = text;
            self.filter();
         });
      };

      var getColumnElementFromID = function(column_id) {
         return '#column-' + self.column_field.id + '-' + column_id;
      };

      var getColumnIDFromElement = function(column_el) {
         var element_id = [column_el];
         if (typeof column_el !== 'string') {
            element_id = $(column_el).prop('id').split('-');
         } else {
            element_id = column_el.split('-');
         }
         return element_id[element_id.length - 1];
      };

      var preserveNewItemForms = function() {
         self.temp_forms = {};
         var columns = $(self.element + " .kanban-column");
         $.each(columns, function(i, column) {
            var forms = $(column).find('.kanban-add-form');
            if (forms.length > 0) {
               self.temp_forms[column.id] = [];
               $.each(forms, function(i2, form) {
                  self.temp_forms[column.id].push($(form).clone());
               });
            }
         });
      };

      var restoreNewItemForms = function() {
         if (self.temp_forms !== undefined && Object.keys(self.temp_forms).length > 0) {
            $.each(self.temp_forms, function(column_id, forms) {
               var column = $('#' + column_id);
               if (column.length > 0) {
                  var column_body = column.find('.kanban-body').first();
                  $.each(forms, function(i, form) {
                     $(form).appendTo(column_body);
                  });
               }
            });
            self.temp_forms = {};
         }
      };

      var preserveScrolls = function() {
         self.temp_kanban_scroll = {
            left: $(self.element + ' .kanban-container').scrollLeft(),
            top: $(self.element + ' .kanban-container').scrollTop()
         };
         self.temp_column_scrolls = {};
         var columns = $(self.element + " .kanban-column");
         $.each(columns, function(i, column) {
            var column_body = $(column).find('.kanban-body');
            if (column_body.scrollTop() !== 0) {
               self.temp_column_scrolls[column.id] = column_body.scrollTop();
            }
         });
      };

      var restoreScrolls = function() {
         if (self.temp_kanban_scroll !== null) {
            $(self.element + ' .kanban-container').scrollLeft(self.temp_kanban_scroll.left);
            $(self.element + ' .kanban-container').scrollTop(self.temp_kanban_scroll.top);
         }
         if (self.temp_column_scrolls !== null) {
            $.each(self.temp_column_scrolls, function(column_id, scroll) {
               $('#' + column_id + ' .kanban-body').scrollTop(scroll);
            });
         }
         self.temp_kanban_scroll = {};
         self.temp_column_scrolls = {};
      };

      /**
       * Clear all columns from the Kanban.
       * Should be used in conjunction with {@link fillColumns()} to refresh the Kanban.
       * @since 9.5.0
       */
      var clearColumns = function() {
         preserveScrolls();
         preserveNewItemForms();
         $(self.element + " .kanban-column").remove();
      };

      /**
       * Add all columns to the kanban. This does not clear the existing columns first.
       *    If you are refreshing the Kanban, you should call {@link clearColumns()} first.
       * @since 9.5.0
       * @param {Object} columns_container JQuery Object of columns container. Not required.
       *    If not specfied, a new object will be created to reference this Kanban's columns container.
       */
      var fillColumns = function(columns_container) {
         if (columns_container === undefined) {
            columns_container = $(self.element + " .kanban-container .kanban-columns").first();
         }

         var already_processed = [];
         $.each(self.user_state.state, function(position, column) {
            if (column['visible'] !== false && column !== 'false') {
               appendColumn(column['column'], self.columns[column['column']], columns_container);
            }
            already_processed.push(column['column']);
         });
         $.each(self.columns, function(column_id, column) {
            if (!already_processed.includes(column_id)) {
               if (column['id'] === undefined) {
                  appendColumn(column_id, column, columns_container);
               }
            }
         });
         restoreNewItemForms();
         restoreScrolls();
      };

      /**
       * Add all event listeners. At this point, all elements should have been added to the DOM.
       * @since 9.5.0
       */
      var registerEventListeners = function() {
         var add_dropdown = $('#kanban-add-dropdown');
         var overflow_dropdown = $('#kanban-overflow-dropdown');

         refreshSortables();

         if (Object.keys(self.supported_itemtypes).length > 0) {
            $(self.element + ' .kanban-container').on('click', '.kanban-add', function(e) {
               var button = $(e.target);
               //Keep menu open if clicking on another add button
               var force_stay_visible = $(add_dropdown.data('trigger-button')).prop('id') !== button.prop('id');
               add_dropdown.css({
                  position: 'fixed',
                  left: button.offset().left,
                  top: button.offset().top + button.outerHeight(true),
                  display: (add_dropdown.css('display') === 'none' || force_stay_visible) ? 'inline' : 'none'
               });
               add_dropdown.data('trigger-button', button);
            });
         }
         $(window).on('click', function(e) {
            if (!$(e.target).hasClass('kanban-add')) {
               add_dropdown.css({
                  display: 'none'
               });
            }
            if (self.allow_modify_view) {
               if (!$.contains($(self.add_column_form)[0], e.target)) {
                  $(self.add_column_form).css({
                     display: 'none'
                  });
               }
               if (self.allow_create_column) {
                  if (!$.contains($(self.create_column_form)[0], e.target) && !$.contains($(self.add_column_form)[0], e.target)) {
                     $(self.create_column_form).css({
                        display: 'none'
                     });
                  }
               }
            }
         });

         if (Object.keys(self.supported_itemtypes).length > 0) {
            $(self.element + ' .kanban-container').on('click', '.kanban-overflow-actions', function(e) {
               var button = $(e.target);
               //Keep menu open if clicking on another add button
               var force_stay_visible = $(overflow_dropdown.data('trigger-button')).prop('id') !== button.prop('id');
               overflow_dropdown.css({
                  position: 'fixed',
                  left: button.offset().left,
                  top: button.offset().top + button.outerHeight(true),
                  display: (overflow_dropdown.css('display') === 'none' || force_stay_visible) ? 'inline' : 'none'
               });
               // Hide sub-menus by default when opening the overflow menu
               overflow_dropdown.find('ul').css({
                  display: 'none'
               });
               overflow_dropdown.find('li').removeClass('active');
               // If this is a protected column, hide any items with data-forbid-protected='true'. Otherwise show them.
               var column = $(e.target.closest('.kanban-column'));
               if (column.hasClass('kanban-protected')) {
                  overflow_dropdown.find('li[data-forbid-protected="true"]').hide();
               } else {
                  overflow_dropdown.find('li[data-forbid-protected="true"]').show();
               }
               overflow_dropdown.data('trigger-button', button);
            });
         }
         $(window).on('click', function(e) {
            if (!$(e.target).hasClass('kanban-overflow-actions')) {
               overflow_dropdown.css({
                  display: 'none'
               });
            }
            if (self.allow_modify_view) {
               if (!$.contains($(self.add_column_form)[0], e.target)) {
                  $(self.add_column_form).css({
                     display: 'none'
                  });
               }
               if (self.allow_create_column) {
                  if (!$.contains($(self.create_column_form)[0], e.target) && !$.contains($(self.add_column_form)[0], e.target)) {
                     $(self.create_column_form).css({
                        display: 'none'
                     });
                  }
               }
            }
         });

         $(self.element + ' .kanban-container').on('click', '.kanban-remove', function(e) {
            // Get root dropdown, then the button that triggered it, and finally the column that the button is in
            var column = $(e.target.closest('.kanban-dropdown')).data('trigger-button').closest('.kanban-column');
            // Hide that column
            hideColumn(getColumnIDFromElement(column));
         });
         $(self.element + ' .kanban-container').on('click', '.kanban-collapse-column', function(e) {
            self.toggleCollapseColumn(e.target.closest('.kanban-column'));
         });
         $(self.element).on('click', '.kanban-add-column', function() {
            refreshAddColumnForm();
         });
         $(self.add_column_form).on('input', "input[name='column-name-filter']", function() {
            var filter_input = $(this);
            $(self.add_column_form + ' li').hide();
            $(self.add_column_form + ' li').filter(function() {
               return $(this).text().toLowerCase().includes(filter_input.val().toLowerCase());
            }).show();
         });
         $(self.add_column_form).on('change', "input[type='checkbox']", function() {
            var column_id = $(this).parent().data('list-id');
            if (column_id !== undefined) {
               if ($(this).is(':checked')) {
                  showColumn(column_id);
               } else {
                  hideColumn(column_id);
               }
            }
         });
         $(self.add_column_form).on('click', '.kanban-create-column', function() {
            var toolbar = $(self.element + ' .kanban-toolbar');
            $(self.add_column_form).css({
               display: 'none'
            });
            $(self.create_column_form).css({
               display: 'block',
               position: 'fixed',
               left: toolbar.offset().left + toolbar.outerWidth(true) - $(self.create_column_form).outerWidth(true),
               top: toolbar.offset().top + toolbar.outerHeight(true)
            });
         });
         $(self.create_column_form).on('click', '.kanban-create-column', function() {
            var toolbar = $(self.element + ' .kanban-toolbar');
            $(self.create_column_form).css({
               display: 'none'
            });
            var name = $(self.create_column_form + " input[name='name']").val();
            $(self.create_column_form + " input[name='name']").val("");
            var color = $(self.create_column_form + " input[name='color']").val();
            createColumn(name, {color: color}, function() {
               // Refresh add column list
               refreshAddColumnForm();
               $(self.add_column_form).css({
                  display: 'block',
                  position: 'fixed',
                  left: toolbar.offset().left + toolbar.outerWidth(true) - $(self.add_column_form).outerWidth(true),
                  top: toolbar.offset().top + toolbar.outerHeight(true)
               });
            });
         });
         $('#kanban-add-dropdown li').on('click', function(e) {
            e.preventDefault();
            var selection = $(e.target);
            // The add dropdown is a single-level dropdown, so the parent is the ul element
            var dropdown = selection.parent();
            // Get the button that triggered the dropdown and then get the column that it is a part of
            // This is because the dropdown exists outside all columns and is not recreated each time it is opened
            var column = $($(dropdown.data('trigger-button')).closest('.kanban-column'));
            // kanban-add-ITEMTYPE (We want the ITEMTYPE token at position 2)
            var itemtype = selection.prop('id').split('-')[2];
            self.clearAddItemForms(column);
            self.showAddItemForm(column, itemtype);
            delayRefresh();
         });
         $('#kanban-bulk-add-dropdown li').on('click', function(e) {
            e.preventDefault();
            var selection = $(e.target);
            // Traverse all the way up to the top-level overflow dropdown
            var dropdown = selection.closest('.kanban-dropdown');
            // Get the button that triggered the dropdown and then get the column that it is a part of
            // This is because the dropdown exists outside all columns and is not recreated each time it is opened
            var column = $($(dropdown.data('trigger-button')).closest('.kanban-column'));
            // kanban-bulk-add-ITEMTYPE (We want the ITEMTYPE token at position 3)
            var itemtype = selection.prop('id').split('-')[3];

            // Force-close the full dropdown
            dropdown.css({'display': 'none'});

            self.clearAddItemForms(column);
            self.showBulkAddItemForm(column, itemtype);
            delayRefresh();
         });
         var switcher = $("select[name='kanban-board-switcher']").first();
         $(self.element + ' .kanban-toolbar').on('select2:select', switcher, function(e) {
            var items_id = e.params.data.id;
            $.ajax({
               type: "GET",
               url: (self.ajax_root + "kanban.php"),
               data: {
                  action: "get_url",
                  itemtype: self.item.itemtype,
                  items_id: items_id
               },
               contentType: 'application/json',
               success: function(url) {
                  window.location = url;
               }
            });
         });

         $(self.element).on('input', '.kanban-add-form input, .kanban-add-form textarea', function() {
            delayRefresh();
         });

         if (!self.allow_order_card) {
            $(self.element).on(
               'mouseenter',
               '.kanban-column',
               function () {
                  if (self.is_sorting_active) {
                     return; // Do not change readonly states if user is sorting elements
                  }
                  // If user cannot order cards, make items temporarily readonly except for current column.
                  $(this).find('.kanban-body > li').removeClass('temporarily-readonly');
                  $(this).siblings().find('.kanban-body > li').addClass('temporarily-readonly');
               }
            );
            $(self.element).on(
               'mouseleave',
               '.kanban-column',
               function () {
                  if (self.is_sorting_active) {
                     return; // Do not change readonly states if user is sorting elements
                  }
                  $(self.element).find('.kanban-body > li').removeClass('temporarily-readonly');
               }
            );
         }

         $(self.element + ' .kanban-container').on('submit', '.kanban-add-form', function(e) {
            e.preventDefault();
            var form = $(e.target);
            var data = {};
            data['inputs'] = form.serialize();
            data['itemtype'] = form.prop('id').split('_')[2];
            data['action'] = 'add_item';

            $.ajax({
               method: 'POST',
               //async: false,
               url: (self.ajax_root + "kanban.php"),
               data: data
            }).done(function() {
               self.refresh();
            });
         });
      };

      /**
       * (Re-)Create the list of columns that can be shown/hidden.
       * This involves fetching the list of valid columns from the server.
       * @since 9.5.0
       */
      var refreshAddColumnForm = function() {
         var columns_used = [];
         $(self.element + ' .kanban-columns .kanban-column').each(function() {
            var column_id = this.id.split('-');
            columns_used.push(column_id[column_id.length - 1]);
         });
         var column_dialog = $(self.add_column_form);
         var toolbar = $(self.element + ' .kanban-toolbar');
         $.ajax({
            method: 'GET',
            url: (self.ajax_root + "kanban.php"),
            data: {
               action: "list_columns",
               itemtype: self.item.itemtype,
               column_field: self.column_field.id
            }
         }).done(function(data) {
            var form_content = $(self.add_column_form + " .kanban-item-content");
            form_content.empty();
            form_content.append("<input type='text' name='column-name-filter' placeholder='" + __('Search') + "'/>");
            var list = "<ul class='kanban-columns-list'>";
            $.each(data, function(column_id, column) {
               var list_item = "<li data-list-id='"+column_id+"'>";
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
      };

      /**
       * (Re-)Initialize JQuery sortable for all items and columns.
       * This should be called every time a new column or item is added to the board.
       * @since 9.5.0
       */
      var refreshSortables = function() {
         // Make sure all items in the columns can be sorted
         var bodies = $(self.element + ' .kanban-body');
         $.each(bodies, function(b) {
            var body = $(b);
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
            start: function(event, ui) {
               self.is_sorting_active = true;

               var card = ui.item;
               // Track the column and position the card was picked up from
               var current_column = card.closest('.kanban-column').attr('id');
               card.data('source-col', current_column);
               card.data('source-pos', card.index());
            },
            update: function(event, ui) {
               if (this === ui.item.parent()[0]) {
                  return self.onKanbanCardSort(ui, this);
               }
            },
            change: function(event, ui) {
               var card = ui.item;
               var source_column = card.data('source-col');
               var source_position = card.data('source-pos');
               var current_column = ui.placeholder.closest('.kanban-column').attr('id');

               // Compute current position based on list of sortable elements without current card.
               // Indeed, current card is still in DOM (but invisible), making placeholder index in DOM
               // not always corresponding to its position inside list of visible ements.
               var sortable_elements = $('#' + current_column + ' ul.ui-sortable > li:not([id="' + card.attr('id') + '"])');
               var current_position = sortable_elements.index(ui.placeholder);
               card.data('current-pos', current_position);

               if (!self.allow_order_card) {
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
            stop: function(event, ui) {
               self.is_sorting_active = false;
               ui.item.closest('.kanban-column').trigger('mouseenter'); // force readonly states refresh
            }
         });

         if (self.allow_modify_view) {
            // Enable column sorting
            $(self.element + ' .kanban-columns').sortable({
               connectWith: self.element + ' .kanban-columns',
               appendTo: '.kanban-container',
               items: '.kanban-column:not(.kanban-protected)',
               placeholder: "sortable-placeholder",
               handle: '.kanban-column-header',
               tolerance: 'pointer',
               stop: function(event, ui) {
                  var column = $(ui.item[0]);
                  updateColumnPosition(getColumnIDFromElement(ui.item[0]), column.index());
               }
            });
            $(self.element + ' .kanban-columns .kanban-column:not(.kanban-protected) .kanban-column-header').addClass('grab');
         }
      };

      /**
       * Construct and return the toolbar HTML for a specified column.
       * @since 9.5.0
       * @param {Object} column Column object that this toolbar will be made for.
       * @returns {string} HTML coded for the toolbar.
       */
      var getColumnToolbarElement = function(column) {
         var toolbar_el = "<span class='kanban-column-toolbar'>";
         var column_id = parseInt(getColumnIDFromElement(column['id']));
         if (self.allow_add_item && (self.limit_addcard_columns.length === 0 || self.limit_addcard_columns.includes(column_id))) {
            toolbar_el += "<i id='kanban_add_" + column['id'] + "' class='kanban-add pointer fas fa-plus' title='" + __('Add') + "'></i>";
            toolbar_el += "<i id='kanban_overflow_actions' class='kanban-overflow-actions pointer fas fa-ellipsis-h' title='" + __('More') + "'></i>";
         }
         toolbar_el += "</span>";
         return toolbar_el;
      };

      /**
       * Hide all columns that don't have a card in them.
       * @since 9.5.0
      **/
      this.hideEmpty = function() {
         var bodies = $(".kanban-body");
         bodies.each(function(index, item) {
            if (item.childElementCount === 0) {
               item.parentElement.style.display = "none";
            }
         });
      };

      /**
       * Show all columns that don't have a card in them.
       * @since 9.5.0
      **/
      this.showEmpty = function() {
         var columns = $(".kanban-column");
         columns.each(function(index, item) {
            item.style.display = "block";
         });
      };

      /**
       * Callback function for when a kanban item is moved.
       * @since 9.5.0
       * @param {Object}  ui       ui value directly from JQuery sortable function.
       * @param {Element} sortable Sortable object
       * @returns {Boolean}       Returns false if the sort was cancelled.
      **/
      this.onKanbanCardSort = function(ui, sortable) {
         var target = sortable.parentElement;
         var source = $(ui.sender);
         var card = $(ui.item[0]);
         var el_params = card.attr('id').split('-');
         var target_params = $(target).attr('id').split('-');
         var column_id = target_params[target_params.length - 1];

         if (el_params.length === 2 && source !== null && !(!self.allow_order_card && source.length === 0)) {
            $.ajax({
               type: "POST",
               url: (self.ajax_root + "kanban.php"),
               data: {
                  action: "update",
                  itemtype: el_params[0],
                  items_id: el_params[1],
                  column_field: self.column_field.id,
                  column_value: column_id
               },
               contentType: 'application/json',
               error: function() {
                  $(sortable).sortable('cancel');
                  return false;
               },
               success: function() {
                  var pos = card.data('current-pos');
                  if (!self.allow_order_card) {
                     card.appendTo($(target).find('.kanban-body').first());
                     pos = card.index();
                  }
                  // Update counters. Always pass the column element instead of the kanban body (card container)
                  self.updateColumnCount($(source).closest('.kanban-column'));
                  self.updateColumnCount($(target).closest('.kanban-column'));
                  card.removeData('source-col');
                  updateCardPosition(card.attr('id'), target.id, pos);
                  return true;
               }
            });
         } else {
            $(sortable).sortable('cancel');
            return false;
         }
      };

      /**
       * Send the new card position to the server.
       * @since 9.5.0
       * @param {string} card The ID of the card being moved.
       * @param {string|number} column The ID or element of the column the card resides in.
       * @param {number} position The position in the column that the card is at.
       * @param {function} error Callback function called when the server reports an error.
       * @param {function} success Callback function called when the server processes the request successfully.
       */
      var updateCardPosition = function(card, column, position, error, success) {
         if (typeof column === 'string' && column.lastIndexOf('column', 0) === 0) {
            column = getColumnIDFromElement(column);
         }
         $.ajax({
            type: "POST",
            url: (self.ajax_root + "kanban.php"),
            data: {
               action: "move_item",
               card: card,
               column: column,
               position: position,
               kanban: self.item
            },
            contentType: 'application/json',
            error: function() {
               if (error) {
                  error();
               }
            },
            success: function() {
               if (success) {
                  success();
               }
            }
         });
      };

      /**
       * Show the column and notify the server of the change.
       * @since 9.5.0
       * @param {number} column The ID of the column.
       */
      var showColumn = function(column) {
         $.ajax({
            type: "POST",
            url: (self.ajax_root + "kanban.php"),
            data: {
               action: "show_column",
               column: column,
               kanban: self.item
            },
            contentType: 'application/json',
            complete: function() {
               $.each(self.user_state.state, function(i, c) {
                  if (parseInt(c['column']) === parseInt(column)) {
                     self.user_state.state[i]['visible'] = true;
                     return false;
                  }
               });
               loadColumn(column, false, true);
               $(self.element + " .kanban-add-column-form li[data-list-id='" + column + "']").prop('checked', true);
            }
         });
      };

      /**
       * Hide the column and notify the server of the change.
       * @since 9.5.0
       * @param {number} column The ID of the column.
       */
      var hideColumn = function(column) {
         $.ajax({
            type: "POST",
            url: (self.ajax_root + "kanban.php"),
            data: {
               action: "hide_column",
               column: column,
               kanban: self.item
            },
            contentType: 'application/json',
            complete: function() {
               $(getColumnElementFromID(column)).remove();
               $.each(self.user_state.state, function(i, c) {
                  if (parseInt(c['column']) === parseInt(column)) {
                     self.user_state.state[i]['visible'] = false;
                     return false;
                  }
               });
               $(self.element + " .kanban-add-column-form li[data-list-id='" + column + "']").prop('checked', false);
            }
         });
      };

      /**
       * Notify the server that the column's position has changed.
       * @since 9.5.0
       * @param {number} column The ID of the column.
       * @param {number} position The position of the column.
       */
      var updateColumnPosition = function(column, position) {
         $.ajax({
            type: "POST",
            url: (self.ajax_root + "kanban.php"),
            data: {
               action: "move_column",
               column: column,
               position: position,
               kanban: self.item
            },
            contentType: 'application/json'
         });
      };

      /**
       * Get or create team member badge
       * @since 9.5.0
       * @param {array} teammember
       * @returns {string} HTML image or icon
       * @see generateUserBadge()
       * @see generateOtherBadge()
      **/
      var getTeamBadge = function(teammember) {
         var itemtype = teammember["itemtype"];
         var items_id = teammember["items_id"];

         if (self.team_badge_cache[itemtype] === undefined ||
                 self.team_badge_cache[itemtype][items_id] === undefined) {
            if (itemtype === 'User') {
               var user_img = null;
               $.ajax({
                  url: (self.ajax_root + "getUserPicture.php"),
                  async: false,
                  data: {
                     users_id: [items_id],
                     size: self.team_image_size,
                  },
                  contentType: 'application/json',
                  dataType: 'json'
               }).done(function(data) {
                  if (data[items_id] !== undefined) {
                     user_img = data[items_id];
                  } else {
                     user_img = null;
                  }
               });

               if (user_img) {
                  self.team_badge_cache[itemtype][items_id] = "<span>" + user_img + "</span>";
               } else {
                  self.team_badge_cache[itemtype][items_id] = generateUserBadge(teammember);
               }
            } else {
               switch (itemtype) {
                  case 'Group':
                     self.team_badge_cache[itemtype][items_id] = generateOtherBadge(teammember, 'fa-users');
                     break;
                  case 'Supplier':
                     self.team_badge_cache[itemtype][items_id] = generateOtherBadge(teammember, 'fa-briefcase');
                     break;
                  case 'Contact':
                     self.team_badge_cache[itemtype][items_id] = generateOtherBadge(teammember, 'fa-user');
                     break;
                  default:
                     self.team_badge_cache[itemtype][items_id] = generateOtherBadge(teammember, 'fa-user');
               }
            }
         }
         return self.team_badge_cache[itemtype][items_id];
      };

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
      var preloadBadgeCache = function(options) {
         var users = [];
         $.each(self.columns, function(column_id, column) {
            if (column['items'] !== undefined) {
               $.each(column['items'], function(card_id, card) {
                  if (card["_team"] !== undefined) {
                     Object.values(card["_team"]).slice(0, self.max_team_images).forEach(function(teammember) {
                        if (teammember['itemtype'] === 'User') {
                           if (self.team_badge_cache['User'][teammember['items_id']] === undefined) {
                              users[teammember['items_id']] = teammember;
                           }
                        }
                     });
                  }
               });
            }
         });
         if (users.length === 0) {
            return;
         }
         $.ajax({
            url: (self.ajax_root + "getUserPicture.php"),
            async: false,
            data: {
               users_id: Object.keys(users),
               size: self.team_image_size
            },
            contentType: 'application/json',
            dataType: 'json'
         }).done(function(data) {
            Object.keys(users).forEach(function(user_id) {
               var teammember = users[user_id];
               if (data[user_id] !== undefined) {
                  self.team_badge_cache['User'][user_id] = "<span>" + data[user_id] + "</span>";
               } else {
                  self.team_badge_cache['User'][user_id] = generateUserBadge(teammember);
               }
            });
            if (options !== undefined && options['trim_cache'] !== undefined) {
               var cached_colors = JSON.parse(window.sessionStorage.getItem('badge_colors'));
               Object.keys(self.team_badge_cache['User']).forEach(function(user_id) {
                  if (users[user_id] === undefined) {
                     delete self.team_badge_cache['User'][user_id];
                     delete cached_colors['User'][user_id];
                  }
               });
               window.sessionStorage.setItem('badge_colors', JSON.stringify(cached_colors));
            }
         });
      };

      /**
       * Convert the given H, S, L values into a color hex code (with prepended hash symbol).
       * @param {number} h Hue
       * @param {number} s Saturation
       * @param {number} l Lightness
       * @returns {string} Hex code color value
       */
      var hslToHexColor = function(h, s, l) {
         var r, g, b;

         if (s === 0) {
            r = g = b = l;
         } else {
            var hue2rgb = function hue2rgb(p, q, t){
               if (t < 0) t += 1;
               if (t > 1) t -= 1;
               if (t < 1/6) return p + (q - p) * 6 * t;
               if (t < 1/2) return q;
               if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
               return p;
            };

            var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            var p = 2 * l - q;
            r = hue2rgb(p, q, h + 1/3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1/3);
         }

         r = ('0' + (r * 255).toString(16)).substr(-2);
         g = ('0' + (g * 255).toString(16)).substr(-2);
         b = ('0' + (b * 255).toString(16)).substr(-2);
         return '#' + r + g + b;
      };

      /**
       * Compute a new badge color or retrieve the cached color from session storage.
       * @since 9.5.0
       * @param {Object} teammember The teammember this badge is for.
       * @returns {string} Hex code color value
       */
      var getBadgeColor = function(teammember) {
         var cached_colors = JSON.parse(window.sessionStorage.getItem('badge_colors'));
         var itemtype = teammember['itemtype'];
         var baseColor = Math.random();
         var lightness = (Math.random() * 10) + (self.dark_theme ? 25 : 70);
         //var bg_color = "hsl(" + baseColor + ", 100%," + lightness + "%,1)";
         var bg_color = hslToHexColor(baseColor, 1, lightness / 100);

         if (cached_colors !== null && cached_colors[itemtype] !== null && cached_colors[itemtype][teammember['id']]) {
            bg_color = cached_colors[itemtype][teammember['id']];
         } else {
            if (cached_colors === null) {
               cached_colors = {
                  User: {},
                  Group: {},
                  Supplier: {},
                  Contact: {}
               };
            }
            cached_colors[itemtype][teammember['id']] = bg_color;
            window.sessionStorage.setItem('badge_colors', JSON.stringify(cached_colors));
         }

         return bg_color;
      };

      /**
       * Generate a user image based on the user's initials.
       * @since 9.5.0
       * @param {string} teammember The teammember array/object that represents the user.
       * @return {string} HTML image of the generated user badge.
       */
      var generateUserBadge = function(teammember) {
         var initials = "";
         if (teammember["firstname"]) {
            initials += teammember["firstname"][0];
         }
         if (teammember["realname"]) {
            initials += teammember["realname"][0];
         }
         // Force uppercase initals
         initials = initials.toUpperCase();

         if (initials.length === 0) {
            return generateOtherBadge(teammember, 'fa-user');
         }

         var canvas = document.createElement('canvas');
         canvas.width = self.team_image_size;
         canvas.height = self.team_image_size;
         var context = canvas.getContext('2d');
         context.strokeStyle = "#f1f1f1";

         context.fillStyle = getBadgeColor(teammember);
         context.beginPath();
         context.arc(self.team_image_size / 2, self.team_image_size / 2, self.team_image_size / 2, 0, 2 * Math.PI);
         context.fill();
         context.fillStyle = self.dark_theme ? 'white' : 'black';
         context.textAlign = 'center';
         context.font = 'bold ' + (self.team_image_size / 2) + 'px sans-serif';
         context.textBaseline = 'middle';
         context.fillText(initials, self.team_image_size / 2, self.team_image_size / 2);
         var src = canvas.toDataURL("image/png");
         return "<span><img src='" + src + "' title='" + teammember['name'] + "'/></span>";
      };

      /**
       * Generate team member icon based on its name and a FontAwesome icon.
       * @since 9.5.0
       * @param {Object} teammember The team member data.
       * @param {string} icon FontAwesome icon to use for this badge.
       * @returns {string} HTML icon of the generated badge.
       */
      var generateOtherBadge = function(teammember, icon) {
         var bg_color = getBadgeColor(teammember);

         return "<span class='fa-stack fa-lg' style='font-size: " + (self.team_image_size / 2) + "px'>\
                     <i class='fas fa-circle fa-stack-2x' style='color: " + bg_color + "' title='" + teammember['name']+ "'></i>\
                     <i class='fas " + icon + " fa-stack-1x' title='" + teammember['name']+ "'></i>\
                  </span>";
      };

      /**
       * Generate a badge to indicate that 'overflow_count' number of team members are not shown on the Kanban item.
       * @since 9.5.0
       * @param {number} overflow_count Number of members without badges on the Kanban item.
       * @returns {string} HTML image of the generated overflow badge.
       */
      var generateOverflowBadge = function(overflow_count) {
         var canvas = document.createElement('canvas');
         canvas.width = self.team_image_size;
         canvas.height = self.team_image_size;
         var context = canvas.getContext('2d');
         context.strokeStyle = "#f1f1f1";

         // Create fill color based on theme type
         var lightness = (self.dark_theme ? 40 : 80);
         context.fillStyle = "hsl(255, 0%," + lightness + "%,1)";
         context.beginPath();
         context.arc(self.team_image_size / 2, self.team_image_size / 2, self.team_image_size / 2, 0, 2 * Math.PI);
         context.fill();
         context.fillStyle = self.dark_theme ? 'white' : 'black';
         context.textAlign = 'center';
         context.font = 'bold ' + (self.team_image_size / 2) + 'px sans-serif';
         context.textBaseline = 'middle';
         context.fillText("+" + overflow_count, self.team_image_size / 2, self.team_image_size / 2);
         var src = canvas.toDataURL("image/png");
         return "<span><img src='" + src + "' title='" + __('%d other team members').replace('%d', overflow_count) + "'/></span>";
      };

      /**
       * Check if the provided color is more light or dark.
       * This function converts the given hex value into HSL and checks the L value.
       * @since 9.5.0
       * @param hex Hex code of the color. It may or may not contain the beginning '#'.
       * @returns {boolean} True if the color is more light.
       */
      var isLightColor = function(hex) {
         var c = hex.substring(1);
         var rgb = parseInt(c, 16);
         var r = (rgb >> 16) & 0xff;
         var g = (rgb >>  8) & 0xff;
         var b = (rgb >>  0) & 0xff;
         var lightness = 0.2126 * r + 0.7152 * g + 0.0722 * b;
         return lightness > 110;
      };

      /**
       * Update the counter for the specified column.
       * @since 9.5.0
       * @param {string|Element|jQuery} column_el The column
       */
      this.updateColumnCount = function(column_el) {
         if (!(column_el instanceof jQuery)) {
            column_el = $(column_el);
         }
         var column_body = $(column_el).find('.kanban-body:first');
         var counter = $(column_el).find('.kanban_nb:first');
         // Get all visible kanban items. This ensures the count is correct when items are filtered out.
         var items = column_body.find('li:not(.filtered-out)');
         counter.text(items.length);
      };

      /**
       * Remove all add item forms from the specified column.
       * @since 9.5.0
       * @param {string|Element|jQuery} column_el The column
       */
      this.clearAddItemForms = function(column_el) {
         if (!(column_el instanceof jQuery)) {
            column_el = $(column_el);
         }
         column_el.find('form').remove();
      };

      /**
       * Add a new form to the Kanban column to add a new item of the specified itemtype.
       * @since 9.5.0
       * @param {string|Element|jQuery} column_el The column
       * @param {string} itemtype The itemtype that is being added
       */
      this.showAddItemForm = function(column_el, itemtype) {
         if (!(column_el instanceof jQuery)) {
            column_el = $(column_el);
         }

         var uniqueID = Math.floor(Math.random() * 999999);
         var formID = "form_add_" + itemtype + "_" + uniqueID;
         var add_form = "<form id='" + formID + "' class='kanban-add-form kanban-form no-track'>";
         var form_header = "<div class='kanban-item-header'>";
         form_header += "<span class='kanban-item-title'>"+self.supported_itemtypes[itemtype]['name']+"</span>";
         form_header += "<i class='fas fa-times' title='Close' onclick='$(this).parent().parent().remove()'></i></div>";
         add_form += form_header;

         add_form += "<div class='kanban-item-content'>";
         $.each(self.supported_itemtypes[itemtype]['fields'], function(name, options) {
            var input_type = options['type'] !== undefined ? options['type'] : 'text';
            var value = options['value'] !== undefined ? options['value'] : '';

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

         var column_id_elements = column_el.prop('id').split('-');
         var column_value = column_id_elements[column_id_elements.length - 1];
         add_form += "<input type='hidden' name='" + self.column_field.id + "' value='" + column_value + "'/>";
         add_form += "<input type='submit' value='" + __('Add') + "' name='add' class='submit'/>";
         add_form += "</form>";
         $(column_el.find('.kanban-body')[0]).append(add_form);
         $('#' + formID).get(0).scrollIntoView(false);
      };

      /**
       * Add a new form to the Kanban column to add multiple new items of the specified itemtype.
       * @since 9.5.0
       * @param {string|Element|jQuery} column_el The column
       * @param {string} itemtype The itemtype that is being added
       */
      this.showBulkAddItemForm = function(column_el, itemtype) {
         if (!(column_el instanceof jQuery)) {
            column_el = $(column_el);
         }

         var uniqueID = Math.floor(Math.random() * 999999);
         var formID = "form_add_" + itemtype + "_" + uniqueID;
         var add_form = "<form id='" + formID + "' class='kanban-add-form kanban-form no-track'>";
         var form_header = "<div class='kanban-item-header'>";
         form_header += "<span class='kanban-item-title'>"+self.supported_itemtypes[itemtype]['name']+"</span>";
         form_header += "<i class='fas fa-times' title='Close' onclick='$(this).parent().parent().remove()'></i>";
         form_header += '<div><span class="kanban-item-subtitle">' + __("One item per line") + '</span></div></div>';
         add_form += form_header;

         add_form += "<div class='kanban-item-content'>";
         add_form += "<textarea name='bulk_item_list'></textarea>";
         $.each(self.supported_itemtypes[itemtype]['fields'], function(name, options) {
            var input_type = options['type'] !== undefined ? options['type'] : 'text';
            var value = options['value'] !== undefined ? options['value'] : '';

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

         var column_id_elements = column_el.prop('id').split('-');
         var column_value = column_id_elements[column_id_elements.length - 1];
         add_form += "<input type='hidden' name='" + self.column_field.id + "' value='" + column_value + "'/>";
         add_form += "<input type='submit' value='" + __('Add') + "' name='add' class='submit'/>";
         add_form += "</form>";
         $(column_el.find('.kanban-body')[0]).append(add_form);
         $('#' + formID).get(0).scrollIntoView(false);
         $("#" + formID).on('submit', function(e) {
            e.preventDefault();
            var form = $(e.target);
            var data = {};
            data['inputs'] = form.serialize();
            data['itemtype'] = form.prop('id').split('_')[2];
            data['action'] = 'bulk_add_item';

            $.ajax({
               method: 'POST',
               //async: false,
               url: (self.ajax_root + "kanban.php"),
               data: data
            }).done(function() {
               $('#'+formID).remove();
               self.refresh();
            });
         });
      };

      /**
       * Create the add column form and add it to the DOM.
       * @since 9.5.0
       */
      var buildAddColumnForm = function() {
         var uniqueID = Math.floor(Math.random() * 999999);
         var formID = "form_add_column_" + uniqueID;
         self.add_column_form = '#' + formID;
         var add_form = "<div id='" + formID + "' class='kanban-form kanban-add-column-form' style='display: none'>";
         add_form += "<form class='no-track'>";
         var form_header = "<div class='kanban-item-header'>";
         form_header += "<span class='kanban-item-title'>" + __('Add a column from existing status') + "</span></div>";
         add_form += form_header;
         add_form += "<div class='kanban-item-content'></div>";
         if (self.allow_create_column) {
            add_form += "<hr>" + __('Or add a new status');
            add_form += "<input type='button' class='submit kanban-create-column' value='" +__('Create status') + "'/>";
         }
         add_form += "</form></div>";
         $(self.element).prepend(add_form);
      };

      /**
       * Create the create column form and add it to the DOM.
       * @since 9.5.0
       */
      var buildCreateColumnForm = function() {
         var uniqueID = Math.floor(Math.random() * 999999);
         var formID = "form_create_column_" + uniqueID;
         self.create_column_form = '#' + formID;
         var create_form = "<div id='" + formID + "' class='kanban-form kanban-create-column-form' style='display: none'>";
         create_form += "<form class='no-track'>";
         var form_header = "<div class='kanban-item-header'>";
         form_header += "<span class='kanban-item-title'>" + __('Create status') + "</span></div>";
         create_form += form_header;
         create_form += "<div class='kanban-item-content'>";
         create_form += "<input name='name'/>";
         $.each(self.column_field.extra_fields, function(name, field) {
            if (name === undefined) {
               return true;
            }
            var value = (field.value !== undefined) ? field.value : '';
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
         $(self.element).prepend(create_form);
      };

      /**
       * Delay the background refresh for a short amount of time.
       * This should be called any time the user is in the middle of an action so that the refresh is not disruptive.
       * @since 9.5.0
       */
      var delayRefresh = function() {
         window.setTimeout(_backgroundRefresh, 10000);
      };

      /**
       * Refresh the Kanban with the new set of columns.
       *    This will clear all existing columns from the Kanban, and replace them with what is provided by the server.
       * @since 9.5.0
       * @param {function} success Callback for when the Kanban is successfully refreshed.
       * @param {function} fail Callback for when the Kanban fails to be refreshed.
       * @param {function} always Callback that is called regardless of the success of the refresh.
       * @param {boolean} initial_load True if this is the first load. On the first load, the user state is not saved.
       */
      this.refresh = function(success, fail, always, initial_load) {
         var _refresh = function() {
            $.ajax({
               method: 'GET',
               //async: false,
               url: (self.ajax_root + "kanban.php"),
               data: {
                  action: "refresh",
                  itemtype: self.item.itemtype,
                  items_id: self.item.items_id,
                  column_field: self.column_field.id
               },
               contentType: 'application/json',
               dataType: 'json'
            }).done(function(columns, textStatus, jqXHR) {
               preloadBadgeCache({
                  trim_cache: true
               });
               clearColumns();
               self.columns = columns;
               fillColumns();
               // Re-filter kanban
               self.filter();
               if (success) {
                  success(columns, textStatus, jqXHR);
               }
            }).fail(function(jqXHR, textStatus, errorThrown) {
               if (fail) {
                  fail(jqXHR, textStatus, errorThrown);
               }
            }).always(function() {
               if (always) {
                  always();
               }
            });
         };
         if (initial_load === undefined || initial_load === true) {
            _refresh();
         } else {
            saveState(false, false, null, null, function() {
               loadState(_refresh);
            });
         }

      };

      /**
       * Append a column to the Kanban
       * @param {number} column_id The ID of the column being added.
       * @param {array} column The column data array.
       * @param {string|Element|jQuery} columns_container The container that the columns are in.
       *    If left null, a new JQueryobject is created with the selector "self.element + ' .kanban-container .kanban-columns'".
       * @param {boolean} revalidate If true, all other columns are checked to see if they have an item in this new column.
       *    If they do, the item is removed from that other column and the counter is updated.
       *    This is useful if an item is changed in another tab or by another user to be in the new column after the original column was added.
       */
      var appendColumn = function(column_id, column, columns_container, revalidate) {
         if (columns_container == null) {
            columns_container = $(self.element + " .kanban-container .kanban-columns").first();
         }
         revalidate = revalidate !== undefined ? revalidate : false;

         column['id'] = "column-" + self.column_field.id + '-' + column_id;
         var collapse = '';
         var position = -1;
         $.each(self.user_state.state, function(order, s_column) {
            if (parseInt(s_column['column']) === parseInt(column_id)) {
               position = order;
               if (s_column['folded'] === true || s_column['folded'] === 'true') {
                  collapse = 'collapsed';
                  return false;
               }
            }
         });
         var _protected = column['_protected'] ? 'kanban-protected' : '';
         var column_classes = "kanban-column " + collapse + " " + _protected;

         var column_top_color = (typeof column['header_color'] !== 'undefined') ? column['header_color'] : 'transparent';
         var column_html = "<div id='" + column['id'] + "' style='border-top: 5px solid "+column_top_color+"' class='"+column_classes+"'></div>";
         var column_el = null;
         if (position < 0) {
            column_el = $(column_html).appendTo(columns_container);
         } else {
            var prev_column = $(columns_container).find('.kanban-column:nth-child(' + (position) + ')');
            if (prev_column.length === 1) {
               column_el = $(column_html).insertAfter(prev_column);
            } else {
               column_el = $(column_html).appendTo(columns_container);
            }
         }
         var cards = column['items'] !== undefined ? column['items'] : [];

         var header_color = column['header_color'];
         var is_header_light = header_color ? isLightColor(header_color) : !self.dark_theme;
         var header_text_class = is_header_light ? 'kanban-text-dark' : 'kanban-text-light';

         var column_header = $("<header class='kanban-column-header'></header>");
         var column_content = $("<div class='kanban-column-header-content'></div>").appendTo(column_header);
         var count = column['items'] !== undefined ? column['items'].length : 0;
         var column_left = $("<span class=''></span>").appendTo(column_content);
         var column_right = $("<span class=''></span>").appendTo(column_content);
         if (self.allow_modify_view) {
            $(column_left).append("<i class='fas fa-caret-right fa-lg kanban-collapse-column pointer' title='" + __('Toggle collapse') + "'/>");
         }
         $(column_left).append("<span class='kanban-column-title "+header_text_class+"' style='background-color: "+column['header_color']+";'>" + column['name'] + "</span></span>");
         $(column_right).append("<span class='kanban_nb'>"+count+"</span>");
         $(column_right).append(getColumnToolbarElement(column));
         $(column_el).prepend(column_header);

         $("<ul class='kanban-body'></ul>").appendTo(column_el);

         var added = [];
         $.each(self.user_state.state, function(i, c) {
            if (c['column'] === column_id) {
               $.each(c['cards'], function(i2, card) {
                  $.each(cards, function(i3, card2) {
                     if (card2['id'] === card) {
                        appendCard(column_el, card2);
                        added.push(card2['id']);
                        return false;
                     }
                  });
               });
            }
         });

         $.each(cards, function(card_id, card) {
            if (added.indexOf(card['id']) < 0) {
               appendCard(column_el, card, revalidate);
            }
         });

         refreshSortables();
      };

      /**
       * Append the card in the specified column, handle duplicate cards in case the card moved, generate badges, and update column counts.
       * @since 9.5.0
       * @param {Element|string} column_el The column to add the card to.
       * @param {Object} card The card to append.
       * @param {boolean} revalidate Check for duplicate cards.
       */
      var appendCard = function(column_el, card, revalidate) {
         if (revalidate) {
            var existing = $('#' + card['id']);
            if (existing !== undefined) {
               var existing_column = existing.closest('.kanban-column');
               existing.remove();
               self.updateColumnCount(existing_column);
            }
         }

         var col_body = $(column_el).find('.kanban-body').first();
         var readonly = card['_readonly'] !== undefined && (card['_readonly'] === true || card['_readonly'] === 1);
         var card_el = "<li id='" + card['id'] + "' class='kanban-item " + (readonly ? 'readonly' : '') + "'>";
         card_el += "<div class='kanban-item-header'>" + card['title'] + "</div>";
         card_el += "<div class='kanban-item-content'>" + (card['content'] || '') + "</div>";
         card_el += "<div class='kanban-item-team'>";
         if (card["_team"] !== undefined && card['_team'].length > 0) {
            $.each(Object.values(card["_team"]).slice(0, self.max_team_images), function(teammember_id, teammember) {
               card_el += getTeamBadge(teammember);
            });
            if (card["_team"].length > self.max_team_images) {
               card_el += generateOverflowBadge(card["_team"].length - self.max_team_images);
            }
         }
         card_el += "</div>";
         card_el += "</li>";
         $(card_el).appendTo(col_body);
         self.updateColumnCount(column_el);
      };

      /**
       * Un-hide all filtered items.
       * This does not reset the filters as it is called whenever the items are being re-filtered.
       * To clear the filter, set self.filters to {_text: '*'} and call self.filter().
       * @since 9.5.0
       */
      this.clearFiltered = function() {
         $(self.element + ' .kanban-item').each(function(i, item) {
            $(item).removeClass('filtered-out');
         });
      };

      /**
       * Applies the current filters.
       * @since 9.5.0
       */
      this.filter = function() {
         // Unhide all items in case they are no longer filtered
         self.clearFiltered();
         // Filter using built-in text filter (Check title)
         $(self.element + ' .kanban-item').each(function(i, item) {
            var title = $(item).find(".kanban-item-header a").text();
            try {
               if (!title.match(new RegExp(self.filters._text, 'i'))) {
                  $(item).addClass('filtered-out');
               }
            } catch (err) {
               // Probably not a valid regular expression. Use simple contains matching.
               if (!title.toLowerCase().includes(self.filters._text.toLowerCase())) {
                  $(item).addClass('filtered-out');
               }
            }
         });
         // Check specialized filters (By column item property). Not currently supported.

         // Update column counters
         $(self.element + ' .kanban-column').each(function(i, column) {
            self.updateColumnCount(column);
         });
      };

      /**
       * Toggle the collapsed state of the specified column.
       * After toggling the collapse state, the server is notified of the change.
       * @since 9.5.0
       * @param {string|Element|JQuery} column_el The column element or object.
       */
      this.toggleCollapseColumn = function(column_el) {
         if (!(column_el instanceof jQuery)) {
            column_el = $(column_el);
         }
         column_el.toggleClass('collapsed');
         var action = column_el.hasClass('collapsed') ? 'collapse_column' : 'expand_column';
         $.ajax({
            type: "POST",
            url: (self.ajax_root + "kanban.php"),
            data: {
               action: action,
               column: getColumnIDFromElement(column_el),
               kanban: self.item
            },
            contentType: 'application/json'
         });
      };

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
      var loadColumn = function(column_id, nosave, revalidate, callback) {
         nosave = nosave !== undefined ? nosave : false;

         var skip_load = false;
         $.each(self.user_state.state, function(i, c) {
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
            url: (self.ajax_root + "kanban.php"),
            contentType: 'application/json',
            dataType: 'json',
            async: false,
            data: {
               action: "get_column",
               itemtype: self.item.itemtype,
               items_id: self.item.items_id,
               column_field: self.column_field.id,
               column_id: column_id
            }
         }).done(function(column) {
            if (column !== undefined) {
               self.columns[column_id] = column[column_id];
               appendColumn(column_id, self.columns[column_id], null, revalidate);
            }
         }).always(function() {
            if (callback) {
               callback();
            }
         });
      };

      /**
       * Create a new column and send it to the server.
       * This will create a new item in the DB based on the item type used for columns.
       * It does not automatically add it to the Kanban.
       * @since 9.5.0
       * @param {string} name The name of the new column.
       * @param {Object} params Extra fields needed to create the column.
       * @param {function} callback Function to call after the column is created (or fails to be created).
       */
      var createColumn = function(name, params, callback) {
         if (name === undefined || name.length === 0) {
            if (callback) {
               callback();
            }
            return;
         }
         $.ajax({
            method: 'POST',
            url: (self.ajax_root + "kanban.php"),
            contentType: 'application/json',
            dataType: 'json',
            data: {
               action: "create_column",
               itemtype: self.item.itemtype,
               items_id: self.item.items_id,
               column_field: self.column_field.id,
               column_name: name,
               params: params
            }
         }).always(function() {
            if (callback) {
               callback();
            }
         });
      };

      /**
       * Update the user state object, but do not send it to the server.
       * This should only be done if there is no state stored on the server, so one needs built.
       * Do NOT use this for changes to the state such as moving cards/columns!
       * @since 9.5.0
       */
      var updateColumnState = function() {
         var new_state = {
            is_dirty: true,
            state: {}
         };
         $(self.element + " .kanban-column").each(function(i, element) {
            var column = $(element);
            var element_id = column.prop('id').split('-');
            var column_id = element_id[element_id.length - 1];
            if (self.user_state.state[i] === undefined || column_id !== self.user_state.state[i]['column'] ||
               self.user_state.state[i]['folded'] !== column.hasClass('collapsed')) {
               new_state.is_dirty = true;
            }
            new_state.state[i] = {
               column: column_id,
               folded: column.hasClass('collapsed'),
               cards: {}
            };
            $.each(column.find('.kanban-item'), function(i2, element2) {
               new_state.state[i]['cards'][i2] = $(element2).prop('id');
               if (self.user_state.state[i] !== undefined && self.user_state.state[i]['cards'] !== undefined && self.user_state.state[i]['cards'][i2] !== undefined  &&
                  self.user_state.state[i]['cards'][i2] !== new_state.state[i]['cards'][i2]) {
                  new_state.is_dirty = true;
               }
            });
         });
         self.user_state = new_state;
      };

      /**
       * Restore the Kanban state for the user from the DB if it exists.
       * This restores the visible columns and their collapsed state.
       * @since 9.5.0
       */
      var loadState = function(callback) {
         $.ajax({
            type: "GET",
            url: (self.ajax_root + "kanban.php"),
            data: {
               action: "load_column_state",
               itemtype: self.item.itemtype,
               items_id: self.item.items_id,
               last_load: self.last_refresh
            },
            contentType: 'application/json'
         }).done(function(state) {
            if (state['state'] === undefined || state['state'] === null || Object.keys(state['state']).length === 0) {
               if (callback) {
                  callback(false);
               }
               return;
            }
            self.user_state = {
               is_dirty: false,
               state: state['state']
            };

            var indices = Object.keys(state['state']);
            for (var i = 0; i < indices.length; i++) {
               var index = indices[i];
               var entry = state['state'][index];
               var element = $('#column-' + self.column_field.id + "-" + entry.column);
               if (element.length === 0) {
                  loadColumn(entry.column, true, false);
               }
               $(self.element + ' .kanban-columns .kanban-column:nth-child(' + index + ')').after(element);
               if (entry.folded === 'true') {
                  element.addClass('collapsed');
               }
            }
            self.last_refresh = state['timestamp'];

            if (callback) {
               callback(true);
            }
         });
      };

      /**
       * Saves the current state of the Kanban to the DB for the user.
       * This saves the visible columns and their collapsed state.
       * This should only be done if there is no state stored on the server, so one needs built.
       * Do NOT use this for changes to the state such as moving cards/columns!
       * @since 9.5.0
       * @param {boolean} rebuild_state If true, the column state is recalculated before saving.
       *    By default, this is false as updates are done as changes are made in most cases.
       * @param {boolean} force_save If true, the user state is saved even if it has not changed.
       * @param {function} success Callback for when the user state is successfully saved.
       * @param {function} fail Callback for when the user state fails to be saved.
       * @param {function} always Callback that is called regardless of the success of the save.
       */
      var saveState = function(rebuild_state, force_save, success, fail, always) {
         rebuild_state = rebuild_state !== undefined ? rebuild_state : false;
         if (!force_save && !self.user_state.is_dirty) {
            if (always) {
               always();
            }
            return;
         }
         // Reload state in case it changed in another tab/window
         if (rebuild_state) {
            // Build state of the Kanban
            updateColumnState();
         }
         if (self.user_state.state === undefined || self.user_state.state === null || Object.keys(self.user_state.state).length === 0) {
            if (always) {
               always();
            }
            return;
         }
         $.ajax({
            type: "POST",
            url: (self.ajax_root + "kanban.php"),
            data: {
               action: "save_column_state",
               itemtype: self.item.itemtype,
               items_id: self.item.items_id,
               state: self.user_state.state
            },
            contentType: 'application/json'
         }).done(function(data, textStatus, jqXHR) {
            self.user_state.is_dirty = false;
            if (success) {
               success(data, textStatus, jqXHR);
            }
         }).fail(function(jqXHR, textStatus, errorThrown) {
            if (fail) {
               fail(jqXHR, textStatus, errorThrown);
            }
         }).always(function() {
            if (always) {
               always();
            }
         });
      };

      /**
       * Initialize the background refresh mechanism.
       * @sicne 9.5.0
       */
      var backgroundRefresh = function() {
         if (self.background_refresh_interval <= 0) {
            return;
         }
         _backgroundRefresh = function() {
            var sorting = $('.ui-sortable-helper');
            // Check if the user is current sorting items
            if (sorting.length > 0) {
               // Wait 10 seconds and try the background refresh again
               delayRefresh();
               return;
            }
            // Refresh and then schedule the next refresh (minutes)
            self.refresh(null, null, function() {
               window.setTimeout(_backgroundRefresh, self.background_refresh_interval * 60 * 1000);
            }, false);
         };
         // Schedule initial background refresh (minutes)
         window.setTimeout(_backgroundRefresh, self.background_refresh_interval * 60 * 1000);
      };

      /**
       * Initialize the Kanban by loading the user's column state, adding the needed elements to the DOM, and starting the background save and refresh.
       * @since 9.5.0
       */
      this.init = function() {
         loadState(function() {
            build();
            $(document).ready(function() {
               $.ajax({
                  type: 'GET',
                  url: (self.ajax_root + 'kanban.php'),
                  data: {
                     action: 'get_switcher_dropdown',
                     itemtype: self.item.itemtype,
                     items_id: self.item.items_id
                  },
                  contentType: 'application/json',
                  success: function($data) {
                     var switcher = $(self.element + " .kanban-toolbar select[name='kanban-board-switcher']");
                     switcher.replaceWith($data);
                  }
               });
               registerEventListeners();
               backgroundRefresh();
            });
         });
      };
      initParams(arguments);
   };
})();
