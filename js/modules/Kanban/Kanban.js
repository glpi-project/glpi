/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

import SearchInput from "../SearchTokenizer/SearchInput.js";

/* global escapeMarkupText */
/* global sortable */
/* global glpi_toast_error, glpi_toast_warning, glpi_toast_info */

/**
 * Kanban rights structure
 * @since 10.0.0
 */
class GLPIKanbanRights {
    constructor(rights) {
        /**
       * If true, then a button will be added to each column to allow new items to be added.
       * When an item is added, a request is made via AJAX to create the item in the DB.
       * Permissions are re-checked server-side during this request.
       * Users will still be limited by the {@link create_card_limited_columns} right both client-side and server-side.
       * @since 9.5.0
       * @since 10.0.0 Moved to new rights class
       * @type {boolean}
       */
        this.create_item = rights['create_item'] || false;

        /**
       * If true, then a button will be added to each card to allow deleting them and the underlying item directly from the kanban.
       * When a card is deleted, a request is made via AJAX to delete the item in the DB.
       * Permissions are re-checked server-side during this request.
       * @since 10.0.0
       * @type {boolean}
       */
        this.delete_item = rights['delete_item'] || false;

        /**
       * If true, then a button will be added to the add column form that lets the user create a new column.
       * For Projects as an example, it would create a new project state.
       * Permissions are re-checked server-side during this request.
       * @since 9.5.0
       * @since 10.0.0 Moved to new rights class
       * @type {boolean}
       */
        this.create_column = rights['create_column'] || false;

        /**
       * Global permission for being able to modify the Kanban state/view.
       * This includes the order of cards in the columns.
       * @since 9.5.0
       * @since 10.0.0 Moved to new rights class
       * @type {boolean}
       */
        this.modify_view = rights['modify_view'] || false;

        /**
       * Limits the columns that the user can add cards to.
       * By default, it is empty which allows cards to be added to all columns.
       * If you don't want the user to add cards to any column, {@link rights.create_item} should be false.
       * @since 9.5.0
       * @since 10.0.0 Moved to new rights class
       * @type {Array}
       */
        this.create_card_limited_columns = rights['create_card_limited_columns'] || [];

        /**
       * Global right for ordering cards.
       * @since 9.5.0
       * @since 10.0.0 Moved to new rights class
       * @type {boolean}
       */
        this.order_card = rights['order_card'] || false;
    }

    /** @see this.create_item */
    canCreateItem() {
        return this.create_item;
    }

    /** @see this.delete_item */
    canDeleteItem() {
        return this.delete_item;
    }

    /** @see this.create_column */
    canCreateColumn() {
        return this.create_column;
    }

    /** @see this.modify_view */
    canModifyView() {
        return this.modify_view;
    }

    /** @see this.order_card */
    canOrderCard() {
        return this.order_card;
    }

    /** @see this.create_card_limited_columns */
    getAllowedColumnsForNewCards() {
        return this.create_card_limited_columns;
    }
}

(function(){
    window.GLPIKanban = function() {
        /**
       * Self-reference for property access in functions.
       */
        const self = this;

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
       * User rights object
       * @type {GLPIKanbanRights}
       */
        this.rights = new GLPIKanbanRights({});

        /** @deprecated 10.0.0 Use rights.canCreateItem() instead */
        this.allow_add_item = false;
        /** @deprecated 10.0.0 Use rights.canDeleteItem() instead */
        this.allow_delete_item = false;
        /** @deprecated 10.0.0 Use rights.canCreateColumn() instead */
        this.allow_create_column = false;
        /** @deprecated 10.0.0 Use rights.canModifyView() instead */
        this.allow_modify_view = false;
        /** @deprecated 10.0.0 Use rights.getAllowedColumnsForNewCards() instead */
        this.limit_addcard_columns = [];
        /** @deprecated 10.0.0 Use rights.canOrderCard() instead */
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
       * Therefore, the column_field id is 'projectstates_id' with any additional fields needed being specified in extra_fields.
       * @since 9.5.0
       * @type {{id: string, extra_fields: Object}}
       */
        this.column_field = {id: '', extra_fields: {}};

        /**
       * Specifies if the Kanban's toolbar (switcher, filters, etc.) should be shown.
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

        this.filter_tokenizer = null;

        this.supported_filters = [];

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
        let _backgroundRefresh = null;

        /**
       * Reference for the background refresh timer
       * @type {null}
       * @private
       */
        var _backgroundRefreshTimer = null;

        /**
       * The user's state object.
       * This contains an up-to-date list of columns that should be shown, the order they are in, and if they are folded.
       * @since 9.5.0
       * @type {{
       *    is_dirty: boolean,
       *    state: {}|{order_index: {column: number, folded: boolean, cards: {Array}}}
       * }}
       * The is_dirty flag indicates if the state was changed and needs to be saved.
       */
        this.user_state = {is_dirty: false, state: {}};

        /**
       * The last time the Kanban was refreshed. This is used by the server to determine if the state needs to be sent to the client again.
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

        this.sort_data = undefined;

        this.mutation_observer = null;

        this.display_initials = true;

        /**
         * Keep track of users pictures that need to be loaded later on
         *
         * @type {Set}
         */
        this.user_pictures_to_load = new Set([]);

        /**
       * Parse arguments and assign them to the object's properties
       * @since 9.5.0
       * @param {Object} args Object arguments
       */
        const initParams = function(args) {
            const overridableParams = [
                'element', 'max_team_images', 'team_image_size', 'item',
                'supported_itemtypes', 'allow_add_item', 'allow_add_column', 'dark_theme', 'background_refresh_interval',
                'column_field', 'allow_modify_view', 'limit_addcard_columns', 'allow_order_card', 'allow_create_column',
                'allow_delete_item', 'supported_filters', 'display_initials'
            ];
            // Use CSS variable check for dark theme detection by default
            self.dark_theme = $('html').css('--is-dark').trim() === 'true';

            if (args.length === 1) {
                for (let i = 0; i < overridableParams.length; i++) {
                    const param = overridableParams[i];
                    if (args[0][param] !== undefined) {
                        self[param] = args[0][param];
                    }
                }
            }
            // Set rights
            if (args[0]['rights'] !== undefined) {
                self.rights = new GLPIKanbanRights(args[0]['rights']);
            } else {
            // 9.5.0 style compatibility
                self.rights = new GLPIKanbanRights({
                    create_item: self.allow_add_item,
                    delete_item: self.allow_delete_item,
                    create_column: self.allow_create_column,
                    modify_view: self.allow_modify_view,
                    create_card_limited_columns: self.limit_addcard_columns,
                    order_card: self.allow_order_card
                });
            }
            if (self.filters._text === undefined) {
                self.filters._text = '';
            }
            /**
          * @type {SearchInput}
          */
            self.filter_input = null;
        };

        const initMutationObserver = function() {
            self.mutation_observer = new MutationObserver((records) => {
                records.forEach(r => {
                    if (r.addedNodes.length > 0) {
                        if (self.is_sorting_active) {
                            const sortable_placeholders = [...r.addedNodes].filter(n => n.classList.contains('sortable-placeholder'));
                            if (sortable_placeholders.length > 0) {
                                const placeholder = $(sortable_placeholders[0]);

                                const current_column = placeholder.closest('.kanban-column').attr('id');

                                // Compute current position based on list of sortable elements without current card.
                                // Indeed, current card is still in DOM (but invisible), making placeholder index in DOM
                                // not always corresponding to its position inside list of visible elements.
                                const sortable_elements = $('#' + current_column + ' ul.kanban-body > li:not([id="' + self.sort_data.card_id + '"])');
                                const current_position = sortable_elements.index(placeholder.get(0));
                                const card = $('#' + self.sort_data.card_id);
                                card.data('current-pos', current_position);

                                if (!self.rights.canOrderCard()) {
                                    if (current_column === self.sort_data.source_column) {
                                        if (current_position !== self.sort_data.source_position) {
                                            placeholder.addClass('invalid-position');
                                        } else {
                                            placeholder.removeClass('invalid-position');
                                        }
                                    } else {
                                        if (!$(placeholder).is(':last-child')) {
                                            placeholder.addClass('invalid-position');
                                        } else {
                                            placeholder.removeClass('invalid-position');
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            });
            self.mutation_observer.observe($(self.element).get(0), {
                subtree: true,
                childList: true
            });
        };

        /**
       * Build DOM elements and defer registering event listeners for when the document is ready.
       * @since 9.5.0
      **/
        const build = function() {
            $(self.element).trigger('kanban:pre_build');
            initMutationObserver();
            if (self.show_toolbar) {
                buildToolbar();
            }
            const kanban_container = $("<div class='kanban-container'><div class='kanban-columns'></div></div>").appendTo($(self.element));

            // Dropdown for single additions
            let add_itemtype_dropdown = "<ul id='kanban-add-dropdown' class='kanban-dropdown dropdown-menu' style='display: none'>";
            Object.keys(self.supported_itemtypes).forEach(function(itemtype) {
                if (self.supported_itemtypes[itemtype]['allow_create'] !== false) {
                    add_itemtype_dropdown += "<li id='kanban-add-" + itemtype + "' class='dropdown-item'><span>" + self.supported_itemtypes[itemtype]['name'] + '</span></li>';
                }
            });
            add_itemtype_dropdown += '</ul>';
            kanban_container.append(add_itemtype_dropdown);

            // Dropdown for overflow (Column)
            let column_overflow_dropdown = "<ul id='kanban-overflow-dropdown' class='kanban-dropdown  dropdown-menu' style='display: none'>";
            let add_itemtype_bulk_dropdown = "<ul id='kanban-bulk-add-dropdown' class='dropdown-menu' style='display: none'>";
            Object.keys(self.supported_itemtypes).forEach(function(itemtype) {
                if (self.supported_itemtypes[itemtype]['allow_create'] !== false && self.supported_itemtypes[itemtype]['allow_bulk_add'] !== false) {
                    add_itemtype_bulk_dropdown += "<li id='kanban-bulk-add-" + itemtype + "' class='dropdown-item'><span>" + self.supported_itemtypes[itemtype]['name'] + '</span></li>';
                }
            });
            add_itemtype_bulk_dropdown += '</ul>';
            const add_itemtype_bulk_link = '<a href="#">' + '<i class="fa-fw fas fa-list"></i>' + __('Bulk add') + '</a>';
            column_overflow_dropdown += '<li class="dropdown-trigger dropdown-item">' + add_itemtype_bulk_link + add_itemtype_bulk_dropdown + '</li>';
            if (self.rights.canModifyView()) {
                column_overflow_dropdown += "<li class='kanban-remove dropdown-item' data-forbid-protected='true'><span>"  + '<i class="fa-fw ti ti-trash"></i>' + __('Delete') + "</span></li>";
            }
            column_overflow_dropdown += '</ul>';
            kanban_container.append(column_overflow_dropdown);

            // Dropdown for overflow (Card)

            let card_overflow_dropdown = "<ul id='kanban-item-overflow-dropdown' class='kanban-dropdown dropdown-menu' style='display: none'>";
            card_overflow_dropdown += `
            <li class='kanban-item-goto dropdown-item'>
               <a href="#"><i class="fa-fw fas fa-share"></i>${__('Go to')}</a>
            </li>`;
            if (self.rights.canDeleteItem()) {
                card_overflow_dropdown += `
                <li class='kanban-item-restore dropdown-item d-none'>
                   <span>
                      <i class="fa-fw ti ti-trash-off"></i>${__('Restore')}
                   </span>
                </li>`;
                card_overflow_dropdown += `
                <li class='kanban-item-remove dropdown-item'>
                   <span>
                      <i class="fa-fw ti ti-trash"></i>${__('Delete')}
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

            const on_refresh = function() {
                if (Object.keys(self.user_state.state).length === 0) {
                    // Save new state since none was stored for the user
                    saveState(true, true);
                }
            };
            self.refresh(on_refresh, null, null, true);

            if (self.rights.canModifyView()) {
                buildAddColumnForm();
                if (self.rights.canCreateColumn()) {
                    buildCreateColumnForm();
                }
            }
            $(self.element).trigger('kanban:post_build');
        };

        const buildToolbar = function() {
            $(self.element).trigger('kanban:pre_build_toolbar');
            let toolbar = $("<div class='kanban-toolbar card flex-column flex-md-row'></div>").appendTo(self.element);
            $("<select name='kanban-board-switcher'></select>").appendTo(toolbar);
            let filter_input = $(`<input name='filter' class='form-control ms-1' type='text' placeholder="${__('Search or filter results')}" autocomplete="off"/>`).appendTo(toolbar);
            if (self.rights.canModifyView()) {
                let add_column = "<button class='kanban-add-column btn btn-outline-secondary ms-1'>" + __('Add column') + "</button>";
                toolbar.append(add_column);
            }

            self.filter_input = new SearchInput(filter_input, {
                allowed_tags: self.supported_filters,
                on_result_change: (e, result) => {
                    self.filters = {
                        _text: ''
                    };
                    self.filters._text = result.getFullPhrase();
                    result.getTaggedTerms().forEach(t => self.filters[t.tag] = {
                        term: t.term || '',
                        exclusion: t.exclusion || false,
                        prefix: t.prefix
                    });
                    self.filter();
                },
                tokenizer_options: {
                    custom_prefixes: {
                        '#': { // Regex prefix
                            label: __('Regex'),
                            token_color: '#00800080'
                        }
                    }
                }
            });
            self.refreshSearchTokenizer();
            self.filter();

            $(self.element).trigger('kanban:post_build_toolbar');
        };

        const getColumnElementFromID = function(column_id) {
            return '#column-' + self.column_field.id + '-' + column_id;
        };

        const getColumnIDFromElement = function(column_el) {
            let element_id = [column_el];
            if (typeof column_el !== 'string') {
                element_id = $(column_el).prop('id').split('-');
            } else {
                element_id = column_el.split('-');
            }
            return element_id[element_id.length - 1];
        };

        const preserveNewItemForms = function() {
            self.temp_forms = {};
            let columns = $(self.element + " .kanban-column");
            $.each(columns, function(i, column) {
                let forms = $(column).find('.kanban-add-form');
                if (forms.length > 0) {
                    self.temp_forms[column.id] = [];
                    $.each(forms, function(i2, form) {
                        // Copy event handlers for element and child elements
                        // Otherwise, the Add button will act like a normal submit button (not wanted)
                        self.temp_forms[column.id].push($(form).clone(true, true));
                    });
                }
            });
        };

        const restoreNewItemForms = function() {
            if (self.temp_forms !== undefined && Object.keys(self.temp_forms).length > 0) {
                $.each(self.temp_forms, function(column_id, forms) {
                    let column = $('#' + column_id);
                    if (column.length > 0) {
                        let column_body = column.find('.kanban-body').first();
                        $.each(forms, function(i, form) {
                            $(form).appendTo(column_body);
                        });
                    }
                });
                self.temp_forms = {};
            }
        };

        const preserveScrolls = function() {
            self.temp_kanban_scroll = {
                left: $(self.element + ' .kanban-container').scrollLeft(),
                top: $(self.element + ' .kanban-container').scrollTop()
            };
            self.temp_column_scrolls = {};
            let columns = $(self.element + " .kanban-column");
            $.each(columns, function(i, column) {
                let column_body = $(column).find('.kanban-body');
                if (column_body.scrollTop() !== 0) {
                    self.temp_column_scrolls[column.id] = column_body.scrollTop();
                }
            });
        };

        const restoreScrolls = function() {
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
        const clearColumns = function() {
            preserveScrolls();
            preserveNewItemForms();
            $(self.element + " .kanban-column").remove();
        };

        /**
       * Add all columns to the kanban. This does not clear the existing columns first.
       *    If you are refreshing the Kanban, you should call {@link clearColumns()} first.
       * @since 9.5.0
       * @param {Object} columns_container JQuery Object of columns container. Not required.
       *    If not specified, a new object will be created to reference this Kanban's columns container.
       */
        const fillColumns = function(columns_container) {
            if (columns_container === undefined) {
                columns_container = $(self.element + " .kanban-container .kanban-columns").first();
            }

            let already_processed = [];
            $.each(self.user_state.state, function(position, column) {
                if (column['visible'] !== false && column !== 'false') {
                    if (self.columns[column['column']] !== undefined) {
                        appendColumn(column['column'], self.columns[column['column']], columns_container);
                    }
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
        const registerEventListeners = function() {
            const add_dropdown = $('#kanban-add-dropdown');
            const column_overflow_dropdown = $('#kanban-overflow-dropdown');
            const card_overflow_dropdown = $('#kanban-item-overflow-dropdown');

            refreshSortables();

            if (Object.keys(self.supported_itemtypes).length > 0) {
                $(self.element + ' .kanban-container').on('click', '.kanban-add', function(e) {
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
            $(window).on('click', function(e) {
                if (!$(e.target).hasClass('kanban-add')) {
                    add_dropdown.css({
                        display: 'none'
                    });
                }
                if (self.rights.canModifyView()) {
                    if (!$.contains($(self.add_column_form)[0], e.target)) {
                        $(self.add_column_form).css({
                            display: 'none'
                        });
                    }
                    if (self.rights.canCreateColumn()) {
                        if (!$.contains($(self.create_column_form)[0], e.target) && !$.contains($(self.add_column_form)[0], e.target)) {
                            $(self.create_column_form).css({
                                display: 'none'
                            });
                        }
                    }
                }
            });

            if (Object.keys(self.supported_itemtypes).length > 0) {
                $(self.element + ' .kanban-container').on('click', '.kanban-column-overflow-actions', function(e) {
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
            $(self.element + ' .kanban-container').on('click', '.kanban-item-overflow-actions', function(e) {
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
                const restore_action = $(card_overflow_dropdown.find('.kanban-item-restore'));
                if (card.data('is_deleted')) {
                    restore_action.removeClass('d-none');
                    delete_action.html('<span><i class="ti ti-trash"></i>'+__('Purge')+'</span>');
                } else {
                    restore_action.addClass('d-none');
                    delete_action.html('<span><i class="ti ti-trash"></i>'+__('Delete')+'</span>');
                }
            });

            $(window).on('click', function(e) {
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
                if (self.rights.canModifyView()) {
                    if (!$.contains($(self.add_column_form)[0], e.target)) {
                        $(self.add_column_form).css({
                            display: 'none'
                        });
                    }
                    if (self.rights.canCreateColumn()) {
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
                const column = $(e.target.closest('.kanban-dropdown')).data('trigger-button').closest('.kanban-column');
                // Hide that column
                hideColumn(getColumnIDFromElement(column));
            });
            $(self.element).on('click', '.item-details-panel .kanban-item-edit-team', (e) => {
                self.showTeamModal($(e.target).closest('.item-details-panel').data('card'));
            });
            $(self.element + ' .kanban-container').on('click', '.kanban-item-remove', function(e) {
            // Get root dropdown, then the button that triggered it, and finally the card that the button is in
                const card = $(e.target.closest('.kanban-dropdown')).data('trigger-button').closest('.kanban-item').prop('id');
                // Try to delete that card item
                deleteCard(card, undefined, undefined);
            });
            $(self.element + ' .kanban-container').on('click', '.kanban-item-restore', function(e) {
                // Get root dropdown, then the button that triggered it, and finally the card that the button is in
                const card = $(e.target.closest('.kanban-dropdown')).data('trigger-button').closest('.kanban-item').prop('id');
                // Try to delete that card item
                restoreCard(card, undefined, undefined);
            });
            $(self.element + ' .kanban-container').on('click', '.kanban-collapse-column', function(e) {
                self.toggleCollapseColumn(e.target.closest('.kanban-column'));
            });
            $(self.element).on('click', '.kanban-add-column', function() {
                refreshAddColumnForm();
            });
            $(self.add_column_form).on('input', "input[name='column-name-filter']", function() {
                const filter_input = $(this);
                $(self.add_column_form + ' li').hide();
                $(self.add_column_form + ' li').filter(function() {
                    return $(this).text().toLowerCase().includes(filter_input.val().toLowerCase());
                }).show();
            });
            $(self.add_column_form).on('change', "input[type='checkbox']", function() {
                const column_id = $(this).parent().data('list-id');
                if (column_id !== undefined) {
                    if ($(this).is(':checked')) {
                        showColumn(column_id);
                    } else {
                        hideColumn(column_id);
                    }
                }
            });
            $(self.add_column_form).on('submit', 'form', function(e) {
                e.preventDefault();
            });
            $(self.add_column_form).on('click', '.kanban-create-column', function() {
                const toolbar = $(self.element + ' .kanban-toolbar');
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
            $(self.create_column_form).on('submit', 'form', function(e) {
                e.preventDefault();

                const toolbar = $(self.element + ' .kanban-toolbar');

                $(self.create_column_form).css({
                    display: 'none'
                });
                const name = $(self.create_column_form + " input[name='name']").val();
                $(self.create_column_form + " input[name='name']").val("");
                const color = $(self.create_column_form + " input[name='color']").val();
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
                const selection = $(this).closest('li');
                // The add dropdown is a single-level dropdown, so the parent is the ul element
                const dropdown = selection.parent();
                // Get the button that triggered the dropdown and then get the column that it is a part of
                // This is because the dropdown exists outside all columns and is not recreated each time it is opened
                const column = $($(dropdown.data('trigger-button')).closest('.kanban-column'));
                // kanban-add-ITEMTYPE (We want the ITEMTYPE token at position 2)
                const itemtype = selection.prop('id').split('-')[2];
                self.clearAddItemForms(column);
                self.showAddItemForm(column, itemtype);
                delayRefresh();
            });
            $('#kanban-bulk-add-dropdown li').on('click', function(e) {
                e.preventDefault();
                const selection = $(this).closest('li');
                // Traverse all the way up to the top-level overflow dropdown
                const dropdown = selection.closest('.kanban-dropdown');
                // Get the button that triggered the dropdown and then get the column that it is a part of
                // This is because the dropdown exists outside all columns and is not recreated each time it is opened
                const column = $($(dropdown.data('trigger-button')).closest('.kanban-column'));
                // kanban-bulk-add-ITEMTYPE (We want the ITEMTYPE token at position 3)
                const itemtype = selection.prop('id').split('-')[3];

                // Force-close the full dropdown
                dropdown.css({'display': 'none'});

                self.clearAddItemForms(column);
                self.showBulkAddItemForm(column, itemtype);
                delayRefresh();
            });
            const switcher = $("select[name='kanban-board-switcher']").first();
            $(self.element + ' .kanban-toolbar').on('select2:select', switcher, function(e) {
                const items_id = e.params.data.id;
                $.ajax({
                    type: "GET",
                    url: (self.ajax_root + "kanban.php"),
                    data: {
                        action: "get_url",
                        itemtype: self.item.itemtype,
                        items_id: items_id
                    },
                    success: function(url) {
                        window.location = url;
                    }
                });
            });

            $(self.element).on('input', '.kanban-add-form input, .kanban-add-form textarea', function() {
                delayRefresh();
            });

            if (!self.rights.canOrderCard()) {
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

            $(self.element + ' .kanban-container').on('submit', '.kanban-add-form:not(.kanban-bulk-add-form)', function(e) {
                e.preventDefault();
                const form = $(e.target);
                const data = {
                    inputs: form.serialize(),
                    itemtype: form.prop('id').split('_')[2],
                    action: 'add_item'
                };
                const itemtype = form.attr('data-itemtype');
                const column_el_id = form.closest('.kanban-column').attr('id');

                $.ajax({
                    method: 'POST',
                    url: (self.ajax_root + "kanban.php"),
                    data: data
                }).done(function() {
                    // Close the form
                    form.remove();
                    self.refresh(undefined, undefined, () => {
                        // Re-open form
                        self.showAddItemForm($(`#${column_el_id}`), itemtype);
                    });
                }).always(() => {
                    $.ajax({
                        method: 'GET',
                        url: (self.ajax_root + "displayMessageAfterRedirect.php"),
                        data: {
                            'get_raw': true
                        }
                    }).done((messages) => {
                        $.each(messages, (level, level_messages) => {
                            $.each(level_messages, (index, message) => {
                                switch (parseInt(level)) {
                                    case 1:
                                        glpi_toast_error(message);
                                        break;
                                    case 2:
                                        glpi_toast_warning(message);
                                        break;
                                    default:
                                        glpi_toast_info(message);
                                }
                            });
                        });
                    });
                });
            });

            $(self.element + ' .kanban-container').on('click', '.kanban-item .kanban-item-title', function(e) {
                e.preventDefault();
                const card = $(e.target).closest('.kanban-item');
                self.showCardPanel(card);
            });
        };

        const showModal = (content, data) => {
            const modal = $('#kanban-modal');
            modal.removeData();
            modal.data(data);
            // Extract script elements from content to be manually inserted later with createElement to ensure they are executed
            // Issue is noticed when content is injected multiple times. Scripts execute the first time only.
            const scripts = $(content).find('script');
            scripts.detach();
            modal.find('.modal-body').html(content);
            scripts.each(function() {
                const script = document.createElement('script');
                script.type = 'text/javascript';
                script.text = this.innerHTML;
                modal.find('.modal-body').append(script);
            });
            modal.modal('show');
        };

        const hideModal = () => {
            $('#kanban-modal').modal('hide');
        };

        /**
       * (Re-)Create the list of columns that can be shown/hidden.
       * This involves fetching the list of valid columns from the server.
       * @since 9.5.0
       */
        const refreshAddColumnForm = function() {
            let columns_used = [];
            $(self.element + ' .kanban-columns .kanban-column').each(function() {
                const column_id = this.id.split('-');
                columns_used.push(column_id[column_id.length - 1]);
            });
            const column_dialog = $(self.add_column_form);
            const toolbar = $(self.element + ' .kanban-toolbar');
            $.ajax({
                method: 'GET',
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "list_columns",
                    itemtype: self.item.itemtype,
                    column_field: self.column_field.id
                }
            }).done(function(data) {
                // Data is sent by the server as an associative array using sorted
                // ids as property names.
                // This is unreliable as js object keys are not ordered.
                // To fix this, we'll convert data into an array which can be
                // reliably sorted.
                Object.keys(data).forEach(function(key) {
                    if (data[key].id === undefined) {
                        data[key].id = key;
                    }
                });
                let sorted_data = Object.values(data); // Cast Object to array
                const collator = new Intl.Collator(undefined, {
                    numeric: true,
                    sensitivity: 'base'
                });
                sorted_data.sort((a, b)  => collator.compare(a.name, b.name));

                const form_content = $(self.add_column_form + " .kanban-item-content");
                form_content.empty();
                form_content.append("<input type='text' class='form-control' name='column-name-filter' placeholder='" + __('Search') + "'/>");
                let list = "<ul class='kanban-columns-list'>";

                sorted_data.forEach(function(column) {
                    let list_item = "<li data-list-id='"+column.id+"'>";
                    // The `columns_used` array seems to store the ids as strings
                    // We'll check if the values exist as they are or as strings to cover both formats
                    if (column.id && (columns_used.includes(column.id) || columns_used.includes(column.id.toString()))) {
                        list_item += "<input type='checkbox' checked='true' class='form-check-input' />";
                    } else {
                        list_item += "<input type='checkbox' class='form-check-input' />";
                    }
                    if (typeof column['color_class'] !== "undefined") {
                        list_item += "<span class='kanban-color-preview "+column['color_class']+"'></span>";
                    } else {
                        list_item += "<span class='kanban-color-preview' style='background-color: "+column['header_color']+"'></span>";
                    }
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
        const refreshSortables = function() {
            $(self.element).trigger('kanban:refresh_sortables');
            // Make sure all items in the columns can be sorted
            const bodies = $(self.element + ' .kanban-body');
            $.each(bodies, function(b) {
                const body = $(b);
                if (body.data('sortable')) {
                    sortable(b, 'destroy');
                }
            });

            sortable(self.element + ' .kanban-body', {
                acceptFrom: '.kanban-body',
                items: '.kanban-item:not(.readonly):not(.temporarily-readonly):not(.filtered-out)',
            });

            $(self.element + ' .kanban-body').off('sortstart');
            $(self.element + ' .kanban-body').on('sortstart', (e) => {
                self.is_sorting_active = true;

                const card = $(e.detail.item);
                // Track the column and position the card was picked up from
                const current_column = card.closest('.kanban-column').attr('id');
                card.data('source-col', current_column);
                card.data('source-pos', e.detail.origin.index);

                self.sort_data = {
                    card_id: card.attr('id'),
                    source_column: current_column,
                    source_position: e.detail.origin.index
                };
            });

            $(self.element + ' .kanban-body').off('sortupdate');
            $(self.element + ' .kanban-body').on('sortupdate', function(e) {
                const card = e.detail.item;
                if (this === $(card).parent()[0]) {
                    return self.onKanbanCardSort(e, this);
                }
            });

            $(self.element + ' .kanban-body').off('sortstop');
            $(self.element + ' .kanban-body').on('sortstop', (e) => {
                self.is_sorting_active = false;
                $(e.detail.item).closest('.kanban-column').trigger('mouseenter'); // force readonly states refresh
            });

            if (self.rights.canModifyView()) {
            // Enable column sorting
                sortable(self.element + ' .kanban-columns', {
                    acceptFrom: self.element + ' .kanban-columns',
                    appendTo: '.kanban-container',
                    items: '.kanban-column:not(.kanban-protected)',
                    handle: '.kanban-column-header',
                    orientation: 'horizontal',
                });
                $(self.element + ' .kanban-columns .kanban-column:not(.kanban-protected) .kanban-column-header').addClass('grab');
            }

            $(self.element + ' .kanban-columns').off('sortstop');
            $(self.element + ' .kanban-columns').on('sortstop', (e) => {
                const column = e.detail.item;
                updateColumnPosition(getColumnIDFromElement(column), $(column).index());
            });
        };

        /**
       * Construct and return the toolbar HTML for a specified column.
       * @since 9.5.0
       * @param {Object} column Column object that this toolbar will be made for.
       * @returns {string} HTML coded for the toolbar.
       */
        const getColumnToolbarElement = function(column) {
            let toolbar_el = "<span class='kanban-column-toolbar'>";
            const column_id = parseInt(getColumnIDFromElement(column['id']));
            if (self.rights.canCreateItem() && (self.rights.getAllowedColumnsForNewCards().length === 0 || self.rights.getAllowedColumnsForNewCards().includes(column_id))) {
                toolbar_el += "<i id='kanban_add_" + column['id'] + "' class='kanban-add btn btn-sm btn-ghost-secondary fas fa-plus' title='" + __('Add') + "'></i>";
                toolbar_el += "<i id='kanban_column_overflow_actions_" + column['id'] +"' class='kanban-column-overflow-actions btn btn-sm btn-ghost-secondary fas fa-ellipsis-h' title='" + __('More') + "'></i>";
            }
            toolbar_el += "</span>";
            return toolbar_el;
        };

        /**
       * Hide all columns that don't have a card in them.
       * @since 9.5.0
      **/
        this.hideEmpty = function() {
            const bodies = $(".kanban-body");
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
            const columns = $(".kanban-column");
            columns.each(function(index, item) {
                item.style.display = "block";
            });
        };

        /**
       * Callback function for when a kanban item is moved.
       * @since 9.5.0
       * @param {Object}  e      Event.
       * @param {Element} sortable Sortable object
       * @returns {Boolean}       Returns false if the sort was cancelled.
      **/
        this.onKanbanCardSort = function(e, sortable) {
            const target = sortable.parentElement;
            const source = $(e.detail.origin.container);
            const card = $(e.detail.item);
            const el_params = card.attr('id').split('-');
            const target_params = $(target).attr('id').split('-');
            const column_id = target_params[target_params.length - 1];

            if (el_params.length === 2 && source !== null && !(!self.rights.canOrderCard() && source.length === 0)) {
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
                    error: function() {
                        window.sortable(sortable, 'cancel');
                        return false;
                    },
                    success: function() {
                        let pos = card.data('current-pos');
                        if (!self.rights.canOrderCard()) {
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
                window.sortable(sortable, 'cancel');
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
        const updateCardPosition = function(card, column, position, error, success) {
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
                error: function() {
                    if (error) {
                        error();
                    }
                },
                success: function() {
                    if (success) {
                        success();
                        $('#'+card).trigger('kanban:card_move');
                    }
                }
            });
        };

        /**
       * Delete a card
       * @since 10.0.0
       * @param {string} card The ID of the card being deleted.
       * @param {function} error Callback function called when the server reports an error.
       * @param {function} success Callback function called when the server processes the request successfully.
       */
        const deleteCard = function(card, error, success) {
            const [itemtype, items_id] = card.split('-', 2);
            const card_obj = $('#'+card);
            const force = card_obj.hasClass('deleted');
            $.ajax({
                type: "POST",
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "delete_item",
                    itemtype: itemtype,
                    items_id: items_id,
                    force: force ? 1 : 0
                },
                error: function() {
                    if (error) {
                        error();
                    }
                },
                success: function() {
                    const column = card_obj.closest('.kanban-column');
                    card_obj.remove();
                    self.updateColumnCount(column);
                    if (success) {
                        success();
                        $('#'+card).trigger('kanban:card_delete');
                    }
                }
            });
        };

        /**
         * Restore a trashed card
         * @param {string} card The ID of the card being restored.
         * @param {function} error Callback function called when the server reports an error.
         * @param {function} success Callback function called when the server processes the request successfully.
         */
        const restoreCard = function(card, error, success) {
            const [itemtype, items_id] = card.split('-', 2);
            const card_obj = $('#'+card);
            $.ajax({
                type: "POST",
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "restore_item",
                    itemtype: itemtype,
                    items_id: items_id,
                },
                error: function() {
                    if (error) {
                        error();
                    }
                },
                success: function() {
                    card_obj.data('is_deleted', false);
                    card_obj.removeClass('deleted');
                    if (success) {
                        success();
                        $('#'+card).trigger('kanban:card_restore');
                    }
                }
            });
        };

        /**
       * Show the column and notify the server of the change.
       * @since 9.5.0
       * @param {number} column The ID of the column.
       */
        const showColumn = function(column) {
            $.ajax({
                type: "POST",
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "show_column",
                    column: column,
                    kanban: self.item
                },
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
        const hideColumn = function(column) {
            $.ajax({
                type: "POST",
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "hide_column",
                    column: column,
                    kanban: self.item
                },
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
        const updateColumnPosition = function(column, position) {
            $.ajax({
                type: "POST",
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "move_column",
                    column: column,
                    position: position,
                    kanban: self.item
                }
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
        const getTeamBadge = function(teammember) {
            const itemtype = teammember["itemtype"];
            const items_id = teammember["id"];

            // If the picture is already cached, return cache value
            if (
                self.team_badge_cache[itemtype] !== undefined &&
                self.team_badge_cache[itemtype][items_id] !== undefined
            ) {
                return self.team_badge_cache[itemtype][items_id];
            }

            // Pictures from users
            if (itemtype === 'User') {
                // Display a placeholder and keep track of the image to load it later
                self.user_pictures_to_load.add(items_id);
                self.team_badge_cache[itemtype][items_id] = generateUserBadge(teammember);

                return self.team_badge_cache[itemtype][items_id];
            }

            // Pictures from groups, supplier, contact
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
            return self.team_badge_cache[itemtype][items_id];
        };

        const fetchUserPicturesToLoad = function() {
            // Get user ids for which we must load their pictures
            const users_ids = Array.from(self.user_pictures_to_load.values());

            if (users_ids.length === 0) {
                // Nothing to be loaded
                return;
            }

            // Clear "to load" list
            self.user_pictures_to_load.clear();

            $.ajax({
                type: 'POST', // Too much data may break GET limit
                url: (self.ajax_root + "getUserPicture.php"),
                data: {
                    users_id: users_ids,
                    size: self.team_image_size,
                }
            }).done(function(data) {
                // For each users, apply the image found
                Object.keys(users_ids).forEach(function(user_id) {
                    if (data[user_id] !== undefined) {
                        // Store new image in cache
                        self.team_badge_cache['User'][user_id] = "<span>" + data[user_id] + "</span>";

                        // Replace placeholders
                        $("[data-placeholder-users-id=" + user_id + "]").each(function() {
                            $(this).parent().html(self.team_badge_cache['User'][user_id]);
                        });
                    }
                });
            });
        };

        /**
       * Convert the given H, S, L values into a color hex code (with prepended hash symbol).
       * @param {number} h Hue
       * @param {number} s Saturation
       * @param {number} l Lightness
       * @returns {string} Hex code color value
       */
        const hslToHexColor = function(h, s, l) {
            let r, g, b;

            if (s === 0) {
                r = g = b = l;
            } else {
                const hue2rgb = function hue2rgb(p, q, t){
                    if (t < 0)
                        t += 1;
                    if (t > 1)
                        t -= 1;
                    if (t < 1/6)
                        return p + (q - p) * 6 * t;
                    if (t < 1/2)
                        return q;
                    if (t < 2/3)
                        return p + (q - p) * (2/3 - t) * 6;
                    return p;
                };

                const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
                const p = 2 * l - q;
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
        const getBadgeColor = function(teammember) {
            let cached_colors = JSON.parse(window.sessionStorage.getItem('badge_colors'));
            const itemtype = teammember['itemtype'];
            const baseColor = Math.random();
            const lightness = (Math.random() * 10) + (self.dark_theme ? 25 : 70);
            //var bg_color = "hsl(" + baseColor + ", 100%," + lightness + "%,1)";
            let bg_color = hslToHexColor(baseColor, 1, lightness / 100);

            if (cached_colors !== null && cached_colors[itemtype] !== null && cached_colors[itemtype][teammember['id']]) {
                bg_color = cached_colors[itemtype][teammember['id']];
            } else {
                if (cached_colors === null) {
                    cached_colors = {
                        User: {},
                        Group: {},
                        Supplier: {},
                        Contact: {},
                        _dark_theme: self.dark_theme
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
       * @param {{}} teammember The teammember array/object that represents the user.
       * @return {string} HTML image of the generated user badge.
       */
        const generateUserBadge = function(teammember) {
            let initials = "";
            if (teammember["firstname"]) {
                initials += teammember["firstname"][0];
            }
            if (teammember["realname"]) {
                initials += teammember["realname"][0];
            }
            // Force uppercase initals
            initials = initials.toUpperCase();

            if (!self.display_initials || initials.length === 0) {
                return generateOtherBadge(teammember, 'fa-user');
            }

            const canvas = document.createElement('canvas');
            canvas.width = self.team_image_size;
            canvas.height = self.team_image_size;
            const context = canvas.getContext('2d');
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
            const src = canvas.toDataURL("image/png");
            const name = teammember['name'].replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            return "<span><img src='" + src + "' title='" + name + "' data-bs-toggle='tooltip' data-placeholder-users-id='" + teammember["id"] + "'/></span>";
        };

        /**
       * Generate team member icon based on its name and a FontAwesome icon.
       * @since 9.5.0
       * @param {Object} teammember The team member data.
       * @param {string} icon FontAwesome icon to use for this badge.
       * @returns {string} HTML icon of the generated badge.
       */
        const generateOtherBadge = function(teammember, icon) {
            const bg_color = getBadgeColor(teammember);
            const name = teammember['name'].replace(/"/g, '&quot;').replace(/'/g, '&#39;');

            return `
            <span class='fa-stack fa-lg' style='font-size: ${(self.team_image_size / 2)}px'>
                <i class='fas fa-circle fa-stack-2x' style="color: ${bg_color}" title="${teammember['name']}"></i>
                <i class='fas ${icon} fa-stack-1x' title="${name}" data-bs-toggle='tooltip'></i>
            </span>
         `;
        };

        /**
       * Generate a badge to indicate that 'overflow_count' number of team members are not shown on the Kanban item.
       * @since 9.5.0
       * @param {number} overflow_count Number of members without badges on the Kanban item.
       * @returns {string} HTML image of the generated overflow badge.
       */
        const generateOverflowBadge = function(overflow_count) {
            const canvas = document.createElement('canvas');
            canvas.width = self.team_image_size;
            canvas.height = self.team_image_size;
            const context = canvas.getContext('2d');
            context.strokeStyle = "#f1f1f1";

            // Create fill color based on theme type
            const lightness = (self.dark_theme ? 40 : 80);
            context.fillStyle = "hsl(255, 0%," + lightness + "%,1)";
            context.beginPath();
            context.arc(self.team_image_size / 2, self.team_image_size / 2, self.team_image_size / 2, 0, 2 * Math.PI);
            context.fill();
            context.fillStyle = self.dark_theme ? 'white' : 'black';
            context.textAlign = 'center';
            context.font = 'bold ' + (self.team_image_size / 2) + 'px sans-serif';
            context.textBaseline = 'middle';
            context.fillText("+" + overflow_count, self.team_image_size / 2, self.team_image_size / 2);
            const src = canvas.toDataURL("image/png");
            return "<span><img src='" + src + "' title='" + __('%d other team members').replace('%d', overflow_count) + "' data-bs-toggle='tooltip'/></span>";
        };

        /**
       * Check if the provided color is more light or dark.
       * This function converts the given hex value into HSL and checks the L value.
       * @since 9.5.0
       * @param hex Hex code of the color. It may or may not contain the beginning '#'.
       * @returns {boolean} True if the color is more light.
       */
        const isLightColor = function(hex) {
            const c = hex.startsWith('#') ? hex.substring(1) : hex;
            const rgb = parseInt(c, 16);
            const r = (rgb >> 16) & 0xff;
            const g = (rgb >>  8) & 0xff;
            const b = (rgb >>  0) & 0xff;
            const lightness = 0.2126 * r + 0.7152 * g + 0.0722 * b;
            return lightness > 110;
        };

        /**
       * Convert a CSS RGB or RGBA string to a hex string including the '#' character.
       * @param {string} rgb The RGB or RGBA string
       * @returns {string} The hex color string
       */
        const rgbToHex = function(rgb) {
            const pattern = /^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+\.?\d*))?\)$/;
            const hex = rgb.match(pattern).slice(1).map((n, i) => (i === 3 ? Math.round(parseFloat(n) * 255) : parseFloat(n))
                .toString(16).padStart(2, '0') // Convert to hex values
                .replace('NaN', '') // Handle NaN values
            ).join('');
            return `#${hex}`;
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
            const column_body = $(column_el).find('.kanban-body:first');
            const counter = $(column_el).find('.kanban_nb:first');
            // Get all visible kanban items. This ensures the count is correct when items are filtered out.
            const items = column_body.find('li:not(.filtered-out)');
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

            const uniqueID = Math.floor(Math.random() * 999999);
            const formID = "form_add_" + itemtype + "_" + uniqueID;
            let add_form = `<form id="${formID}" class="kanban-add-form card kanban-form no-track" data-itemtype="${itemtype}">`;
            let form_header = "<div class='kanban-item-header d-flex justify-content-between'>";
            form_header += `
            <span class='kanban-item-title'>
               <i class="${self.supported_itemtypes[itemtype]['icon']}"></i>
               ${self.supported_itemtypes[itemtype]['name']}
            </span>`;
            form_header += `<i class="ti ti-x cursor-pointer" title="${__('Close')}" onclick="$(this).parent().parent().remove()"></i></div>`;
            add_form += form_header;

            add_form += "<div class='kanban-item-content'>";
            $.each(self.supported_itemtypes[itemtype]['fields'], function(name, options) {
                const input_type = options['type'] !== undefined ? options['type'] : 'text';
                const value = options['value'] !== undefined ? options['value'] : '';

                if (input_type.toLowerCase() === 'textarea') {
                    add_form += "<textarea class='form-control' name='" + name + "'";
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
                    add_form += "<input class='form-control' type='" + input_type + "' name='" + name + "'";
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
            add_form += "<input type='hidden' name='" + self.column_field.id + "' value='" + column_value + "'/>";
            add_form += "<input type='submit' value='" + __('Add') + "' name='add' class='btn btn-primary'/>";
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

            const uniqueID = Math.floor(Math.random() * 999999);
            const formID = "form_add_" + itemtype + "_" + uniqueID;
            let add_form = "<form id='" + formID + "' class='kanban-add-form kanban-bulk-add-form kanban-form no-track'>";

            add_form += `
            <div class='kanban-item-header'>
                <span class='kanban-item-title'>
                   <i class="${self.supported_itemtypes[itemtype]['icon']}"></i>
                   ${self.supported_itemtypes[itemtype]['name']}
                </span>
                <i class='ti ti-x' title='Close' onclick='$(this).parent().parent().remove()'></i>
                <div>
                    <span class="kanban-item-subtitle">${__("One item per line")}</span>
                 </div>
           </div>
         `;

            add_form += "<div class='kanban-item-content'>";
            $.each(self.supported_itemtypes[itemtype]['fields'], function(name, options) {
                const input_type = options['type'] !== undefined ? options['type'] : 'text';
                const value = options['value'] !== undefined ? options['value'] : '';

                // We want to include all hidden fields as they are usually mandatory (project ID)
                if (input_type === 'hidden') {
                    add_form += "<input type='hidden' name='" + name + "'";
                    if (value !== undefined) {
                        add_form += " value='" + value + "'";
                    }
                    add_form += "/>";
                } else if (input_type.toLowerCase() === 'raw') {
                    add_form += value;
                }
            });
            add_form += "<textarea name='bulk_item_list'></textarea>";
            add_form += "</div>";

            const column_id_elements = column_el.prop('id').split('-');
            const column_value = column_id_elements[column_id_elements.length - 1];
            add_form += "<input type='hidden' name='" + self.column_field.id + "' value='" + column_value + "'/>";
            add_form += "<input type='submit' value='" + __('Add') + "' name='add' class='submit'/>";
            add_form += "</form>";
            $(column_el.find('.kanban-body')[0]).append(add_form);
            $('#' + formID).get(0).scrollIntoView(false);
            $("#" + formID).on('submit', function(e) {
                e.preventDefault();
                const form = $(e.target);
                const data = {
                    inputs: form.serialize(),
                    itemtype: form.prop('id').split('_')[2],
                    action: 'bulk_add_item'
                };

                $.ajax({
                    method: 'POST',
                    url: (self.ajax_root + "kanban.php"),
                    data: data
                }).done(function() {
                    $('#'+formID).remove();
                    self.refresh();
                }).always(() => {
                    $.ajax({
                        method: 'GET',
                        url: (self.ajax_root + "displayMessageAfterRedirect.php"),
                        data: {
                            'get_raw': true
                        }
                    }).done((messages) => {
                        $.each(messages, (level, level_messages) => {
                            $.each(level_messages, (index, message) => {
                                switch (parseInt(level)) {
                                    case 1:
                                        glpi_toast_error(message);
                                        break;
                                    case 2:
                                        glpi_toast_warning(message);
                                        break;
                                    default:
                                        glpi_toast_info(message);
                                }
                            });
                        });
                    });
                });
            });
        };

        /**
       * Create the add column form and add it to the DOM.
       * @since 9.5.0
       */
        const buildAddColumnForm = function() {
            const uniqueID = Math.floor(Math.random() * 999999);
            const formID = "form_add_column_" + uniqueID;
            self.add_column_form = '#' + formID;
            let add_form = `
            <div id="${formID}" class="kanban-form kanban-add-column-form dropdown-menu" style="display: none">
                <form class='no-track'>
                    <div class='kanban-item-header'>
                        <span class='kanban-item-title'>${__('Add a column from existing status')}</span>
                    </div>
                    <div class='kanban-item-content'></div>
         `;
            if (self.rights.canCreateColumn()) {
                add_form += `
               <hr>${__('Or add a new status')}
               <button class='btn btn-primary kanban-create-column d-block'>${__('Create status')}</button>
            `;
            }
            add_form += "</form></div>";
            $(self.element).prepend(add_form);
        };

        /**
       * Create the create column form and add it to the DOM.
       * @since 9.5.0
       */
        const buildCreateColumnForm = function() {
            const uniqueID = Math.floor(Math.random() * 999999);
            const formID = "form_create_column_" + uniqueID;
            self.create_column_form = '#' + formID;
            let create_form = `
            <div id='${formID}' class='kanban-form kanban-create-column-form dropdown-menu' style='display: none'>
                <form class='no-track'>
                    <div class='kanban-item-header'>
                        <span class='kanban-item-title'>${__('Create status')}</span>
                    </div>
                    <div class='kanban-item-content'>
                    <input name='name' class='form-control'/>
         `;
            $.each(self.column_field.extra_fields, function(name, field) {
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
            create_form += "<button type='submit' class='btn btn-primary'>" + __('Create status') + "</button>";
            create_form += "</form></div>";
            $(self.element).prepend(create_form);
        };

        /**
       * Delay the background refresh for a short amount of time.
       * This should be called any time the user is in the middle of an action so that the refresh is not disruptive.
       * @since 9.5.0
       */
        const delayRefresh = function() {
            window.clearTimeout(_backgroundRefreshTimer);
            _backgroundRefreshTimer = window.setTimeout(_backgroundRefresh, 10000);
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
            const _refresh = function() {
                $.ajax({
                    method: 'GET',
                    url: (self.ajax_root + "kanban.php"),
                    data: {
                        action: "refresh",
                        itemtype: self.item.itemtype,
                        items_id: self.item.items_id,
                        column_field: self.column_field.id
                    }
                }).done(function(columns, textStatus, jqXHR) {
                    clearColumns();
                    self.columns = columns;
                    fillColumns();
                    // Re-filter kanban
                    self.filter();
                    if (success) {
                        success(columns, textStatus, jqXHR);
                        $(self.element).trigger('kanban:refresh');
                    }
                    fetchUserPicturesToLoad();
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
        const appendColumn = function(column_id, column, columns_container, revalidate) {
            if (columns_container == null) {
                columns_container = $(self.element + " .kanban-container .kanban-columns").first();
            }
            revalidate = revalidate !== undefined ? revalidate : false;

            column['id'] = "column-" + self.column_field.id + '-' + column_id;
            let collapse = '';
            let position = -1;
            $.each(self.user_state.state, function(order, s_column) {
                if (parseInt(s_column['column']) === parseInt(column_id)) {
                    position = order;
                    if (s_column['folded'] === true || s_column['folded'] === 'true') {
                        collapse = 'collapsed';
                        return false;
                    }
                }
            });
            const _protected = column['_protected'] ? 'kanban-protected' : '';
            const column_classes = "kanban-column card " + collapse + " " + _protected;

            const column_top_color = (typeof column['header_color'] !== 'undefined') ? column['header_color'] : '';
            const column_html = "<div id='" + column['id'] + "' style='border-top-color: "+column_top_color+"' class='"+column_classes+"'></div>";
            let column_el = null;
            if (position < 0) {
                column_el = $(column_html).appendTo(columns_container);
            } else {
                const prev_column = $(columns_container).find('.kanban-column:nth-child(' + (position) + ')');
                if (prev_column.length === 1) {
                    column_el = $(column_html).insertAfter(prev_column);
                } else {
                    column_el = $(column_html).appendTo(columns_container);
                }
            }
            const cards = column['items'] !== undefined ? column['items'] : [];

            const column_header = $("<header class='kanban-column-header'></header>");
            const column_content = $("<div class='kanban-column-header-content'></div>").appendTo(column_header);
            const count = column['items'] !== undefined ? column['items'].length : 0;
            const column_left = $("<span class=''></span>").appendTo(column_content);
            const column_right = $("<span class=''></span>").appendTo(column_content);
            if (self.rights.canModifyView()) {
                $(column_left).append("<i class='fas fa-caret-right fa-lg kanban-collapse-column btn btn-sm btn-ghost-secondary' title='" + __('Toggle collapse') + "'/>");
            }
            $(column_left).append("<span class='kanban-column-title badge "+(column['color_class'] || '')+"' style='background-color: "+column['header_color']+"; color: "+column['header_fg_color']+";'>" + column['name'] + "</span></span>");
            $(column_right).append("<span class='kanban_nb badge bg-secondary'>"+count+"</span>");
            $(column_right).append(getColumnToolbarElement(column));
            $(column_el).prepend(column_header);
            // Re-apply header text color to handle the actual background color now that the element is actually in the DOM.
            const column_title = $('#'+column['id']).find('.kanban-column-title').eq(0);
            let header_color = column_title.css('background-color') ? rgbToHex(column_title.css('background-color')) : '#ffffff';
            const is_header_light = header_color ? isLightColor(header_color) : !self.dark_theme;
            const header_text_class = is_header_light ? 'kanban-text-dark' : 'kanban-text-light';
            column_title.removeClass('kanban-text-light kanban-text-dark');
            column_title.addClass(header_text_class);

            const column_body = $("<ul class='kanban-body card-body'></ul>").appendTo(column_el);

            column_el.attr('data-drop-only', column['drop_only']);

            if (!column['drop_only']) {
                let added = [];
                $.each(self.user_state.state, function (i, c) {
                    if (c['column'] === column_id) {
                        $.each(c['cards'], function (i2, card) {
                            $.each(cards, function (i3, card2) {
                                if (card2['id'] === card) {
                                    appendCard(column_el, card2);
                                    added.push(card2['id']);
                                    return false;
                                }
                            });
                        });
                    }
                });

                $.each(cards, function (card_id, card) {
                    if (added.indexOf(card['id']) < 0) {
                        appendCard(column_el, card, revalidate);
                    }
                });
            } else {
                $(`
               <li class="position-relative mx-auto mt-2" style="width: 250px">
                  ${__('This column cannot support showing cards due to how many cards would be shown. You can still drag cards into this column.')}
               </li>
            `).appendTo(column_body);
            }

            refreshSortables();
            self.filter();
        };

        /**
       * Append the card in the specified column, handle duplicate cards in case the card moved, generate badges, and update column counts.
       * @since 9.5.0
       * @param {Element|string} column_el The column to add the card to.
       * @param {Object} card The card to append.
       * @param {boolean} revalidate Check for duplicate cards.
       */
        const appendCard = function(column_el, card, revalidate = false) {
            if (revalidate) {
                const existing = $('#' + card['id']);
                if (existing !== undefined) {
                    const existing_column = existing.closest('.kanban-column');
                    existing.remove();
                    self.updateColumnCount(existing_column);
                }
            }

            const itemtype = card['id'].split('-')[0];
            const col_body = $(column_el).find('.kanban-body').first();
            const readonly = card['_readonly'] !== undefined && (card['_readonly'] === true || card['_readonly'] === 1);
            let card_el = `
            <li id="${card['id']}" class="kanban-item card ${readonly ? 'readonly' : ''}">
                <div class="kanban-item-header">
                    <span class="kanban-item-title" title="${card['title_tooltip']}">
                    <i class="${self.supported_itemtypes[itemtype]['icon']}"></i>
                        ${card['title']}
                    </span>
                    <i class="kanban-item-overflow-actions fas fa-ellipsis-h btn btn-sm btn-ghost-secondary"></i>
                </div>
                <div class="kanban-item-content">${(card['content'] || '')}</div>
                <div class="kanban-item-team">
         `;
            const team_count = Object.keys(card['_team']).length;
            if (card["_team"] !== undefined && team_count > 0) {
                $.each(Object.values(card["_team"]).slice(0, self.max_team_images), function(teammember_id, teammember) {
                    card_el += getTeamBadge(teammember);
                });
                if (card["_team"].length > self.max_team_images) {
                    card_el += generateOverflowBadge(team_count - self.max_team_images);
                }
            }
            card_el += "</div></li>";
            const card_obj = $(card_el).appendTo(col_body);
            card_obj.data('form_link', card['_form_link'] || undefined);
            if (card['_metadata']) {
                $.each(card['_metadata'], (k, v) => {
                    card_obj.data(k, v);
                });
                if (card_obj.data('is_deleted')) {
                    card_obj.addClass('deleted');
                }
            }
            card_obj.data('_team', card['_team']);
            self.updateColumnCount(column_el);
        };

        this.refreshSearchTokenizer = () => {
            self.filter_input.tokenizer.clearAutocomplete();

            // Refresh core tags autocomplete
            self.filter_input.tokenizer.setAutocomplete('type', Object.keys(self.supported_itemtypes).map(k => `<i class="${self.supported_itemtypes[k].icon} me-1"></i>` + k));
            self.filter_input.tokenizer.setAutocomplete('milestone', ["true", "false"]);
            self.filter_input.tokenizer.setAutocomplete('deleted', ["true", "false"]);

            $(self.element).trigger('kanban:refresh_tokenizer', self.filter_input.tokenizer);
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
            $(self.element).trigger('kanban:pre_filter', self.filters);
            // Unhide all items in case they are no longer filtered
            self.clearFiltered();

            $(self.element + ' .kanban-item').each(function(i, item) {
                const card = $(item);
                let shown = true;
                const title = card.find("span.kanban-item-title").text().trim();

                const filter_text = (filter_data, target, matchers = ['regex', 'includes']) => {
                    if (filter_data.prefix === '#' && matchers.includes('regex')) {
                        return filter_regex_match(filter_data, target);
                    } else {
                        if (matchers.includes('includes')) {
                            filter_include(filter_data, target);
                        }
                        if (matchers.includes('equals')) {
                            filter_equal(filter_data, target);
                        }
                    }
                };

                const filter_include = (filter_data, haystack) => {
                    if ((!haystack.toLowerCase().includes(filter_data.term.toLowerCase())) !== filter_data.exclusion) {
                        shown = false;
                    }
                };

                const filter_equal = (filter_data, target) => {
                    if ((target != filter_data.term) !== filter_data.exclusion) {
                        shown = false;
                    }
                };

                const filter_regex_match = (filter_data, target) => {
                    try {
                        if ((!target.trim().match(filter_data.term)) !== filter_data.exclusion) {
                            shown = false;
                        }
                    } catch (e) {
                        // Invalid regex
                        glpi_toast_error(
                            __('The regular expression you entered is invalid. Please check it and try again.'),
                            __('Invalid regular expression')
                        );
                    }
                };

                const filter_teammember = (filter_data, itemtype) => {
                    const team_members = card.data('_team');
                    let has_matching_member = false;
                    $.each(team_members, (i, m) => {
                        if (m.itemtype === itemtype && (m.name.toLowerCase().includes(filter_data.term.toLowerCase()) !== filter_data.exclusion)) {
                            has_matching_member = true;
                        }
                    });
                    if (!has_matching_member) {
                        shown = false;
                    }
                };

                const filter_boolean = (filter_data, target) => {
                    const negative_values = ['false', 'no', '0', 0, false, undefined];
                    const negative_filter = negative_values.includes(typeof filter_data.term === 'string' ? filter_data.term.toLowerCase() : filter_data.term);
                    const negative_target = negative_values.includes(typeof target === 'string' ? target.toLowerCase() : target);
                    if ((negative_target !== negative_filter) !== filter_data.exclusion) {
                        shown = false;
                    }
                };

                if (self.filters._text) {
                    try {
                        if (!title.match(new RegExp(self.filters._text, 'i'))) {
                            shown = false;
                        }
                    } catch (err) {
                        // Probably not a valid regular expression. Use simple contains matching.
                        if (!title.toLowerCase().includes(self.filters._text.toLowerCase())) {
                            shown = false;
                        }
                    }
                }

                if (self.filters.deleted !== undefined) {
                    filter_boolean(self.filters.deleted, card.data('is_deleted'));
                }

                if (self.filters.title !== undefined) {
                    filter_text(self.filters.title, title);
                }

                if (self.filters.type !== undefined) {
                    filter_text(self.filters.type, card.attr('id').split('-')[0], ['regex', 'equals']);
                }

                if (self.filters.milestone !== undefined) {
                    filter_boolean(self.filters.milestone, card.data('is_milestone'));
                }

                if (self.filters.category !== undefined) {
                    filter_text(self.filters.category, card.data('category'));
                }

                if (self.filters.content !== undefined) {
                    filter_text(self.filters.content, card.data('content'));
                }

                if (self.filters.team !== undefined) {
                    const team_search = self.filters.team.term.toLowerCase();
                    const team_members = card.data('_team');
                    let has_matching_member = false;
                    $.each(team_members, (i, m) => {
                        if (m.name.toLowerCase().includes(team_search)) {
                            has_matching_member = true;
                        }
                    });
                    if (!has_matching_member) {
                        shown = false;
                    }
                }

                if (self.filters.user !== undefined) {
                    filter_teammember(self.filters.user, 'User');
                }

                if (self.filters.group !== undefined) {
                    filter_teammember(self.filters.group, 'Group');
                }

                if (self.filters.supplier !== undefined) {
                    filter_teammember(self.filters.supplier, 'Supplier');
                }

                if (self.filters.contact !== undefined) {
                    filter_teammember(self.filters.contact, 'Contact');
                }

                if (!shown) {
                    card.addClass('filtered-out');
                }
            });

            $(self.element).trigger('kanban:filter', {
                filters: self.filters,
                kanban_element: self.element
            });

            // Update column counters
            $(self.element + ' .kanban-column').each(function(i, column) {
                self.updateColumnCount(column);
            });
            $(self.element).trigger('kanban:post_filter', self.filters);
        };

        /**
       * Toggle the collapsed state of the specified column.
       * After toggling the collapse state, the server is notified of the change.
       * @since 9.5.0
       * @param {string|Element|jQuery} column_el The column element or object.
       */
        this.toggleCollapseColumn = function(column_el) {
            if (!(column_el instanceof jQuery)) {
                column_el = $(column_el);
            }
            column_el.toggleClass('collapsed');
            const action = column_el.hasClass('collapsed') ? 'collapse_column' : 'expand_column';
            $.ajax({
                type: "POST",
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: action,
                    column: getColumnIDFromElement(column_el),
                    kanban: self.item
                }
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
        const loadColumn = async function(column_id, nosave, revalidate, callback = undefined) {
            nosave = nosave !== undefined ? nosave : false;

            let skip_load = false;
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

            try {
                const column = await $.ajax({
                    method: 'GET',
                    url: (self.ajax_root + "kanban.php"),
                    data: {
                        action: "get_column",
                        itemtype: self.item.itemtype,
                        items_id: self.item.items_id,
                        column_field: self.column_field.id,
                        column_id: column_id
                    }
                });

                if (column !== undefined && Object.keys(column).length > 0) {
                    self.columns[column_id] = column[column_id];
                    appendColumn(column_id, self.columns[column_id], null, revalidate);
                }
            } finally {
                if (callback) {
                    callback();
                }
            }
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
        const createColumn = function(name, params, callback) {
            if (name === undefined || name.length === 0) {
                if (callback) {
                    callback();
                }
                return;
            }
            $.ajax({
                method: 'POST',
                url: (self.ajax_root + "kanban.php"),
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
       * This should only be done if there is no state stored on the server, so one needs to be built.
       * Do NOT use this for changes to the state such as moving cards/columns!
       * @since 9.5.0
       */
        const updateColumnState = function() {
            const new_state = {
                is_dirty: true,
                state: {}
            };
            $(self.element + " .kanban-column").each(function(i, element) {
                const column = $(element);
                const element_id = column.prop('id').split('-');
                const column_id = element_id[element_id.length - 1];
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

        this.showCardPanel = (card) => {
            if (!card) {
                $('.item-details-panel').remove();
            }
            const [itemtype, items_id] = card.prop('id').split('-');
            $.ajax({
                method: 'GET',
                url: (self.ajax_root + "kanban.php"),
                data: {
                    itemtype: itemtype,
                    items_id: items_id,
                    action: 'load_item_panel'
                }
            }).done((result) => {
                $('.item-details-panel').remove();
                $(self.element).append($(result));
                $('.item-details-panel').data('card', card);
                // Load badges
                $('.item-details-panel ul.team-list li').each((i, l) => {
                    l = $(l);
                    const member_itemtype = l.attr('data-itemtype');
                    const member_items_id = l.attr('data-items_id');
                    let member_item = getTeamBadge({
                        itemtype: member_itemtype,
                        id: member_items_id,
                        name: l.attr('data-name'),
                        realname: l.attr('data-realname'),
                        firstname: l.attr('data-firstname')
                    });
                    l.append(`
                     <div class="member-details">
                        ${member_item}
                        ${escapeMarkupText(l.attr('data-name')) || `${member_itemtype} (${member_items_id})`}
                     </div>
                     <button type="button" name="delete" class="btn btn-ghost-danger">
                        <i class="ti ti-x" title="${__('Delete')}"></i>
                     </button>
                  `);
                });
            });

            $(self.element).on('click', '.item-details-panel ul.team-list button[name="delete"]', (e) => {
                const list_item = $(e.target).closest('li');
                const member_itemtype = list_item.attr('data-itemtype');
                const member_items_id = list_item.attr('data-items_id');
                const panel = $(e.target).closest('.item-details-panel');
                const itemtype = panel.attr('data-itemtype');
                const items_id = panel.attr('data-items_id');
                const role = list_item.closest('.list-group').attr('data-role');

                if (itemtype && items_id) {
                    removeTeamMember(itemtype, items_id, member_itemtype, member_items_id, role);
                    list_item.remove();
                }
            });
        };

        this.showTeamModal = (card_el) => {
            const [card_itemtype, card_items_id] = card_el.prop('id').split('-', 2);
            let content = '';
            const modal = $('#kanban-modal');
            // Remove old click handlers
            modal.off('click', 'button[name="add"]');
            modal.off('click', 'button[name="delete"]');

            modal.on('click', 'button[name="add"]', () => {
                $('.actor_entry').each(function() {
                    let itemtype = $(this).data('itemtype');
                    let items_id = $(this).data('items-id');
                    let role = $(this).data('actortype');
                    if (itemtype && items_id) {
                        addTeamMember(card_itemtype, card_items_id, itemtype, items_id, role).done(() => {
                            self.showCardPanel($(`#${card_itemtype}-${card_items_id}`));
                        });
                    }
                });
                hideModal();
            });
            modal.on('click', 'button[name="delete"]', (e) => {
                const list_item = $(e.target).closest('li');
                const itemtype = list_item.attr('data-itemtype');
                const items_id = list_item.attr('data-items-id');
                const role = list_item.closest('ul').attr('data-role');

                if (itemtype && items_id) {
                    removeTeamMember(card_itemtype, card_items_id, itemtype, items_id, role).done(() => {
                        self.showCardPanel($(`#${card_itemtype}-${card_items_id}`));
                    });
                    list_item.remove();
                }
            });
            $.ajax({
                method: 'GET',
                url: (self.ajax_root + "kanban.php"),
                data: {
                    itemtype: card_itemtype,
                    items_id: card_items_id,
                    action: 'load_teammember_form'
                }
            }).done((result) => {
                const teammember_types_dropdown = $(`#kanban-teammember-item-dropdown-${card_itemtype}`).html();
                content += `
                    ${teammember_types_dropdown}
                    ${result}
                    <button type="button" name="add" class="btn btn-primary">${_x('button', 'Add')}</button>
                `;
                showModal(content, {
                    card_el: card_el
                });
            });
        };

        const addTeamMember = (itemtype, items_id, member_type, members_id, role) => {
            return $.ajax({
                method: 'POST',
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "add_teammember",
                    itemtype: itemtype,
                    items_id: items_id,
                    itemtype_teammember: member_type,
                    items_id_teammember: members_id,
                    role: role
                }
            }).done(() => {
                self.refresh(null, null, function() {
                    _backgroundRefreshTimer = window.setTimeout(_backgroundRefresh, self.background_refresh_interval * 60 * 1000);
                }, false);
            }).fail(() => {
                glpi_toast_error(__('Failed to add team member'), __('Error'));
            });
        };

        const removeTeamMember = (itemtype, items_id, member_type, members_id, role) => {
            return $.ajax({
                method: 'POST',
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "delete_teammember",
                    itemtype: itemtype,
                    items_id: items_id,
                    itemtype_teammember: member_type,
                    items_id_teammember: members_id,
                    role: role
                }
            }).done(() => {
                self.refresh(null, null, function() {
                    _backgroundRefreshTimer = window.setTimeout(_backgroundRefresh, self.background_refresh_interval * 60 * 1000);
                }, false);
            }).fail(() => {
                glpi_toast_error(__('Failed to remove team member'), __('Error'));
            });
        };

        /**
       * Restore the Kanban state for the user from the DB if it exists.
       * This restores the visible columns and their collapsed state.
       * @since 9.5.0
       */
        const loadState = function(callback) {
            $(self.element).trigger('kanban:pre_load_state');
            $.ajax({
                type: "GET",
                url: (self.ajax_root + "kanban.php"),
                data: {
                    action: "load_column_state",
                    itemtype: self.item.itemtype,
                    items_id: self.item.items_id,
                    last_load: self.last_refresh
                }
            }).done(async function(state) {
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

                const indices = Object.keys(state['state']);
                const promises = [];
                for (let i = 0; i < indices.length; i++) {
                    const index = indices[i];
                    const entry = state['state'][index];
                    const element = $('#column-' + self.column_field.id + "-" + entry.column);
                    if (element.length === 0) {
                        promises.push(loadColumn(entry.column, true, false));
                    }
                    $(self.element + ' .kanban-columns .kanban-column:nth-child(' + index + ')').after(element);
                    if (entry.folded === 'true') {
                        element.addClass('collapsed');
                    }
                }
                await Promise.all(promises);
                self.last_refresh = state['timestamp'];

                if (callback) {
                    callback(true);
                    $(self.element).trigger('kanban:post_load_state');
                }
            });
        };

        /**
       * Saves the current state of the Kanban to the DB for the user.
       * This saves the visible columns and their collapsed state.
       * This should only be done if there is no state stored on the server, so one needs to be built.
       * Do NOT use this for changes to the state such as moving cards/columns!
       * @since 9.5.0
       * @param {boolean} rebuild_state If true, the column state is recalculated before saving.
       *    By default, this is false as updates are done as changes are made in most cases.
       * @param {boolean} force_save If true, the user state is saved even if it has not changed.
       * @param {function} success Callback for when the user state is successfully saved.
       * @param {function} fail Callback for when the user state fails to be saved.
       * @param {function} always Callback that is called regardless of the success of the save.
       */
        const saveState = function(rebuild_state, force_save, success, fail, always) {
            $(self.element).trigger('kanban:pre_save_state');
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
                }
            }).done(function(data, textStatus, jqXHR) {
                self.user_state.is_dirty = false;
                if (success) {
                    success(data, textStatus, jqXHR);
                    $(self.element).trigger('kanban:post_save_state');
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
       * @since 9.5.0
       */
        const backgroundRefresh = function() {
            if (self.background_refresh_interval <= 0) {
                return;
            }
            _backgroundRefresh = function() {
                const sorting = $('.sortable-placeholder');
                // Check if the user is current sorting items
                if (sorting.length > 0) {
                    // Wait 10 seconds and try the background refresh again
                    delayRefresh();
                    return;
                }
                // Refresh and then schedule the next refresh (minutes)
                self.refresh(null, null, function() {
                    _backgroundRefreshTimer = window.setTimeout(_backgroundRefresh, self.background_refresh_interval * 60 * 1000);
                }, false);
            };
            // Schedule initial background refresh (minutes)
            _backgroundRefreshTimer = window.setTimeout(_backgroundRefresh, self.background_refresh_interval * 60 * 1000);
        };

        /**
       * Initialize the Kanban by loading the user's column state, adding the needed elements to the DOM, and starting the background save and refresh.
       * @since 9.5.0
       */
        this.init = function() {
            $(self.element).data('js_class', self);
            $(self.element).trigger('kanban:pre_init');
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
                        success: function($data) {
                            const switcher = $(self.element + " .kanban-toolbar select[name='kanban-board-switcher']");
                            switcher.replaceWith($data);
                        }
                    });
                    registerEventListeners();
                    backgroundRefresh();
                });
            });
            $(self.element).trigger('kanban:post_init');
        };

        initParams(arguments);
    };
})();
