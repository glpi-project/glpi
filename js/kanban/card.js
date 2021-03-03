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
 * Kanban Card
 */
export default class KanbanCard {

   /**
    *
    * @param {KanbanColumn} initial_column The initial column this card belongs to. This is used when creating the element.
    *    Cards cannot be properly moved between boards so the board the initial column belongs to is the board this card will be bound to.
    * @param params
    */
   constructor(initial_column, params) {

      /**
       * The ID of the card. Typically itemtype-items_id.
       * @type {string}
       */
      this.id = params['id'];

      /**
       * The card's title
       * @type {string}
       */
      this.title = params['title'] || '';

      /**
       * The tooltip shown when hovering over the card's title
       * @type {string}
       */
      this.title_tooltip = params['title_tooltip'] || '';

      this.content = params['content'] || '';

      /**
       * If the card is readonly or not
       * @type {boolean}
       */
      this.readonly = Boolean(params['readonly']) || false;

      this.is_deleted = params['is_deleted'] || false;

      this.form_link = params['_form_link'];

      /**
       * The board this card is bound to
       * @type {KanbanBoard}
       */
      this.board = initial_column.board;

      /**
       *
       * @type {{}}
       */
      this.team = params['_team'] = {};

      this.createElement(initial_column);
   }

   /**
    * @returns {KanbanColumn}
    */
   getColumn() {

   }

   /**
    *
    * @param {KanbanColumn} initial_column
    */
   createElement(initial_column) {
      const itemtype = this.id.split('-')[0];
      const col_body = $(initial_column.getElement()).find('.kanban-body').first();
      let card_el = `
            <li id="${this.id}" class="kanban-item ${this.readonly ? 'readonly' : ''} ${this.is_deleted ? 'deleted' : ''}">
                <div class="kanban-item-header">
                    <span class="kanban-item-title" title="${this.title_tooltip}">
                    <i class="${initial_column.board.config.supported_itemtypes[itemtype]['icon']}"></i>
                        ${this.title}
                    </span>
                    <i class="kanban-item-overflow-actions fas fa-ellipsis-h pointer"></i>
                </div>
                <div class="kanban-item-content">${this.content}</div>
                <div class="kanban-item-team">
         `;
      const team_count = Object.keys(this.team).length;
      if (team_count > 0) {
         $.each(Object.values(this.team).slice(0, this.board.config.max_team_images), function(teammember_id, teammember) {
            const team_member = new TeamMember(teammember['itemtype'], teammember['items_id'],
               teammember['name'] || '', teammember);
            card_el += team_member.getBadge();
         });
         if (this.team.length > self.max_team_images) {
            card_el += TeamMemberBadgeFactory.generateOverflowBadge(team_count - self.max_team_images);
         }
      }
      card_el += "</div></li>";
      $(card_el).appendTo(col_body).data('form_link', this.form_link || undefined);
   }
}
