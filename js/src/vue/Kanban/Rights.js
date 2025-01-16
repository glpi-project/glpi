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

/**
 * Kanban rights structure
 * @since 10.0.0
 */
export class Rights {
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
