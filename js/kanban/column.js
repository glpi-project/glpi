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

import {ColorUtil, TeamMember, TeamMemberBadgeFactory} from "../teamwork";
import KanbanCard from "./card";

/**
 * Kanban Column
 */
export default class KanbanColumn {

   constructor(params) {
      /**
       * The id for the column
       * @since x.x.x
       * @type {number}
       */
      this.id = params['id'];
      /**
       * The Kanban board containing this column
       * @since x.x.x
       * @type {KanbanBoard}
       */
      this.board = params['board'];
      /**
       * The displayed name for the column
       * @since x.x.x
       * @type {string}
       */
      this.name = params['name'];
      /**
       * The color displayed in the header of the column
       * @since x.x.x
       * @type {string}
       */
      this.header_color = params['header_color'] || 'transparent';
      /**
       * A flag indicating if this column is protected.
       *
       * A protected column cannot be removed from the board. For example, the No Status column for Projects is protected.
       * @since x.x.x
       * @type {boolean}
       */
      this.protected = Boolean(params['_protected'] || false);

      /**
       *
       * @type {boolean}
       */
      this.collapsed = Boolean(params['folded'] || false);

      /**
       *
       * @type {KanbanCard[]}
       */
      this.cards = [];
   }

   getID() {
      return this.id;
   }

   getName() {
      return this.name;
   }

   setName(value) {
      this.name = value;
      return this;
   }

   getHeaderColor() {
      return this.header_color;
   }

   setHeaderColor(value) {
      this.header_color = value;
      return this;
   }

   getProtected() {
      return this.protected;
   }

   setProtected(value) {
      this.protected = value;
      return this;
   }

   getElement() {
      return '#column-' + this.board.config.column_field.id + '-' + this.id;
   }

   static getIDFromElement(column_el) {
      let element_id;
      if (typeof column_el !== 'string') {
         element_id = $(column_el).prop('id').split('-');
      } else {
         element_id = column_el.split('-');
      }
      return element_id[element_id.length - 1];
   }

   registerListeners() {
      $(this.board.element).on('kanban:filter', () => {
         this.updateCounter();
      });
   }

   /**
    * Create the required server-side items for a new column
    * @param {KanbanBoard} board
    * @param name
    * @param params
    * @returns {Promise<unknown>|*}
    */
   static createServerItem(board, name, params) {
      if (name === undefined || name.length === 0) {
         return new Promise(() => {});
      }
      return $.ajax({
         method: 'POST',
         url: (board.ajax_root + "kanban.php"),
         contentType: 'application/json',
         dataType: 'json',
         data: {
            action: "create_column",
            itemtype: board.item.itemtype,
            items_id: board.item.items_id,
            column_field: board.config.column_field.id,
            column_name: name,
            params: params
         }
      });
   }

   /**
    * Get a column from the server by ID
    * @param {KanbanBoard} board
    * @param column_id
    */
   static getColumn(board, column_id) {
      let received_column = null;
      $.ajax({
         method: 'GET',
         url: (board.ajax_root + "kanban.php"),
         contentType: 'application/json',
         dataType: 'json',
         async: false,
         data: {
            action: "get_column",
            itemtype: board.item.itemtype,
            items_id: board.item.items_id,
            column_field: board.config.column_field.id,
            column_id: column_id
         }
      }).done((column) => {
         if (column !== undefined) {
            received_column = new KanbanColumn(column);
         }
      });
      return received_column;
   }

   static getAvailableColumns(board) {
      return $.ajax({
         method: 'GET',
         url: (board.ajax_root + "kanban.php"),
         data: {
            action: "list_columns",
            itemtype: board.item.itemtype,
            column_field: board.config.column_field.id
         }
      });
   }

   /**
    *
    */
   createElement() {
      const columns_container = $(this.board.element + " .kanban-container .kanban-columns").first();

      const column_element_id = this.getElement().substring(1);

      let collapse = '';
      let position = -1;
      $.each(this.board.user_state.state, (order, s_column) => {
         if (parseInt(s_column['column']) === this.id) {
            position = order;
            if (s_column['folded'] === true || s_column['folded'] === 'true') {
               collapse = 'collapsed';
               return false;
            }
         }
      });
      const _protected = this.protected ? 'kanban-protected' : '';
      const column_classes = "kanban-column " + collapse + " " + _protected;

      const column_html = `<div id='${column_element_id}' style='border-top: 5px solid ${this.header_color}' class='${column_classes}'></div>`;

      let column_el;
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

      const is_header_light = this.header_color !== 'transparent' ? ColorUtil.isLightColor(this.header_color) : !this.board.config.dark_theme;
      const header_text_class = is_header_light ? 'kanban-text-dark' : 'kanban-text-light';

      const column_header = $("<header class='kanban-column-header'></header>");
      const column_content = $("<div class='kanban-column-header-content'></div>").appendTo(column_header);
      const column_left = $("<span class=''></span>").appendTo(column_content);
      const column_right = $("<span class=''></span>").appendTo(column_content);
      if (this.board.rights.canModifyView()) {
         $(column_left).append("<i class='fas fa-caret-right fa-lg kanban-collapse-column pointer' title='" + __('Toggle collapse') + "'/>");
      }
      $(column_left).append("<span class='kanban-column-title "+header_text_class+"' style='background-color: "+this.header_color+";'>" + this.name + "</span></span>");
      $(column_right).append("<span class='kanban_nb'>0</span>");

      let toolbar_el = "<span class='kanban-column-toolbar'>";
      if (this.board.rights.canCreateItem() && (this.board.rights.getAllowedColumnsForNewCards().length === 0 || this.board.rights.getAllowedColumnsForNewCards().includes(this.id))) {
         toolbar_el += "<i id='kanban_add_" + column_element_id + "' class='kanban-add pointer fas fa-plus' title='" + __('Add') + "'></i>";
         toolbar_el += "<i id='kanban_column_overflow_actions_" + column_element_id +"' class='kanban-column-overflow-actions pointer fas fa-ellipsis-h' title='" + __('More') + "'></i>";
      }
      toolbar_el += "</span>";

      $(column_right).append(toolbar_el);
      $(column_el).prepend(column_header);

      $("<ul class='kanban-body'></ul>").appendTo(column_el);

      this.registerListeners();
   }

   /**
    * Hide the column and notify the server of the change.
    * @since x.x.x
    */
   hide() {
      $.ajax({
         type: "POST",
         url: (this.board.ajax_root + "kanban.php"),
         data: {
            action: "hide_column",
            column: this.id,
            kanban: this.board.item
         },
         contentType: 'application/json',
         complete: () => {
            $(this.getElement()).remove();
            $.each(this.board.user_state.state, (i, c) => {
               if (parseInt(c['column']) === parseInt(this.id)) {
                  this.board.user_state.state[i]['visible'] = false;
                  return false;
               }
            });
            delete this.board.columns[this.id];
            $(this.board.element + " .kanban-add-column-form li[data-list-id='" + this.id + "']").prop('checked', false);
         }
      });
   }

   /**
    * Show the column and notify the server of the change.
    * @since x.x.x
    */
   show() {
      $.ajax({
         type: "POST",
         url: (this.board.ajax_root + "kanban.php"),
         data: {
            action: "show_column",
            column: this.id,
            kanban: this.board.item
         },
         contentType: 'application/json',
         complete: () => {
            $.each(this.board.user_state.state, (i, c) => {
               if (parseInt(c['column']) === parseInt(this.id)) {
                  this.board.user_state.state[i]['visible'] = true;
                  return false;
               }
            });
            this.board.loadColumn(this.id, false, true);
            $(this.board.element + " .kanban-add-column-form li[data-list-id='" + this.id + "']").prop('checked', true);
         }
      });
   }

   /**
    * Toggle the collapsed state of the specified column.
    * After toggling the collapse state, the server is notified of the change.
    * @since x.x.x
    */
   toggleCollapse() {
      const column_el = $(this.getElement());
      column_el.toggleClass('collapsed');
      const action = column_el.hasClass('collapsed') ? 'collapse_column' : 'expand_column';
      $.ajax({
         type: "POST",
         url: (this.board.ajax_root + "kanban.php"),
         data: {
            action: action,
            column: this.id,
            kanban: this.board.item
         },
         contentType: 'application/json'
      });
   }

   /**
    * Delete a card
    * @since x.x.x
    * @param {string} card The ID of the card being deleted.
    * @param {function} error Callback function called when the server reports an error.
    * @param {function} success Callback function called when the server processes the request successfully.
    */
   deleteCard(card, error, success) {
      const [itemtype, items_id] = card.split('-', 2);
      const card_obj = $('#'+card);
      const force = card_obj.hasClass('deleted');
      $.ajax({
         type: "POST",
         url: (this.board.ajax_root + "kanban.php"),
         data: {
            action: "delete_item",
            itemtype: itemtype,
            items_id: items_id,
            force: force ? 1 : 0
         },
         contentType: 'application/json',
         error: function() {
            if (error) {
               error();
            }
         },
         success: () => {
            card_obj.remove();
            if (success) {
               success();
            }
         }
      });
   }

   /**
    *
    * @param {KanbanCard} card
    */
   addCard(card) {
      this.cards.push(card);
   }

   /**
    * Move a card within this column
    */
   orderCard(card, position) {
      const current_pos = this.cards.keys.filter((k) => {
         return this.cards[k].getID() === card;
      }).first();
      if (current_pos.length === 1) {
         this.cards[position] = this.cards[current_pos];
         // TODO Handle card shifting
         delete this.cards[current_pos];
      }
   }

   updateCounter() {
      const column_el = $(this.getElement());
      const column_body = $(column_el).find('.kanban-body:first');
      const counter = $(column_el).find('.kanban_nb:first');
      // Get all visible kanban items. This ensures the count is correct when items are filtered out.
      const items = column_body.find('li:not(.filtered-out)');
      counter.text(items.length);
   }
}
