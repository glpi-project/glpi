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

import {TeamMember, TeamMemberBadgeFactory} from "../teamwork";

/**
 * Kanban Column
 */
export default class KanbanColumn {

   constructor(params) {
      /**
       * The id for the column
       * @since x.x.x
       * @type {string}
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

   /**
    *
    */
   createElement() {

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
            kanban: self.item
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
            const column = card_obj.closest('.kanban-column');
            card_obj.remove();
            this.updateColumnCount(column);
            if (success) {
               success();
            }
         }
      });
   }

   /**
    * Append the card in the specified column, handle duplicate cards in case the card moved, generate badges, and update column counts.
    * @since 9.5.0
    * @param {Element|string} column_el The column to add the card to.
    * @param {Object} card The card to append.
    * @param {boolean} revalidate Check for duplicate cards.
    */
   appendCard(column_el, card, revalidate = false) {
      if (revalidate) {
         const existing = $('#' + card['id']);
         if (existing !== undefined) {
            const existing_column = existing.closest('.kanban-column');
            existing.remove();
            this.updateColumnCount(existing_column);
         }
      }

      const itemtype = card['id'].split('-')[0];
      const col_body = $(column_el).find('.kanban-body').first();
      const readonly = card['_readonly'] !== undefined && (card['_readonly'] === true || card['_readonly'] === 1);
      let card_el = `
            <li id="${card['id']}" class="kanban-item ${readonly ? 'readonly' : ''} ${card['is_deleted'] ? 'deleted' : ''}">
                <div class="kanban-item-header">
                    <span class="kanban-item-title" title="${card['title_tooltip']}">
                    <i class="${this.board.config.supported_itemtypes[itemtype]['icon']}"></i>
                        ${card['title']}
                    </span>
                    <i class="kanban-item-overflow-actions fas fa-ellipsis-h pointer"></i>
                </div>
                <div class="kanban-item-content">${(card['content'] || '')}</div>
                <div class="kanban-item-team">
         `;
      const team_count = Object.keys(card['_team']).length;
      if (card["_team"] !== undefined && team_count > 0) {
         $.each(Object.values(card["_team"]).slice(0, self.max_team_images), function(teammember_id, teammember) {
            const team_member = new TeamMember(teammember['itemtype'], teammember['items_id'],
               teammember['name'] || '', teammember);
            card_el += team_member.getBadge();
         });
         if (card["_team"].length > self.max_team_images) {
            card_el += TeamMemberBadgeFactory.generateOverflowBadge(team_count - self.max_team_images);
         }
      }
      card_el += "</div></li>";
      $(card_el).appendTo(col_body).data('form_link', card['_form_link'] || undefined);
      self.updateColumnCount(column_el);
   };
}
